<?php
session_start();
include('dbconnect.php');

date_default_timezone_set("Asia/Manila");
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");

$username = $_SESSION['fname'];
$status = 'OPEN';
$f325number = $_GET['f325number'];

// update in dbf325number
$query = mysqli_query($conn, "UPDATE dbf325number 
SET 
tmnumber='',
drivername='',
platenumber='',
logisticremarks='',
status='$status',
datesched=NULL,
datecleared=NULL
WHERE f325number='$f325number'");

if (!$query) {
    die('Error: ' . mysqli_error($conn));
}

// update in dbraw
mysqli_query($conn, "UPDATE dbraw SET datecleared = NULL, status='$status', statusout = '$status' WHERE f325number='$f325number'");

// insert in dbhistory
$processed = 'Re-open';
mysqli_query($conn, "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) 
VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')");

header("Location: clearing.php?status=reopen");
$conn->close();
exit();
?>