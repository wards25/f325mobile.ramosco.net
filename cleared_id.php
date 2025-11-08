<?php
include_once("dbconnect.php");

$id = $_POST['id'];

$list_query = mysqli_query($conn,"SELECT * FROM cleared_list WHERE id = '$id'");
$fetch_row = mysqli_fetch_array($list_query);
$fetch_count = mysqli_num_rows($list_query);

// $id = $fetch_list['id'];
	if($fetch_count > 0) {
		$data = array(
			'id' => $fetch_row['id'],
			'qty' => $fetch_row['quantity']
		);
	} else {
		$data = array(
			'id' => '0',
			'qty' => '0'
		);
	}

	echo json_encode($data);

?>