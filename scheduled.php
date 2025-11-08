<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

// delete prdlist in db 
mysqli_query($conn,"DELETE FROM cleared_list WHERE user = '$username'");

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
                <h1 class="h3 mb-0 text-gray-800">Scheduled F325</h1>
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
                            $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> F325 cleared successfully.';
                            ?>
                            <!--<meta http-equiv="refresh" content="2.7;url=scheduled.php">-->
                            <?php
                            break;
                        case 'verify':
                            $statusType = 'alert-success';
                            $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> F325 for verification.';
                            break;
                        case 'dispose':
                            $statusType = 'alert-success';
                            $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> F325 disposed successfully.';
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


                    <!-- Content Row -->
                    <div class="row">

                        <!-- Earnings (Monthly) Card Example -->
                        <div class="col-xl-12 col-md-12 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total F325 Scheduled Status</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php
                                                    $scheduled_query = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'scheduled' AND emaildate BETWEEN '2024-01-01' AND NOW()");
                                                    $scheduled_count = mysqli_num_rows($scheduled_query);
                                                    echo number_format($scheduled_count);
                                                ?>
                                            </div>
                                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> | <a href="verification.php">For Verification</a></small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="scheduled.php">
                        <div class="form-row">
                            <div class="col-9">
                                <?php 
                                if(isset($_POST['company'])){
                                    if(is_numeric($_POST['company'])){
                                        $company = $_POST['company'];
                                        $company_query = mysqli_query($conn,"SELECT * FROM dbcompany WHERE vendorcode = '$company'");
                                        $fetch_company = mysqli_fetch_array($company_query);
                                        $company_name = $fetch_company['name'];
                                    }else{

                                    }

                                }else{

                                }

                                $query ="SELECT * FROM dbcompany WHERE active = '1 ' ORDER BY name ASC";
                                $result = $conn->query($query);
                                if($result->num_rows > 0) {
                                  $options= mysqli_fetch_all($result, MYSQLI_ASSOC);?>
                                 
                                <select class="form-control form-control-sm" name="company" required>
                                    <?php
                                    if(is_numeric($_POST['company'])){
                                        echo '<option value="'.$company.'">'.$company_name.'</option>';
                                    }else{
                                        
                                    }
                                    echo '<option value="all">ALL COMPANIES</option>';
                                    foreach ($options as $option) {
                                        echo '<option value="'.$option['vendorcode'].'">'.$option['name'].'</option>';
                                    }
                                }
                                ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <button type="submit" class="btn form-control form-control-sm btn-sm" name="view" style="background-color:#915c83; color:#ffffff;"><i class="fa fa-sm fa-filter"></i> Filter</button>
                            </div>
                        </div>
                        <br>
                    </form>

                    <?php
                    if(isset($_POST['view'])){
                        $vendor = $_POST['company'];

                        if($vendor == 'all' || $vendor == ''){
                        ?>
                            <!-- DataTales Example -->
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead class="table-info text-dark text-center">
                                                <tr>
                                                    <th>Sched Date</th>
                                                    <th>Document No.</th>
                                                    <th>Location</th>
                                                    <th>Days From Sched</th>
                                                    <th>Clear F325</th>
                                                    <th>For Verif.</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-center">
                                                <?php
                                                $now = date('Y-m-d');
                                                $datetime2 = new DateTime($now);                                                

                                                $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE location IN (".$location.") AND status = 'scheduled' AND emaildate BETWEEN '2023-01-01' AND NOW()");
                                                while($row = mysqli_fetch_array($result))
                                                    {
                                                        $datetime1 = new DateTime($row['datesched']);
                                                        $difference = $datetime1->diff($datetime2);
                                                        $diff = $difference->format('%a' );
                                                        
                                                if($row['verificationdate'] == '0000-00-00'){
                                                    echo '<tr>';
                                                }else{
                                                    echo '<tr class="table-warning">';
                                                } 
                                                ?>
                                                    <?php echo '<td>'.$row['datesched'].'</td>'; ?>
                                                    <?php echo '<td>'.$row['f325number'].'</td>'; ?>
                                                    <?php echo '<td>'.$row['location'].'</td>'; ?>
                                                    <?php echo '<td class="text-danger">'.$diff.' Days</td>'; ?>
                                                    <td><center><a type="submit" name="view" class="data btn-sm btn-success" onclick="window.open('view_scheduled.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo $row['emaildate'] ?>&company=<?php echo $row['vendor'] ?>')">View</a></center></td>
                                                    <!-- <td><center><a type="submit" name="view" class="data btn-sm btn-success" onclick="window.open('view_scan.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo $row['emaildate'] ?>&company=<?php echo $row['vendor'] ?>')">View</a></center></td> -->
                                                    <?php 
                                                    if($row['verificationdate'] == '0000-00-00'){
                                                        echo '<td></td>';
                                                    }else{
                                                        echo '<td>'.$row['verificationdate'].'</td>';
                                                    }
                                                    ?>
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
                            }else{
                        ?>
                            <!-- DataTales Example -->
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                            <thead class="table-info text-dark text-center">
                                                <tr>
                                                    <th>Sched Date</th>
                                                    <th>Document No.</th>
                                                    <th>Location</th>
                                                    <th>Days Still</th>
                                                    <th>Clear F325</th>
                                                    <th>For Verif.</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-center">
                                                <?php
                                                $now = date('Y-m-d');
                                                $datetime2 = new DateTime($now);

                                                $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE location IN (".$location.") AND status = 'scheduled' AND vendor = '$vendor' AND emaildate BETWEEN '2023-01-01' AND NOW()");
                                                while($row = mysqli_fetch_array($result))
                                                    {
                                                        $datetime1 = new DateTime($row['datesched']);
                                                        $difference = $datetime1->diff($datetime2);
                                                        $diff = $difference->format('%a' );

                                                if($row['verificationdate'] == '0000-00-00'){
                                                    echo '<tr>';
                                                }else{
                                                    echo '<tr class="table-warning">';
                                                }
                                                ?>
                                                    <?php echo '<td>'.$row['datesched'].'</td>'; ?>
                                                    <?php echo '<td>'.$row['f325number'].'</td>'; ?>
                                                    <?php echo '<td>'.$row['location'].'</td>'; ?>
                                                    <?php echo '<td class="text-danger">'.$diff.' Days</td>'; ?>
                                                    <td><center><a type="submit" name="view" class="data btn-sm btn-success" onclick="window.open('view_scheduled.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo $row['emaildate'] ?>&company=<?php echo $row['vendor'] ?>')">View</a></center></td>
                                                    <!-- <td><center><a type="submit" name="view" class="data btn-sm btn-success" onclick="window.open('view_scan.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo $row['emaildate'] ?>&company=<?php echo $row['vendor'] ?>')">View</a></center></td> -->
                                                    <?php 
                                                    if($row['verificationdate'] == '0000-00-00'){
                                                        echo '<td></td>';
                                                    }else{
                                                        echo '<td>'.$row['verificationdate'].'</td>';
                                                    }
                                                    ?>
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
                    }else{
                    ?>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="table-info text-dark text-center">
                                        <tr>
                                            <th>Sched Date</th>
                                            <th>Document No.</th>
                                            <th>Location</th>
                                            <th>Days Still</th>
                                            <th>Clear F325</th>
                                            <th>For Verif.</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        <?php
                                        $now = date('Y-m-d');
                                        $datetime2 = new DateTime($now);

                                        // $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE location IN (".$location.") AND status = 'scheduled' AND emaildate BETWEEN '2023-01-01' AND NOW()");

                                        $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = 'scheduled' AND emaildate BETWEEN '2023-01-01' AND NOW()");
                                        while($row = mysqli_fetch_array($result))
                                            {
                                                $datetime1 = new DateTime($row['datesched']);
                                                $difference = $datetime1->diff($datetime2);
                                                $diff = $difference->format('%a' );

                                        if($row['verificationdate'] == '0000-00-00'){
                                            echo '<tr>';
                                        }else{
                                            echo '<tr class="table-warning">';
                                        }
                                        ?>
                                            <?php echo '<td>'.$row['datesched'].'</td>'; ?>
                                            <?php echo '<td>'.$row['f325number'].'</td>'; ?>
                                            <?php echo '<td>'.$row['location'].'</td>'; ?>
                                            <?php echo '<td class="text-danger">'.$diff.' Days</td>'; ?>
                                            <td><center><a type="submit" name="view" class="data btn-sm btn-success" onclick="window.open('view_scheduled.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo $row['emaildate'] ?>&company=<?php echo $row['vendor'] ?>')">View</a></center></td>
                                            <!-- <td><center><a type="submit" name="view" class="data btn-sm btn-success" onclick="window.open('view_scan.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo $row['emaildate'] ?>&company=<?php echo $row['vendor'] ?>')">View</a></center></td> -->
                                            <?php 
                                            if($row['verificationdate'] == '0000-00-00'){
                                                echo '<td></td>';
                                            }else{
                                                echo '<td>'.$row['verificationdate'].'</td>';
                                            }
                                            ?>
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