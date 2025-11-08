<?php 
session_start();
include('dbconnect.php');

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="Shortlanded Report as of '.date('m-d-Y').'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
$header = array();

// header
if(isset($_POST['slnumber'])){$header[] = 'SL NUMBER';}
if(isset($_POST['user'])){$header[] = 'USER PROCESSED';}
if(isset($_POST['f325number'])){$header[] = 'F325 NUMBER';}
if(isset($_POST['skucode'])){$header[] = 'SKU CODE';}
if(isset($_POST['description'])){$header[] = 'DESCRIPTION';}
if(isset($_POST['category'])){$header[] = 'CATEGORY';}
if(isset($_POST['brcode'])){$header[] = 'BRCODE';}
if(isset($_POST['brname'])){$header[] = 'BRNAME';}
if(isset($_POST['quantity'])){$header[] = 'QTY';}
if(isset($_POST['expiration'])){$header[] = 'EXPIRATION';}
if(isset($_POST['costextended'])){$header[] = 'COST EXTENDED';}
if(isset($_POST['company'])){$header[] = 'COMPANY';}
if(isset($_POST['sldate'])){$header[] = 'SL DATE';}
if(isset($_POST['payment'])){$header[] = 'PAYMENT STATUS';}
if(isset($_POST['checkedby'])){$header[] = 'CHECKED BY';}
if(isset($_POST['checkdate'])){$header[] = 'CHECK DATE';}
fputcsv($output,$header);

$from = date("Y-m-d",strtotime($_POST['from']));
$to = date("Y-m-d",strtotime($_POST['to']));
$status = $_POST['status'];

if($status == 'all'){
	$f325_query = mysqli_query($conn,"SELECT * FROM sl_number WHERE dateprocessed BETWEEN '$from' AND '$to'");
}else{
	$f325_query = mysqli_query($conn,"SELECT * FROM sl_number WHERE paymentstatus = '$status' AND dateprocessed BETWEEN '$from' AND '$to'");
}

	while($fetch_f325 = mysqli_fetch_array($f325_query)) {

	    $f325number = $fetch_f325['f325no'];

		$result	= mysqli_query($conn,"SELECT * FROM sl_list WHERE f325no = '$f325number' ORDER BY id ASC");

		while($row = mysqli_fetch_array($result))
		{

			$mdccode = $row['mdccode'];
			$vendor = $row['vendor'];

			// get nickname of vendor
			$nickname_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode='$vendor'");
			$fetch_nickname = mysqli_fetch_array($nickname_query);

			$company = $fetch_nickname['nickname'];

			// dbproduct
			$product_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdccode'");
			$fetch_product = mysqli_fetch_array($product_query);

			if (is_array($fetch_product))
			{
				$category = $fetch_product['category'];
			}
			else
			{
				$category = 'For Fill-up';
			}

			$brcode = $fetch_f325['brcode'];

			// branch code detail
			$branch_query = mysqli_query($conn,"SELECT * FROM dbcensus WHERE code='$brcode' ");
			$fetch_branch = mysqli_fetch_array($branch_query);

			if (is_array($fetch_branch))
			{
				$branch = $fetch_branch['branchname'];
			}
			else
			{
				$branch = 'For Fill-up';
			}

			if(isset($_POST['slnumber'])){$slno = $row['slno'];}else{$slno = '@';}
			if(isset($_POST['user'])){$user = $row['user'];}else{$user = '@';}
			if(isset($_POST['f325number'])){$f325number = $row['f325no'];}else{$f325number = '@';}
			if(isset($_POST['skucode'])){$mdccode = $row['mdccode'];}else{$mdccode = '@';}
			if(isset($_POST['description'])){$description = $fetch_product['description'];}else{$description = '@';}
			if(isset($_POST['category'])){$category = $category;}else{$category = '@';}
			if(isset($_POST['brcode'])){$brcode = $row['brcode'];;}else{$brcode = '@';}
			if(isset($_POST['brname'])){$brname = $branch;}else{$brname = '@';}
			if(isset($_POST['quantity'])){$quantity = $row['qty'];}else{$quantity = '@';}

			if(isset($_POST['expiration']))
			{
				if ($row['expiration'] == '0000-00-00')
				{
					$expiration = '';
				}
				else
				{
					$expiration = $row['expiration'];
				}
			}
			else
			{
				$expiration = '@';
			}

			if(isset($_POST['costextended'])){$costextended = $row['costextended'];}else{$costextended = '@';}
			if(isset($_POST['company'])){$company = $company;}else{$company = '@';}

			if(isset($_POST['sldate']))
			{
				if ($fetch_f325['dateprocessed'] == '0000-00-00')
				{
					$sldate = '';
				}
				else
				{
					$sldate = $fetch_f325['dateprocessed'];
				}
			}
			else
			{
				$sldate = '@';
			}

			if(isset($_POST['payment'])){$payment = $fetch_f325['paymentstatus'];}else{$payment = '@';}
			if(isset($_POST['checkedby'])){$checkedby = $fetch_f325['user'];}else{$checkedby = '@';}

			if(isset($_POST['checkdate']))
			{
				if ($fetch_f325['checkdate'] == '0000-00-00')
				{
					$checkdate = '';
				}
				else
				{
					$checkdate = $fetch_f325['checkdate'];
				}
			}
			else
			{
				$checkdate = '@';
			}

			$array_list = array($slno,$user,$f325number,$mdccode,$description,$category,$brcode,$brname,$quantity,$expiration,$costextended,$company,$sldate,$payment,$checkedby,$checkdate);

			$remove = array('@');
			$result_array = array_diff($array_list, $remove);

			fputcsv($output,$result_array);
		}
	}

fclose($output);

$conn->close();
?>