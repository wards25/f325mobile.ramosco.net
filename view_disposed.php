<?php
session_start();
//error_reporting(0);
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

if(!isset($_SESSION['id']))
{
  header("Location: index.php");
}
$res=mysqli_query($conn,"SELECT * FROM dbuser WHERE id=".$_SESSION['id']);
$userRow=mysqli_fetch_array($res);
?>

<?php
include_once("nav.php");
?>
                <!-- Begin Page Content -->
                <div class="container-fluid">

<?php
    if(!isset($_POST['view'])){
    $f325number = $_GET['f325number'];
?>

                <script>
                    window.setTimeout(function() {
                        $(".alert").fadeTo(500, 0).slideUp(500, function(){
                            $(this).remove(); 
                        });
                    }, 2000);
                </script>

                <?php
                // Get status message
                if(!empty($_GET['status'])){
                    switch($_GET['status']){
                        case 'succ':
                            $statusType = 'alert-success';
                            $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> F325 submitted successfully.';
                            ?>
                            <meta http-equiv="refresh" content="2.7;url=manual.php">
                            <?php
                            break;
                        case 'err':
                            $statusType = 'alert-danger';
                            $statusMsg = '<i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> No data encoded.';
                            break;
                        default:
                            $statusType = '';
                            $statusMsg = '';
                    }
                }
                ?>

                <!-- Display status message -->
                <?php if(!empty($statusMsg)){ ?>
                <div class="alert <?php echo $statusType; ?> alert-dismissable fade show" role="alert">
                     <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?php echo $statusMsg; ?>
                </div>
                <?php } ?>

                <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <?php
                            $info_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE f325number = '$f325number'");
                            $fetch_info = mysqli_fetch_array($info_query);
                            ?>
                                    <div class="row">
                                        <div class="col-6">
                                            <label><i>F325 No:</i></label>
                                            <input type="number" class="form-control form-control-sm" value="<?php echo $f325number; ?>" maxlength="12" readonly>
                                        </div>
                                        <div class="col-6">
                                            <label><i>F325 Date:</i></label>
                                            <input type="date" class="form-control form-control-sm" value="<?php echo $fetch_info['f325date'];?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <label><i>Branch Name:</i></label>
                                            <?php 
                                            $code = $fetch_info['brcode'];
                                            $branch_query = mysqli_query($conn, "SELECT * FROM dbcensus WHERE code = '$code'");
                                            $fetch_branch = mysqli_fetch_array($branch_query);
                                            ?>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_branch['code']." - ".$fetch_branch['branchname'];?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <label><i>Email Date:</i></label>
                                            <input type="date" class="form-control form-control-sm" value="<?php echo $fetch_info['emaildate']; ?>" readonly>
                                        </div>
                                        <div class="col-6">
                                            <label><i>Status:</i></label>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_info['status']; ?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <label><i>Prepared By:</i></label>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_info['preparedby']; ?>" readonly>
                                        </div>
                                        <div class="col-6">
                                            <label><i>Issued By:</i></label>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_info['issuedby']; ?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <label><i>Driver Name:</i></label>
                                    <?php
                                        if($fetch_info['status'] == 'DISPOSED'){
                                    ?>
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_info['drivername']; ?>" readonly>
                                                &nbsp;&nbsp;&nbsp;
                                                <span class="input-group-addon"><a type="button" class="d-sm-inline-block btn btn-sm btn-danger shadow-sm" href="#" data-toggle="modal" data-target="#documentModal">Document</a></span>
                                            </div>
                                    <?php 
                                        }else{
                                    ?>  
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_info['drivername']; ?>" readonly>
                                    <?php
                                        }
                                    ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <label><i>TM No:</i></label>
                                            <input type="number" class="form-control form-control-sm" value="<?php echo $fetch_info['tmnumber']; ?>" readonly>
                                        </div>
                                        <div class="col-6">
                                            <label><i>Plate No:</i></label>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_info['platenumber']; ?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <label><i>Company:</i></label>
                                            <?php 
                                            $vendorcode = $fetch_info['vendor'];
                                            $branch_query = mysqli_query($conn, "SELECT * FROM dbcompany WHERE vendorcode = '$vendorcode'");
                                            $fetch_branch = mysqli_fetch_array($branch_query);
                                            ?>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_branch['name'];?>" readonly>
                                            </select>
                                        </div>
                                    </div>

                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="table-info text-center">
                                            <tr>
                                                <th>Item</th>
                                                <th>Qty</th>
                                                <th>Rcvd</th>
                                                <th>Cost Ext</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                                // Add prdlist in db 
                                                $product_query = mysqli_query($conn,"SELECT * FROM dbraw WHERE f325number = '$f325number'");
                                                while($fetch_product = mysqli_fetch_array($product_query)){
                                                    $mdc = $fetch_product['mdccode'];
                                                    $description_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdc'");
                                                    $fetch_description = mysqli_fetch_array($description_query);
                                            ?>
                                            <tr>
                                                <?php
                                                echo '<td><center>'.$fetch_product['mdccode'].' -- '.$fetch_description['description'].'</center></td>';
                                                echo '<td><center>'.$fetch_product['quantity'].'</center></td>';
                                                echo '<td><center>'.$fetch_product['rcvdqty'].'</center></td>';
                                                echo '<td><center>'.number_format($fetch_product['costextended'], 2).'</center></td>'; 
                                                ?>
                                            </tr>
                                        <?php
                                            }
                                        ?>
                                        <?php
                                        $total_query = mysqli_query($conn,"SELECT sum(costextended) FROM dbraw WHERE f325number = '$f325number'");
                                        $fetch_total = mysqli_fetch_array($total_query);
                                            echo '<tr class="table-warning"><td colspan="3" class="table-light text-dark"><b>Total Amount Vat-in: </b></td>';
                                            echo '<td class="table-light text-danger text-center"><b>₱'.number_format($fetch_total['sum(costextended)'] ,2).'</b></td></tr>';
                                        ?>
                                        </tbody>
                                        <tfoot>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <form method="POST" action="disposed_process.php" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-12">
                                <label><i>Disposal Document:</i></label>
                                <?php
                                $disposal_status = $fetch_info['status'];
                                if($disposal_status == 'DISPOSED'){
                                    echo '<input type="file" accept="image/*" capture="capture" class="form-control form-control-sm" name="image" required disabled/>';
                                }else{
                                    echo '<input type="file" accept="image/*" capture="capture" class="form-control form-control-sm" name="image" required/>';
                                    echo '<input type="text" name="f325number" value="'.$f325number.'" hidden>';
                                }
                                ?>
                            </div>
                        </div>

                        <center>
                        <hr>
                            <button class="d-sm-inline-block btn btn-sm btn-secondary shadow-sm" onclick="window.close()">x</button>
                            <a type="button" class="d-sm-inline-block btn btn-sm btn-info shadow-sm" href="#" data-toggle="modal" data-target="#historyModal">History</a>
                            <?php
                            if($disposal_status == 'DISPOSED'){

                            }else{
                                echo '<button type="submit" name="submit" class="d-sm-inline-block btn btn-sm btn-danger shadow-sm"><i class="fa fa-trash"></i> Dispose</button>';
                            }
                            ?>
                        </form>
                            <?php
                            $sl_query = mysqli_query($conn,"SELECT * FROM sl_number WHERE f325no = '$f325number'");
                            $sl_count = mysqli_num_rows($sl_query);

                            if($sl_count == 1){
                            ?>
                                <a type="button" class="d-sm-inline-block btn btn-sm btn-danger shadow-sm" href="#" data-toggle="modal" data-target="#slModal"><i class="fa fa-xs fa-money-bill"></i> SL Info</a>
                            <?php
                            }else{

                            }
                            ?>
                        </center>
                        
                        <!-- History Modal-->
                        <div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">History</h6>
                                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                        <div class="modal-body">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered">
                                                    <thead class="table-info text-center">
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Action</th>
                                                            <th>Date / Time</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                            // Add prdlist in db 
                                                            $history_query = mysqli_query($conn,"SELECT * FROM dbhistory WHERE processnumber='$f325number' ORDER BY id DESC ");
                                                            while($fetch_history = mysqli_fetch_array($history_query)){
                                                        ?>
                                                        <tr>
                                                            <?php echo '<td><center>'.$fetch_history['name'].'</center></td>'; ?>
                                                            <?php echo '<td><center>'.$fetch_history['processed'].'</center></td>'; ?>
                                                            <?php echo '<td><center>'.$fetch_history['dateprocessed'].' | '.$fetch_history['timeprocessed'].'</center></td>'; ?>
                                                    <?php
                                                        }
                                                    ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Document Modal-->
                            <div class="modal fade" id="documentModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                                aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">Disposal Document</h6>
                                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">×</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <img src="filepicture/dbfile/<?php echo $f325number;?>.jpg" class="img-fluid">
                                        </div>
                                        <div class="modal-footer">
                                            <a class="btn btn-success btn-sm" type="button" onclick="window.open('download_f325.php?f325number=<?php echo $f325number; ?>&folder=dbfile')"><i class="fa fa-sm fa-download"></i> Download</a>
                                            <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
                <!-- End Table -->            

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

    <?php
        }
    include_once("footer.php");
    ?>
    
        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a> 

    <!-- Bootstrap core JavaScript-->
    <!-- <script src="vendor/jquery/jquery.min.js"></script> -->
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>

</body>

</html>