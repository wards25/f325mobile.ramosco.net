<?php
session_start();
include('dbconnect.php');

$nameexport = $_POST['name-export'];


//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="Printed Summary as of ' . date('m-d-Y') . '.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

fputcsv($output, array('F325 Number', 'F325 Date', 'F325 Email Date', 'Code', 'Branch Name', 'Region', 'Prepared By', 'Issued By', 'Company'));

$export_query = "SELECT * FROM dbf325number WHERE status='PRINTED' AND process='UPLOADED' AND emaildate='$nameexport' ";

// location
$location = "location='' ";

for ($loc = 1; $loc <= 10; $loc++) {
	// get location
	$location_query = mysqli_query($conn, "SELECT * FROM dblocation WHERE id='$loc' ");
	$fetch_location = mysqli_fetch_array($location_query);

	if ($_SESSION['loc' . $fetch_location['id']] == '1') {
		$location .= "OR location='" . $fetch_location['location'] . "' ";
	}
}

$export_query .= "AND (" . $location . ") ";


// company
$company = "vendor='' ";

for ($i = 1; $i <= 10; $i++) {
	//get vendor code of company
	$vendorcode_query = mysqli_query($conn, "SELECT * FROM dbcompany WHERE id='$i' ");
	$fetch_vendorcode = mysqli_fetch_array($vendorcode_query);

	if ($_SESSION['comp' . $fetch_vendorcode['id']] == '1') {
		$company .= "OR vendor='" . $fetch_vendorcode['vendorcode'] . "'";
	}
}

$export_query .= "AND (" . $company . ") ";

$export_query .= "ORDER BY vendor,brcode ASC";

$exportcsv_query = mysqli_query($conn, $export_query);
while ($fetch_exportcsv = mysqli_fetch_array($exportcsv_query)) {
	$brcode = $fetch_exportcsv['brcode'];
	// branch name
	$census_query = mysqli_query($conn, "SELECT branchname,region FROM dbcensus WHERE code='$brcode' ");
	$fetch_census = mysqli_fetch_array($census_query);

	if (is_array($fetch_census)) {
		$branchname = $fetch_census['branchname'];
		$region = $fetch_census['region'];
	} else {
		$branchname = "For Fill-Up";
		$region = "For Fill-Up";
	}

	// company
	$vendorcode = $fetch_exportcsv['vendor'];
	$company_query = mysqli_query($conn, "SELECT vendorcode,nickname FROM dbcompany WHERE vendorcode='$vendorcode'");
	$fetch_company = mysqli_fetch_array($company_query);

	if (is_array($fetch_company)) {
		$vendor = $fetch_company['nickname'];
	} else {
		$vendor = 'For Fill-Up';
	}

	fputcsv($output, array("'" . $fetch_exportcsv['f325number'], $fetch_exportcsv['f325date'], $fetch_exportcsv['emaildate'], $brcode, $branchname, $region, $fetch_exportcsv['preparedby'], $fetch_exportcsv['issuedby'], $vendor));
}

fclose($output);

$conn->close();
?>