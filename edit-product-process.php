<?php
session_start();
include('dbconnect.php');

$id = $_POST['id'];
$mdccode = $_POST['mdccode'];
$oldmdccode = $_POST['oldmdccode'];
$itemcode = $_POST['itemcode'];
$description = $_POST['description'];
$category = $_POST['category'];
// $oldcategory = $_POST['oldcategory'];
$dmpicode = $_POST['dmpicode'] ?? "";
$dmpipack = $_POST['dmpipack'] ?? "";
$dmpiclass = $_POST['dmpiclass'] ?? "";
$uom = $_POST['uom'];
$company = $_POST['company'];
// $oldvendor = $_POST['oldvendor'];

if ($oldmdccode == $mdccode && $oldcategory == $category && $oldvendor == $company)
{
	// date and time
	date_default_timezone_set("Asia/Manila");
	$dateprocessed = date("Y-m-d");
	$timeprocessed = date("H:i:s");

	$username = $_SESSION['fname'];

	// insert in dbhistory 
	$processed = 'Update';
	mysqli_query($conn,"INSERT INTO dbproducthistory(mdccode,name,processed,dateprocessed,timeprocessed) VALUES ('$mdccode','$username','$processed','$dateprocessed','$timeprocessed')");

	// update detail
	mysqli_query($conn,"UPDATE dbproduct SET mdccode='$mdccode',itemcode='$itemcode',description='$description',vendor='$company',category='$category',dmpicode='$dmpicode',dmpipack='$dmpipack',dmpiclassification='$dmpiclass',uom='$uom' WHERE id='$id' ");

	// update category in dbraw
	mysqli_query($conn,"UPDATE dbraw SET category='$category' WHERE mdccode='$oldmdccode' AND category='$oldcategory' ");

	echo "Update successfully!";
}
else
{
	// check mdccode if available
	$check_query = mysqli_query($conn,"SELECT id,mdccode FROM dbproduct WHERE mdccode='$mdccode' AND category='$category' AND vendor='$company' ");
	$fetch_check = mysqli_fetch_array($check_query);

	if (is_array($fetch_check))
	{
		if($fetch_check['id'] == $id)
		{

		}
		else
		{
			echo "Already Exist!";
		}
	}
	else
	{
		// date and time
		date_default_timezone_set("Asia/Manila");
		$dateprocessed = date("Y-m-d");
		$timeprocessed = date("H:i:s");

		$username = $_SESSION['fname'];

		// insert in dbhistory
		$processed = 'Update';
		mysqli_query($conn,"INSERT INTO dbproducthistory(mdccode,name,processed,dateprocessed,timeprocessed) VALUES ('$mdccode','$username','$processed','$dateprocessed','$timeprocessed')");

		// update detail
		mysqli_query($conn,"UPDATE dbproduct SET mdccode='$mdccode',itemcode='$itemcode',description='$description',vendor='$company',category='$category',dmpicode='$dmpicode',dmpipack='$dmpipack',dmpiclassification='$dmpiclass',uom='$uom' WHERE id='$id' ");

		// update category in dbraw
		mysqli_query($conn,"UPDATE dbraw SET category='$category' WHERE mdccode='$oldmdccode' AND category='$oldcategory' ");

		echo "Update successfully!";
	}
}

$conn->close();
?>