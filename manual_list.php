<?php
session_start();
include_once("dbconnect.php");
$user = $_SESSION['fname'];

    $result = mysqli_query($conn,"SELECT * FROM manual_list WHERE user = '$user'");
    while($row = mysqli_fetch_array($result)){
    	$mdccode = $row['mdccode'];
    	$product_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdccode'");
    	$fetch_product = mysqli_fetch_array($product_query);
?>
    <tr>
        <?php echo '<td><center>'.$row['mdccode'].' -- '.$fetch_product['description'].'</center></td>'; ?>
        <?php echo '<td><center>'.$row['quantity'].'</center></td>'; ?>
        <?php echo '<td><center>'.$row['received'].'</center></td>'; ?>
    	<?php echo '<td><center>'.number_format($row['unitcost'], 2).'</center></td>'; ?>
        <?php echo '<td><center>'.$row['reason'].'</center></td>'; ?>
        <?php echo '<td><center>'.$row['dmpireason'].'</center></td>'; ?>
        <?php echo '<td><center>'.$row['bbd'].'</center></td>'; ?>
    	<td><center><a class="text-danger btn-remove btn-xs" data-id="<?php echo $row['id']?>"><i class="fa fa-times fa-xs"></i></a></center></td>
    </tr>
    
<?php
    $qty = $row['quantity'];
    $unitcost = number_format($row['unitcost'],2);
    $total += $unitcost * $qty;

    }

    if(empty($total)){

    }else{
        echo '<tr class="table-warning"><td colspan="3" class="table-light text-dark"><b>Total Amount Vat-in: </b></td>
        <td class="table-light text-danger text-center"><b>₱'.number_format($total ,2).'</b></td>
        <td colspan="3" class="table-light text-dark"></td></tr>';
    }
?>