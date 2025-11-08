<?php
session_start();
date_default_timezone_set("Asia/Manila");
include_once("dbconnect.php");

if (isset($_POST['submit']))
{
	$username = $_SESSION['fname'];
	$dateprocessed = date("Y-m-d");
	$timeprocessed = date("H:i:s");
	$f325number = $_POST['f325number'];
	$status = 'DISPOSED';

	$droot = $_SERVER['DOCUMENT_ROOT'];
	$directory = "/f325.ramosco.net/filepicture";
	$junkfile = "/junkfile/";
	$dbfile = "/dbfile/";

	$allowed_ext = array('jpg');
	$filename = $_FILES["image"]["name"];
	$extension = pathinfo($filename, PATHINFO_EXTENSION);
	$newnamefile = $f325number.'.'.$extension;

	if (in_array($extension, $allowed_ext))
	{
		$filelocation = $droot.$directory.$junkfile.$filename;

		// move upload file
		move_uploaded_file($_FILES["image"]["tmp_name"],$filelocation);

		// rename file
		rename($filelocation, $droot.$directory.$junkfile.$f325number.'.jpg');

		// check if file is exist in folder
		if (file_exists($droot.$directory.$dbfile.$newnamefile))
		{
			echo "File exist!";
		}
		else
		{
			// copy file from junkfile
			copy($droot.$directory.$junkfile.$newnamefile, $droot.$directory.$dbfile.$newnamefile);

			// remove file from junkfile
			unlink($droot.$directory.$junkfile.$newnamefile);

			// update status in dbf325number
			mysqli_query($conn,"UPDATE dbf325number SET status='$status' WHERE f325number='$f325number' ");

			// update status in dbraw
			mysqli_query($conn,"UPDATE dbraw SET status='$status',statusout='$status' WHERE f325number='$f325number' ");

			// insert in dbhistory
			$processed = 'Disposed';
			mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')");

			$qstring = '?status=succ';
		}
	}
	$qstring = '?status=succ';
}

// Redirect to the listing page
header("Location: disposed.php".$qstring);