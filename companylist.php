<?php
include('dbconnect.php');

$company_query = mysqli_query($conn,"SELECT * FROM dbcompany ORDER BY id ASC");
while ($fetch_company = mysqli_fetch_array($company_query))
{
	?>
		<option value="<?php echo $fetch_company['id']; ?>"><?php echo $fetch_company['id'].'. '.$fetch_company['name']; ?></option>
	<?php
}
$conn->close();
?>