<?php
session_start();
include_once("dbconnect.php");

$username = $_SESSION['fname'];

if (isset($_POST['code']))
{
	$code = $_POST['code'];
		
	$total_query = mysqli_query($conn,"SELECT sum(quantity),sum(received) FROM manual_list WHERE user = '$username'");
	$fetch_total = mysqli_fetch_array($total_query);

	$totalquantity = $fetch_total['sum(quantity)'];
	$totalreceived = $fetch_total['sum(received)'];

		if($totalquantity == $totalreceived || $totalquantity < $totalreceived){

				$final_query = mysqli_query($conn,"SELECT * FROM manual_list WHERE user = '$username'");
				while($fetch_final = mysqli_fetch_array($final_query)){
					$final_qty = $fetch_final['quantity'];
					$final_received = $fetch_final['received'];

					if($final_qty == $final_received || $final_qty < $final_received){
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