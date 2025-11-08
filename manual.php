<?php
session_start();
//error_reporting(0);
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

// delete prdlist in db 
mysqli_query($conn,"DELETE FROM manual_list WHERE user = '$username'");

if(!isset($_SESSION['id']) || $_SESSION['manual']=='0')
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

                <script>
                    window.setTimeout(function() {
                        $(".alert").fadeTo(500, 0).slideUp(500, function(){
                            $(this).remove(); 
                        });
                    }, 2000);

                    function getcategory(val) {
                        $.ajax({
                        type: "POST",
                        url: "manual_product.php",
                        data:'company_id='+val,
                        success: function(data){
                            $("#category-list").html(data);
                        }
                        });
                        $.ajax({
                        type: "POST",
                        url: "manual_reason.php",
                        data:'company='+val,
                        success: function(data){
                            $("#reason-list").html(data);
                        }
                        });
                    }
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
                        case 'exist':
                            $statusType = 'alert-warning';
                            $statusMsg = '<i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> F325 number exists.';
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

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800"></h1>
                </div>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <form method="POST" action="manual_process.php" enctype="multipart/form-data">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <label><i>F325 No:</i></label>
                                    <input type="number" id="f325number" class="form-control form-control-sm" name="f325number" onKeyPress="if(this.value.length==12) return false;" onInput="checkUsername()" required>
                                    <span id="check-username"></span>
                                </div>
                                <div class="col-6">
                                    <label><i>F325 Date:</i></label>
                                    <input type="date" class="form-control form-control-sm" name="f325date" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label><i>Branch Name:</i></label>
                                    <?php 
                                    $query ="SELECT * FROM dbcensus";
                                    $result = $conn->query($query);
                                    if($result->num_rows> 0){
                                      $options= mysqli_fetch_all($result, MYSQLI_ASSOC);?>
                                     
                                    <select class="form-control form-control-sm branchcode" name="code" id="search_code" required>
                                      <?php 
                                      foreach ($options as $option) {
                                      ?>
                                        <option value="<?php echo $option['code'];?>"><?php echo $option['code'].' - '.$option['branchname']; ?> </option>
                                        <?php 
                                        }
                                      }
                                       ?>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label><i>Email Date:</i></label>
                                    <input type="date" class="form-control form-control-sm" name="emaildate" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <label><i>Prepared By:</i></label>
                                    <input type="text" class="form-control form-control-sm" name="prepared" required>
                                </div>
                                <div class="col-6">
                                    <label><i>Issued By:</i></label>
                                    <input type="text" class="form-control form-control-sm" name="issued" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label><i>Driver Name:</i></label>
                                    <input type="text" class="form-control form-control-sm" name="driver" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <label><i>TM No:</i></label>
                                    <input type="number" class="form-control form-control-sm" name="tmnumber" required>
                                </div>
                                <div class="col-6">
                                    <label><i>Plate No:</i></label>
                                    <input type="text" class="form-control form-control-sm" name="platenumber" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label><i>Company:</i></label>
                                    <?php 
                                    $query ="SELECT * FROM dbcompany WHERE active = '1'";
                                    $result = $conn->query($query);
                                    if($result->num_rows> 0){
                                      $options= mysqli_fetch_all($result, MYSQLI_ASSOC);?>
                                     
                                    <select class="form-control form-control-sm" onChange="getcategory(this.value);" name="company" id="company" required>
                                        <option value="">Select Company</option>
                                      <?php 
                                      foreach ($options as $option) {
                                      ?>
                                        <option value="<?php echo $option['vendorcode'];?>"><?php echo $option['name']; ?> </option>
                                        <?php 
                                        }
                                      }
                                       ?>
                                    </select>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label><i>AR / SL No:</i></label>
                                    <input type="text" class="form-control form-control-sm slno" name="arnumber" id="slno" readonly>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label><i>AR / SL Document:</i></label>
                                    <input type="file" accept="image/*" capture="capture" class="form-control form-control-sm document" id="document" name="image" disabled/>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label><i>ILR No:</i></label>
                                    <input type="text" class="form-control form-control-sm" name="ilrno" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <label><i>Is Document Stamped?</i></label>
                                    <br>
                                    <div class="form-check form-check-inline">
                                      <input class="form-check-input" type="radio" id="inlineCheckbox1" value="1" name="stamped" required>
                                      <label class="form-check-label" for="inlineCheckbox1">YES</label>
                                      &nbsp;&nbsp;&nbsp;&nbsp;
                                      <input class="form-check-input" type="radio" id="inlineCheckbox2" value="0" name="stamped">
                                      <label class="form-check-label" for="inlineCheckbox2">NO</label>
                                    </div>
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
                                                    <th>Unit Cost</th>
                                                    <th>Reason</th>
                                                    <th>DMPI Reason</th>
                                                    <th>BBD</th>
                                                </tr>
                                            </thead>
                                            <tbody id="item-list">
                                            </tbody>
                                            <tfoot>
                                            </tfoot>
                                        </table>
                                    </div>
                                        <tr>
                                            <td><center><a type="button" class="btn btn-sm" href="#" data-toggle="modal" data-target="#itemModal" style="background:#915c83; color:#ffffff;"><i class="fa fa-boxes"></i> Add Item(s)</a></center></td>
                                        </tr>
                                </div>
                            </div>
                            <center>
                            <hr>
                                <button type="submit" name="submit" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add F325</button>
                            </form>
                            </center>
                            </div>
                    </div>
                    <!-- End Table -->

                    <!-- Item Modal-->
                    <div class="modal fade" id="itemModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">Add Item(s)</h6>
                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                                <form id="ItemForm">
                                    <div class="modal-body">
                                        <div id="alert"></div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label>Item Name/Code:</label>
                                                    <select class="form-control form-control-sm search-product" name="mdccode" id="category-list" required>
                                                        <option value="">Select Company</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-12">
                                                    <label>Reason:</label>
                                                        <select class="form-control form-control-sm" name="reason" id="reason-list" required> </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-12" id="dmpi-reason" style="display:none;">
                                                    <label>DMPI Reason:</label>
                                                    <?php 
                                                    $query ="SELECT * FROM dbdmpireason ORDER BY reasoncode ASC";
                                                    $result = $conn->query($query);
                                                    if($result->num_rows> 0){
                                                      $options= mysqli_fetch_all($result, MYSQLI_ASSOC);?>
                                                     
                                                    <select class="form-control form-control-sm" name="dmpireason" required>
                                                        <option value="0"></option>
                                                      <?php 
                                                      foreach ($options as $option) {
                                                      ?>
                                                        <option value="<?php echo $option['reasoncode'];?>"><?php echo $option['reasoncode'].' - '.$option['reason']; ?> </option>
                                                        <?php 
                                                        }
                                                      }
                                                       ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-6">
                                                    <label>Quantity:</label>
                                                    <input type="number" class="form-control form-control-sm" name="qty" required>
                                                </div>
                                                <div class="col-6">
                                                    <label>Received Quantity:</label>
                                                    <input type="number" class="form-control form-control-sm" name="received" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-6">
                                                    <label>Unit Cost:</label>
                                                    <input type="number" class="form-control form-control-sm" name="unit" step="any" required>
                                                </div>
                                                <div class="col-6">
                                                    <label>BBD:</label>
                                                    <input type="date" class="form-control form-control-sm" name="bbd">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                                    <button type="submit" id="add" name="add" class="btn btn-sm" style="background:#915c83; color:#ffffff;"><i class="fa fa-boxes"></i> Add Item(s)</button>
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

        //check f325 if exist
        function checkUsername() {
            jQuery.ajax({
            url: "check_availability.php",
            data:'f325number='+$("#f325number").val(),
            type: "POST",
            success:function(data){
                $("#check-username").html(data);
            },
            error:function (){}
            });
        }

       // submit item
       $('#ItemForm').submit(function(e){
            e.preventDefault();
            var item = $('#ItemForm').serialize();
            $.ajax({
                type: "post",
                url: "manual_add.php",
                data: item,
                success: function(data) {
                    $('#ItemForm')[0].reset();
                    ManualUpdate();
                    if(data == '') {
                        ItemList();
                    } else {
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

       // item list
       function ItemList() {
            $.ajax({
                type: "post",
                url: "manual_list.php",
                success: function(data) {
                    $('#item-list').html(data);
                }
            });
       }
       ItemList();

       // delete item
        $(document).on('click', '.btn-remove', function(){
            var id = $(this).data("id");
            $.ajax({
                type: "post",
                url: "manual_delete.php",
                data: {id:id},
                success: function() {
                    ItemList();
                    ManualUpdate();
                }
            });
            
        });
        $('.search-product').select2({
            theme: "bootstrap",
            dropdownParent: $("#itemModal")
        });
        $('#search_code').select2({
            theme: "bootstrap"
        });
        
        // on change item code
        $(document).on('change','#category-list',function(){
            var mdccode = $(this).val();
            $.ajax({
                type: "post",
                url: "fetch_dmpi.php",
                data: {mdccode:mdccode},
                dataType: "json",
                success: function(data) {
                    if(data.category == 'DMPI') {
                        $('#dmpi-reason').show();
                    } else {
                        $('#dmpi-reason').hide();
                    }
                }
            });
        });

        function ManualUpdate() {
            let code = $('.branchcode').val();
            let slno = $('#slno');
            $.ajax({
                type: "post",
                url: "manual_update.php",
                data: {code:code},
                success: function(data) {
                    slno.val(data);

                    if(slno.val() =='') {
                        $('#document').attr('disabled',true);
                        $('#document').val('');
                    } else {
                        $('#document').attr('required','required');
                        $('#document').removeAttr('disabled');
                    }
                }
            });
        }
        ManualUpdate();

        // on change branch name in SL field
        $(document).on('change','.branchcode',function(){
            ManualUpdate();
        });
        
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