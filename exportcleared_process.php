<?php 
session_start();
include('dbconnect.php');

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="Cleared F325 Report as of '.date('m-d-Y').'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

fputcsv($output, array('F325 NUMBER', 'BR CODE', 'BRANCH NAME', 'EMAIL DATE', 'F325 DATE', 'SCHED DATE', 'CLEARED DATE', 'COMPANY', 'PROCESS'));

$from = $_POST['from'];
$to = $_POST['to'];
$company = $_POST['company'];

if($company == 'all'){
	$result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'cleared' AND datecleared BETWEEN '$from' AND '$to'");
}else{
	$result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'cleared' AND vendor = '$company' AND datecleared BETWEEN '$from' AND '$to'");
}

	while($row = mysqli_fetch_array($result))
	{
		if($row['process'] == 'APPS'){
			$process = 'MANUAL';
		}else{
			$process = 'UPLOAD';
		}

		$vendorcode = $row['vendor'];
		$company_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode = '$vendorcode'");
		$fetch_company = mysqli_fetch_array($company_query);
		$company = $fetch_company['nickname'];

		$brcode = $row['brcode'];
		$census_query = mysqli_query($conn,"SELECT * FROM dbcensus WHERE code = '$brcode'");
		$fetch_census = mysqli_fetch_array($census_query);

	    fputcsv($output, array($row['f325number'],$row['brcode'],$fetch_census['branchname'],$row['emaildate'],$row['f325date'],$row['datesched'],$row['datecleared'],$company,$process));
	}

fclose($output);

$conn->close();
?>