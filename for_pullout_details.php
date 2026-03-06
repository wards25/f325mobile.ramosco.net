<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

include_once("nav.php");

// Get parameters safely
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$company = isset($_GET['company']) ? mysqli_real_escape_string($conn, $_GET['company']) : '';
$vendorcode = isset($_GET['vc']) ? mysqli_real_escape_string($conn, $_GET['vc']) : '';
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            Pullout Details
        </h1>
        <a href="for_pullout.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                Company: <b><?php echo htmlspecialchars($company); ?></b> |
                Principal: <b><?php echo htmlspecialchars($category); ?></b>
            </h6>
        </div>
        <div class="card-body">
            <form id="create-batch-pullout" method="POST" action="create_batch.php">
                <input type="hidden" name="create_batch" value="1">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#confirmBatchModal">
                    <i class="fas fa-plus-circle"></i> Create Batch
                </button>
                <button id="toggleSelect" type="button" class="btn btn-success">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead class="table-info text-dark text-center">
                            <tr>
                                <th class="text-center">f325number</th>
                                <th class="text-center">Mdccode</th>
                                <th class="text-center">Location</th>
                                <th class="text-center">Received Quantity</th>
                                <th class="text-center">Pullout Quantity</th>
                                <th class="text-center">Total</th>
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
                                r.location,
                                r.rcvdqty,
                                r.forpullout,
                                r.unitcost * r.forpullout AS total_cost,
                                r.costextended,
                                p.description
                            FROM dbraw r
                            INNER JOIN dbcompany c
                                ON r.vendorcode = c.vendorcode
                            INNER JOIN dbproduct p 
                                ON r.mdccode = p.mdccode
                                AND r.vendorcode = p.vendor
                            WHERE 
                                r.forpullout >= 1
                                AND r.batchnumber_forpullout = ''
                                AND r.statusout = 'CLEARED'
                                AND r.category = '$category'
                                AND c.nickname = '$company'
                                AND p.vendor = '$vendorcode'
                                AND r.location IN ($location_filter)

                        ";

                            $result = mysqli_query($conn, $sql);

                            if (!$result) {
                                die("SQL Error: " . mysqli_error($conn));
                            }

                            $grandTotal = 0;

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $amount = $row['costextended'];
                                    $grandTotal += $amount;

                                    echo "
                                    <tr>
                                        <td>{$row['f325number']}</td>
                                        <td>{$row['mdccode']} - {$row['description']}</td>
                                        <td>{$row['location']}</td>
                                        <td>{$row['rcvdqty']}</td>
                                        <td>{$row['forpullout']}</td>
                                        <td>₱" . number_format($row['total_cost'], 2) . "</td>
                                        <td>
                                            <input type='checkbox' 
                                            class='form-check-input row-checkbox big-checkbox' 
                                            name='items[]' 
                                             value='{$row['f325number']}|{$row['mdccode']}'>
                                        </td>
                                    </tr>
                                    ";
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

<!-- /.container-fluid -->

<?php include_once("footer.php"); ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const toggleBtn = document.getElementById("toggleSelect");
        const checkboxes = document.querySelectorAll(".row-checkbox");
        const confirmBtn = document.getElementById("confirmCreateBatch");
        const form = document.getElementById("create-batch-pullout");

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