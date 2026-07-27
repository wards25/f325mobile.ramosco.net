<?php
include_once "dbconnect.php";
session_start();

$errors = [];
$updated = 0;
$f325List = [];

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
    $_SESSION['pullout_errors'] = ["File upload failed."];
    header("Location: import_forpull_forcharge.php?status=err");
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];

if (($handle = fopen($file, "r")) !== FALSE) {

    fgetcsv($handle); // Skip header row

    // ── Prepare statements ───────────────────────────────────────
    $stmtPullout = $conn->prepare("
        UPDATE dbraw
        SET forpullout = ?
        WHERE mdccode = ? 
          AND f325number = ? 
          AND (batchnumber_forpullout IS NULL OR batchnumber_forpullout = '')
    ");
    if (!$stmtPullout)
        die("Prepare failed for Pullout: " . $conn->error);

    $stmtCharge = $conn->prepare("
        UPDATE dbraw
        SET forcharging = ?
        WHERE mdccode = ? 
          AND f325number = ? 
          AND (batchnumber_forcharging IS NULL OR batchnumber_forcharging = '')
    ");
    if (!$stmtCharge)
        die("Prepare failed for Charging: " . $conn->error);

    $rowNumber = 1;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowNumber++;

        $f325Number = trim($data[0] ?? '');
        $mdccode = trim($data[1] ?? '');
        $qty = (int) ($data[3] ?? 0);
        $forpullout = (int) ($data[10] ?? 0);
        $forcharging = (int) ($data[11] ?? 0);

        // ── Skip error rows ───────────────────────────────────────
        if (empty($f325Number) || empty($mdccode))
            continue;
        if ($qty <= 0)
            continue;
        if (($forpullout + $forcharging) !== $qty)
            continue;

        // ── Check F325 exists ─────────────────────────────────────
        $checkF325 = mysqli_query($conn, "SELECT f325number FROM dbf325number WHERE f325number='$f325Number' LIMIT 1");
        if (!$checkF325)
            die("Query failed (checkF325) Row {$rowNumber}: " . mysqli_error($conn));
        if (mysqli_num_rows($checkF325) < 1)
            continue;

        // ── Check mdccode exists in dbproduct ─────────────────────
        $checkProduct = mysqli_query($conn, "SELECT mdccode FROM dbproduct WHERE TRIM(mdccode) = TRIM('$mdccode') LIMIT 1");
        if (!$checkProduct)
            die("Query failed (checkProduct) Row {$rowNumber}: " . mysqli_error($conn));
        if (mysqli_num_rows($checkProduct) < 1)
            continue;

        // Save f325 for history logging
        $f325List[] = $f325Number;

        // ── Update Pullout ────────────────────────────────────────
        if ($forpullout > 0) {
            $stmtPullout->bind_param("iss", $forpullout, $mdccode, $f325Number);
            if (!$stmtPullout->execute()) {
                die("Execute failed (Pullout) Row {$rowNumber}: " . $stmtPullout->error);
            }

            if ($stmtPullout->affected_rows > 0) {
                $updated += $forpullout;
            } else {
                $errors[] = "Row {$rowNumber}: Pullout for {$mdccode} / {$f325Number} already uploaded or not found.";
            }
        }

        // ── Update Charging ───────────────────────────────────────
        if ($forcharging > 0) {
            $stmtCharge->bind_param("iss", $forcharging, $mdccode, $f325Number);
            if (!$stmtCharge->execute()) {
                die("Execute failed (Charging) Row {$rowNumber}: " . $stmtCharge->error);
            }

            if ($stmtCharge->affected_rows > 0) {
                $updated += $forcharging;
            } else {
                $errors[] = "Row {$rowNumber}: Charging for {$mdccode} / {$f325Number} already uploaded or not found.";
            }
        }
    }

    fclose($handle);
    $stmtPullout->close();
    $stmtCharge->close();
}

// ── Log history (unique F325 numbers only) ───────────────────────
$uniqueF325 = array_unique($f325List);

foreach ($uniqueF325 as $f325) {
    $processed = 'Import Pullout and Charging.';
    $username = $_SESSION['fname'] ?? 'Unknown';
    $dateprocessed = date("Y-m-d");
    $timeprocessed = date("H:i:s");

    $sql1 = "INSERT INTO dbhistory (processnumber, name, processed, dateprocessed, timeprocessed) 
             VALUES ('$f325', '$username', '$processed', '$dateprocessed', '$timeprocessed')";

    $historyQuery = mysqli_query($conn, $sql1);
    if (!$historyQuery)
        die("Query failed (dbhistory) F325 {$f325}: " . mysqli_error($conn));
}
// echo "<pre>";
// echo "Updated: " . $updated . "\n";
// echo "Errors:\n"; print_r($errors);
// echo "F325 List:\n"; print_r($f325List);
// echo "</pre>";
// die();

// // ── Store session results & redirect ─────────────────────────────
// $_SESSION['pullout_errors'] = $errors;
// $_SESSION['pullout_success'] = $updated;

if ($updated > 0) {
    header("Location: import_forpull_forcharge.php?status=succ");
} else {
    // header("Location: import_forpull_forcharge.php?status=err");
}
exit;
?>