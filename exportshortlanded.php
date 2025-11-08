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
                    <h1 class="h3 mb-0 text-gray-800">Export SL F325 Report</h1>
                </div>

                <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" action="exportshortlanded_process.php">
                                <div class="form-row">
                                    <div class="col-6">
                                        <label>SL Date From:</label>
                                        <input type="date" class="form-control form-control-sm" name="from" required>
                                    </div>
                                    <div class="col-6">
                                        <label>SL Date To:</label>
                                        <input type="date" class="form-control form-control-sm" name="to" required>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="col-12">
                                        <label>Status:</label>
                                        <select class="form-control form-control-sm" name="status" required>
                                            <option value="all">ALL</option>
                                            <option value="paid">PAID</option>
                                            <option value="unpaid">UNPAID</option>
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
                                                            <input class="form-check-input" type="checkbox" name="slnumber" id="flexCheckDefault1008">
                                                            <label class="form-check-label" for="flexCheckDefault1008">SL Number</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="user" id="flexCheckDefault1009">
                                                            <label class="form-check-label" for="flexCheckDefault1009">User Processed</label>
                                                        </div>
                                                    </td>
                                                </tr>
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
                                                            <input class="form-check-input" type="checkbox" name="quantity" id="flexCheckDefault1011">
                                                            <label class="form-check-label" for="flexCheckDefault1011">Quantity</label>
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
                                                            <input class="form-check-input" type="checkbox" name="costextended" id="flexCheckDefault1013">
                                                            <label class="form-check-label" for="flexCheckDefault1013">Cost Extended</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="company" id="flexCheckDefault1014">
                                                            <label class="form-check-label" for="flexCheckDefault1014">Company</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="sldate" id="flexCheckDefault1015">
                                                            <label class="form-check-label" for="flexCheckDefault1015">SL Date</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="payment" id="flexCheckDefault1016">
                                                            <label class="form-check-label" for="flexCheckDefault1016">Payment Status</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="checkedby" id="flexCheckDefault1017">
                                                            <label class="form-check-label" for="flexCheckDefault1017">Checked By</label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="checkdate" id="flexCheckDefault1018">
                                                            <label class="form-check-label" for="flexCheckDefault1018">Check Date</label>
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