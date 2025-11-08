<?php
session_start();
date_default_timezone_set("Asia/Manila");
include_once("dbconnect.php");

if (isset($_POST['submit']))
{
	$dateprocessed = date("Y-m-d");
	$f325number = $_POST['f325number'];
	$verificationreason = $_POST['verificationreason'];

	mysqli_query($conn,"UPDATE dbf325number SET verificationdate = '$dateprocessed', verificationreason = '$verificationreason' WHERE f325number = '$f325number'");

	$qstring = '?status=verify';
}

header("Location: scheduled.php".$qstring);
