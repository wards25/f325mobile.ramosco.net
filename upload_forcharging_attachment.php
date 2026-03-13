<?php
session_start();
include_once('dbconnect.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batchnumber = mysqli_real_escape_string($conn, $_POST['batchnumber']);
    $uploader = $_SESSION['fname'] ?? 'Unknown';
    $uploadDate = date('Y-m-d');
    $uploadTime = date('H:i:s');
    $custodian_name = mysqli_real_escape_string($conn, $_POST['custodian_name'] ?? '');
    $logpnumber = mysqli_real_escape_string($conn, $_POST['logpnumber'] ?? '');
    $charging_date = mysqli_real_escape_string($conn, $_POST['charge_date'] ?? '');


    $hub = '';

    $hubQuery = "
    SELECT location 
    FROM dbraw 
    WHERE batchnumber_forcharging = '$batchnumber'
    LIMIT 1
";
    $hubResult = mysqli_query($conn, $hubQuery);
    if ($hubRow = mysqli_fetch_assoc($hubResult)) {
        $hub = mysqli_real_escape_string($conn, $hubRow['location']);
    }

    $batchInsert = "
    UPDATE dbpullout
    SET status = 'PAID', reference = '$batchnumber', custodian_name = '$custodian_name', logp_number = '$logpnumber', charge_date = '$charging_date'
    WHERE reference = '$batchnumber'
";

    $query = mysqli_query($conn, $batchInsert);
    if (!$query) {
        die("Error inserting batch: " . mysqli_error($conn));
    }
    $updateStatus = "
    UPDATE dbraw
    SET statusout = 'PAID'
    WHERE batchnumber_forcharging = '$batchnumber'
";
    mysqli_query($conn, $updateStatus);

    $uploadDir = "uploads/attachments/$batchnumber/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Get current max sequence_no
    $seqQuery = "SELECT MAX(sequence_no) AS max_seq 
                 FROM tbl_attachments 
                 WHERE batchnumber = '$batchnumber'";
    $seqResult = mysqli_query($conn, $seqQuery);
    $seqRow = mysqli_fetch_assoc($seqResult);
    $sequence = (int) ($seqRow['max_seq'] ?? 0);


    // ===== FOR UPLOAD LOGP IMAGES =====
    if (!empty($_FILES['logp_images']['name'][0])) {

        foreach ($_FILES['logp_images']['tmp_name'] as $index => $tmpName) {

            if ($_FILES['logp_images']['error'][$index] === 0) {

                $sequence++;

                $ext = pathinfo($_FILES['logp_images']['name'][$index], PATHINFO_EXTENSION);
                $newName = sprintf('%03d_LOGP.%s', $sequence, $ext);
                $targetPath = $uploadDir . $newName;

                move_uploaded_file($tmpName, $targetPath);

                $insert = "
                    INSERT INTO tbl_attachments 
                    (batchnumber, sequence_no, path, document_type, uploader, upload_date, upload_time)
                    VALUES 
                    ('$batchnumber', $sequence, '$targetPath', 'LOGP', '$uploader', '$uploadDate', '$uploadTime')
                ";
                mysqli_query($conn, $insert);
            }
        }
    }
    // ===== FOR UPLOAD PULLOUT SUMMARY =====
    if (!empty($_FILES['pullout_summary']['name'][0])) { // check first file

        foreach ($_FILES['pullout_summary']['tmp_name'] as $index => $tmpName) {

            if ($_FILES['pullout_summary']['error'][$index] === 0) {

                $sequence++;

                $ext = pathinfo($_FILES['pullout_summary']['name'][$index], PATHINFO_EXTENSION);
                $newName = sprintf('%03d_PRINTOUT.%s', $sequence, $ext);
                $targetPath = $uploadDir . $newName;

                move_uploaded_file($tmpName, $targetPath);

                $insert = "
                INSERT INTO tbl_attachments 
                (batchnumber, sequence_no, path, document_type, uploader, upload_date, upload_time)
                VALUES 
                ('$batchnumber', $sequence, '$targetPath', 'PRINT OUT', '$uploader', '$uploadDate', '$uploadTime')
            ";
                mysqli_query($conn, $insert);
            }
        }
    }

     // ===== INSERT HISTORY LOG FOR PAID ATTACHMENT =====
    $username = $uploader;
    $dateprocessed = $uploadDate;
    $timeprocessed = $uploadTime;
    $processed = 'Upload Paid Attachment';

    mysqli_query($conn, "
        INSERT INTO dbhistory(processnumber, name, processed, dateprocessed, timeprocessed) 
        VALUES ('$batchnumber', '$username', '$processed', '$dateprocessed', '$timeprocessed')
    ");

    echo "<script>
    const batchnumber = " . json_encode($batchnumber) . ";
    window.location.href = 'charging_batchlist.php?status=succ';
</script>";

    exit();
}
