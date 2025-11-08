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
    $emaildate = $_GET['emaildate'];
    $company = $_GET['company'];
    $_SESSION['f325number'] = $f325number;
    
    mysqli_query($conn,"DELETE FROM cleared_list WHERE user = '$username'");

    // Add prdlist in db 
    //$product_query = mysqli_query($conn,"SELECT * FROM dbraw WHERE f325number = '$f325number' AND skustatus = '1'");
    $product_query = mysqli_query($conn,"SELECT * FROM dbraw WHERE f325number = '$f325number'");
    while($fetch_product = mysqli_fetch_array($product_query)){
        $db_id = $fetch_product['id'];
        $mdccode = $fetch_product['mdccode'];
        $quantity = $fetch_product['quantity'];
        $bbd = $fetch_product['expiration'];
        $unitcost = $fetch_product['unitcost'];
        $reason = $fetch_product['reasoncode'];
        $bbd = $fetch_product['expiration'];

        // Check if item code exists
            $check_query = mysqli_query($conn,"SELECT * FROM cleared_list WHERE mdccode = '$mdccode' AND user = '$username'");
            $row = mysqli_num_rows($check_query);

            if ($row >= 1)
            {
                
            }
            else
            {

            $category_query = mysqli_query($conn,"SELECT category FROM dbproduct WHERE mdccode = '$mdccode'");
            $fetch_category = mysqli_fetch_array($category_query);
            $category = $fetch_category['category'];

            mysqli_query($conn,"INSERT INTO cleared_list(db_id,user,mdccode,category,quantity,received,unitcost,reason,dmpireason,bbd) VALUES('$db_id','$username','$mdccode','$category','$quantity','','$unitcost','$reason','','$bbd')");
        }
    }
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
                            <!--<meta http-equiv="refresh" content="2.7;url=manual.php">-->
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
                                <input type="number" class="form-control form-control-sm" name="f325number" value="<?php echo $f325number; ?>" maxlength="12" readonly>
                            </div>
                            <div class="col-6">
                                <label><i>F325 Date:</i></label>
                                <input type="date" class="form-control form-control-sm" name="f325date" value="<?php echo $fetch_info['f325date'];?>" readonly>
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
                                <input type="text" name="code" value="<?php echo $code; ?>" id="branchcode" hidden>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <label><i>Email Date:</i></label>
                                <input type="date" class="form-control form-control-sm" name="emaildate" value="<?php echo $fetch_info['emaildate']; ?>" readonly>
                            </div>
                            <div class="col-6">
                                <label><i>Verification Date:</i></label>
                                <input type="date" class="form-control form-control-sm" name="emaildate" value="<?php echo $fetch_info['verificationdate']; ?>" readonly>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <form id="ItemForm">
                                <label><i>Scan Barcode:</i></label>
                                <input type="text" class="form-control form-control-sm" name="mdccode" autofocus="on" autocomplete="off">
                                <input type="text" name="name" value="<?php echo $_SESSION['fname']; ?>" hidden>
                                        <td><center><button type="submit" id="add" name="add" class="btn btn-sm" style="background:#915c83; color:#ffffff;" hidden><i class="fa fa-boxes"></i> Add Item(s)</button></center></td>
                                </form>
                            </div>
                        </div>
                        <hr>
                        <div id="alert"></div>
                        <!-- Item Modal-->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-info text-center">
                                            <tr>
                                                <th>Item</th>
                                                <th>Rcvd</th>
                                                <th>BBD</th>
                                            </tr>
                                        </thead>
                                        <tbody id="item-list">
                                        </tbody>
                                        <tfoot>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <center>
                        <hr>
                            <!-- <button type="submit" name="submit" class="btn bt-sm btn-success btn-sm"><i class="fa fa-stamp"></i> Clear F325</button> -->
                        </center>
                    </div>
                </div>
                <!-- End Table -->

                <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">Remove Item(s)</h6>
                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                                <form id="DeleteItem">
                                    <div class="modal-body">Are you sure you want to delete this item?
                                        <small><i>(deleting this item cannot be undone)</i></small>
                                        <input type="text" name="id" class="cleared-id" hidden>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                                        <button class="btn btn-danger btn-sm" type="submit"><i class="fa fa-sm fa-trash"></i> Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                                    
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

    <script>
        function getcategory() {
            var vendorcode = $('.vendorcode').val();
            $.ajax({
                type: "POST",
                url: "manual_product.php",
                data:'company_id='+vendorcode,
                success: function(data){
                    $("#category-list").html(data);
                }
            });
        }
        getcategory();

       // submit item
       $('#ItemForm').submit(function(e){
            e.preventDefault();
            var item = $('#ItemForm').serialize();
            $.ajax({
                type: "post",
                url: "scan_add.php",
                data: item,
                success: function(data) {
                    $('#ItemForm')[0].reset();
                    
                    if(data == '') {
                        ItemList();
                    } else {
                        ItemList();
                        $('#alert').show();
                        $('#alert').html(data);
                        window.setTimeout(function() {
                            $(".alert").fadeTo(500, 0).slideUp(500, function(){
                                $(this).remove(); 
                            });
                        }, 2000);
                    }
                }
            });
        }); 

       // on click delete-btn
        $(document).on('click','.delete-btn',function(){
            var id = $(this).data('id');
            $.ajax({
                type: "post",
                url: "scan_id.php",
                data: {id:id},
                dataType: "json",
                success: function(data){
                    $('#deleteModal').modal('show');
                    $('.cleared-id').val(data.id);
                }
            });
        });
        
        // delete item
        $('#DeleteItem').submit(function(e){
            e.preventDefault();
            var delete_id = $('#DeleteItem').serialize();
            $.ajax({
                type: "post",
                url: "scan_remove.php",
                data: delete_id,
                success: function(){
                    $('#deleteModal').modal('hide');
                    ItemList();
                    ClearedUpdate();
                }
            });
        });

       // item list
       function ItemList() {
            $.ajax({
                type: "post",
                url: "scan_list.php",
                success: function(data) {
                    $('#item-list').html(data);
                }
            });
       }
       ItemList();

       // delete item
       /*
        $(document).on('click', '.btn-remove', function(){
            var id = $(this).data("id");
            $.ajax({
                type: "post",
                url: "cleared_delete.php",
                data: {id:id},
                success: function() {
                    ItemList();
                }
            });
            
        });
        */
        $('.search-product').select2({
            theme: "bootstrap",
            dropdownParent: $("#itemModal")
        });

        // // on click delete-btn
        // $(document).on('click','.delete-btn',function(){
        //     var id = $(this).data('id');
        //     $.ajax({
        //         type: "post",
        //         url: "cleared_id.php",
        //         data: {id:id},
        //         dataType: "json",
        //         success: function(data){
        //             $('#deleteModal').modal('show');
        //             $('.cleared-id').val(data.id);
        //         }
        //     });
        // });
        
        // // delete item
        // $('#DeleteItem').submit(function(e){
        //     e.preventDefault();
        //     var delete_id = $('#DeleteItem').serialize();
        //     $.ajax({
        //         type: "post",
        //         url: "cleared_delete.php",
        //         data: delete_id,
        //         success: function(){
        //             $('#deleteModal').modal('hide');
        //             ItemList();
        //             ClearedUpdate();
        //         }
        //     });
        // });
        
    </script>

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