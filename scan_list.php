<?php
session_start();
include_once("dbconnect.php");
$user = $_SESSION['fname'];
$f325number = $_SESSION['f325number'];

    $result = mysqli_query($conn,"SELECT * FROM cleared_list WHERE user = '$user' AND received>0");
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
        <?php echo '<td><center>'.$row['received'].'</center></td>'; ?>
        <td><center><a type="button" class="text-danger btn-xs delete-btn" href="#" data-id="<?php echo $row['id']?>"><i class="fa fa-times fa-xs"></i></a></center></td>
    </tr>
<?php
}
?>
