<?php
session_start();
include('dbconnect.php');

date_default_timezone_set("Asia/Manila");
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");

$username = $_SESSION['fname'];
$status = 'SCHEDULED';
$f325number = $_POST['f325number'];
$tmnumber = $_POST['tmnumber'];
$driver = strtoupper($_POST['driver']);
$platenumber = strtoupper($_POST['platenumber']);
$datesched = date('Y-m-d',strtotime($_POST['datesched']));
if (isset($_POST['remarks']))
{
	$remarks = $_POST['remarks'];
}
else
{
	$remarks = '';
}

// update in dbf325number
mysqli_query($conn,"UPDATE dbf325number SET status='$status',tmnumber='$tmnumber',drivername='$driver',platenumber='$platenumber',datesched='$datesched',logisticremarks='$remarks' WHERE f325number='$f325number' ");

// update in dbraw
mysqli_query($conn,"UPDATE dbraw SET status='$status' WHERE f325number='$f325number' ");

// insert in dbhistory
$processed = 'Scheduled';
mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')");

echo 'Update successfully!';

$conn->close();
?>