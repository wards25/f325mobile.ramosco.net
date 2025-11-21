<?php
include('dbconnect.php');

$processnumber = $_POST['processnumber'];

// get history
$history_query = mysqli_query($conn,"SELECT * FROM dbhistory WHERE processnumber='$processnumber' ORDER BY id DESC ");
$row = mysqli_num_rows($history_query);

if ($row >= 1)
{
	while ($fetch_history = mysqli_fetch_array($history_query))
	{
		?>
		<tr>
			<td class="tbl-history-td1"><?php echo $fetch_history['name']; ?></td>
			<td class="tbl-history-td2"><?php echo $fetch_history['processed']; ?></td>
			<td class="tbl-history-td3"><?php echo $fetch_history['dateprocessed'].' | '.$fetch_history['timeprocessed']; ?></td>
		</tr>
		<?php
	}
}
else
{
	?>
	<tr>
		<td colspan="3" style="text-align: center;">No result found.</td>
	</tr>
	<?php
}

$conn->close();
?>