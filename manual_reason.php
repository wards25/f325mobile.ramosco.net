<?php
session_start();
require_once("dbconnect.php");

$company = $_POST['company'];

if($_POST["company"] == '18565' || $_POST["company"] == '85001') 
{
	$query = mysqli_query($conn,"SELECT * FROM dbmercuryreason WHERE company = 'unilever' ORDER BY id ASC");
}else{
	$query = mysqli_query($conn,"SELECT * FROM dbmercuryreason WHERE company = 'multiline' ORDER BY id ASC");
}

while($row = mysqli_fetch_array($query)){
?>

<option value="<?php echo $row['nameinitial'];?>"><?php echo $row['nameinitial'].' - '.$row['reason']; ?> </option>

<?php
}
?>