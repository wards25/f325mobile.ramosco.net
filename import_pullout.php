<?php
include 'dbconnect.php';
session_start();

if (isset($_POST['upload'])) {

    $errors = [];
    $updated = 0;
    $rowNumber = 1;
    $f325List = []; // store all f325 numbers

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

            // Process each CSV row
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNumber++;

                $f325Number = trim($data[0] ?? '');
                $mdccode = trim($data[1] ?? '');
                $qty = (int) ($data[3] ?? 0);
                $forpullout = (int) ($data[8] ?? 0);
                $forcharging = (int) ($data[9] ?? 0);

                // Save f325 for logging later
                if (!empty($f325Number)) {
                    $f325List[] = $f325Number;
                }

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

    // ---- LOGGING HISTORY (UNIQUE F325 NUMBERS) ----
    $uniqueF325 = array_unique($f325List);

    foreach ($uniqueF325 as $f325) {

        $processed = 'Import Pullout and Charging.';
        $username = $_SESSION['fname'] ?? 'Unknown';
        $dateprocessed = date("Y-m-d");
        $timeprocessed = date("H:i:s");

        $sql1 = "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) 
                 VALUES ('$f325','$username','$processed','$dateprocessed','$timeprocessed')";

        if (!mysqli_query($conn, $sql1)) {
            echo "ERROR inserting history: " . mysqli_error($conn);
        }
    }

    // Store results in session
    $_SESSION['pullout_errors'] = $errors;
    $_SESSION['pullout_success'] = $updated;

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>