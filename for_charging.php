<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

$username = $_SESSION['fname'] ?? '';

// Clear previous list
mysqli_query($conn, "DELETE FROM cleared_list WHERE user = '$username'");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);

include_once("nav.php");
?>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">For Charging</h1>
    </div>

    <!-- Alert Message -->
    <script>
        window.setTimeout(function () {
            $(".alert").fadeTo(500, 0).slideUp(500, function () {
                $(this).remove();
            });
        }, 2000);
    </script>

    <?php
    if (!empty($_GET['status'])) {
        switch ($_GET['status']) {
            case 'succ':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> Created a Batch successfully.';
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

    <?php if (!empty($statusMsg)) { ?>
        <div class="alert <?= $statusType ?> alert-dismissable fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <?= $statusMsg ?>
        </div>
    <?php } ?>

    <!-- Table Card -->
    <div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <form method="POST" action="create_batch_forcharge.php" id="create-batch-forcharging">
                        <div class="d-flex mb-3">
                            <input type="hidden" name="create_batch" value="1">
                            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal"
                                data-bs-target="#confirmBatchModal">
                                <i class="fas fa-plus-circle"></i> Create Batch
                            </button>
                            <button type="button" id="toggleSelect" class="btn btn-success">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="dataTable" width="100%"
                                cellspacing="0">
                                <thead class="table-info text-dark text-center">
                                    <tr>
                                        <th class="text-center">f325number</th>
                                        <th class="text-center">Mdccode</th>
                                        <th class="text-center">For Charge Quantity</th>
                                        <th class="text-center">Amount</th>
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

                                    $sql = "
                                        SELECT 
                                            r.f325number,
                                            r.mdccode,
                                            r.forcharging,
                                            r.unitcost,
                                            r.costextended
                                        FROM dbraw r
                                        INNER JOIN dbcompany c
                                            ON r.vendorcode = c.vendorcode
                                        WHERE 
                                            r.forcharging >= 1  
                                        AND batchnumber_forcharging = ''
                                        AND r.location IN ($location_filter)
                                    ";

                                    $result = mysqli_query($conn, $sql);
                                    if (!$result) {
                                        die("SQL Error: " . mysqli_error($conn));
                                    }

                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>
                                                <td>{$row['f325number']}</td>
                                                <td>{$row['mdccode']}</td>
                                                <td>{$row['forcharging']}</td>
                                                <td>₱" . number_format($row['unitcost'], 2) . "</td>
                                                <td>
                                                    <input type='checkbox' class='form-check-input row-checkbox big-checkbox' 
                                                        name='items[]' value='{$row['f325number']}|{$row['mdccode']}'>
                                                </td>
                                            </tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- Confirmation Modal -->
<div class="modal fade" id="confirmBatchModal" tabindex="-1" aria-labelledby="confirmBatchLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmBatchLabel">Confirm Batch Creation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to create this batch?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="confirmCreateBatch" type="button" class="btn btn-primary">Yes, Create</button>
            </div>
        </div>
    </div>
</div>
<?php include_once("footer.php"); ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggleBtn = document.getElementById("toggleSelect");
        const checkboxes = document.querySelectorAll(".row-checkbox");
        const confirmBtn = document.getElementById("confirmCreateBatch");
        const form = document.getElementById("create-batch-forcharging");

        confirmBtn.addEventListener("click", function () {
            form.submit();
        });

        let allSelected = false;

        toggleBtn.addEventListener("click", function () {
            allSelected = !allSelected;

            checkboxes.forEach(cb => cb.checked = allSelected);

            toggleBtn.innerHTML = allSelected ?
                '<i class="fas fa-times-square"></i> Unselect All' :
                '<i class="fas fa-check-square"></i> Select All';
        });
    });
</script>