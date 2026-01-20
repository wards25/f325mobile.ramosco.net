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
        header("Location: for_charging.php");
        exit();
    }

    $items = $_POST['items'];

    $conn->begin_transaction();

    try {
        // Generate batch number: CHG-YYYYMM-000001
        $yearMonth = date('Ym'); 
        $prefix = "CHG-$yearMonth-";

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

        // Update each selected item individually to ensure exact pairings
        foreach ($items as $item) {
            list($f325, $mdc) = explode('|', $item);

            $stmt = $conn->prepare("
                UPDATE dbraw 
                SET batchnumber = ? 
                WHERE f325number = ? AND mdccode = ?
            ");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("sii", $batchNumber, $f325, $mdc);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();

        $_SESSION['success'] = "Batch created successfully! Batch Number: $batchNumber";
        header("Location: for_charging.php?status=succ");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: for_charging.php");
        exit();
    }
}
?>
