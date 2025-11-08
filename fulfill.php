<?php
session_start();
include_once("dbconnect.php");
$username = $_SESSION['fname'];
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");

if(!isset($_POST['submit']) && $_SESSION['payment']=='0'){

	header("Location: index.php");

}else{

	$f325number = $_POST['f325number'];
	$arnumber = $_POST['arnumber'];

	//update dbraw
	mysqli_query($conn,"UPDATE dbraw SET slstatus = 'PAID' WHERE f325number = '$f325number' AND arnumber = '$arnumber'");
	
	//update sl_list
	mysqli_query($conn,"UPDATE sl_list SET paymentstatus = 'PAID', checkedby = '$username', checkdate = '$dateprocessed' WHERE f325no = '$f325number' AND slno = '$arnumber'");

	//update sl_number
	mysqli_query($conn,"UPDATE sl_number SET paymentstatus = 'PAID', checkedby = '$username', checkdate = '$dateprocessed' WHERE f325no = '$f325number'");

	// insert to dbhistory
	//$processed = 'Paid';
	//mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')");

	$qstring = '&status=succ';

	header("Location: view_shortlanded.php?f325number=".$f325number.$qstring);

}
	

