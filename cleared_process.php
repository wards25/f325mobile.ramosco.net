<?php
ob_start();
session_start();
date_default_timezone_set("Asia/Manila");
include_once("dbconnect.php");

if (isset($_POST['submit']))
{
	$username = $_SESSION['fname'];
	$ordernumber = $_POST['f325number'];
	$orderdate = $_POST['f325date'];
	$emaildate = $_POST['emaildate'];
	$code = $_POST['code'];
	$prepared = utf8_encode(str_replace(array("'"), '', ucwords($_POST['prepared'])));
	$issued = utf8_encode(str_replace(array("'"), '', ucwords($_POST['issued'])));
	$driver = strtoupper($_POST['driver']);
	$tmnumber = $_POST['tmnumber'];
	$platenumber = strtoupper($_POST['platenumber']);
	$status = 'CLEARED';
	$remarks = $_POST['remarks'];
	$dateprocessed = date("Y-m-d");
	$timeprocessed = date("H:i:s");
	$ilrno = $_POST['ilrno'];
	$stamped = $_POST['stamped'];

	// check if item code exists
	$check_query = mysqli_query($conn,"SELECT * FROM cleared_list WHERE user = '$username'");
	$row = mysqli_num_rows($check_query);

	if ($row >= 1)
	{
		// insert into slnumber
		if(empty($_POST['arnumber'])){
			$arnumber = '';
		}else{
			$arnumber = $_POST['arnumber'];
			$sl_query = mysqli_query($conn,"SELECT * FROM sl_number ORDER BY id DESC LIMIT 1");
			$sl_count = mysqli_num_rows($sl_query);

			if($sl_count >=1){
				$fetch_sl = mysqli_fetch_array($sl_query);
				$slno = $fetch_sl['slno'];
				$slno += 1;
			}else{
				$slno = '100001';
			}
				mysqli_query($conn,"INSERT INTO sl_number (slno,f325no,brcode,dateprocessed,timeprocessed,user,paymentstatus) VALUES ('$slno','$ordernumber','$code','$dateprocessed','$timeprocessed','$username','UNPAID')");
		}

		// branch detail
		$census_query = mysqli_query($conn,"SELECT * FROM dbcensus WHERE code = '$code'");
		$fetch_census = mysqli_fetch_array($census_query);

		$cluster = $fetch_census['cluster'];
		$location = $fetch_census['location'];
		$deducttype = $fetch_census['deducttype'];

		$data_query = mysqli_query($conn,"SELECT * FROM cleared_list WHERE user = '$username'");
		while($fetch_data = mysqli_fetch_array($data_query)) {
			$db_id = $fetch_data['db_id'];
			$mdccode = $fetch_data['mdccode'];
			$quantity = $fetch_data['quantity'];
			$unitcost = $fetch_data['unitcost'];
			$received = $fetch_data['received'];
			$reason = strtoupper($fetch_data['reason']);
			$dmpireason = $fetch_data['dmpireason'];
			$bbd = $fetch_data['bbd'];
			$costextended = ($quantity * $unitcost);

			// get category
			$category_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdccode' AND active = '1' ");
			$fetch_category = mysqli_fetch_array($category_query);
			if (is_array($fetch_category))
			{
				$category = $fetch_category['category'];
				$dmpiclass = $fetch_category['dmpiclassification'];
			}
			else
			{
				$category = '';
				$dmpiclass = '';
			}

			$vcode = $fetch_category['vendor'];

			if($db_id == '0'){

				if($quantity == $received || $quantity < $received){
					// insert raw data
					mysqli_query($conn,"INSERT INTO dbraw(f325number,mdccode,category,vendorcode,deducttype,dmpiclass,quantity,expiration,unitcost,costextended,reasoncode,arnumber,arreason,dmpireason,rcvdqty,dmpiref,deductref,deductqty,deductcostextended,datecleared,pulloutref,location,status,statusout,paymentstatus,skustatus,slstatus,skutype) VALUES ('$ordernumber','$mdccode','$category','$vcode','$deducttype','$dmpiclass','$quantity','$bbd','$unitcost','$costextended','$reason','$arnumber','','$dmpireason','$received','','','0','0.00','$dateprocessed','','$location','$status','$status','','1','','Added')");
				}else{
					// insert raw data
					mysqli_query($conn,"INSERT INTO dbraw(f325number,mdccode,category,vendorcode,deducttype,dmpiclass,quantity,expiration,unitcost,costextended,reasoncode,arnumber,arreason,dmpireason,rcvdqty,dmpiref,deductref,deductqty,deductcostextended,datecleared,pulloutref,location,status,statusout,paymentstatus,skustatus,slstatus,skutype) VALUES ('$ordernumber','$mdccode','$category','$vcode','$deducttype','$dmpiclass','$quantity','$bbd','$unitcost','$costextended','$reason','$arnumber','','$dmpireason','$received','','','0','0.00','$dateprocessed','','$location','$status','$status','','1','UNPAID','Added')");

					//insert sl list
					$short = $quantity - $received;
					$shortuc = ($unitcost * $short);
					mysqli_query($conn,"INSERT INTO sl_list(slno,f325no,brcode,drivername,mdccode,qty,costextended,expiration,vendor,dateprocessed,user,paymentstatus) VALUES ('$arnumber','$ordernumber','$code','$driver','$mdccode','$short','$shortuc','$bbd','$vcode','$dateprocessed','$username','UNPAID')");
				}
				
			}else if($db_id > 0){

				if($quantity == $received || $quantity < $received){
					// update raw data
					mysqli_query($conn,"UPDATE dbraw SET category='$category',arnumber='$arnumber',mdccode='$mdccode',expiration='$bbd',dmpireason='0',dmpiclass='$dmpiclass',reasoncode='$reason',quantity='$quantity',rcvdqty='$received',unitcost='$unitcost',costextended='$costextended',datecleared='$dateprocessed',status='$status',statusout='$status' WHERE id='$db_id' ");
				}else{
					// update raw data
					mysqli_query($conn,"UPDATE dbraw SET category='$category',arnumber='$arnumber',mdccode='$mdccode',expiration='$bbd',dmpireason='0',dmpiclass='$dmpiclass',reasoncode='$reason',quantity='$quantity',rcvdqty='$received',unitcost='$unitcost',costextended='$costextended',datecleared='$dateprocessed',status='$status',statusout='$status',slstatus='UNPAID' WHERE id='$db_id' ");

					//insert sl list
					$short = $quantity - $received;
					$shortuc = ($unitcost * $short);
					mysqli_query($conn,"INSERT INTO sl_list(slno,f325no,brcode,drivername,mdccode,qty,costextended,expiration,vendor,dateprocessed,user,paymentstatus) VALUES ('$arnumber','$ordernumber', '$code', '$driver','$mdccode','$short','$shortuc','$bbd','$vcode','$dateprocessed','$username','UNPAID')");
				}
			}
		}
			
			// upload image
			$filename = $_FILES["image"]["name"];
  			$ext = pathinfo($filename, PATHINFO_EXTENSION);
  			$new_file_name = $ordernumber.'.'.$ext;
			//$filelocation = "C:/public/www/f325.ramosco.net/filepicture/dbapps/";
			$filelocation = "C:/xampp/htdocs/f325.ramosco.net/filepicture/dbapps/";
			move_uploaded_file($_FILES["image"]["tmp_name"], $filelocation.$new_file_name);

			// update in dbf325number
			$time = date("H:i:s");
			mysqli_query($conn,"UPDATE dbf325number SET datecleared='$dateprocessed',arnumber='$arnumber',status='$status',clearingremarks='$remarks',ilrno='$ilrno',stamped='$stamped',cleared_time='$time' WHERE f325number='$ordernumber' ");

			// insert to dbhistory
			$processed = 'Cleared';
			mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$ordernumber','$username','$processed','$dateprocessed','$timeprocessed')");

			$qstring = '?status=succ';

		}else{
			$qstring = '?status=err';
		}
	}

// Redirect to the listing page
header("Location: scheduled.php".$qstring);
?>