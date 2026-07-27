<?php
// $host = '192.168.8.201:6033';
// $port = '6033';
// $dbuser = 'f325_prod';
// $dbpass = 'dNoSLSjXCSe3yljU1ii06uX1vmJANY';
// $database = 'f325_prod';

$host = 'localhost';
$dbuser = 'root';
$dbpass = '';
$database = 'dbf325';
    

date_default_timezone_set("Asia/Manila");

// connect to server
// $conn = mysqli_connect($host, $dbuser, $dbpass, $database, $port);
$conn = mysqli_connect($host, $dbuser, $dbpass, $database);

//for locacal host 
// $conn = mysqli_connect($host, $dbuser, $dbpass, $database, $port);
if (!$conn) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Server Error</title>
        <style>
            body{
                display:flex;
                justify-content:center;
                align-items:center;
                height:100vh;
                background:#f5f5f5;
                font-family:Arial;
                flex-direction:column;
            }
            img{
                max-width:300px;
            }
        </style>
    </head>
    <body>  
        <img src="img/server-error.png" alt="Server Error">
        <h4>500. The Server Encountered an Error</h4>
    </body>
    </html>
    <?php
    exit;
}
?>
<?php 
	// select database
    $select_db = mysqli_select_db($conn, $database);
    if (!$select_db){
        die("Database Selection Failed" . mysqli_error($conn));
}
?>