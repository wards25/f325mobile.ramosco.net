<?php
session_start();
//error_reporting(0);
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

if(!isset($_SESSION['id']) && $_SESSION['payment']=='0')
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
                            $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> F325 fulfilled successfully.';
                            ?>
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
                                    <?php
                                    $sl_info = mysqli_query($conn,"SELECT * FROM sl_list WHERE f325no = '$f325number'");
                                    $fetch_sl = mysqli_fetch_array($sl_info);
                                    ?>
                                    <div class="row">
                                        <div class="col-12">
                                            <label><i>SL No:</i></label>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_sl['slno']; ?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <label><i>F325 No:</i></label>
                                            <div class="input-group">
                                                <input type="number" class="form-control form-control-sm" value="<?php echo $f325number; ?>" maxlength="12" readonly>
                                                &nbsp;&nbsp;&nbsp;
                                                <span class="input-group-addon"><a type="submit" name="view" class="data btn-sm btn-warning" style="background-color: #915c83;" onclick="window.open('view_f325.php?f325number=<?php echo $f325number; ?>')">View</a></span>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <label><i>Driver Name:</i></label>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_sl['drivername']; ?>" readonly>
                                        </div>
                                        <div class="col-6">
                                            <label><i>SL Date:</i></label>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_sl['dateprocessed']; ?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <label><i>Fulfilled By:</i></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_sl['checkedby']; ?>" readonly>
                                                &nbsp;&nbsp;&nbsp;
                                                <span class="input-group-addon"><a type="button" class="d-sm-inline-block btn btn-sm btn-info shadow-sm" href="#" data-toggle="modal" data-target="#documentModal">Document</a></span>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <label><i>Fulfill Date:</i></label>
                                            <?php
                                            if($fetch_sl['checkdate'] == '0000-00-00'){
                                                echo '<input type="text" class="form-control form-control-sm" readonly>';
                                            }else{
                                            ?>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_sl['checkdate']; ?>" readonly>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="col-6">
                                            <label><i>SL Status:</i></label>
                                            <input type="text" class="form-control form-control-sm" value="<?php echo $fetch_sl['paymentstatus']; ?>" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead class="table-info text-center">
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Qty</th>
                                                    <th>Cost Ext</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $unpaid_query = mysqli_query($conn,"SELECT * FROM sl_list WHERE f325no = '$f325number'");
                                                    while($fetch_unpaid = mysqli_fetch_array($unpaid_query)){
                                                ?>
                                                <tr>
                                                    <?php
                                                    $mdc = $fetch_unpaid['mdccode'];
                                                    $description_query = mysqli_query($conn,"SELECT * FROM dbproduct WHERE mdccode = '$mdc'");
                                                    $fetch_description = mysqli_fetch_array($description_query);
                                                    ?>

                                                    <?php echo '<td><center>'.$fetch_unpaid['mdccode'].' -- '.$fetch_description['description'].'</center></td>'; ?>
                                                    <?php echo '<td><center>'.$fetch_unpaid['qty'].'</center></td>'; ?>
                                                    <?php echo '<td><center>'.number_format($fetch_unpaid['costextended'], 2).'</center></td>'; ?>
                                            <?php
                                                }
                                            ?>
                                            <?php
                                            $totalunpaid_query = mysqli_query($conn,"SELECT sum(costextended) FROM sl_list WHERE f325no = '$f325number'");
                                            $fetch_totalunpaid = mysqli_fetch_array($totalunpaid_query);
                                                echo '<tr class="table-warning"><td colspan="2" class="table-light text-dark"><b>Total Amount Vat-in: </b></td>';
                                                echo '<td class="table-light text-danger text-center"><b>₱'.number_format($fetch_totalunpaid['sum(costextended)'] ,2).'</b></td></tr>';
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                        <center>
                        <hr>
                            <button class="d-sm-inline-block btn btn-sm btn-secondary shadow-sm" onclick="window.close()">Close</button>
                            <?php
                            if($fetch_sl['paymentstatus'] == 'UNPAID'){
                            ?>
                                <a type="button" class="d-sm-inline-block btn btn-sm btn-success shadow-sm" href="#" data-toggle="modal" data-target="#slModal"><i class="fa fa-xs fa-money-bill"></i> Fulfill Payment</a>
                            <?php
                            }else{

                            }
                            ?>
                        </center>
                        </div>

                        <!-- SL Modal-->
                        <div class="modal fade" id="slModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">Fulfill Payment</h6>
                                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">Select "Fulfill" below if you want to fulfill this unpaid transaction.</div>
                                    <div class="modal-footer">
                                        <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                                    <form method="POST" action="fulfill.php">
                                        <input type="text" name="f325number" value="<?php echo $f325number; ?>" hidden>
                                        <input type="text" name="arnumber" value="<?php echo $fetch_sl['slno']; ?>" hidden>
                                        <button type="submit" name="submit" class="btn btn-success btn-sm">Fulfill</button>
                                    </form>
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
                                        <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">Attached Document</h6>
                                        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="filepicture/dbapps/<?php echo $f325number;?>.jpg" class="img-fluid">
                                    </div>
                                    <div class="modal-footer">
                                        <a class="btn btn-success btn-sm" type="button" onclick="window.open('download_f325.php?f325number=<?php echo $f325number; ?>&folder=dbapps')"><i class="fa fa-sm fa-download"></i> Download</a>
                                        <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
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