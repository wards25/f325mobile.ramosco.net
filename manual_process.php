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
	$company = $_POST['company'];
	$status = 'CLEARED';
	$dateprocessed = date("Y-m-d");
	$timeprocessed = date("H:i:s");
	$ilrno = $_POST['ilrno'];
	$stamped = $_POST['stamped'];

	// check if item code exists
	$check_query = mysqli_query($conn,"SELECT * FROM manual_list WHERE user = '$username'");
	$row = mysqli_num_rows($check_query);

	if ($row >= 1)
	{
		// check if f325number is already in database
		$f325check_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE f325number = '$ordernumber'");
		$fetch_check = mysqli_fetch_array($f325check_query);

		if ($fetch_check <= 0)
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
				mysqli_query($conn,"INSERT INTO sl_number (slno,f325no,dateprocessed,timeprocessed,user) VALUES ('$slno','$ordernumber','$dateprocessed','$timeprocessed','$username')");
		}

		// branch detail
		$census_query = mysqli_query($conn,"SELECT * FROM dbcensus WHERE code = '$code'");
		$fetch_census = mysqli_fetch_array($census_query);

		$cluster = $fetch_census['cluster'];
		$location = $fetch_census['location'];
		$deducttype = $fetch_census['deducttype'];

		$data_query = mysqli_query($conn,"SELECT * FROM manual_list WHERE user = '$username'");
		while($fetch_data = mysqli_fetch_array($data_query)){
			$mdccode = $fetch_data['mdccode'];
			$quantity = $fetch_data['quantity'];
			$received = $fetch_data['received'];
			$dmpireason = $fetch_data['dmpireason'];
			$unitcost = $fetch_data['unitcost'];
			$reasoncode = $fetch_data['reason'];
			$bbd = $fetch_data['bbd'];
			$costextended = ($quantity * $unitcost);

			$product_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdccode'");
			$fetch_product = mysqli_fetch_array($product_query);
			$category = $fetch_product['category'];
			$vendorcode = $fetch_product['vendor'];

			if($quantity == $received || $quantity < $received){
				// insert raw data
				mysqli_query($conn,"INSERT INTO dbraw(f325number,mdccode,category,vendorcode,deducttype,dmpiclass,quantity,expiration,unitcost,costextended,reasoncode,arnumber,arreason,dmpireason,rcvdqty,dmpiref,deductref,deductqty,deductcostextended,datecleared,pulloutref,location,status,statusout,paymentstatus,skustatus,slstatus,skutype) VALUES ('$ordernumber','$mdccode','$category','$vendorcode','$deducttype','','$quantity','$bbd','$unitcost','$costextended','$reasoncode','','','$dmpireason','$received','','','0','0.00','$dateprocessed','','$location','CLEARED','','','1','','Manual')");
			}else{
				// insert raw data
				mysqli_query($conn,"INSERT INTO dbraw(f325number,mdccode,category,vendorcode,deducttype,dmpiclass,quantity,expiration,unitcost,costextended,reasoncode,arnumber,arreason,dmpireason,rcvdqty,dmpiref,deductref,deductqty,deductcostextended,datecleared,pulloutref,location,status,statusout,paymentstatus,skustatus,slstatus,skutype) VALUES ('$ordernumber','$mdccode','$category','$vendorcode','$deducttype','','$quantity','$bbd','$unitcost','$costextended','$reasoncode','$arnumber','','$dmpireason','$received','','','0','0.00','$dateprocessed','','$location','CLEARED','','','1','UNPAID','Manual')");

				//insert sl list
				$short = $quantity - $received;
				$shortuc = ($unitcost * $short);
				mysqli_query($conn,"INSERT INTO sl_list(slno,f325no,drivername,mdccode,qty,costextended,vendor,dateprocessed,user,paymentstatus) VALUES ('$arnumber','$ordernumber','$driver','$mdccode','$short','$shortuc','$vendorcode','$dateprocessed','$username','UNPAID')");
			}
		}
			// upload image
			if(empty($_FILES["image"])){

			}else{
				$filename = $_FILES["image"]["name"];
	  			$ext = pathinfo($filename, PATHINFO_EXTENSION);
	  			$new_file_name = $ordernumber.'.'.$ext;
				//$filelocation = "C:/public/www/f325.ramosco.net/filepicture/dbapps/";
				$filelocation = "C:/xampp/htdocs/f325.ramosco.net/filepicture/dbapps/";
				move_uploaded_file($_FILES["image"]["tmp_name"], $filelocation.$new_file_name);
			}

			// insert to dbf325number
			mysqli_query($conn,"INSERT INTO dbf325number(f325number,brcode,preparedby,issuedby,emaildate,f325date,vendor,tmnumber,drivername,platenumber,datesched,datecleared,arnumber,pageno,printremarks,logisticremarks,clearingremarks,cluster,location,deducttype,status,process,verificationdate,verificationreason,ilrno,stamped,cleared_time) VALUES ('$ordernumber','$code','$prepared','$issued','$emaildate','$orderdate','$company','$tmnumber','$driver','$platenumber','0000-00-00','$dateprocessed','','','','','','$cluster','$location','$deducttype','$status','MANUAL','0000-00-00','','$ilrno','$stamped','$timeprocessed')");

			// insert to dbhistory
			$processed = 'Cleared';
			mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$ordernumber','$username','$processed','$dateprocessed','$timeprocessed')");

			$qstring = '?status=succ';

		}else{
			$qstring = '?status=exist';
		}
		}else{
			$qstring = '?status=err';
		}
	}

// Redirect to the listing page
header("Location: manual.php".$qstring);
?>