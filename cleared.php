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
                <h1 class="h3 mb-0 text-gray-800">Cleared F325</h1>
                </div>

                    <!-- Content Row -->
                    <div class="row">

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-12 col-md-12 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total F325 Cleared Status</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $cleared_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'cleared'");
                                                    $cleared_count = mysqli_num_rows($cleared_query);
                                                    echo number_format($cleared_count);
                                                ?>
                                            </div>
                                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-stamp  fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" action="cleared.php">
                                <div class="form-row">
                                    <div class="col-6">
                                        <label>Cleared Date From:</label>
                                        <input type="date" class="form-control form-control-sm" name="from" required>
                                    </div>
                                    <div class="col-6">
                                        <label>Cleared Date To:</label>
                                        <input type="date" class="form-control form-control-sm" name="to" required>
                                    </div>
                                    <div class="col-12">
                                        <br>
                                    <?php
                                        $query = "SELECT * FROM dbcompany WHERE active = '1 ' ORDER BY name ASC";
                                        $result = $conn->query($query);
                                        if($result->num_rows> 0) {
                                          $options= mysqli_fetch_all($result, MYSQLI_ASSOC);?>
                                         
                                        <select class="form-control form-control-sm" name="company" required>
                                            <?php
                                            echo '<option value="all">ALL COMPANIES</option>';
                                            foreach ($options as $option) {
                                                echo '<option value="'.$option['vendorcode'].'">'.$option['name'].'</option>';
                                            }
                                        }
                                    ?>
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
                            <h6 class="m-0 font-weight-bold text-primary">Cleared F325 | From: <?php echo $_POST['from']; ?> To: <?php echo $_POST['to']; ?> 
                                <?php 
                                $company = $_POST['company'];

                                    if($company == 'all'){
                                        echo '('.$_POST['company'].')';
                                    }else{
                                        $company_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode = '$company'");
                                        $fetch_company = mysqli_fetch_array($company_query);
                                        echo '('.$fetch_company['nickname'].')';
                                    }
                                ?>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="table-info text-dark text-center">
                                        <tr>
                                            <th>Cleared Date</th>
                                            <th>Document No.</th>
                                            <th>View</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        <?php
                                        $from = $_POST['from'];
                                        $to = $_POST['to'];

                                        if($company == 'all'){
                                            $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'cleared' AND datecleared BETWEEN '$from' AND '$to'");
                                        }else{
                                            $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE vendor = '$company' AND status = 'cleared' AND datecleared BETWEEN '$from' AND '$to'");
                                        }
                                        
                                        while($row = mysqli_fetch_array($result))
                                            {
                                        ?>
                                        <tr>
                                            <?php echo '<td>'.$row['datecleared'].'</td>'; ?>
                                            <?php echo '<td>'.$row['f325number'].'</td>'; ?>
                                            <td><center><a type="submit" name="view" class="data btn-sm btn-success" onclick="window.open('view_f325.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo $row['emaildate'] ?>')">View</a></center></td>
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
                    <form method="POST" action="exportcleared_process.php">
                        <input type="text" name="from" value="<?php echo $from; ?>" hidden>
                        <input type="text" name="to" value="<?php echo $to; ?>" hidden>
                        <input type="text" name="company" value="<?php echo $company; ?>" hidden>
                        <center><button type="submit" class="btn btn-sm btn-success"><i class="fa fa-download"></i> Export to Excel</button></center>
                    </form>
                    <?php
                }
                ?>
                <br>
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