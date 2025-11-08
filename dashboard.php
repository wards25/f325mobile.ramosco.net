<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");

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

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Total F325 Open Status</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $open_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'open' AND emaildate BETWEEN '2024-01-01' AND NOW()");
                                                    $open_count = mysqli_num_rows($open_query);
                                                    echo number_format($open_count);
                                                ?>
                                            </div>
                                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> | <a href="open.php">View</a></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total F325 Printed Status</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $printed_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'printed' AND emaildate BETWEEN '2024-01-01' AND NOW()");
                                                    $printed_count = mysqli_num_rows($printed_query);
                                                    echo number_format($printed_count);
                                                ?>
                                            </div>
                                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> | <a href="printed.php">View</a></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-print fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total F325 Scheduled Status</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $scheduled_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'scheduled'AND emaildate BETWEEN '2024-01-01' AND NOW()");
                                                    $scheduled_count = mysqli_num_rows($scheduled_query);
                                                    echo number_format($scheduled_count);
                                                ?>
                                            </div>
                                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> | <a href="scheduled.php">View</a></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Requests Card Example -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total F325 Cleared Status</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $cleared_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'cleared' AND emaildate BETWEEN '2024-01-01' AND NOW()");
                                                    $cleared_count = mysqli_num_rows($cleared_query);
                                                    echo number_format($cleared_count);
                                                ?>
                                            </div>
                                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> | <a href="cleared.php">View</a></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-stamp fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <center><h6 class="m-0 font-weight-bold text-primary">Shortlanded Summary</h6></center>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                                    <thead class="table-info text-dark text-center">
                                        <tr>
                                            <th>Company</th>
                                            <th>Amount Unpaid</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        <?php
                                        $vendor_query = mysqli_query($conn,"SELECT * FROM sl_list GROUP BY vendor");
                                        while($fetch_vendor = mysqli_fetch_array($vendor_query))
                                            {
                                                $vendorcode = $fetch_vendor['vendor'];
                                                $company_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode = '$vendorcode'");
                                                $fetch_company = mysqli_fetch_array($company_query);

                                                $sum_query = mysqli_query($conn,"SELECT sum(qty),sum(costextended) FROM sl_list WHERE vendor = '$vendorcode' AND paymentstatus = 'UNPAID'");
                                                $fetch_sum = mysqli_fetch_array($sum_query);
                                        ?>
                                        <tr>
                                            <?php echo '<td>'.$fetch_company['nickname'].'</td>'; ?>
                                            <?php echo '<td>₱'.number_format($fetch_sum['sum(costextended)'], 2).'</td>'; ?>
                                        </tr>
                                            <?php
                                                }
                                            ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- End Table -->



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