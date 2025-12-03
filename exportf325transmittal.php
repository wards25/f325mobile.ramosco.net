<?php
session_start();
include('db.php');

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="Raw Report as of '.date('m-d-Y').'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

$header = array();

// header
if(isset($_POST['inputf325number'])){$header[] = 'F325 NUMBER';}
if(isset($_POST['inputmdccode'])){$header[] = 'MDC CODE';}
if(isset($_POST['inputcategory'])){$header[] = 'CATEGORY';}
if(isset($_POST['inputdmpiclass'])){$header[] = 'DMPI Class';}
if(isset($_POST['inputdmpireason'])){$header[] = 'DMPI Reason';}
if(isset($_POST['inputreasoncode'])){$header[] = 'REASON CODE';}
if(isset($_POST['inputquantity'])){$header[] = 'QUANTITY';}
if(isset($_POST['inputrcvdqty'])){$header[] = 'RECEIVED QUANTITY';}
if(isset($_POST['inputexpiration'])){$header[] = 'EXPIRATION';}
if(isset($_POST['inputstatus'])){$header[] = 'STATUS';}
if(isset($_POST['inputarnumber'])){$header[] = 'AR NUMBER';}
if(isset($_POST['inputarreason'])){$header[] = 'AR REASON';}
if(isset($_POST['inputbrcode'])){$header[] = 'BR CODE';}
if(isset($_POST['inputbrcode'])){$header[] = 'BRANCH NAME';}
if(isset($_POST['inputpreparedby'])){$header[] = 'PREPARED BY';}
if(isset($_POST['inputissuedby'])){$header[] = 'ISSUED BY';}
if(isset($_POST['inputemaildate'])){$header[] = 'EMAIL DATE';}
if(isset($_POST['inputf325date'])){$header[] = 'F325 DATE';}
if(isset($_POST['inputvendor'])){$header[] = 'VENDOR CODE';}
if(isset($_POST['inputtmnumber'])){$header[] = 'TM NUMBER';}
if(isset($_POST['inputplatenumber'])){$header[] = 'PLATE NUMBER';}
if(isset($_POST['inputdrivername'])){$header[] = 'DRIVER NAME';}
if(isset($_POST['inputdatesched'])){$header[] = 'DATE SCHEDULE';}
if(isset($_POST['inputdatecleared'])){$header[] = 'DATE CLEARED';}
if(isset($_POST['inputunitcost'])){$header[] = 'UNIT COST';}
if(isset($_POST['inputcostextended'])){$header[] = 'COST EXTENDED';}
if(isset($_POST['inputprintremarks'])){$header[] = 'PRINT REMARKS';}
if(isset($_POST['inputlogisticremarks'])){$header[] = 'LOGISTIC REMARKS';}
if(isset($_POST['inputclearingremarks'])){$header[] = 'CLEARING REMARKS';}
if(isset($_POST['inputcluster'])){$header[] = 'CLUSTER';}
if(isset($_POST['inputlocation'])){$header[] = 'LOCATION';}
if(isset($_POST['inputprocess'])){$header[] = 'PROCESS';}

fputcsv($output,$header);

$filter_query = "SELECT * FROM dbf325number ";

$filter_query .= "WHERE ";

// date range
$inputfrom = $_POST['inputfrom'];
$inputto = $_POST['inputto'];

$filter_query .= "emaildate BETWEEN '$inputfrom' AND '$inputto' ";

// location
if (strlen($_POST['select_location']) >= '1')
{
 	$location = $_POST['select_location'];
 	$filter_query .= "AND location='$location' ";
}
else
{
	$location = "location='' ";

	for ($loc = 1; $loc <= 10; $loc++)
	{ 
		// get location
		$location_query = mysqli_query($conn,"SELECT * FROM dblocation WHERE id='$loc' ");
		$fetch_location = mysqli_fetch_array($location_query);

		if ($_SESSION['loc'.$fetch_location['id']] == '1'){$location .= "OR location='".$fetch_location['location']."' ";}
	}

	$filter_query .= "AND (".$location.") ";
}

// status
$allstatus = "status='' ";

foreach ($_POST['inputselectionstatus'] as $key => $value)
{
	if (strlen($value) >= '1'){$allstatus .= "OR status='".$value."'"; }
}

$filter_query .= "AND (".$allstatus.")";

$query = mysqli_query($conn,$filter_query);
while ($fetch_query = mysqli_fetch_array($query))
{
	$f325number = $fetch_query['f325number'];
	$vendor = $fetch_query['vendor'];

	// get nickname of vendor
	$nickname_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode='$vendor'");
	$fetch_nickname = mysqli_fetch_array($nickname_query);

	$nicknamevendor = $fetch_nickname['nickname'];

	//dbraw
	$f325number_query = mysqli_query($conn,"SELECT * FROM dbraw WHERE f325number='$f325number' ");
	while ($fetch_raw = mysqli_fetch_array($f325number_query))
	{
		$rawmdccode = $fetch_raw['mdccode'];

		// dbproduct
		$product_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode='$rawmdccode' AND vendor='$vendor'");
		$fetch_product = mysqli_fetch_array($product_query);

		if (is_array($fetch_product))
		{
			$rawcategory = $fetch_product['category'];
			$rawdmpiclass = $fetch_product['dmpiclassification'];
		}
		else
		{
			$rawcategory = 'For Fill-up';
			$rawdmpiclass = '';
		}

		$brcode = $fetch_query['brcode'];
		// branch code detail
		$branch_query = mysqli_query($conn,"SELECT * FROM dbcensus WHERE code='$brcode' ");
		$fetch_branch = mysqli_fetch_array($branch_query);

		if (is_array($fetch_branch))
		{
			$rawbranch = $fetch_branch['branchname'];
		}
		else
		{
			$rawbranch = 'For Fill-up';
		}

		if ($fetch_raw['dmpireason'] == 0)
		{
			$fullreason = '';
		}
		else
		{
			$rawdmpireason = $fetch_raw['dmpireason'];
			// get reason
			$reason_query = mysqli_query($conn,"SELECT * FROM dbdmpireason WHERE reasoncode='$rawdmpireason'");
			$fetch_reason = mysqli_fetch_array($reason_query);

			$rawreason = $fetch_reason['reason'];

			$fullreason = $rawdmpireason.'-'.$rawreason;
		}

		if(isset($_POST['inputf325number'])){$rawf325number = $f325number;}else{$code = '@';}
		if(isset($_POST['inputmdccode'])){$mdccode = $fetch_raw['mdccode'];}else{$mdccode = '@';}
		if(isset($_POST['inputcategory'])){$category = $rawcategory;}else{$category = '@';}
		if(isset($_POST['inputdmpiclass'])){$dmpiclass = $rawdmpiclass;}else{$dmpiclass = '@';}
		if(isset($_POST['inputdmpireason'])){$dmpireason = $fullreason;}else{$dmpireason = '@';}
		if(isset($_POST['inputreasoncode'])){$reasoncode = $fetch_raw['reasoncode'];}else{$reasoncode = '@';}
		if(isset($_POST['inputquantity'])){$quantity = $fetch_raw['quantity'];}else{$quantity = '@';}
		if(isset($_POST['inputrcvdqty'])){$rcvdqty = $fetch_raw['rcvdqty'];}else{$rcvdqty = '@';}
		if(isset($_POST['inputexpiration'])){$expiration = $fetch_raw['expiration'];}else{$expiration = '@';}
		if(isset($_POST['inputstatus'])){$status = $fetch_query['status'];}else{$status = '@';}
		if(isset($_POST['inputarnumber'])){$arnumber = $fetch_query['arnumber'];}else{$arnumber = '@';}
		if(isset($_POST['inputarreason'])){$arreason = $fetch_raw['arreason'];}else{$arreason = '@';}
		if(isset($_POST['inputbrcode'])){$code = $brcode;}else{$code = '@';}
		if(isset($_POST['inputbrcode'])){$branchname = $rawbranch;}else{$branchname = '@';}
		if(isset($_POST['inputpreparedby'])){$preparedby = $fetch_query['preparedby'];}else{$preparedby = '@';}
		if(isset($_POST['inputissuedby'])){$issuedby = $fetch_query['issuedby'];}else{$issuedby = '@';}
		if(isset($_POST['inputemaildate'])){$emaildate = $fetch_query['emaildate'];}else{$emaildate = '@';}
		if(isset($_POST['inputf325date'])){$f325date = $fetch_query['f325date'];}else{$f325date = '@';}
		if(isset($_POST['inputvendor'])){$rawvendor = $nicknamevendor;}else{$rawvendor = '@';}
		if(isset($_POST['inputtmnumber'])){$tmnumber = $fetch_query['tmnumber'];}else{$tmnumber = '@';}
		if(isset($_POST['inputplatenumber'])){$platenumber = $fetch_query['platenumber'];}else{$platenumber = '@';}
		if(isset($_POST['inputdrivername'])){$drivername = $fetch_query['drivername'];}else{$drivername = '@';}
		if(isset($_POST['inputdatesched']))
		{
			if ($fetch_query['datesched'] == '0000-00-00')
			{
				$datesched = '';
			}
			else
			{
				$datesched = $fetch_query['datesched'];
			}
		
		}
		else
		{
			$datesched = '@';
		}

		if(isset($_POST['inputdatecleared']))
		{
			if ($fetch_query['datecleared'] == '0000-00-00')
			{
				$datecleared = '';
			}
			else
			{
				$datecleared = $fetch_query['datecleared'];
			}
		}
		else
		{
			$datecleared = '@';
		}

		if(isset($_POST['inputunitcost'])){$unitcost = $fetch_raw['unitcost'];}else{$unitcost = '@';}
		if(isset($_POST['inputcostextended'])){$costextended = $quantity*$fetch_raw['unitcost'];}else{$costextended = '@';}
		if(isset($_POST['inputprintremarks'])){$printremarks = $fetch_query['printremarks'];}else{$printremarks = '@';}
		if(isset($_POST['inputlogisticremarks'])){$logisticremarks = $fetch_query['logisticremarks'];}else{$logisticremarks = '@';}
		if(isset($_POST['inputclearingremarks'])){$clearingremarks = $fetch_query['clearingremarks'];}else{$clearingremarks = '@';}
		if(isset($_POST['inputcluster'])){$cluster = $fetch_query['cluster'];}else{$cluster = '@';}
		if(isset($_POST['inputlocation'])){$location = $fetch_query['location'];}else{$location = '@';}
		if(isset($_POST['inputprocess'])){$process = $fetch_query['process'];}else{$process = '@';}

		$array_list = array($rawf325number,$mdccode,$category,$dmpiclass,$dmpireason,$reasoncode,$quantity,$rcvdqty,$expiration,$status,$arnumber,$arreason,$code,$branchname,$preparedby,$issuedby,$emaildate,$f325date,$rawvendor,$tmnumber,$platenumber,$drivername,$datesched,$datecleared,$unitcost,$costextended,$printremarks,$logisticremarks,$clearingremarks,$cluster,$location,$process);
		$remove = array('@');

		$result_array = array_diff($array_list, $remove);

		fputcsv($output,$result_array);
	}
}

fclose($output);

$conn->close();
?>