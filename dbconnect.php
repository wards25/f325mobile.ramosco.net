<?php
<<<<<<< HEAD
$host = '192.168.8.201';
$port = '6033';
$dbuser = 'dbf325-dev';
$dbpass = 'du65T0zDlQl0rbAtE4TvXlKgAcP2yM';
$database = 'dbf325';

    
=======
// $host = '192.168.8.201';
// $port = '6033';
// $dbuser = 'dbf325-dev';
// $dbpass = 'du65T0zDlQl0rbAtE4TvXlKgAcP2yM';
$database = 'dbf325';


>>>>>>> 44ac350420260f5310e58126a5ef01461f12204f

date_default_timezone_set("Asia/Manila");

// connect to server
<<<<<<< HEAD
$conn = mysqli_connect($host, $dbuser, $dbpass, $database, $port);
=======
$conn = mysqli_connect('localhost', 'root', '', $database);
>>>>>>> 44ac350420260f5310e58126a5ef01461f12204f

//for locacal host 
// $conn = mysqli_connect($host, $dbuser, $dbpass, $database, $port);
if (!$conn){
    die("Database Connection Failed" . mysqli_error($conn));
}
	// select database
    $select_db = mysqli_select_db($conn, $database);
    if (!$select_db){
        die("Database Selection Failed" . mysqli_error($conn));
}
?>
