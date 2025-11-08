<?php
session_start();
include_once("dbconnect.php");

$id = $_POST['id'];

$item_query = mysqli_query($conn,"DELETE FROM manual_list WHERE id = '$id'");

