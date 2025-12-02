<?php
session_start();
include('dbconnect.php');

date_default_timezone_set("Asia/Manila");
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");
$id = $_POST['id'];
$action = $_POST['action'];

if ($action === "deactivate") {

    mysqli_query($conn, "UPDATE dbcensus SET status = 0 WHERE id = '$id' ");

    $username = $_SESSION['fname'];
    $processed = 'Deactivate';

    mysqli_query($conn, "INSERT INTO dbcustomerhistory(customerid,name,processed,dateprocessed,timeprocessed)
                         VALUES ('$id','$username','$processed','$dateprocessed','$timeprocessed')");

    header("Location: store-list.php?status=deactivated");
    exit;
}
$code = $_POST['code'];
$branchname = $_POST['branchname'];
$shipping = $_POST['shipping'];
$billing = $_POST['billing'];
$franchise = $_POST['franchise'];
$region = $_POST['region'];
$cluster = $_POST['cluster'];
$deducttype = $_POST['deducttype'];
$location = $_POST['location'];

mysqli_query($conn, "UPDATE dbcensus 
                    SET code='$code',
                        branchname='$branchname',
                        shipping='$shipping',
                        billing='$billing',
                        franchise='$franchise',
                        region='$region',
                        cluster='$cluster',
                        location='$location',
                        deducttype='$deducttype'
                    WHERE id='$id' ");

date_default_timezone_set("Asia/Manila");

$username = $_SESSION['fname'];
$processed = 'Update';

mysqli_query($conn, "INSERT INTO dbcustomerhistory(customerid,name,processed,dateprocessed,timeprocessed)
                     VALUES ('$id','$username','$processed','$dateprocessed','$timeprocessed')");

header("Location: store-list.php?status=success");
$conn->close();
