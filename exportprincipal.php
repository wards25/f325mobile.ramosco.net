<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");

if(!isset($_SESSION['id']) || $_SESSION['report']=='0')
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

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Export F325 Report</h1>
                </div>

                <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" action="exportprincipal_process.php">
                                <div class="form-row">
                                    <div class="col-6">
                                        <label>Email Date From:</label>
                                        <input type="date" class="form-control form-control-sm" name="from" required>
                                    </div>
                                    <div class="col-6">
                                        <label>Email Date To:</label>
                                        <input type="date" class="form-control form-control-sm" name="to" required>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="col-12">
                                    <label>Principal:</label>
                                    <?php 
                                        $query ="SELECT * FROM dbproduct GROUP BY category ORDER BY category ASC";
                                        $result = $conn->query($query);
                                        if($result->num_rows> 0){
                                          $options= mysqli_fetch_all($result, MYSQLI_ASSOC);?>
                                         
                                        <select class="form-control form-control-sm" name="principal" required>
                                            <option value="all">ALL</option>
                                          <?php 
                                          foreach ($options as $option) {
                                          ?>
                                            <option value="<?php echo $option['category'];?>"><?php echo $option['category']; ?> </option>
                                            <?php 
                                            }
                                          }
                                           ?>
                                        </select>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="col-6">
                                        <label>Status:</label>
                                        <select class="form-control form-control-sm" name="status" required>
                                            <option value="all">ALL</option>
                                            <option value="cancelled">CANCELLED</option>
                                            <option value="cleared">CLEARED</option>
                                            <option value="disposed">DISPOSED</option>
                                            <option value="for billed">FOR BILLED</option>
                                            <option value="for billing">FOR BILLING</option>
                                            <option value="for payment">FOR PAYMENT</option>
                                            <option value="okfordeduct">OKFORDEDUCT</option>
                                            <option value="open">OPEN</option>
                                            <option value="printed">PRINTED</option>
                                            <option value="scheduled">SCHEDULED</option>
                                            <option value="uploaded">UPLOADED</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label>Location:</label>
                                        <select class="form-control form-control-sm" name="location" required>
                                            <option value="all">ALL</option>
                                            <option value="cainta">CAINTA</option>
                                            <option value="cdo">CDO</option>
                                            <option value="cebu">CEBU</option>
                                            <option value="davao">DAVAO</option>
                                            <option value="iloilo">ILOILO</option>
                                            <option value="pangasinan">PANGASINAN</option>
                                            <option value="south luzon">SOUTH LUZON</option>
                                        </select>
                                    </div>
                                </div>
                                <hr>
                                <!-- Column Table -->
                                <div class="form-row">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="f325number" id="flexCheckDefault1001">
                                                            <label class="form-check-label" for="flexCheckDefault1001">F325 Number</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="skucode" id="flexCheckDefault1002">
                                                            <label class="form-check-label" for="flexCheckDefault1002">SKU Code</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="description" id="flexCheckDefault1003">
                                                            <label class="form-check-label" for="flexCheckDefault1003">Description</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="category" id="flexCheckDefault1004">
                                                            <label class="form-check-label" for="flexCheckDefault1004">Category</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="brcode" id="flexCheckDefault1005">
                                                            <label class="form-check-label" for="flexCheckDefault1005">Branch Code</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="brname" id="flexCheckDefault1006">
                                                            <label class="form-check-label" for="flexCheckDefault1006">Branch Name</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="dmpiclass" id="flexCheckDefault1007">
                                                            <label class="form-check-label" for="flexCheckDefault1007">DMPI Class</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="dmpireason" id="flexCheckDefault1008">
                                                            <label class="form-check-label" for="flexCheckDefault1008">DMPI Reason</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="reasoncode" id="flexCheckDefault1009">
                                                            <label class="form-check-label" for="flexCheckDefault1009">Reason Code</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="quantity" id="flexCheckDefault1010">
                                                            <label class="form-check-label" for="flexCheckDefault1010">Quantity</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="rcvdqty" id="flexCheckDefault1011">
                                                            <label class="form-check-label" for="flexCheckDefault1011">Received Qty</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="expiration" id="flexCheckDefault1012">
                                                            <label class="form-check-label" for="flexCheckDefault1012">Expiration</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="f325status" id="flexCheckDefault1013">
                                                            <label class="form-check-label" for="flexCheckDefault1013">Status</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="arnumber" id="flexCheckDefault1014">
                                                            <label class="form-check-label" for="flexCheckDefault1014">AR Number</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="arreason" id="flexCheckDefault1015">
                                                            <label class="form-check-label" for="flexCheckDefault1015">AR Reason</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="preparedby" id="flexCheckDefault1016">
                                                            <label class="form-check-label" for="flexCheckDefault1016">Prepared By</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="issuedby" id="flexCheckDefault1017">
                                                            <label class="form-check-label" for="flexCheckDefault1017">Issued By</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="emaildate" id="flexCheckDefault1018">
                                                            <label class="form-check-label" for="flexCheckDefault1018">Email Date</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="f325date" id="flexCheckDefault1019">
                                                            <label class="form-check-label" for="flexCheckDefault1019">F325 Date</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="company" id="flexCheckDefault1020">
                                                            <label class="form-check-label" for="flexCheckDefault1020">Company</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="tmnumber" id="flexCheckDefault1021">
                                                            <label class="form-check-label" for="flexCheckDefault1021">TM Number</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="platenumber" id="flexCheckDefault1022">
                                                            <label class="form-check-label" for="flexCheckDefault1022">Plate Number</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="driver" id="flexCheckDefault1023" >
                                                            <label class="form-check-label" for="flexCheckDefault1023">Driver Name</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="scheddate" id="flexCheckDefault1024">
                                                            <label class="form-check-label" for="flexCheckDefault1024">Sched Date</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="cleardate" id="flexCheckDefault1025">
                                                            <label class="form-check-label" for="flexCheckDefault1025">Cleared Date</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="unitcost" id="flexCheckDefault1026">
                                                            <label class="form-check-label" for="flexCheckDefault1026">Unit Cost</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="costextended" id="flexCheckDefault1027" >
                                                            <label class="form-check-label" for="flexCheckDefault1027">Cost Extended</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="print" id="flexCheckDefault1028">
                                                            <label class="form-check-label" for="flexCheckDefault1028">Print Remarks</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="log" id="flexCheckDefault1029">
                                                            <label class="form-check-label" for="flexCheckDefault1029">Log Remarks</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="clearing" id="flexCheckDefault1030">
                                                            <label class="form-check-label" for="flexCheckDefault1030">Clearing Remarks</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="cluster" id="flexCheckDefault1031">
                                                            <label class="form-check-label" for="flexCheckDefault1031">Cluster</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="f325location" id="flexCheckDefault1032">
                                                            <label class="form-check-label" for="flexCheckDefault1032">Location</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="process" id="flexCheckDefault1033">
                                                            <label class="form-check-label" for="flexCheckDefault1033">Process</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="ilr" id="flexCheckDefault1034">
                                                            <label class="form-check-label" for="flexCheckDefault1034">ILR No</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-12">
                                        <center><small class="text-danger"><u><i>Reminder: Exportation Will Take Minutes, Please Be Patient When Exporting A Report.</u></i></small></center>
                                        <hr>
                                        <button type="submit" class="form-control btn btn-sm" name="view" style="background-color:#915c83; color:#ffffff;"><i class="fa fa-download"></i> Export Data</button>
                                    </div>
                                </div>
                            </form>
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

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
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