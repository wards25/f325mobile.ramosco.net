<?php 
session_start();
include('dbconnect.php');
$cleareddate = date("Y-m-d", strtotime($_POST['f325date']));
$timefrom = date("H:i:s", strtotime($_POST['timefrom']));
$timeto = date("H:i:s", strtotime($_POST['timeto']));

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="F325 Transmittal '.$cleareddate.'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
fputcsv($output, array('BR CODE', 'BRANCH NAME', 'F325 NO', 'COMPANY', 'ACTUAL QTY', 'RCVD QTY', 'SL FORM', 'CLEARED DATE', 'CLEARED TIME'));

// $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'cleared' AND datecleared = '$cleareddate' AND location = 'cainta' AND cleared_time BETWEEN '$timefrom' AND '$timeto' ORDER BY cleared_time");

$result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE cleared_time BETWEEN '$timefrom' AND '$timeto' AND status = 'cleared' AND datecleared = '$cleareddate' AND location = 'cainta' ORDER BY cleared_time");

// $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE cleared_time BETWEEN '$timefrom' AND '$timeto' AND status = 'cleared' AND datecleared = '$cleareddate' ORDER BY cleared_time");

	while($row = mysqli_fetch_array($result))
	{
		$vendorcode = $row['vendor'];
		$company_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode = '$vendorcode'");
		$fetch_company = mysqli_fetch_array($company_query);
		$company = $fetch_company['nickname'];

		$brcode = $row['brcode'];
		$census_query = mysqli_query($conn,"SELECT * FROM dbcensus WHERE code = '$brcode'");
		$fetch_census = mysqli_fetch_array($census_query);

		$f325number = $row['f325number'];
		$qty_query = mysqli_query($conn,"SELECT f325number,sum(quantity) as totalqty,sum(rcvdqty) as totalrcvd FROM dbraw WHERE f325number = '$f325number'");
		$fetch_qty = mysqli_fetch_assoc($qty_query);

	    fputcsv($output, array($row['brcode'],$fetch_census['branchname'],$row['f325number'],$company,$fetch_qty['totalqty'],$fetch_qty['totalrcvd'],$row['arnumber'],$row['datecleared'],$row['cleared_time']));
	}

fclose($output);

$conn->close();
?>