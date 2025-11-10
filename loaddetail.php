<?php
include('dbconnect.php');

$id = $_POST['id'];

if ($id == 'addnew')
{
	$name = '';
	$nickname = '';
	$vendorcode = '';
	$refcode = '';
	$address = '';
	$bypass = '';
	$active = '';
}
else
{
	$detail_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE id='$id' ");
	$fetch_detail = mysqli_fetch_array($detail_query);

	$name = $fetch_detail['name'];
	$nickname = $fetch_detail['nickname'];
	$vendorcode = $fetch_detail['vendorcode'];
	$refcode = $fetch_detail['refcode'];
	$address = $fetch_detail['address'];
	$bypass = $fetch_detail['bypass'];
	$active = $fetch_detail['active'];
}

echo json_encode(array(
	'name' => $name,
	'nickname' => $nickname,
	'vendorcode' => $vendorcode,
	'refcode' => $refcode,
	'address' => $address,
	'bypass' => $bypass,
	'active' => $active
));

$conn->close();
?>