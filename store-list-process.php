<?php
session_start();
include('dbconnect.php');

$code = $_POST['code'];
$branchname = utf8_encode($_POST['branchname']);
$shipping = utf8_encode(str_replace("'", "''",$_POST['shipping']));
$billing = utf8_encode(str_replace("'", "''",$_POST['billing']));
$franchise = $_POST['franchise'];
$region = $_POST['region'];
$cluster = $_POST['cluster'];
$deducttype = $_POST['deducttype'];
$location = $_POST['location'];
$status = "1";

// check if code is exist
$check_query = mysqli_query($conn,"SELECT code FROM dbcensus WHERE code='$code' AND status='1' ");
$fetch_check = mysqli_fetch_array($check_query);

if (is_array($fetch_check))
{
	echo "Already exist!";
}
else
{
	mysqli_query($conn,"INSERT INTO dbcensus(code,branchname,shipping,billing,franchise,region,cluster,location,deducttype,status,batch) VALUES('$code','$branchname','$shipping','$billing','$franchise','$region','$cluster','$location','$deducttype','$status','')");
	// date and time
	date_default_timezone_set("Asia/Manila");
	$dateprocessed = date("Y-m-d");
	$timeprocessed = date("H:i:s");

	$username = $_SESSION['fname'];

	// get id
	$id_query = mysqli_query($conn,"SELECT id,code FROM dbcensus WHERE code='$code' ");
	$fetch_id = mysqli_fetch_array($id_query);

	if (is_array($fetch_id))
	{
		$id = $fetch_id['id'];
		// insert in dbhistory
		$processed = 'Added new store';
		mysqli_query($conn,"INSERT INTO dbcustomerhistory(customerid,name,processed,dateprocessed,timeprocessed) VALUES ('$id','$username','$processed','$dateprocessed','$timeprocessed')");
	}

	echo "Added successfully.";
    header("Location: store-list.php?status=success");
}

$conn->close();
?>