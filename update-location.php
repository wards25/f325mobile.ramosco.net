<?php
include('dbconnect.php');

$id = $_POST['id'];
$value = $_POST['value'];
$active  = $_POST['active'];
// update location
mysqli_query($conn, "UPDATE dblocation SET location='$value',active='$active' WHERE id='$id' ");
echo"success";
$conn->close();
?>