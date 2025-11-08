<?php
session_start();
include_once("dbconnect.php");

$id = $_POST['id'];

$select_id = mysqli_query($conn,"SELECT * FROM cleared_list WHERE id = '$id'");
$fetch_id = mysqli_fetch_array($select_id);

$db_id = $fetch_id['db_id'];

	if($db_id == '0'){
		mysqli_query($conn,"DELETE FROM cleared_list WHERE id = '$id'");
	}else{
		mysqli_query($conn,"DELETE FROM cleared_list WHERE id = '$id'");
		mysqli_query($conn,"DELETE FROM dbraw WHERE id = '$db_id'");
		//mysqli_query($conn,"UPDATE dbraw SET skustatus = '0' WHERE id = '$db_id'");
	}
	

