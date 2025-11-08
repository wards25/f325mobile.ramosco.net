<?php
$host = '192.168.1.23';
//$dbuser = 'apps';
//$dbpass = 'ramosco@123456789';
$dbuser = 'rgcit1';
$dbpass = '524743it1';
$database = 'dbf325';

// connect to server
$conn = mysqli_connect($host, $dbuser, $dbpass);
if (!$conn){
    die("Database Connection Failed" . mysqli_error($conn));
}
	// select database
$select_db = mysqli_select_db($conn, $database);
if (!$select_db){
    die("Database Selection Failed" . mysqli_error($conn));
}
?>