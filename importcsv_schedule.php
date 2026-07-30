<?php

session_start();
include('dbconnect.php');

date_default_timezone_set("Asia/Manila");

$username = $_SESSION['fname'];
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");
$status = "SCHEDULED";
$beforestatus = "PRINTED";

if(isset($_POST['f325number']))
{

    $total = count($_POST['f325number']);

    for($i=0; $i<$total; $i++)
    {

        $f325number = trim($_POST['f325number'][$i]);
        $code = $_POST['code'][$i];
        $tmnumber = $_POST['tmnumber'][$i];
        $drivername = $_POST['drivername'][$i];
        $platenumber = $_POST['platenumber'][$i];
        $datesched = date("Y-m-d", strtotime($_POST['datesched'][$i]));
        $remarks = $_POST['remarks'][$i];

        // check if PRINTED
        $check = mysqli_query($conn,"
            SELECT f325number
            FROM tbl_f325number
            WHERE f325number='$f325number'
            AND status='$beforestatus'
        ");

        if(mysqli_num_rows($check) > 0)
        {

            // update tbl_f325number
            $query = mysqli_query($conn,"
                UPDATE tbl_f325number
                SET
                tmnumber='$tmnumber',
                drivername='$drivername',
                platenumber='$platenumber',
                datesched='$datesched',
                logisticremarks='$remarks',
                status='$status'
                WHERE f325number='$f325number'
                AND status ='$beforestatus'
            ");

            // update dbraw
            mysqli_query($conn,"
                UPDATE tbl_raw
                SET status='$status'
                WHERE f325number='$f325number'
                AND status ='$beforestatus'
            ");

            // history
            $processed = "Scheduled";

            mysqli_query($conn,"
                INSERT INTO tbl_history
                (processnumber,name,processed,dateprocessed,timeprocessed)
                VALUES
                ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')
            ");

        }

    }

    

}
else
{

    header("Location: scheduled.php?status=err");

}

$conn->close();

?>