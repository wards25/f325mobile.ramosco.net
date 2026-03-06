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

    $uploadDir = "uploads/disposed/";
    // Create folder if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowed_ext = array('jpeg','jpg','png');
    $filename = $_FILES["image"]["name"];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $newnamefile = $f325number . '.' . $extension;
    $filelocation = $uploadDir . $newnamefile;

    if (in_array($extension, $allowed_ext))
    {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $filelocation))
        {

            // update status in dbf325number
            mysqli_query($conn,"UPDATE dbf325number SET status='$status' WHERE f325number='$f325number' ");

            // update status in dbraw
            mysqli_query($conn,"UPDATE dbraw SET status='$status', statusout='$status' WHERE f325number='$f325number' ");

            // insert in dbhistory
            $processed = 'Disposed';
            mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) 
            VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')");

            $qstring = '?status=succ';
        }
        else
        {
            $qstring = '?status=uploaderror';
        }
    }
    else
    {
        $qstring = '?status=invalidfile';
    }
}

// Redirect to the listing page
header("Location: disposed.php".$qstring);
exit();
?>