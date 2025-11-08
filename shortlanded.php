<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");

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

                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Unpaid F325</h1>
                </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-12 col-md-12 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Unpaid F325</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $sl_query = mysqli_query($conn,"SELECT sum(costextended) FROM sl_list WHERE paymentstatus = 'UNPAID'");
                                                    $fetch_sl = mysqli_fetch_array($sl_query);
                                                    echo '₱'.number_format($fetch_sl['sum(costextended)'], 2);
                                                ?>
                                            </div>
                                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> | <a href="shortlanded_complete.php">Complete List</a></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="table-info text-dark text-center">
                                        <tr>
                                            <th>SL Date</th>
                                            <th>SL No.</th>
                                            <th>Days Unpaid</th>
                                            <th>View</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        <?php
                                        $now = date('Y-m-d');
                                        $datetime2 = new DateTime($now);
                                    
                                        $result = mysqli_query($conn,"SELECT * FROM sl_number WHERE paymentstatus = 'UNPAID' GROUP BY f325no");
                                        while($row = mysqli_fetch_array($result))
                                            {
                                                $datetime1 = new DateTime($row['dateprocessed']);
                                                $difference = $datetime1->diff($datetime2);
                                                $diff = $difference->format('%a' );
                                        ?>
                                        <tr>
                                            <?php echo '<td>'.$row['dateprocessed'].'</td>'; ?>
                                            <?php echo '<td>'.$row['slno'].'</td>'; ?>
                                            <?php echo '<td class="text-danger">'.$diff.' Days</td>'; ?>
                                            <td><center><a type="submit" name="view" class="data btn-sm btn-success" onclick="window.open('view_shortlanded.php?f325number=<?php echo $row['f325no'] ?>')">View</a></center></td>
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