<?php
session_start();
include_once("dbconnect.php");

$user = $_SESSION['fname'];
$mdccode = $_POST['mdccode'];
$qty = $_POST['qty'];
$unit = $_POST['unit'];
$received = $_POST['received'];
$reason = $_POST['reason'];
$dmpireason = $_POST['dmpireason'];
$bbd = $_POST['bbd'];

// check if item code exists
$check_query = mysqli_query($conn, "SELECT * FROM manual_list WHERE mdccode = '$mdccode' AND user = '$user'");
$row = mysqli_num_rows($check_query);

if($row >= 1){
	echo '<div class="alert alert-warning alert-dismissable fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> Item Already Encoded.
          </div>';
}else{

	$vendor_query = mysqli_query($conn, "SELECT * FROM dbproduct WHERE mdccode = '$mdccode'");
	$fetch_vendor = mysqli_fetch_array($vendor_query);
	$vendor = $fetch_vendor['vendor'];

	$existingvendor_query = mysqli_query($conn, "SELECT * FROM manual_list WHERE user = '$user'");
	$existingvendor_row = mysqli_num_rows($existingvendor_query);

	if($existingvendor_row >= 1){
		$fetch_existingvendor = mysqli_fetch_array($existingvendor_query);
		$existing_vendor = $fetch_existingvendor['vendor'];

		if($vendor == $existing_vendor){
			mysqli_query($conn, "INSERT INTO manual_list (user,mdccode,quantity,unitcost,received,reason,dmpireason,bbd,vendor) VALUES('$user','$mdccode','$qty','$unit','$received','$reason','$dmpireason','$bbd','$vendor')");
		}else{
			echo '<div class="alert alert-warning alert-dismissable fade show" role="alert">
		            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		            <i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> Company not same.
		          </div>';
		}

	}else{
		mysqli_query($conn, "INSERT INTO manual_list (user,mdccode,quantity,unitcost,received,reason,dmpireason,bbd,vendor) VALUES('$user','$mdccode','$qty','$unit','$received','$reason','$dmpireason','$bbd','$vendor')");
	}
}

?>