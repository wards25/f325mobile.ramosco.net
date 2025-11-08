<?php
session_start();
include_once("dbconnect.php");

$user = $_POST['name'];
$barcode = $_POST['mdccode'];
$vendor_code = $_SESSION['vendor'];

//get mdc code
$code_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE barcode = '$barcode' AND vendor = '$vendor_code'");
$count_barcode = mysqli_num_rows($code_query);
$fetch_code = mysqli_fetch_array($code_query);

if($count_barcode >= 1){

    $mdccode = $fetch_code['mdccode'];

    // check if item code exists
    $check_query = mysqli_query($conn,"SELECT * FROM cleared_list WHERE mdccode = '$mdccode' AND user = '$user'");
    $row = mysqli_num_rows($check_query);
    $fetch_received = mysqli_fetch_array($check_query);

    if($row >= 1){
        $id = $fetch_received['id'];

        if($fetch_received['db_id'] == 0){
            $new_received = 1 + $fetch_received['received'];
            mysqli_query($conn,"UPDATE cleared_list SET received = '$new_received' WHERE id = '$id'");
        }else{
            if($fetch_received['received'] < 0){
                mysqli_query($conn,"UPDATE cleared_list SET received = '1' WHERE id = '$id'");
            }else{
                $new_received = 1 + $fetch_received['received'];
                mysqli_query($conn,"UPDATE cleared_list SET received = '$new_received' WHERE id = '$id'");
            }
        }

    }else{

        $mdccode_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdccode'");
        $check_mdccode = mysqli_num_rows($mdccode_query);

        if($check_mdccode >= 1){

            $vendor_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdccode' AND vendor = '$vendor_code'");
            $check_vendor = mysqli_num_rows($vendor_query);

            if($check_vendor >= 1){
                $category_query = mysqli_query($conn,"SELECT category FROM dbproduct WHERE mdccode = '$mdccode' AND vendor = '$vendor_code'");
                $fetch_category = mysqli_fetch_array($category_query);
                $category = $fetch_category['category'];

                mysqli_query($conn,"INSERT INTO cleared_list (db_id,user,mdccode,category,quantity,received,unitcost,reason,dmpireason,bbd) VALUES('0','$user','$mdccode','$category','1','1','0','','','')");
            }else{
                echo '<div class="alert alert-warning alert-dismissable fade show alert2" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> '.$mdccode.' not available in selected company.
                  </div>'; 
            }
        }else{
            echo '<div class="alert alert-danger alert-dismissable fade show alert2" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> '.$mdccode.' not found, please add item to database.
                  </div>';
        }
    }

}else{
    echo '<div class="alert alert-danger alert-dismissable fade show alert2" role="alert"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> <i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b>Barcode not found, please encode barcode to database.</div>';
}

?>