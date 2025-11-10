<?php
include('dbconnect.php');

$id = $_POST['id'];
$name = strtoupper($_POST['name']);
$nickname = strtoupper($_POST['nickname']);
$vendorcode = $_POST['vendorcode'];
$referencecode = $_POST['referencecode'];
$address = $_POST['address'];

$bypass = isset($_POST['bypass']) ? $_POST['bypass'] : '0';
$active = isset($_POST['active']) ? $_POST['active'] : '0';

// update dbcompany
if(mysqli_query($conn,"UPDATE dbcompany SET 
    name='$name',
    nickname='$nickname',
    vendorcode='$vendorcode',
    refcode='$referencecode',
    address='$address',
    bypass='$bypass',
    active='$active' 
    WHERE id='$id'")) {
    echo 'success'; 
} else {
    echo 'error: '.mysqli_error($conn); 
}

$conn->close();
?>
