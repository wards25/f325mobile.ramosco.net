<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
}
$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);
?>

<?php
include_once("nav.php");
?>
<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Search F325</h1>
    </div>

<<<<<<< HEAD
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="search.php">
                <div class="form-row">
                    <div class="col-12">
                        <label>F325 Number</label>
                        <input type="number" class="form-control form-control-sm" name="f325number"
                            onKeyPress="if(this.value.length==12) return false;" required>
                    </div>
=======
                <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="POST" action="search.php">
                                <div class="form-row">
                                    <div class="col-12">
                                        <label>F325 Number</label>
                                        <input type="number" class="form-control form-control-sm" name="f325number" onKeyPress="if(this.value.length==12) return false;" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-12">
                                        <br>
                                        <select class="form-control form-control-sm" name="status" required>
                                            <option value="all">ALL</option>
                                            <option value="cleared">CLEARED</option>
                                            <option value="disposed">DISPOSED</option>
                                            <option value="open">OPEN</option>
                                            <option value="printed">PRINTED</option>
                                            <option value="scheduled">SCHEDULED</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <br>
                                        <select class="form-control form-control-sm" name="location" required>
                                            <option value="all">ALL</option>
                                            <option value="cainta">CAINTA</option>
                                            <option value="cdo">CDO</option>
                                            <option value="cebu">CEBU</option>
                                            <option value="davao">DAVAO</option>
                                            <option value="iloilo">ILOILO</option>
                                            <option value="pangasinan">PANGASINAN</option>
                                            <option value="southl uzon">SOUTH LUZON</option>
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
                            <h6 class="m-0 font-weight-bold text-primary"><?php echo ucfirst($_POST['status']); ?> F325 List | F325number: <?php echo $_POST['f325number']; ?> / Loc: <?php echo strtoupper($_POST['location']);?></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="table-info text-dark text-center">
                                        <tr>
                                            <th>Email Date</th>
                                            <th>Document No.</th>
                                            <th>View</th>
                                            <th>Bypass</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        <?php
                                        $f325_number = $_POST['f325number'];
                                        $status = $_POST['status'];
                                        $location = $_POST['location'];

                                        if($status == 'all'){

                                            if($location == 'all'){
                                                $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE f325number = '$f325_number'");
                                            }else{
                                                $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE location = '$location' AND f325number = '$f325_number'");
                                            }

                                        }else{

                                            if($location == 'all'){
                                                $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE status = '$status' AND f325number = '$f325_number'");
                                            }else{
                                                $result = mysqli_query($conn,"SELECT * FROM dbf325number WHERE location = '$location' AND status = '$status' AND f325number = '$f325_number'");
                                            }

                                        }
                                        
                                        while($row = mysqli_fetch_array($result))
                                            {
                                        ?>
                                        <tr>
                                            <?php echo '<td>'.$row['emaildate'].'</td>'; ?>
                                            <?php echo '<td>'.$row['f325number'].'</td>'; ?>
                                            <td><center><a type="submit" name="view" class="data btn-sm btn-primary" onclick="window.open('view_f325.php?f325number=<?php echo $row['f325number'] ?>')">View</a></center></td>
                                            <?php
                                            if($row['status'] == 'OPEN' || $row['status'] == 'SCHEDULED' || $row['status'] == 'PRINTED'){
                                            ?>
                                            <td><center><a type="submit" name="bypass" class="data btn-sm btn-success" onclick="window.open('view_scheduled.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo urlencode($row['emaildate']); ?>&company=<?php echo urlencode($row['vendor']); ?>')">Bypass</a></center></td>
                                            <?php
                                            }else{
                                                ?>
                                            <td><center><span class="badge bg-success">Cleared</span></center></td>
                                            <?php
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

>>>>>>> 44ac350420260f5310e58126a5ef01461f12204f
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <br>
                        <select class="form-control form-control-sm" name="status" required>
                            <option value="all">ALL</option>
                            <option value="cleared">CLEARED</option>
                            <option value="disposed">DISPOSED</option>
                            <option value="open">OPEN</option>
                            <option value="printed">PRINTED</option>
                            <option value="scheduled">SCHEDULED</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <br>
                        <select class="form-control form-control-sm" name="location" required>
                            <option value="all">ALL</option>
                            <option value="cainta">CAINTA</option>
                            <option value="cdo">CDO</option>
                            <option value="cebu">CEBU</option>
                            <option value="davao">DAVAO</option>
                            <option value="iloilo">ILOILO</option>
                            <option value="pangasinan">PANGASINAN</option>
                            <option value="southl uzon">SOUTH LUZON</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <br>
                        <button type="submit" class="form-control btn btn-sm btn-success" name="view"><i
                                class="fa fa-search"></i> Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    if (isset($_POST['view'])) {
        ?>
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?php echo ucfirst($_POST['status']); ?> F325 List |
                    F325number: <?php echo $_POST['f325number']; ?> / Loc: <?php echo strtoupper($_POST['location']); ?>
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead class="table-info text-dark text-center">
                            <tr>
                                <th>Email Date</th>
                                <th>Document No.</th>
                                <th>View</th>
                                <th>Bypass</th>
                                <th>Re-print</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php
                            $f325_number = $_POST['f325number'];
                            $status = $_POST['status'];
                            $location = $_POST['location'];

                            if ($status == 'all') {

                                if ($location == 'all') {
                                    $result = mysqli_query($conn, "SELECT * FROM dbf325number WHERE f325number = '$f325_number'");
                                } else {
                                    $result = mysqli_query($conn, "SELECT * FROM dbf325number WHERE location = '$location' AND f325number = '$f325_number'");
                                }

                            } else {

                                if ($location == 'all') {
                                    $result = mysqli_query($conn, "SELECT * FROM dbf325number WHERE status = '$status' AND f325number = '$f325_number'");
                                } else {
                                    $result = mysqli_query($conn, "SELECT * FROM dbf325number WHERE location = '$location' AND status = '$status' AND f325number = '$f325_number'");
                                }

                            }

                            while ($row = mysqli_fetch_array($result)) {
                                ?>
                                <tr>
                                    <?php echo '<td>' . $row['emaildate'] . '</td>'; ?>
                                    <?php echo '<td>' . $row['f325number'] . '</td>'; ?>
                                    <td>
                                        <center><a type="submit" name="view" class="data btn-sm btn-primary"
                                                onclick="window.open('view_f325.php?f325number=<?php echo $row['f325number'] ?>')">View</a>
                                        </center>
                                    </td>
                                    <?php
                                    if (
                                        $_SESSION['clearing'] == '1' &&
                                        ($row['status'] == 'OPEN' || $row['status'] == 'SCHEDULED' || $row['status'] == 'PRINTED')
                                    ) {
                                        ?>
                                        <td>
                                            <center><a type="submit" name="bypass" class="data btn-sm btn-success"
                                                    onclick="window.open('view_scheduled.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo urlencode($row['emaildate']); ?>&company=<?php echo urlencode($row['vendor']); ?>')">Bypass</a>
                                            </center>
                                        </td>
                                        <?php
                                    } else {
                                        ?>
                                        <td>
                                            <center><span class="badge bg-success">Cleared</span></center>
                                        </td>
                                        <?php
                                    }
                                    ?>
                                    <td>
                                        <center><a type="button" class="data btn-sm btn-warning"
                                                onclick="window.open('print-notepad-details.php?f325number=<?php echo $row['f325number'] ?>&action=RE-PRINT')">Re-print</a>
                                        </center>
                                    </td>
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

<<<<<<< HEAD

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
=======
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
>>>>>>> 44ac350420260f5310e58126a5ef01461f12204f
</body>

</html>