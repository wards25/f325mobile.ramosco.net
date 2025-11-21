<?php
include('dbconnect.php');

$f325number = $_POST['f325number'];
$vcode = $_POST['vcode'];

// query
$order_query = mysqli_query($conn,"SELECT * FROM dbraw WHERE f325number='$f325number' ");
while ($fetch_order = mysqli_fetch_array($order_query))
{
	?>
	<tr class="tbl-order-detail-tr">
		<td class="tbl-order-detail-td1">
			<?php echo $mdccode = $fetch_order['mdccode']; ?>
		</td>
		<?php
		$product_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode='$mdccode' AND vendor='$vcode' AND active='1' ");
		$fetch_product = mysqli_fetch_array($product_query);
		if (is_array($fetch_product))
		{
			$itemcode = $fetch_product['itemcode'];
			$description = $fetch_product['description'];
			$uom = $fetch_product['uom'];
		}
		else
		{
			$itemcode = "";
			$description = "Fill up";
			$uom = "";
		}
		?>
		<td class="tbl-order-detail-td2">
			<?php echo $itemcode; ?>
		</td>
		<td class="tbl-order-detail-td3">
			<?php echo $description; ?>
		</td>
		<td class="tbl-order-detail-td9">
			<?php echo $fetch_order['expiration']; ?>
		</td>
		<td class="tbl-order-detail-td4">
			<?php echo $fetch_order['reasoncode']; ?>
		</td>
		<td class="tbl-order-detail-td5">
			<?php echo $quantity = $fetch_order['quantity']; ?>
		</td>
		<td class="tbl-order-detail-td6">
			<?php
			if($quantity >= 2)
			{
				if (is_array($fetch_product))
				{
					echo $uom.'S';
				}
			}
			else
			{
				if (is_array($fetch_product))
				{
					echo $uom;
				}
			}
			?>
		</td>
		<td class="tbl-order-detail-td7">
			<?php
			echo number_format($fetch_order['unitcost'],2);
			?>
		</td>
		<td class="tbl-order-detail-td8 subtotal-lines" subtotal="<?php echo $quantity * $fetch_order['unitcost']; ?>">
			<?php
			echo number_format($quantity * $fetch_order['unitcost'],2);
			?>
		</td>
	</tr>
	<?php
}


$conn->close();
?>