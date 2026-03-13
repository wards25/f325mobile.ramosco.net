<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);

include_once("nav.php");
?>

<!-- Begin Page Content -->
<div class="container-fluid">
    <?php
    // Get status message
    if (!empty($_GET['status'])) {
        switch ($_GET['status']) {
            case 'succ':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> Upload Attachment successfully.';
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
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Batch Paid</h1>
    </div>
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
                            $scheduled_query = mysqli_query($conn, "SELECT SUM(unitcost * forpullout) AS total_cost FROM dbraw WHERE forpullout >=1 AND batchnumber_forcharging <> '' AND statusout = 'PAID'");

                            $row = mysqli_fetch_assoc($scheduled_query);
                            $total_cost = $row['total_cost'] ?? 0;

                            echo "₱" . number_format($total_cost, 2);
                            ?>
                        </div>
                        <small class="mb-0 text-gray-800">as of <?php echo date("h:i A"); ?> | <a href="pulledout.php" class="text-decoration-none">BATCH Pulled Out</a></small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                            <th class="text-center">Batch Number</th>
                            <th class="text-center">Principal</th>
                            <th class="text-center">Amount</th>
                            <th class="text-center">Date Paid</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">

                        <?php
                        $allowed_locations = [];

                        $query = "SELECT id, location FROM dblocation WHERE active = 1 ORDER BY location ASC";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            $loc_id = $row['id'];

                            if (!empty($_SESSION['loc' . $loc_id]) && $_SESSION['loc' . $loc_id] == 1) {
                                $allowed_locations[] = "'" . mysqli_real_escape_string($conn, $row['location']) . "'";
                            }
                        }
                        $location_filter = implode(",", $allowed_locations);

                        $query = "
                                SELECT 
                                    r.batchnumber_forcharging AS batchnumber,
                                    r.category,
                                    p.charge_date,
                                    SUM(r.unitcost * r.forcharging) AS total_cost
                                FROM dbraw r
                                INNER JOIN dbpullout p 
                                    ON r.batchnumber_forcharging = p.reference
                                WHERE r.batchnumber_forcharging IS NOT NULL 
                                    AND r.forcharging >= 1
                                    AND r.batchnumber_forcharging <> '' 
                                    AND r.statusout = 'PAID'
                                    AND r.location IN ($location_filter)
                                GROUP BY r.batchnumber_forcharging, r.category, p.charge_date
                                ORDER BY r.batchnumber_forcharging ASC
                            ";
                        $result = mysqli_query($conn, $query);
                        if(!$result){
                            die("Error executing query: " . mysqli_error($conn));
                        }
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                <td>{$row['batchnumber']}</td>
                                <td>{$row['category']}</td>
                                <td>{$row['total_cost']}</td>
                                <td>{$row['charge_date']}</td>
                               <td>
                                <a href='charged_batchlist_details.php?batchnumber={$row['batchnumber']}'
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

<?php
include_once("footer.php");
?>

<script>
    window.setTimeout(function() {
        $(".alert").fadeTo(500, 0).slideUp(500, function() {
            $(this).remove();
        });
    }, 2000);
</script>