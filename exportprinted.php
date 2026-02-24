<?php
session_start();
include_once('dbconnect.php');

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="F325 Printed as of '.date('m-d-Y').'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

fputcsv($output, array('F325 Number','Code','TM #','Driver Name','Plate Number','Date Schedule','Remarks','Cluster'));

$export_query = "SELECT * FROM dbf325number WHERE status='PRINTED' ";

// location
$location = "location='' ";

for ($loc = 1; $loc <= 10; $loc++)
{ 
	// get location
	$location_query = mysqli_query($conn,"SELECT * FROM dblocation WHERE id='$loc' ");
	$fetch_location = mysqli_fetch_array($location_query);

	if ($_SESSION['loc'.$fetch_location['id']] == '1'){$location .= "OR location='".$fetch_location['location']."' ";}
}

$export_query .= "AND (".$location.") ";

$export_query .= "ORDER BY vendor,brcode ASC";

$exportcsv_query = mysqli_query($conn,$export_query);
while ($fetch_exportcsv = mysqli_fetch_array($exportcsv_query))
{
	fputcsv($output,array("'".$fetch_exportcsv['f325number'],$fetch_exportcsv['brcode'],$fetch_exportcsv['tmnumber'],$fetch_exportcsv['drivername'],$fetch_exportcsv['platenumber'],'','',$fetch_exportcsv['cluster']));
}

fclose($output);

$conn->close();
?>