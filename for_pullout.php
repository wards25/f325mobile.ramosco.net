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
        <h1 class="h3 mb-0 text-gray-800">Import Pull Out</h1>
    </div>

    <script>
        window.setTimeout(function () {
            $(".alert").fadeTo(500, 0).slideUp(500, function () {
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
        <div class="col-xl-12 col-md-12 mb-4">
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
        </div>
        <!-- DataTables Example -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">

                        <!-- TABLE HEADER -->
                        <thead class="table-info text-dark text-center">
                            <tr>
                                <th>MDCCODE</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Vendor</th>
                                <th>Total Cost</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php
                            $query = "
                        SELECT 
                            mdccode,
                            category,
                            quantity,
                            vendorcode,
                            costextended
                        FROM dbraw
                        WHERE forpullout = '1'
                        ORDER BY datecleared ASC
                    ";

                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "
                                <td>{$row['mdccode']}</td>
                                <td>{$row['category']}</td>
                                <td>{$row['quantity']}</td>
                                <td>{$row['vendorcode']}</td>
                                <td>₱" . number_format($row['costextended'], 2) . "</td>
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