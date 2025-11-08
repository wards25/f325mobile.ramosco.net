<?php
session_start();
include_once("dbconnect.php");

$user = $_POST['name'];
$mdccode = $_POST['mdccode'];
$qty = $_POST['qty'];
$received = $_POST['received'];
$unit = $_POST['unit'];
$reason = $_POST['reason'];
$dmpireason = $_POST['dmpireason'];

// check if item code exists
$check_query = mysqli_query($conn,"SELECT * FROM cleared_list WHERE mdccode = '$mdccode' AND user = '$user'");
$row = mysqli_num_rows($check_query);

if($row >= 1){
    echo '<div class="alert alert-warning alert-dismissable fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> Item Already Encoded.
          </div>';
}else{

    $category_query = mysqli_query($conn,"SELECT category FROM dbproduct WHERE mdccode = '$mdccode'");
    $fetch_category = mysqli_fetch_array($category_query);
    $category = $fetch_category['category'];

	mysqli_query($conn,"INSERT INTO cleared_list (db_id,user,mdccode,category,quantity,received,unitcost,reason,dmpireason,bbd) VALUES('0','$user','$mdccode','$category','$qty','$received','$unit','$reason','$dmpireason','')");
}

?>