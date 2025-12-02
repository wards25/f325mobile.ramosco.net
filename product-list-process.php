<?php
session_start();
include('dbconnect.php');

$mdccode = $_POST['mdccode'];
$itemcode = $_POST['itemcode'];
$description = $_POST['description'];
$category = $_POST['category'];
$dmpicode = $_POST['dmpicode'] ?? "";
$dmpipack = $_POST['dmpipack'] ?? "";
$dmpiclass = $_POST['dmpiclass'] ?? "";
$uom = $_POST['uom'];
$vendor = $_POST['company'];
$status = "1";

// check if code is exist
$check_query = mysqli_query($conn,"SELECT mdccode FROM dbproduct WHERE mdccode='$mdccode' AND category='$category' AND vendor='$vendor' ");
$fetch_check = mysqli_fetch_array($check_query);

if (is_array($fetch_check))
{
	echo "Already exist!";
}
else
{
	mysqli_query($conn,"INSERT INTO dbproduct(mdccode,itemcode,barcode,casecode,description,category,vendor,dmpicode,dmpipack,dmpiclassification,uom,active,batch) VALUES('$mdccode','$itemcode','','','$description','$category','$vendor','$dmpicode','$dmpipack','$dmpiclass','$uom','$status','')");
	
	// date and time
	date_default_timezone_set("Asia/Manila");
	$dateprocessed = date("Y-m-d");
	$timeprocessed = date("H:i:s");

	$username = $_SESSION['fname'];

	// insert in dbhistory
	$processed = 'Added new product';
	mysqli_query($conn,"INSERT INTO dbproducthistory(mdccode,name,processed,dateprocessed,timeprocessed) VALUES ('$mdccode','$username','$processed','$dateprocessed','$timeprocessed')");

	echo "Added successfully!";
    header("Location: product-list.php?status=success");
}

$conn->close();
?>