<?php
$host = '192.168.8.201';
$port = '6033';
$dbuser = 'dbf325-dev';
$dbpass = 'du65T0zDlQl0rbAtE4TvXlKgAcP2yM';
$database = 'dbf325';

date_default_timezone_set("Asia/Manila");

// connect to server
// $conn = mysqli_connect('localhost', 'apps', 'ramosco@123456789', $database);

//for locacal host 
$conn = mysqli_connect($host, $dbuser, $dbpass, $database, $port);
if (!$conn){
    die("Database Connection Failed" . mysqli_error($conn));
}
	// select database
    $select_db = mysqli_select_db($conn, $database);
    if (!$select_db){
        die("Database Selection Failed" . mysqli_error($conn));
}
?>