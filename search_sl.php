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
                <h1 class="h3 mb-0 text-gray-800">Search SL F325</h1>
                </div>

                <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" action="search_sl.php">
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
                                <div class="form-row">
                                    <div class="col-12">
                                        <br>
                                        <select class="form-control form-control-sm" name="status" required>
                                            <option value="ALL">ALL</option>
                                            <option value="PAID">PAID</option>
                                            <option value="UNPAID">UNPAID</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <br>
                                        <button type="submit" class="form-control btn btn-sm btn-success" name="view"><i class="fa fa-search"></i> Search</button>
                                    </div>
                                </div>
                            </form>
                    </div>
                    </div>

                    <?php
                    if(isset($_POST['view'])){
                    ?>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary"><?php echo ucfirst($_POST['status']); ?> SL F325 List | From: <?php echo $_POST['from']; ?> To: <?php echo $_POST['to']; ?></h6>
                        </div>
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

                                        $from = $_POST['from'];
                                        $to = $_POST['to'];
                                        $status = $_POST['status'];

                                        if($status == 'PAID' || $status == 'UNPAID'){
                                            $result = mysqli_query($conn,"SELECT * FROM sl_number WHERE paymentstatus = '$status' AND dateprocessed BETWEEN '$from' AND '$to' GROUP BY f325no");
                                        }else{
                                            $result = mysqli_query($conn,"SELECT * FROM sl_number WHERE dateprocessed BETWEEN '$from' AND '$to' GROUP BY f325no");
                                        }
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
                    <?php
                }
                ?>

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