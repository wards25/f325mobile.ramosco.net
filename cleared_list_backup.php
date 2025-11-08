<?php
session_start();
include_once("dbconnect.php");
$user = $_SESSION['fname'];
$f325number = $_SESSION['f325number'];

    $result = mysqli_query($conn,"SELECT * FROM cleared_list WHERE user = '$user'");
    while($row = mysqli_fetch_array($result)) {
    $mdc = $row['mdccode'];

    $product_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdc'");
    $fetch_product = mysqli_fetch_array($product_query);

    if($row['db_id'] == 0){
        echo '<tr class="table-warning text-center">';
    }else{
        echo '<tr class="text-center">';
    }
    ?>
        <?php echo '<td><center>'.$row['mdccode'].' -- '.$fetch_product['description'].'</center></td>'; ?>
        <?php echo '<td><center>'.$row['quantity'].'</center></td>'; ?>.
        <?php
            if($row['db_id']=='0'){
                echo '<td><center>'.$row['received'].'</center></td>';
                echo '<td><center>'.number_format($row['unitcost'], 2).'</center></td>';
            }else{
        ?>
        <td><center><input type="number" style="width:45px;" class="form-control form-control-sm received-qty" data-id="<?php echo $row['id']?>" value="<?php echo $row['received'];?>"></center></td>
        <td><center><input type="number" style="width:70px;" class="form-control form-control-sm unitcost" data-id="<?php echo $row['id']?>" value="<?php echo $row['unitcost'];?>" step="any"></center></td>
        <?php
            }
        ?>
        <?php echo '<td><center>'.$row['reason'].'</center></td>'; ?>
        
        <?php
        if($row['db_id'] == 0){

            if($row['category'] == 'DMPI'){
                echo '<td><center>'.$row['dmpireason'].'</center></td>';
            }else{
                echo '<td><center>'.$row['dmpireason'].'</center></td>';
            }

        }else if($row['db_id'] >= 1){

            if($row['category'] == 'DMPI'){ 
                echo '<td><center><input type="text" inputmode="numeric" pattern="[0-9]*" style="width:45px;" onKeyPress="if(this.value.length==2) return false;" class="form-control form-control-sm dmpireason" data-id="'.$row['id'].'" value="'.$row['dmpireason'].'" required></td></center>';
            }else{
                echo '<td></td>';
            }
        }
            echo '<td><center>'.$row['bbd'].'</center></td>'; 
        ?>
        <td><center><a type="button" class="text-danger btn-xs delete-btn" href="#" data-id="<?php echo $row['id']?>"><i class="fa fa-times fa-xs"></i></a></center></td>
    </tr>

<?php
    $qty = $row['quantity'];
    $unitcost = number_format($row['unitcost'],2);
    $total += $unitcost * $qty;
    }
?>
    <tr class="table-warning"><td colspan="3" class="table-light text-dark"><b>Total Amount Vat-in: </b></td>
    <td colspan="2" class="table-light text-danger text-center"><b>₱<?php echo number_format($total ,2); ?></b></td>
    <td colspan="2"><center><a type="button" class="btn btn-light btn-sm" href="#" data-toggle="modal" data-target="#reasonModal">DMPI Reason Codes</a></center></td>
