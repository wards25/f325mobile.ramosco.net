<?php
ob_start();
session_start();
date_default_timezone_set("Asia/Manila");
include_once("dbconnect.php");

if (isset($_POST['submit'])) {
    $username = $_SESSION['fname'];
    $ordernumber = $_POST['f325number'];
    $orderdate = $_POST['f325date'];
    $emaildate = $_POST['emaildate'];
    $code = $_POST['code'];
    $prepared = utf8_encode(str_replace("'", '', ucwords($_POST['prepared'])));
    $issued = utf8_encode(str_replace("'", '', ucwords($_POST['issued'])));
    $driver = strtoupper($_POST['driver']);
    $tmnumber = $_POST['tmnumber'];
    $platenumber = strtoupper($_POST['platenumber']);
    $status = 'CLEARED';
    $remarks = $_POST['remarks'];
    $dateprocessed = date("Y-m-d");
    $timeprocessed = date("H:i:s");
    $ilrno = $_POST['ilrno'];
    $stamped = $_POST['stamped'];


    // check if items exist in cleared_list
    $check_query = mysqli_query($conn, "SELECT * FROM cleared_list WHERE user = '$username'");
    $row = mysqli_num_rows($check_query);

    if ($row >= 1) {

        // SL Number logic
        if (empty($_POST['arnumber'])) {
            $arnumber = '';
        } else {
            $arnumber = $_POST['arnumber'];
            $sl_query = mysqli_query($conn, "SELECT * FROM sl_number ORDER BY id DESC LIMIT 1");
            $sl_count = mysqli_num_rows($sl_query);
            if ($sl_count >= 1) {
                $fetch_sl = mysqli_fetch_array($sl_query);
                $slno = $fetch_sl['slno'] + 1;
            } else {
                $slno = '100001';
            }
            mysqli_query($conn, "INSERT INTO sl_number 
                (slno,f325no,brcode,dateprocessed,timeprocessed,user,paymentstatus)
                VALUES ('$slno','$ordernumber','$code','$dateprocessed','$timeprocessed','$username','UNPAID')");
        }

        // branch detail
        $census_query = mysqli_query($conn, "SELECT * FROM dbcensus WHERE code = '$code'");
        $fetch_census = mysqli_fetch_array($census_query);
        $cluster = $fetch_census['cluster'];
        $location = $fetch_census['location'];
        $deducttype = $fetch_census['deducttype'];

        // loop cleared items
        $data_query = mysqli_query($conn, "SELECT * FROM cleared_list WHERE user = '$username'");
        while ($fetch_data = mysqli_fetch_array($data_query)) {
            $db_id = $fetch_data['db_id'];
            $mdccode = $fetch_data['mdccode'];
            $quantity = $fetch_data['quantity'];
            $unitcost = $fetch_data['unitcost'];
            $received = $fetch_data['received'];
            $reason = strtoupper($fetch_data['reason']);
            $dmpireason = $fetch_data['dmpireason'];
            $bbd = $fetch_data['bbd'];

            if (!empty($bbd)) {
                $bbd_parts = explode("/", $bbd);
                if (count($bbd_parts) == 3) {
                    $month = $bbd_parts[0];
                    $day = $bbd_parts[1];
                    $year = $bbd_parts[2];
                    // If year is 2 digits, convert to 4 digits
                    if (strlen($year) == 2) {
                        $year = '20' . $year;
                    }
                    $bbd = $month . '/' . $day . '/' . substr($year, 2);
                }
            }

            $costextended = ($quantity * $unitcost);

            // get category and vendor
            $category_query = mysqli_query($conn, "SELECT * FROM dbproduct WHERE mdccode = '$mdccode' AND active = '1'");
            $fetch_category = mysqli_fetch_array($category_query);
            $category = $fetch_category['category'] ?? '';
            $dmpiclass = $fetch_category['dmpiclassification'] ?? '';
            $vcode = $fetch_category['vendor'] ?? '';

            if ($db_id == '0') {
                if ($quantity <= $received) {
                    // insert raw data
                    $query = mysqli_query($conn, "INSERT INTO dbraw
                        (f325number,mdccode,category,vendorcode,deducttype,dmpiclass,quantity,expiration,unitcost,costextended,reasoncode,arnumber,arreason,dmpireason,rcvdqty,dmpiref,deductref,deductqty,deductcostextended,datecleared,pulloutref,location,status,statusout,paymentstatus,skustatus,slstatus,skutype, forpullout, forcharging)
                        VALUES
                        ('$ordernumber','$mdccode','$category','$vcode','$deducttype','$dmpiclass','$quantity','$bbd','$unitcost','$costextended','$reason','$arnumber','','$dmpireason','$received','','','0','0.00','$dateprocessed','','$location','$status','$status','','1','','Added','0','0')");
                    if (!$query) {
                        die(mysqli_error($conn));
                    }
                } else {
                    // insert raw data
                    $query2 = mysqli_query($conn, "INSERT INTO dbraw
                        (f325number,mdccode,category,vendorcode,deducttype,dmpiclass,quantity,expiration,unitcost,costextended,reasoncode,arnumber,arreason,dmpireason,rcvdqty,dmpiref,deductref,deductqty,deductcostextended,datecleared,pulloutref,location,status,statusout,paymentstatus,skustatus,slstatus,skutype, forpullout, forcharging)
                        VALUES
                        ('$ordernumber','$mdccode','$category','$vcode','$deducttype','$dmpiclass','$quantity','$bbd','$unitcost','$costextended','$reason','$arnumber','','$dmpireason','$received','','','0','0.00','$dateprocessed','','$location','$status','$status','','1','UNPAID','Added','0','0')");
                    if (!$query2) {
                        die(mysqli_error($conn));
                    }

                    // insert into sl_list with checkedby and checkdate
                    $short = $quantity - $received;
                    $shortuc = ($unitcost * $short);
                    $query = mysqli_query($conn, "INSERT INTO sl_list
                        (slno,f325no,brcode,drivername,mdccode,qty,costextended,expiration,vendor,dateprocessed,user,checkedby,paymentstatus,checkdate)
                        VALUES
                        ('$arnumber','$ordernumber','$code','$driver','$mdccode','$short','$shortuc','$bbd','$vcode','$dateprocessed','$username','$username','UNPAID','$dateprocessed')");
                    if (!$query) {
                        die("Error inserting into sl_list: " . mysqli_error($conn));
                    }
                }
            } else if ($db_id > 0) {
                if ($quantity <= $received) {
                    mysqli_query($conn, "UPDATE dbraw SET category='$category',arnumber='$arnumber',mdccode='$mdccode',expiration='$bbd',dmpireason='0',dmpiclass='$dmpiclass',reasoncode='$reason',quantity='$quantity',rcvdqty='$received',unitcost='$unitcost',costextended='$costextended',datecleared='$dateprocessed',status='$status',statusout='$status' WHERE id='$db_id'");
                } else {
                    mysqli_query($conn, "UPDATE dbraw SET category='$category',arnumber='$arnumber',mdccode='$mdccode',expiration='$bbd',dmpireason='0',dmpiclass='$dmpiclass',reasoncode='$reason',quantity='$quantity',rcvdqty='$received',unitcost='$unitcost',costextended='$costextended',datecleared='$dateprocessed',status='$status',statusout='$status',slstatus='UNPAID' WHERE id='$db_id'");

                    $short = $quantity - $received;
                    $shortuc = ($unitcost * $short);
                    $query = mysqli_query($conn, "INSERT INTO sl_list
                        (slno,f325no,brcode,drivername,mdccode,qty,costextended,expiration,vendor,dateprocessed,user,checkedby,paymentstatus,checkdate)
                        VALUES
                        ('$arnumber','$ordernumber','$code','$driver','$mdccode','$short','$shortuc','$bbd','$vcode','$dateprocessed','$username','$username','UNPAID','$dateprocessed')");
                    if (!$query) {
                        die("Error inserting into sl_list: " . mysqli_error($conn));
                    }
                }
            }
        }

        // handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $filename = $_FILES["image"]["name"];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed = ["jpg", "jpeg", "png", "gif", "webp"];

            if (in_array($ext, $allowed)) {
                $new_file_name = $ordernumber . '.' . $ext;
                $upload_dir = __DIR__ . "/uploads/shortlanded/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $destination = $upload_dir . $new_file_name;
                move_uploaded_file($_FILES["image"]["tmp_name"], $destination);
            }
        }

        // update dbf325number
        $time = date("H:i:s");
        mysqli_query($conn, "UPDATE dbf325number SET datecleared='$dateprocessed',arnumber='$arnumber',status='$status',clearingremarks='$remarks',ilrno='$ilrno',stamped='$stamped',cleared_time='$time' WHERE f325number='$ordernumber'");

        // insert into history
        $processed = 'Cleared';
        mysqli_query($conn, "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$ordernumber','$username','$processed','$dateprocessed','$timeprocessed')");

        $qstring = '?status=succ';
    } else {
        $qstring = '?status=err';
    }
}

// Redirect
header("Location: clearing.php" . $qstring);