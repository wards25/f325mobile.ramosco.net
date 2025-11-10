<?php
include('dbconnect.php');

// get all location
$location_query = mysqli_query($conn,"SELECT * FROM dblocation ");
while ($fetch_location = mysqli_fetch_array($location_query))
{
	?>
	<tr>
		<td class="tbl-list-location-td1">
			<?php echo $fetch_location['id']; ?>
		</td>
		<td class="tbl-list-location-td2">
			<input type="text" class="input-style input-location" locid="<?php echo $fetch_location['id']; ?>" value="<?php echo $fetch_location['location']; ?>" onclick="$(this).select();">
		</td>
		<td class="tbl-list-location-td3">
			<input type="checkbox" class="input-checkbox-style input-checkbox-active" value="1" <?php if($fetch_location['active'] == '1'){echo "checked='checked'";} ?>> Active
		</td>
	</tr>
	<?php
}

$conn->close();
?>