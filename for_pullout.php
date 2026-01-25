<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

// delete prdlist in db 
mysqli_query($conn, "DELETE FROM cleared_list WHERE user = '$username'");

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
        <h1 class="h3 mb-0 text-gray-800">For Pull Out</h1>
    </div>

    <script>
        window.setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function() {
                $(this).remove();
            });
        }, 2000);
    </script>

    <?php
    // Get status message
    if (!empty($_GET['status'])) {
        switch ($_GET['status']) {
            case 'succ':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> Import Pullout successfully.';
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
    <?php if (!empty($statusMsg)) { ?>
        <div class="alert <?php echo $statusType; ?> alert-dismissable fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <?php echo $statusMsg; ?>
        </div>
    <?php } ?>



    <!-- Content Row -->
    <div class="row">

        <!-- Earnings (Total) Card Example -->
        <!-- <div class="col-xl-12 col-md-12 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Amount </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $scheduled_query = mysqli_query($conn, "SELECT SUM(costextended) AS total_cost FROM dbraw WHERE forpullout = '1'");

                                $row = mysqli_fetch_assoc($scheduled_query);
                                $total_cost = $row['total_cost'] ?? 0;

                                echo "₱" . number_format($total_cost, 2);
                                ?>
                            </div>
                            <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> </small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- <div class="col-xl-12 col-md-12 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Select CSV file</div>
                            <div>
                                <form action="import_pullout.php" method="POST" enctype="multipart/form-data">
                                    <div class="input-group mb-3">
                                        <input type="file" name="csv_file" class="form-control" required>
                                        <button class="btn btn-primary btn-sm" type="submit" name="upload">
                                            <i class="fas fa-upload"></i> Upload
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <!-- DataTables Example -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">

                        <thead class="table-info text-dark text-center">
                            <tr>
                                <th class="text-center">Company</th>
                                <th class="text-center">Principal</th>
                                <th class="text-center">Pullout Quantity</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php
                            $query = "
                                SELECT 
                                    c.nickname AS company,
                                    r.category,
                                    r.vendorcode,
                                    SUM(r.forpullout) AS total_qty,
                                    SUM(r.unitcost * r.forpullout) AS total_cost
                                FROM dbraw r
                                INNER JOIN dbcompany c
                                    ON r.vendorcode = c.vendorcode
                                WHERE r.forpullout >= 1 AND batchnumber = ''
                                GROUP BY c.nickname, r.category
                                ORDER BY r.category ASC
                            ";


                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>
                                <td>{$row['company']}</td>
                                <td>{$row['category']}</td>
                                <td>{$row['total_qty']}</td>
                                <td>₱" . number_format($row['total_cost'], 2) . "</td>
                                <td>
                                    <a href='for_pullout_details.php?category=" . urlencode($row['category']) . "&company=" . urlencode($row['company']) . "&vc=" . urlencode($row['vendorcode']) . "' 
                                    class='btn btn-primary btn-sm'>
                                    View
                                    </a>
                                </td>
                            </tr>";
                            }

                            ?>
                        </tbody>

                    </table>
                </div>
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
</body>

</html>