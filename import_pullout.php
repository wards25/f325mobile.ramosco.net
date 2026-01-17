<?php
include 'dbconnect.php';
session_start();

if (isset($_POST['upload'])) {

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
        die("CSV upload failed.");
    }

    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== FALSE) {

        fgetcsv($handle); // skip header

        $username = $_SESSION['fname'];
        $updated = 0;
        $processed = 'Import for Pullout.';
        $dateprocessed = date('Y-m-d');
        $timeprocessed = date('H:i:s');

        // PREPARE STATEMENTS
        $stmtAll = $conn->prepare("
            UPDATE dbraw 
            SET forpullout = 1
            WHERE mdccode = ? AND f325number = ?
        ");

        $stmtPullout = $conn->prepare("
            UPDATE dbraw 
            SET forpullout = 1
            WHERE mdccode = ? AND f325number = ?
        ");

        $stmtCharge = $conn->prepare("
            UPDATE dbraw 
            SET forcharging = 1
            WHERE mdccode = ? AND f325number = ?
        ");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

            $f325Number = trim($data[0]);
            $mdccode = trim($data[1]);

            $qty = isset($data[3]) ? (int) $data[3] : 0;
            $forpullout = isset($data[8]) ? (int) $data[8] : 0;
            $forcharging = isset($data[9]) ? (int) $data[9] : 0;

            if ($qty > 0 && $forcharging === $qty && $forpullout === 0) {

                // CHARGING ONLY
                $stmtCharge->bind_param("ss", $mdccode, $f325Number);
                $stmtCharge->execute();
                $updated++;

            } elseif ($qty > 0 && $forpullout === $qty && $forcharging === 0) {

                // PULLOUT ONLY
                $stmtPullout->bind_param("ss", $mdccode, $f325Number);
                $stmtPullout->execute();
                $updated++;

            } elseif ($qty > 0 && ($forpullout + $forcharging) === $qty) {

                // MIXED (PULLOUT + CHARGE)
                $stmtAll->bind_param("ss", $mdccode, $f325Number);
                $stmtAll->execute();
                $updated++;
            }

        }

        // Insert history (use prepared statement ideally)
        $sql1 = "
            INSERT INTO dbhistory(processnumber, name, processed, dateprocessed, timeprocessed)
            VALUES ('$f325Number','$username','$processed','$dateprocessed','$timeprocessed')
        ";
        mysqli_query($conn, $sql1);

        // Close statements
        $stmtAll->close();
        $stmtPullout->close();
        $stmtCharge->close();
        fclose($handle);

        header("Location: for_pullout.php?status=succ");
        exit;
    }
}
?>