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
    $_SESSION['vendor'] = $company;
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

            $category_query = mysqli_query($conn,"SELECT category FROM dbproduct WHERE mdccode = '$mdccode' AND vendor = '$company'");
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
                    <div id="alert2"></div>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <form id="ScanForm">
                            <label><i>Scan Barcode:</i></label>
                            <input type="text" class="form-control form-control-sm" name="mdccode" autofocus="on" autocomplete="off" placeholder="click to scan barcode...">
                            <input type="text" name="name" value="<?php echo $_SESSION['fname']; ?>" hidden>
                                    <td><center><button type="submit" id="scan" name="add" class="btn btn-sm" style="background:#915c83; color:#ffffff;" hidden><i class="fa fa-boxes"></i> Add Item(s)</button></center></td>
                            </form>
                        </div>
                    </div>
                    <hr>
                    <form method="POST" action="cleared_process.php" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-12">
                            <label><i>AR / SL No:</i></label>
                                <a href="" type="button" class="close" aria-label="Close" data-toggle="modal" data-target="#infoModal">
                                        <span aria-hidden="true"><i class="fa fa-info-circle fa-xs" style="color:#915c83;"></i></span>
                                    </a>
                            <input type="text" class="form-control form-control-sm slno" name="arnumber" id="slno" readonly>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <label><i>SL Document:</i></label>
                            <input type="file" accept="image/*" capture="capture" class="form-control form-control-sm document" id="document" name="image" disabled/>
                        </div>
                        <div class="col-6">
                            <label><i>ILR No:</i></label>
                            <input type="text" class="form-control form-control-sm" name="ilrno" required>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <div class="row"> 
                            <div class="col-12">
                                <a type="button" class="btn btn-info btn-user btn-block btn-sm shadow" href="#" data-toggle="modal" data-target="#historyModal">View History</a>
                            </div>
                        </div>
                    </div>
                    <hr>

                    <!-- Info Modal-->
                    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">Document Info</h6>
                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">×</span>
                                    </button>
                                </div>
                                    <div class="modal-body">
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
                                            <div class="col-6">
                                                <label><i>Prepared By:</i></label>
                                                <input type="text" class="form-control form-control-sm" name="prepared" value="<?php echo $fetch_info['preparedby']; ?>" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label><i>Issued By:</i></label>
                                                <input type="text" class="form-control form-control-sm" name="issued" value="<?php echo $fetch_info['issuedby']; ?>" readonly>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-12">
                                                <label><i>Driver Name:</i></label>
                                                <input type="text" class="form-control form-control-sm" name="driver" value="<?php echo $fetch_info['drivername']; ?>" readonly>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-6">
                                                <label><i>TM No:</i></label>
                                                <input type="number" class="form-control form-control-sm" name="tmnumber" value="<?php echo $fetch_info['tmnumber']; ?>" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label><i>Plate No:</i></label>
                                                <input type="text" class="form-control form-control-sm" name="platenumber" value="<?php echo $fetch_info['platenumber']; ?>" readonly>
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
                                                <input type="text" class="form-control form-control-sm vendorcode" value="<?php echo $company;?>" hidden>
                                                <input type="text" name="vendorcode" value="<?php echo $fetch_info['vendor'];?>" hidden>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Item Modal-->
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
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
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <label><i>Remarks:</i></label>
                                <textarea class="form-control form-control-sm" rows="3" name="remarks"></textarea>
                            </div>
                        </div>
                        <hr>
                        <center>
                        <div class="row">
                            <div class="col-12">
                                <label><i>Is F325 Document Stamped?</i></label>
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
                            <button class="d-sm-inline-block btn btn-sm btn-secondary shadow-sm" onclick="window.close()">x</button>
                            <button type="button" class="d-sm-inline-block btn btn-sm btn-warning text-dark shadow-sm" href="#" data-toggle="modal" data-target="#verificationModal">For Verification</button>
                            <button type="submit" name="submit" class="btn bt-sm btn-success btn-sm"><i class="fa fa-stamp"></i> Clear F325</button>
                        </form>
                        </center>
                    </div>
                </div>
                <!-- End Table -->


                <!-- Verification Modal -->
                <div class="modal fade" id="verificationModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">Select Verification Reason</h6>
                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <form method="POST" action="verification_process.php">
                                <div class="modal-body">
                                    <select class="form-control form-control-sm" name="verificationreason">
                                        <option value="Re-Del">Re-Del</option>
                                        <option value="Branch Verification">Branch Verification</option>
                                        <option value="Closed Branch Verification">Closed Branch Verification</option>
                                        <option value="Principal Verification">Principal Verification</option>
                                    </select>
                                    <input type="text" name="f325number" value=<?php echo $f325number; ?> hidden>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                                    <button class="btn btn-warning text-dark btn-sm" name="submit" type="submit"><i class="fa fa-sm fa-check"></i> For Verification</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

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
                                                    </select>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-12">
                                                <label>Reason:</label>
                                                <?php 
                                                if ($company == '18565' || $company == '85001'){
                                                    $query ="SELECT * FROM dbmercuryreason WHERE company = 'unilever'";
                                                }else{
                                                    $query ="SELECT * FROM dbmercuryreason WHERE company = 'multiline'";
                                                }
                                                $result = $conn->query($query);
                                                if($result->num_rows> 0){
                                                  $options= mysqli_fetch_all($result, MYSQLI_ASSOC);?>
                                                 
                                                <select class="form-control form-control-sm" name="reason" required>
                                                  <?php 
                                                  foreach ($options as $option) {
                                                  ?>
                                                    <option value="<?php echo $option['nameinitial'];?>"><?php echo $option['nameinitial'].' - '.$option['reason']; ?> </option>
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
                                            <div class="col-12">
                                                <label>Unit Cost:</label>
                                                <input type="number" class="form-control form-control-sm" name="unit" step="any" required>
                                            </div>
                                        </div>
                                    </div><!-- 
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-12">
                                                <label>BBD:</label>
                                                <input type="date" class="form-control form-control-sm" name="bbd">
                                            </div>
                                        </div>
                                    </div> -->
                                </div> 
                                <input type="text" name="name" value="<?php echo $_SESSION['fname']; ?>" hidden>
                            <div class="modal-footer">
                                <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                                <button type="submit" id="add" name="add" class="btn btn-sm" style="background:#915c83; color:#ffffff;"><i class="fa fa-boxes"></i> Add Item(s)</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>               

                <!-- Reason Modal-->
                <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel" style="color:#915c83;">DMPI Reason Codes</h6>
                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                                <div class="modal-body">
                                    <div id="alert"></div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col">
                                                1 - Foreign Material
                                                <br>
                                                2 - Discolored
                                                <br>
                                                3 - Underfilled
                                                <br>
                                                4 - Near Expiry/Expired
                                                <br>
                                                5 - Bulging Packs
                                                <br>
                                                6 - Burst Pack
                                                <br>
                                                7 - Rusty Can
                                                <br>
                                                8 - Dented Packs
                                                <br>
                                                9 - Rat Bites
                                                <br>
                                                10 - Dirty Bottle CapSeal
                                                <br>
                                                11 - Broken Bottle
                                                <br>
                                                12 - Dirty Label
                                                <br>
                                                13 - Broken/Wk Seal Pasta
                                                <br>
                                                14 - Punctured/Cut
                                                <br>
                                                15 - Damaged Carton
                                                <br>
                                                17 - Weak/Bad Seal
                                                <br>
                                                18 - No Label
                                            </div>
                                            <div class="col">
                                                21 - Underpacked
                                                <br>
                                                23 - Secondary Damage 
                                                <br>
                                                25 - Damaged Double Seam Can 
                                                <br>
                                                26 - Damaged Side Seam Can 
                                                <br>
                                                27 - Wrong Label/Wrong Prod
                                                <br>
                                                28 - Torn Label
                                                <br>
                                                30 - Delaminated 
                                                <br>
                                                31 - Off-Taste
                                                <br>
                                                32 - Crack Pasta
                                                <br>
                                                33 - QA Rejected 
                                                <br>
                                                34 - Unrdble/Wrong/No BBD/Code Defects
                                                <br>
                                                35 - No/Blurd BBD Code
                                                <br>
                                                36 - Prod Hardening (Dry Mix)
                                                <br>
                                                37 - Insect Bites
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            <div class="modal-footer">
                                <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

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

        // item list
        function ItemList() {
            $.ajax({
                type: "post",
                url: "cleared_list.php",
                success: function(data) {
                    $('#item-list').html(data);
                    ClearedUpdate();
                }
            });
        }
        ItemList();

        // submit item
        $('#ItemForm').submit(function(e){
            e.preventDefault();
            var item = $('#ItemForm').serialize();
            $.ajax({
                type: "post",
                url: "cleared_add.php",
                data: item,
                success: function(data) {
                    $('#ItemForm')[0].reset();
                    
                    if(data == '') {
                        ItemList();
                        ClearedUpdate();
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

        // scan item
        $('#ScanForm').submit(function(e){
            e.preventDefault();
            var item = $('#ScanForm').serialize();
            $.ajax({
                type: "post",
                url: "scan_add.php",
                data: item,
                success: function(data) {
                    $('#ScanForm')[0].reset();
                    if(data == '') {
                        ClearedUpdate();
                        ItemList();

                    } else {
                        $('#alert2').show();
                        $('#alert2').html(data);
                        window.setTimeout(function() {
                            $(".alert2").fadeTo(500, 0).slideUp(500, function(){
                                $(this).remove(); 
                            });
                        }, 2000);
                    }
                }
            });
        });

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

        // on click delete-btn
        $(document).on('click','.delete-btn',function(){
            var id = $(this).data('id');
            $.ajax({
                type: "post",
                url: "cleared_id.php",
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
                url: "cleared_delete.php",
                data: delete_id,
                success: function(){
                    $('#deleteModal').modal('hide');
                    ItemList();
                    ClearedUpdate();
                }
            });
        });

        // keyup update received qty
        $(document).on('keyup','.received-qty',function(){
            var id = $(this).data('id');
            var val = $(this).val();
            var branchcode = $('#branchcode').val();
            var field = 'received';
            var slno = $('#slno');
            $.ajax({
                type: "post",
                url: "cleared_update.php",
                data: {id:id,value:val,field:field,code:branchcode},
                success: function(data) {
                    $('#slno').val(data);

                    if(slno.val() =='') {
                        $('#document').attr('disabled',true);
                        $('#document').val('');
                    } else {
                        $('#document').attr('required','required');
                        $('#document').removeAttr('disabled');
                    }
                }
            });
        });

        // keyup update quantity
        $(document).on('keyup','.quantity',function(){
            var id = $(this).data('id');
            var val = $(this).val();
            var branchcode = $('#branchcode').val();
            var field = 'quantity';
            $.ajax({
                type: "post",
                url: "cleared_update.php",
                data: {id: id,value:val,field:field,code:branchcode},
                success: function() {
                }
            });
        });

        // keyup update unit cost
        $(document).on('keyup','.unitcost',function(){
            var id = $(this).data('id');
            var val = $(this).val();
            var branchcode = $('#branchcode').val();
            var field = 'unitcost';
            $.ajax({
                type: "post",
                url: "cleared_update.php",
                data: {id: id,value:val,field:field,code:branchcode},
                success: function() {
                }
            });
        });

        // keyup update reason
        $(document).on('change','.reason',function(){
            var id = $(this).data('id');
            var val = $(this).val();
            var branchcode = $('#branchcode').val();
            var field = 'reason';
            $.ajax({
                type: "post",
                url: "cleared_update.php",
                data: {id: id,value:val,field:field,code:branchcode},
                success: function() {
                }
            });
        });

        // keyup update bbd
        $(document).on('change','.bbd',function(){
            var id = $(this).data('id');
            var val = $(this).val();
            var branchcode = $('#branchcode').val();
            var field = 'bbd';
            $.ajax({
                type: "post",
                url: "cleared_update.php",
                data: {id: id,value:val,field:field,code:branchcode},
                success: function() {
                }
            });
        });

        // keyup update dmpireason
        $(document).on('keyup','.dmpireason',function(){
            var id = $(this).data('id');
            var val = $(this).val();
            var branchcode = $('#branchcode').val();
            var field = 'dmpireason';
            $.ajax({
                type: "post",
                url: "cleared_update.php",
                data: {id: id,value:val,field:field,code:branchcode},
                success: function() {
                }
            });
        });

        function ClearedUpdate() {
            var id = $('.received-qty').data('id');
            var val = $('.received-qty').val();
            var branchcode = $('#branchcode').val();
            var field = 'received';
            var slno = $('#slno');
            $.ajax({
                type: "post",
                url: "cleared_update.php",
                data: {id:id,value:val,field:field,code:branchcode},
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