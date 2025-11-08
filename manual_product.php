<?php
session_start();
require_once("dbconnect.php");

$company = $_POST['company_id'];

if(!empty($_POST["company_id"])) 
{
	$query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE vendor = '$company'");
	while($row=mysqli_fetch_array($query))  
	{
?>
	<option value="<?php echo $row['mdccode']; ?>"><?php echo $row['mdccode'].' - '.$row['description']; ?></option>
<?php
	}
}
?>