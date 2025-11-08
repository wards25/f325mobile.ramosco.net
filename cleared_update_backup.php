<?php
session_start();
include_once("dbconnect.php");

$username = $_SESSION['fname'];

$id = $_POST['id'];
$value = $_POST['value'];
$field = $_POST['field'];

if($field == 'received'){
	mysqli_query($conn,"UPDATE cleared_list SET received='$value' WHERE id = '$id'");
	
}else if($field == 'unitcost'){

	mysqli_query($conn,"UPDATE cleared_list SET unitcost='$value' WHERE id = '$id'");
}

if (isset($_POST['code']))
{
	$code = $_POST['code'];

	$value_query = mysqli_query($conn,"SELECT * FROM cleared_list WHERE user = '$username' AND id = '$id'");
	$fetch_value = mysqli_fetch_array($value_query);
	$quantity = $fetch_value['quantity'];
	$received = $fetch_value['received'];
	
	if($quantity == $received){
		
		$total_query = mysqli_query($conn,"SELECT sum(quantity),sum(received) FROM cleared_list WHERE user = '$username'");
		$fetch_total = mysqli_fetch_array($total_query);

		$totalquantity = $fetch_total['sum(quantity)'];
		$totalreceived = $fetch_total['sum(received)'];

			if($totalquantity == $totalreceived){
				echo '';
			}else{

				// load next reference
				$load_query = mysqli_query($conn,"SELECT * FROM sl_number ORDER BY id DESC LIMIT 1");
				$row = mysqli_num_rows($load_query);

				if($row >=1){
				
					$fetch_load = mysqli_fetch_array($load_query);
					$last_number = explode('-', $fetch_load['slno']);
					echo "SL".date("Ym")."-".$code."-".($last_number[count($last_number)-1]+1);
				}else{
					echo "SL".date("Ym")."-".$code."-100001";
				}
			}

	}else{

		// load next reference
		$load_query = mysqli_query($conn,"SELECT * FROM sl_number ORDER BY id DESC LIMIT 1");
		$row = mysqli_num_rows($load_query);

		if($row >=1){

			$fetch_load = mysqli_fetch_array($load_query);
			$last_number = explode('-', $fetch_load['slno']);
			echo "SL".date("Ym")."-".$code."-".($last_number[count($last_number)-1]+1);
		}else{
			echo "SL".date("Ym")."-".$code."-100001";
		}
		
	}
}