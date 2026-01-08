<?php
include 'dbconnect.php'; // mysqli $conn

if (isset($_POST['upload'])) {

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
        die("CSV upload failed.");
    }

    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== FALSE) {

        fgetcsv($handle);

        $updated = 0;

        $stmt = $conn->prepare(
            "UPDATE dbraw 
             SET forpullout = 1 
             WHERE mdccode = ? AND f325number = ?"
        );

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

            $f325Number  = trim($data[0]);
            $mdccode     = trim($data[1]);

            $qty         = (int)$data[3];
            $forpullout  = (int)$data[8];
            $forcharging = (int)$data[9];

            if (($forpullout + $forcharging) === $qty) {

                $stmt->bind_param("ss", $mdccode, $f325Number);
                $stmt->execute();

                if ($stmt->affected_rows >= 0) {
                    $updated++;
                }
            }
        }

        $stmt->close();
        fclose($handle);

        header("Location: for_pullout.php?status=succ");
        exit;
    }
}
?>
