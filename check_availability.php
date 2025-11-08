<?php
# create database connection
include_once("dbconnect.php");

$f325number = $_POST['f325number'];
$numlength = strlen((string)$f325number);

if($numlength == 12) {

  $query = "SELECT * FROM dbf325number WHERE f325number = '$f325number'";
  $result = mysqli_query($conn,$query);
  $count = mysqli_num_rows($result);

  if($count>0) {
    echo "<span style='color:red'><small>F325 already exists.</small></span>";
    echo "<script>$('#submit').prop('disabled',true);</script>";
  }else{
    echo "<span style='color:green'><small>F325 available.</small></span>";
    echo "<script>$('#submit').prop('disabled',false);</script>";
  }
}
?>