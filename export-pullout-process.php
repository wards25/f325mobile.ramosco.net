<?php
session_start();
include('dbconnect.php');

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="For Pull Out Report as of '.date('m-d-Y').'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

fputcsv($output,array('BRANCH CODE','BRANCH NAME','REGION','LOCATION','COMPANY','F325 DATE','EMAIL DATE','F325 NUMBER','DESCRIPTION','CATEGORY','QUANTITY','UOM','COST EXTENDED'));

if (isset($_POST['select_location']))
{
	if ($_POST['select_location'] == 'ALL')
	{
		$raw_query = mysqli_query($conn,"SELECT * FROM dbraw WHERE (statusout='CLEARED' OR statusout='UPLOADED') ");
	}
	else
	{
		$location = $_POST['select_location'];
		$raw_query = mysqli_query($conn,"SELECT * FROM dbraw WHERE location='$location' AND (statusout='CLEARED' OR statusout='UPLOADED') ");
	}
	while ($fetch_raw = mysqli_fetch_array($raw_query))
	{
		$f325number = $fetch_raw['f325number'];
		$mdccode = $fetch_raw['mdccode'];

		// dbf325number
		$f325number_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE f325number='$f325number' ");
		$fetch_f325number = mysqli_fetch_array($f325number_query);

		$brcode = $fetch_f325number['brcode'];
		$emaildate = $fetch_f325number['emaildate'];
		$f325date = $fetch_f325number['f325date'];
		$vendor = $fetch_f325number['vendor'];

		// branch name
		$branch_query = mysqli_query($conn,"SELECT * FROM dbcensus WHERE code='$brcode' ");
		$fetch_branch = mysqli_fetch_array($branch_query);

		$branchname = $fetch_branch['branchname'];
		$region = $fetch_branch['region'];

		// company
		$company_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode='$vendor' ");
		$fetch_company = mysqli_fetch_array($company_query);

		$nickname = $fetch_company['nickname'];

		// product detail
		$prd_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode='$mdccode' ");
		$fetch_prd = mysqli_fetch_array($prd_query);

		if (is_array($fetch_prd))
		{
			$description = $mdccode.' -- '.$fetch_prd['description'];
			$category = $fetch_prd['category'];
		}
		else
		{
			$description = 'For Fill-up';
			$category = 'For Fill-up';
		}
		
		if ($fetch_raw['quantity'] >= 2)
		{
			$uom = 'PCS';
		}
		else
		{
			$uom = 'PC';
		}

		fputcsv($output,array($brcode,$branchname,$region,$fetch_raw['location'],$nickname,$f325date,$emaildate,"'".$f325number,$description,$category,$fetch_raw['quantity'],$uom,$fetch_raw['costextended']));
	}

	fclose($output);
}

$conn->close();
?>