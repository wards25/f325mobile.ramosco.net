<?php
include 'dbconnect.php'; // mysqli $conn
session_start();
if (isset($_POST['upload'])) {

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
        die("CSV upload failed.");
    }

    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== FALSE) {

        fgetcsv($handle);
        $username = $_SESSION['fname'];
        $updated = 0;
        $processed = 'Import for Pullout.';
        $dateprocessed = date('Y-m-d');
        $timeprocessed = date('H:i:s');

        $stmt = $conn->prepare(
            "UPDATE dbraw 
             SET forpullout = 1 
             WHERE mdccode = ? AND f325number = ?"
        );

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

            $f325Number = trim($data[0]);
            $mdccode = trim($data[1]);

            $qty = isset($data[3]) ? (int) $data[3] : 0;
            $forpullout = isset($data[8]) ? (int) $data[8] : 0;
            $forcharging = isset($data[9]) ? (int) $data[9] : 0;

            if ($qty > 0 && ($forpullout + $forcharging) === $qty) {
                $stmt->bind_param("ss", $mdccode, $f325Number);
                $stmt->execute();
                if ($stmt->affected_rows >= 0) {
                    $updated++;
                }
            }
        }
        // Insert history
        $sql1 = "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')";
        if (!mysqli_query($conn, $sql1)) {
            echo "ERROR inserting dbf325number: " . mysqli_error($conn);
        }
        $stmt->close();
        fclose($handle);
        
        header("Location: for_pullout.php?status=succ");
        exit;
    }
}
?>