<?php
session_start();
include('dbconnect.php');

date_default_timezone_set("Asia/Manila");
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");

$username = $_SESSION['fname'];
$status = 'PRINTED';
$f325number = $_POST['f325number'];
$tmnumber = '';
$driver = '';
$platenumber = '';
$datesched = '0000-00-00';

// update in dbf325number
mysqli_query($conn,"UPDATE dbf325number SET status='$status',tmnumber='$tmnumber',drivername='$driver',platenumber='$platenumber',datesched='$datesched' WHERE f325number='$f325number' ");

// update in dbraw
mysqli_query($conn,"UPDATE dbraw SET status='$status' WHERE f325number='$f325number' ");

// insert in dbhistory
$processed = 'Re-open';
mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')");

echo 'Update successfully!';

$conn->close();
?>