<?php 
session_start();
include('dbconnect.php');

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="F325 Per Principal Report as of '.date('m-d-Y').'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
$header = array();

// header
if(isset($_POST['f325number'])){$header[] = 'F325 NUMBER';}
if(isset($_POST['skucode'])){$header[] = 'SKU CODE';}
if(isset($_POST['description'])){$header[] = 'DESCRIPTION';}
if(isset($_POST['category'])){$header[] = 'CATEGORY';}
if(isset($_POST['brcode'])){$header[] = 'BRCODE';}
if(isset($_POST['brname'])){$header[] = 'BRNAME';}
if(isset($_POST['dmpiclass'])){$header[] = 'DMPI CLASS';}
if(isset($_POST['dmpireason'])){$header[] = 'DMPI REASON';}
if(isset($_POST['reasoncode'])){$header[] = 'REASON CODE';}
if(isset($_POST['quantity'])){$header[] = 'QTY';}
if(isset($_POST['rcvdqty'])){$header[] = 'RCVD QTY';}
if(isset($_POST['expiration'])){$header[] = 'EXPIRATION';}
if(isset($_POST['f325status'])){$header[] = 'STATUS';}
if(isset($_POST['arnumber'])){$header[] = 'AR NO';}
if(isset($_POST['arreason'])){$header[] = 'AR REASON';}
if(isset($_POST['preparedby'])){$header[] = 'PREPARED BY';}
if(isset($_POST['issuedby'])){$header[] = 'ISSUED BY';}
if(isset($_POST['emaildate'])){$header[] = 'EMAIL DATE';}
if(isset($_POST['f325date'])){$header[] = 'F325 DATE';}
if(isset($_POST['company'])){$header[] = 'COMPANY';}
if(isset($_POST['tmnumber'])){$header[] = 'TM NO';}
if(isset($_POST['platenumber'])){$header[] = 'PLATE NO';}
if(isset($_POST['driver'])){$header[] = 'DRIVER';}
if(isset($_POST['scheddate'])){$header[] = 'SCHED DATE';}
if(isset($_POST['cleardate'])){$header[] = 'CLEARED DATE';}
if(isset($_POST['unitcost'])){$header[] = 'UNIT COST';}
if(isset($_POST['costextended'])){$header[] = 'COST EXT';}
if(isset($_POST['print'])){$header[] = 'PRINT';}
if(isset($_POST['log'])){$header[] = 'LOGISTIC';}
if(isset($_POST['clearing'])){$header[] = 'CLEARING';}
if(isset($_POST['cluster'])){$header[] = 'CLUSTER';}
if(isset($_POST['f325location'])){$header[] = 'LOCATION';}
if(isset($_POST['process'])){$header[] = 'PROCESS';}
if(isset($_POST['ilrno'])){$header[] = 'ILR NO';}

fputcsv($output,$header);

$from = date("Y-m-d",strtotime($_POST['from']));
$to = date("Y-m-d",strtotime($_POST['to']));
$status = $_POST['status'];
$principal = $_POST['principal'];
$location = $_POST['location'];

if($location == 'all'){
	if($status == 'all'){
		$f325_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE emaildate BETWEEN '$from' AND '$to'");
	}else{
		$f325_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = '$status' AND emaildate BETWEEN '$from' AND '$to'");
	}
}else{
	if($status == 'all'){
		$f325_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE location = '$location' AND emaildate BETWEEN '$from' AND '$to'");
	}else{
		$f325_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = '$status' AND location = '$location' AND emaildate BETWEEN '$from' AND '$to'");
	}
}

	while($fetch_f325 = mysqli_fetch_array($f325_query)) {

	    $f325number = $fetch_f325['f325number'];
	    $vendor = $fetch_f325['vendor'];

	    if($location == 'all'){
	    	$result	= mysqli_query($conn,"SELECT * FROM dbraw WHERE f325number = '$f325number' ORDER BY id ASC ");
	    }else{
	    	$result	= mysqli_query($conn,"SELECT * FROM dbraw WHERE f325number = '$f325number' AND category = '$principal' ORDER BY id ASC ");
	    }

		// get nickname of vendor
		$nickname_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode='$vendor'");
		$fetch_nickname = mysqli_fetch_array($nickname_query);

		$company = $fetch_nickname['nickname'];

		while($row = mysqli_fetch_array($result))
		{

			$mdccode = $row['mdccode'];

			// dbproduct
			$product_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdccode'");
			$fetch_product = mysqli_fetch_array($product_query);

			if (is_array($fetch_product))
			{
				$category = $fetch_product['category'];
				$dmpiclass = $fetch_product['dmpiclassification'];
			}
			else
			{
				$category = 'For Fill-up';
				$dmpiclass = '';
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

			if ($row['dmpireason'] == 0)
			{
				$fullreason = '';
			}
			else
			{
				$dmpireason = $row['dmpireason'];

				// get reason
				$reason_query = mysqli_query($conn,"SELECT * FROM dbdmpireason WHERE reasoncode='$dmpireason'");
				$fetch_reason = mysqli_fetch_array($reason_query);

				$rawreason = $fetch_reason['reason'];
				$fullreason = $dmpireason.'-'.$rawreason;
			}

			if(isset($_POST['f325number'])){$f325number = $row['f325number'];}else{$f325number = '@';}
			if(isset($_POST['skucode'])){$mdccode = $row['mdccode'];}else{$mdccode = '@';}
			if(isset($_POST['description'])){$description = $fetch_product['description'];}else{$description = '@';}
			if(isset($_POST['category'])){$category = $category;}else{$category = '@';}
			if(isset($_POST['brcode'])){$brcode = $fetch_f325['brcode'];;}else{$brcode = '@';}
			if(isset($_POST['brname'])){$brname = $branch;}else{$brname = '@';}
			if(isset($_POST['dmpiclass'])){$dmpiclass = $dmpiclass;}else{$dmpiclass = '@';}
			if(isset($_POST['dmpireason'])){$dmpireason = $fullreason;}else{$dmpireason = '@';}
			if(isset($_POST['reasoncode'])){$reasoncode = $row['reasoncode'];}else{$reasoncode = '@';}
			if(isset($_POST['quantity'])){$quantity = $row['quantity'];}else{$quantity = '@';}
			if(isset($_POST['rcvdqty'])){$rcvdqty = $row['rcvdqty'];}else{$rcvdqty = '@';}
			if(isset($_POST['expiration'])){$expiration = $row['expiration'];}else{$expiration = '@';}
			if(isset($_POST['f325status'])){$f325status = $row['status'];}else{$f325status = '@';}
			if(isset($_POST['arnumber'])){$arnumber = $row['arnumber'];}else{$arnumber = '@';}
			if(isset($_POST['arreason'])){$arreason = $row['arreason'];}else{$arreason = '@';}
			if(isset($_POST['preparedby'])){$preparedby = $fetch_f325['preparedby'];}else{$preparedby = '@';}
			if(isset($_POST['issuedby'])){$issuedby = $fetch_f325['issuedby'];}else{$issuedby = '@';}
			if(isset($_POST['emaildate'])){$emaildate = $fetch_f325['emaildate'];}else{$emaildate = '@';}
			if(isset($_POST['f325date'])){$f325date = $fetch_f325['f325date'];}else{$f325date = '@';}
			if(isset($_POST['company'])){$company = $company;}else{$company = '@';}
			if(isset($_POST['tmnumber'])){$tmnumber = $fetch_f325['tmnumber'];}else{$tmnumber = '@';}
			if(isset($_POST['platenumber'])){$platenumber = $fetch_f325['platenumber'];}else{$platenumber = '@';}
			if(isset($_POST['driver'])){$driver = $fetch_f325['drivername'];}else{$driver = '@';}

			if(isset($_POST['scheddate']))
			{
				if ($fetch_f325['datesched'] == '0000-00-00')
				{
					$datesched = '';
				}
				else
				{
					$datesched = $fetch_f325['datesched'];
				}
			}
			else
			{
				$datesched = '@';
			}

			if(isset($_POST['cleardate']))
			{
				if ($fetch_f325['datecleared'] == '0000-00-00')
				{
					$datecleared = '';
				}
				else
				{
					$datecleared = $fetch_f325['datecleared'];
				}
			}
			else
			{
				$datecleared = '@';
			}

			if(isset($_POST['unitcost'])){$unitcost = $row['unitcost'];}else{$unitcost = '@';}
			if(isset($_POST['costextended'])){$costextended = $row['quantity'] * $row['unitcost'];}else{$costextended = '@';}
			if(isset($_POST['print'])){$print = $fetch_f325['printremarks'];}else{$print = '@';}
			if(isset($_POST['log'])){$log = $fetch_f325['logisticremarks'];}else{$log = '@';}
			if(isset($_POST['clearing'])){$clearing = $fetch_f325['clearingremarks'];}else{$clearing = '@';}
			if(isset($_POST['cluster'])){$cluster = $fetch_f325['cluster'];}else{$cluster = '@';}
			if(isset($_POST['location'])){$location = $fetch_f325['location'];}else{$location = '@';}
			if(isset($_POST['process'])){$process = $fetch_f325['process'];}else{$process = '@';}
			if(isset($_POST['ilrno'])){$ilrno = $fetch_f325['ilrno'];}else{$ilrno = '@';}

			$array_list = array($f325number,$mdccode,$description,$category,$brcode,$brname,$dmpiclass,$dmpireason,$reasoncode,$quantity,$rcvdqty,$expiration,$f325status,$arnumber,$arreason,$preparedby,$issuedby,$emaildate,$f325date,$company,$tmnumber,$platenumber,$driver,$datesched,$datecleared,$unitcost,$costextended,$print,$log,$clearing,$cluster,$location,$process,$ilrno);

			$remove = array('@');
			$result_array = array_diff($array_list, $remove);

			fputcsv($output,$result_array);
		}
	}

fclose($output);

$conn->close();
?>