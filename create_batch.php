<?php
session_start();
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['create_batch'])) {

    if (!isset($_POST['items']) || !is_array($_POST['items']) || count($_POST['items']) === 0) {
        $_SESSION['error'] = "Please select at least one row.";
        $redirectUrl = "for_pullout_details.php";
        if (isset($_GET['category'])) $redirectUrl .= "?category=" . urlencode($_GET['category']);
        if (isset($_GET['company'])) $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&') . "company=" . urlencode($_GET['company']);
        header("Location: $redirectUrl");
        exit();
    }

    $items = $_POST['items'];
    $userId = $_SESSION['id'];

    // Prepare arrays
    $f325numbers = [];
    $mdccodes = [];

    foreach ($items as $item) {
        // checkbox value format: f325number|mdccode
        $parts = explode('|', $item);
        if (count($parts) === 2) {
            $f325numbers[] = intval($parts[0]);
            $mdccodes[] = intval($parts[1]);
        }
    }

    if (count($f325numbers) === 0 || count($mdccodes) === 0) {
        $_SESSION['error'] = "Invalid item selection.";
        header("Location: for_pullout_details.php");
        exit();
    }

    $conn->begin_transaction();

    try {
        $yearMonth = date('y'); 
        $prefix = "PU-$yearMonth-";

        $sqlLast = "
            SELECT batchnumber
            FROM dbraw
            WHERE batchnumber LIKE ?
            ORDER BY batchnumber DESC
            LIMIT 1
        ";

        $stmtLast = $conn->prepare($sqlLast);
        if (!$stmtLast) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $like = $prefix . '%';
        $stmtLast->bind_param('s', $like);
        $stmtLast->execute();
        $result = $stmtLast->get_result();

        $nextNumber = 1;
        if ($row = $result->fetch_assoc()) {
            $lastNumber = intval(substr($row['batchnumber'], -6));
            $nextNumber = $lastNumber + 1;
        }

        $stmtLast->close();

        $sequence = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        $batchNumber = $prefix . $sequence;

        $fPlaceholders = implode(',', array_fill(0, count($f325numbers), '?'));
        $mPlaceholders = implode(',', array_fill(0, count($mdccodes), '?'));

        $sql = "
            UPDATE dbraw
            SET batchnumber = ?
            WHERE f325number IN ($fPlaceholders)
              AND mdccode IN ($mPlaceholders)
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $types = 's' . str_repeat('i', count($f325numbers) + count($mdccodes));
        $params = array_merge([$batchNumber], $f325numbers, $mdccodes);

        $tmp = [];
        foreach ($params as $key => $value) {
            $tmp[$key] = &$params[$key];
        }
        array_unshift($tmp, $types);

        call_user_func_array([$stmt, 'bind_param'], $tmp);

        $stmt->execute();
        $stmt->close();

        $conn->commit();

        $_SESSION['success'] = "Batch created successfully! Batch Number: $batchNumber";
        header("Location: for_pullout.php?status=succ");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: for_pullout_details.php?category=" . urlencode($_GET['category'] ?? '') . "&company=" . urlencode($_GET['company'] ?? ''));
        exit();
    }
}
