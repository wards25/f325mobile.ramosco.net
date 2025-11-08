<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

// delete prdlist in db 
mysqli_query($conn,"DELETE FROM cleared_list WHERE user = '$username'");

if(!isset($_SESSION['id']) || $_SESSION['schedule']=='0')
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
                <h1 class="h3 mb-0 text-gray-800">Disposed F325</h1>
                </div>

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
                            $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> F325 disposed successfully.';
                            ?>
                            <!--<meta http-equiv="refresh" content="2.7;url=scheduled.php">-->
                            <?php
                            break;
                        case 'err':
                            $statusType = 'alert-danger';
                            $statusMsg = '<i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> Image error.';
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


                    <!-- Content Row -->
                    <div class="row">

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-12 col-md-12 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Total Disposed F325</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $scheduled_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'disposed' AND emaildate BETWEEN '2023-01-01' AND NOW()");
                                                    $scheduled_count = mysqli_num_rows($scheduled_query);
                                                    echo number_format($scheduled_count);
                                                ?>
                                            </div>
                                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> | <a href="disposed.php">Dispose F325</a></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-trash fa-2x text-gray-300"></i>
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
                                            <th>Disposed Date</th>
                                            <th>Document No.</th>
                                            <th>View</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        <?php
                                        $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'disposed' AND emaildate BETWEEN '2023-01-01' AND NOW()");
                                        while($row = mysqli_fetch_array($result))
                                            {
                                                $f325number = $row['f325number'];
                                                $history_query = mysqli_query($conn,"SELECT * FROM dbhistory WHERE processnumber = '$f325number'  ORDER BY id DESC LIMIT 1");
                                                $fetch_history = mysqli_fetch_array($history_query);
                                        ?>
                                        <tr>
                                            <?php echo '<td>'.$fetch_history['dateprocessed'].'</td>'; ?>
                                            <?php echo '<td>'.$row['f325number'].'</td>'; ?>
                                            <td><center><a type="submit" name="view" class="data btn-sm btn-info" onclick="window.open('view_disposed.php?f325number=<?php echo $row['f325number'] ?>')">View</a></center></td>
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