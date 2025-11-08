<?php
$host = '192.168.1.23';
//$dbuser = 'apps';
//$dbpass = 'ramosco@123456789';
$dbuser = 'rgcit1';
$dbpass = '524743it1';
$database = 'dbf325';

// connect to server
$f325conn = mysqli_connect($host, $dbuser, $dbpass);
if (!$f325conn){
    die("Database Connection Failed" . mysqli_error($f325conn));
}
	// select database
$select_db = mysqli_select_db($f325conn, $database);
if (!$select_db){
    die("Database Selection Failed" . mysqli_error($f325conn));
}
?>