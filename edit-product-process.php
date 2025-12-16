<?php
session_start();
include('dbconnect.php');

// Get POST data
$id = $_POST['id'];
$mdccode = $_POST['mdccode'];
$oldmdccode = $_POST['oldmdccode'];
$itemcode = $_POST['itemcode'];
$description = $_POST['description'];
$category = $_POST['category'];
$dmpicode = $_POST['dmpicode'] ?? "";
$dmpipack = $_POST['dmpipack'] ?? "";
$dmpiclass = $_POST['dmpiclass'] ?? "";
$uom = $_POST['uom'];
$company = $_POST['company'];

$action = $_POST['action'];

// Handle the action
if ($action == 'update') {
    // Update logic (same as before)
    if ($oldmdccode == $mdccode && $oldcategory == $category && $oldvendor == $company) {
        // date and time
        date_default_timezone_set("Asia/Manila");
        $dateprocessed = date("Y-m-d");
        $timeprocessed = date("H:i:s");

        $username = $_SESSION['fname'];

        // Insert into dbhistory
        $processed = 'Update';
        mysqli_query($conn,"INSERT INTO dbproducthistory(mdccode,name,processed,dateprocessed,timeprocessed) 
                           VALUES ('$mdccode','$username','$processed','$dateprocessed','$timeprocessed')");

        // Update product details
        mysqli_query($conn,"UPDATE dbproduct SET mdccode='$mdccode',itemcode='$itemcode',description='$description',vendor='$company',category='$category',dmpicode='$dmpicode',dmpipack='$dmpipack',dmpiclassification='$dmpiclass',uom='$uom' WHERE id='$id'");

        // Update category in dbraw
        mysqli_query($conn,"UPDATE dbraw SET category='$category' WHERE mdccode='$mdccode' AND category='$category' ");

        echo "Update successfully!";
    }
    else {
        // Check if the product exists with the new values
        $check_query = mysqli_query($conn,"SELECT id,mdccode FROM dbproduct WHERE mdccode='$mdccode' AND category='$category' AND vendor='$company'");
        $fetch_check = mysqli_fetch_array($check_query);

        if (is_array($fetch_check)) {
            if ($fetch_check['id'] == $id) {
                // Same product, continue updating
            } else {
                echo "Product already exists!";
            }
        } else {
            // date and time
            date_default_timezone_set("Asia/Manila");
            $dateprocessed = date("Y-m-d");
            $timeprocessed = date("H:i:s");

            $username = $_SESSION['fname'];

            // Insert into dbhistory
            $processed = 'Update';
            mysqli_query($conn,"INSERT INTO dbproducthistory(mdccode,name,processed,dateprocessed,timeprocessed) 
                               VALUES ('$mdccode','$username','$processed','$dateprocessed','$timeprocessed')");

            // Update product details
            mysqli_query($conn,"UPDATE dbproduct SET mdccode='$mdccode',itemcode='$itemcode',description='$description',vendor='$company',category='$category',dmpicode='$dmpicode',dmpipack='$dmpipack',dmpiclassification='$dmpiclass',uom='$uom' WHERE id='$id'");

            // Update category in dbraw
            mysqli_query($conn,"UPDATE dbraw SET category='$category' WHERE mdccode='$mdccode' AND category='$category' ");

            header("Location: edit-product-list.php?edit_id=$id&success=1");
            exit();
        }
    }
} elseif ($action == 'deactivate') {
    // Deactivate logic
    // Update the active status to 0
    $update_query = "UPDATE dbproduct SET active=0 WHERE id='$id'";
    if (mysqli_query($conn, $update_query)) {
        echo "Product has been deactivated.";
    } else {
        echo "Error deactivating product: " . mysqli_error($conn);
    }
}

$conn->close();
?>
