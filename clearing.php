<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

$username = $_SESSION['fname'];

// Delete cleared_list in DB
mysqli_query($conn, "DELETE FROM cleared_list WHERE user = '$username'");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);
?>

<?php include_once("nav.php"); ?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Clearing</h1>
    </div>

    <script>
        window.setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function() {
                $(this).remove();
            });
        }, 2000);
    </script>

    <?php
    if (!empty($_GET['status'])) {
        switch ($_GET['status']) {
            case 'succ':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i> <b>Success!</b> F325 cleared successfully.';
                break;
            case 'reopen':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i> <b>Success!</b> F325 re-opened successfully.';
                break;
            case 'verify':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i> <b>Success!</b> F325 for verification.';
                break;
            case 'dispose':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i> <b>Success!</b> F325 disposed successfully.';
                break;
            case 'err':
                $statusType = 'alert-danger';
                $statusMsg = '<i class="fa fa-exclamation-triangle"></i> <b>Error!</b> No data encoded.';
                break;
            default:
                $statusType = '';
                $statusMsg = '';
        }
    }
    ?>

    <?php if (!empty($statusMsg)) { ?>
        <div class="alert <?php echo $statusType; ?> alert-dismissable fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $statusMsg; ?>
        </div>
    <?php } ?>

    <div class="row">

        <div class="col-xl-12 col-md-12 mb-4">

            <div class="card border-left-warning shadow h-100 py-2">

                <div class="card-body">

                    <div class="row no-gutters align-items-center">

                        <div class="col ml-4 mr-2">

                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total F325 For Clear Status
                            </div>

                            <div class="h5 mb-0 font-weight-bold text-gray-800">

                                <?php
                                $scheduled_query = mysqli_query(
                                    $conn,
                                    "SELECT * FROM dbf325number
                                     WHERE status = 'SCHEDULED'
                                     AND emaildate BETWEEN '2024-01-01' AND NOW()"
                                );
                                $scheduled_count = mysqli_num_rows($scheduled_query);
                                echo number_format($scheduled_count);
                                ?>

                            </div>

                            <small>
                                as of <?php echo date("h:i A"); ?>
                                | <a href="cleared.php">F325 Cleared</a>
                            </small>

                        </div>

                        <div class="col-auto mr-4">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="POST" action="clearing.php">

        <div class="form-row">

            <!-- Company Select -->
            <div class="col-5">
                <?php
                $query = "SELECT * FROM dbcompany WHERE active='1' ORDER BY name ASC";
                $result = $conn->query($query);
                if ($result->num_rows > 0) {
                    $options = mysqli_fetch_all($result, MYSQLI_ASSOC);
                ?>
                    <select class="form-select form-control-sm" name="company" required>
                        <option value="all">ALL COMPANIES</option>
                        <?php
                        foreach ($options as $option) {
                            echo '<option value="' . $option['vendorcode'] . '">' . $option['name'] . '</option>';
                        }
                        ?>
                    </select>
                <?php } ?>
            </div>

            <!-- Branch Select with branchname from dbcensus -->
            <div class="col-4">
                        <?php
                        $query = "SELECT * FROM dbcensus";
                        $result = $conn->query($query);
                        if ($result->num_rows > 0) {
                            $options = mysqli_fetch_all($result, MYSQLI_ASSOC); ?>

                            <select class="form-control form-control-sm branchcode" name="brcode" id="search_code" required>
                                <option value="all">ALL BRANCHES</option>
                                <?php
                                foreach ($options as $option) {
                                    ?>
                                    <option value="<?php echo $option['code']; ?>">
                                        <?php echo $option['code'] . ' - ' . $option['branchname']; ?> </option>
                                <?php
                                }
                        }
                        ?>
                        </select>
                    </div>

            <!-- Filter Button -->
            <div class="col-3">
                <button type="submit" name="view" class="btn form-control form-control-sm btn-sm" style="background:#915c83;color:white">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>

        </div>

        <br>

    </form>

    <div class="card shadow mb-4">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-bordered" id="dataTable">

                    <thead class="table-info text-dark text-center">
                        <tr>
                            <th>Sched Date</th>
                            <th>Document No.</th>
                            <th>Company</th>
                            <th>Branch</th>
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

                        // Build query based on filter
                        $where = "f.status='scheduled' AND f.emaildate BETWEEN '2023-01-01' AND NOW()";

                        if (isset($_POST['view'])) {
                            $vendor = $_POST['company'];
                            $brcode = $_POST['brcode'];

                            if ($vendor != 'all') {
                                $where .= " AND f.vendor='$vendor'";
                            }

                            if ($brcode != 'all') {
                                $where .= " AND f.brcode='$brcode'";
                            }
                        }

                        // Updated query with INNER JOIN to dbcensus to get branchname
                        $query = "
                            SELECT f.*, c.name AS company_name, b.branchname
                            FROM dbf325number f
                            LEFT JOIN dbcompany c ON f.vendor = c.vendorcode
                            LEFT JOIN dbcensus b ON f.brcode = b.code
                            WHERE $where
                        ";

                        $result = mysqli_query($conn, $query);
                        if (!$result) {
                            echo "SQL Error: " . mysqli_error($conn);
                        }

                        while ($row = mysqli_fetch_array($result)) {
                            $datetime1 = new DateTime($row['datesched']);
                            $difference = $datetime1->diff($datetime2);
                            $diff = $difference->format('%a');

                            $row_class = ($row['verificationdate'] == NULL || $row['verificationdate'] == '0000-00-00') ? '' : 'table-warning';
                            echo '<tr class="' . $row_class . '">';
                        ?>

                            <td><?php echo $row['datesched']; ?></td>
                            <td><?php echo $row['f325number']; ?></td>
                            <td><?php echo $row['company_name']; ?></td>
                            <td><?php echo $row['branchname']; ?></td>
                            <td><?php echo $row['location']; ?></td>
                            <td class="text-danger"><?php echo $diff; ?> Days</td>
                            <td>
                                <a class="btn btn-sm btn-success"
                                   onclick="window.open('view_scheduled.php?f325number=<?php echo $row['f325number'] ?>&emaildate=<?php echo $row['emaildate'] ?>&company=<?php echo $row['vendor'] ?>')">
                                    View
                                </a>
                            </td>
                            <td>
                                <?php
                                echo ($row['verificationdate'] == '0000-00-00') ? '' : $row['verificationdate'];
                                ?>
                            </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>
        </div>
    </div>

</div>

<?php include_once("footer.php"); ?>

</body>
</html>
<script>
$(document).ready(function() {
    $('#search_code').select2({
        theme: "bootstrap"
    });
});
</script>