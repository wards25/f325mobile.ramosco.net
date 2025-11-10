<?php
$host = 'localhost';
$dbuser = 'root';
$dbpass = '';
$database = 'dbf325';

date_default_timezone_set("Asia/Manila");

// connect to server
// $conn = mysqli_connect('localhost', 'apps', 'ramosco@123456789', $database);

//for locacal host 
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