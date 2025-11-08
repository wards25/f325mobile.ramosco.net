<?php
session_start();
require_once("dbconnect.php");

$mdccode = $_POST['mdccode'];

$category_query = mysqli_query($conn,"SELECT category FROM dbproduct WHERE mdccode = '$mdccode'");
$fetch_category = mysqli_fetch_array($category_query);

$category = $fetch_category['category'];
echo json_encode(array('category' => $category));
?>