<?php
include 'dbconnect.php';
session_start();

if (isset($_POST['upload'])) {

    $errors = [];
    $updated = 0;
    $rowNumber = 1;

    // Check file upload
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
        $errors[] = "File upload failed.";
    } else {

        $file = $_FILES['csv_file']['tmp_name'];

        if (($handle = fopen($file, "r")) !== FALSE) {

            fgetcsv($handle); // Skip header row

            // Prepare statements for updating pullout and charging
            $stmtPullout = $conn->prepare("
                UPDATE dbraw
                SET forpullout = ?
                WHERE mdccode = ? 
                  AND f325number = ? 
                  AND (batchnumber IS NULL OR batchnumber = '')
            ");
            if (!$stmtPullout) die("Prepare failed for Pullout: " . $conn->error);

            $stmtCharge = $conn->prepare("
                UPDATE dbraw
                SET forcharging = ?
                WHERE mdccode = ? 
                  AND f325number = ? 
                  AND (batchnumber IS NULL OR batchnumber = '')
            ");
            if (!$stmtCharge) die("Prepare failed for Charging: " . $conn->error);

            // Process each CSV row
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNumber++;

                // --- Adjust column indices to match your CSV ---
                $f325Number  = trim($data[0] ?? '');   // column 1
                $mdccode     = trim($data[1] ?? '');   // column 0
                $qty         = (int)($data[3] ?? 0);   // column 7 → total qty
                $forpullout  = (int)($data[8] ?? 0);  // column 22 → pullout
                $forcharging = (int)($data[9] ?? 0);  // column 23 → charging

                // --- Validations ---
                if ($qty <= 0) {
                    $errors[] = "Row {$rowNumber}: Invalid QTY value.";
                    continue;
                }

                if (($forpullout + $forcharging) !== $qty) {
                    $errors[] = "Row {$rowNumber}: Pullout + Charging ({$forpullout}+{$forcharging}) must equal QTY ({$qty}).";
                    continue;
                }

                // --- Update Pullout ---
                if ($forpullout > 0) {
                    $stmtPullout->bind_param("iss", $forpullout, $mdccode, $f325Number);
                    $stmtPullout->execute();
                    if ($stmtPullout->affected_rows > 0) {
                        $updated += $forpullout;
                    } else {
                        $errors[] = "Row {$rowNumber}: Pullout for mdccode {$mdccode} / f325 {$f325Number} already uploaded.";
                    }
                }

                // --- Update Charging ---
                if ($forcharging > 0) {
                    $stmtCharge->bind_param("iss", $forcharging, $mdccode, $f325Number);
                    $stmtCharge->execute();
                    if ($stmtCharge->affected_rows > 0) {
                        $updated += $forcharging;
                    } else {
                        $errors[] = "Row {$rowNumber}: Charging for mdccode {$mdccode} / f325 {$f325Number} already uploaded.";
                    }
                }
            }

            fclose($handle);
            $stmtPullout->close();
            $stmtCharge->close();
        }
    }

    // Store results in session
    $_SESSION['pullout_errors']  = $errors;
    $_SESSION['pullout_success'] = $updated;

    exit;
}
