<?php
session_start();
include_once("dbconnect.php");

$id = $_POST['id'];

$reset_id = mysqli_query($conn,"UPDATE cleared_list SET received = 0 WHERE id = '$id'");

	

