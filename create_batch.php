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
    $batchNumber = "BATCH-" . date("YmdHis") . "-" . rand(100, 999);
    $userId = $_SESSION['id'];

    // Prepare arrays to hold f325numbers and mdccodes
    $f325numbers = [];
    $mdccodes = [];

    foreach ($items as $item) {
        // Split f325number and mdccode from the checkbox value
        // Format: f325number|mdccode
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
        // Build placeholders for prepared statement
        $fPlaceholders = implode(',', array_fill(0, count($f325numbers), '?'));
        $mPlaceholders = implode(',', array_fill(0, count($mdccodes), '?'));

        // Construct SQL
        $sql = "UPDATE dbraw SET batchnumber = ?, forpullout = 0 WHERE f325number IN ($fPlaceholders) AND mdccode IN ($mPlaceholders)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Merge batchNumber + f325numbers + mdccodes into a single array for binding
        $types = str_repeat('i', count($f325numbers) + count($mdccodes));
        $types = 's' . $types; // 's' for batchNumber
        $params = array_merge([$batchNumber], $f325numbers, $mdccodes);

        // Use call_user_func_array to bind dynamically
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
