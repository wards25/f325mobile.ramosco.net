<?php
include('dbconnect.php');

// get all location
$location_query = mysqli_query($conn,"SELECT * FROM tbl_location ");
while ($fetch_location = mysqli_fetch_array($location_query))
{
	?>
	<tr>
		<td class="tbl-list-location-td1">
			<?php echo $fetch_location['id']; ?>
		</td>
		<td class="tbl-list-location-td2">
			<input type="text" class="form-control input-style input-location mt-2" locid="<?php echo $fetch_location['id']; ?>" value="<?php echo $fetch_location['location']; ?>" onclick="$(this).select();">
		</td>
		<td class="tbl-list-location-td3">
			<input type="checkbox" class="input-checkbox-style input-checkbox-active my-2" value="1" <?php if($fetch_location['active'] == '1'){echo "checked='checked'";} ?>> Active
		</td>
	</tr>
	<?php
}

$conn->close();
?>