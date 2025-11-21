<?php
session_start();
include('dbconnect.php');

$status = $_POST['status'];
$process = 'UPLOADED';

// query
$search_query = "SELECT * FROM dbf325number WHERE status='$status' AND process='$process' ";
// $search_query = "SELECT * FROM dbf325number WHERE status='$status' ";

// allow to re-open is today
//if ($status == 'PRINTED')
//{
	//$today = date("Y-m-d");
	//$search_query .= "AND emaildate='$today' ";
//}

// search
if (strlen($_POST['search']) >= 1)
{
	$selectsearch = $_POST['selectsearch'];
	$search = $_POST['search'];

	$search_query .= "AND $selectsearch LIKE '%$search%'";
}

// company
if (strlen($_POST['company']) >= 1)
{
	$company = $_POST['company'];$search_query .= "AND vendor='$company' ";
}
else
{
	// company
	$company = "vendor='' ";

	for ($i = 1; $i <= 10; $i++)
	{
		//get vendor code of company
		$vendorcode_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE id='$i' ");
		$fetch_vendorcode = mysqli_fetch_array($vendorcode_query);

		if ($_SESSION['comp'.$fetch_vendorcode['id']] == '1'){$company .= "OR vendor='".$fetch_vendorcode['vendorcode']."'";} 
	}

	$search_query .= "AND (".$company.") ";
}

// location
$location = "location='' ";

for ($loc = 1; $loc <= 10; $loc++)
{ 
	// get location
	$location_query = mysqli_query($conn,"SELECT * FROM dblocation WHERE id='$loc' ");
	$fetch_location = mysqli_fetch_array($location_query);

	if ($_SESSION['loc'.$fetch_location['id']] == '1'){$location .= "OR location='".$fetch_location['location']."' ";}
}

$search_query .= "AND (".$location.") "; 

$search_query .= "ORDER BY vendor,brcode ASC LIMIT 500";

// search in database
$search_database = mysqli_query($conn,$search_query);

// total row result
$row = mysqli_num_rows($search_database);

// condition if have result
if ($row >= 1)
{
	// loop result
	while ($fetch_search = mysqli_fetch_array($search_database))
	{
		?>
		<tr class="tbl-list-order-tr" f325id="<?php echo $fetch_search['id']; ?>">
			<td class="tbl-list-order-td1">
				<?php echo $fetch_search['f325number']; ?>
			</td>
			<td class="tbl-list-order-td2">
				<?php
				$code = $fetch_search['brcode'];

				$code_query = mysqli_query($conn,"SELECT * FROM dbcensus WHERE code='$code' ");
				$fetch_code = mysqli_fetch_array($code_query);
				if (is_array($fetch_code))
				{
					echo $fetch_code['franchise'].' '.$code.' - '.$fetch_code['branchname'];
				}
				else
				{
					echo $code.' - ';
				}
				?>
			</td>
			<td class="tbl-list-order-td3">
				<?php echo date("m-d-Y",strtotime($fetch_search['emaildate'])); ?>
			</td>
			<td class="tbl-list-order-td4">
				<?php echo date("m-d-Y",strtotime($fetch_search['f325date'])); ?>
			</td>
			<td class="tbl-list-order-td5">
				<?php
				$vendorcode = $fetch_search['vendor'];

				$vendor_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode='$vendorcode'");
				$fetch_vendor = mysqli_fetch_array($vendor_query);
				if (is_array($fetch_vendor))
				{
					echo $fetch_vendor['name'];
				}
				else
				{
					echo 'For Fill-Up';
				}
				?>
			</td>
			<td class="tbl-list-order-td6">
				<?php echo $fetch_search['status']; ?>
			</td>
		</tr>
		<?php
	}
}
else
{
	// no data result
	?>
	<tr>
		<td class="td-no-data" colspan="6">
			No data result Found.
		</td>
	</tr>
	<?php
}

$conn->close();
?>