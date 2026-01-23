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
            <form method="POST" action="create_batch.php">
                <button type="submit" name="create_batch" class="btn btn-primary">
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
                                <th class="text-center">Received Quantity</th>
                                <th class="text-center">Pullout Quantity</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php
                            $sql = "
                            SELECT 
                                r.f325number,
                                r.mdccode,
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
                            WHERE 
                                r.forpullout >= 1
                                AND batchnumber = ''
                                AND r.category = '$category'
                                AND c.nickname = '$company'
                                AND p.vendor = '$vendorcode'
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
<!-- /.container-fluid -->

<?php include_once("footer.php"); ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.getElementById("toggleSelect");
        const checkboxes = document.querySelectorAll(".row-checkbox");

        let allSelected = false;

        toggleBtn.addEventListener("click", function() {
            allSelected = !allSelected;

            checkboxes.forEach(cb => cb.checked = allSelected);

            toggleBtn.innerHTML = allSelected ?
                '<i class="fas fa-times-square"></i> Unselect All' :
                '<i class="fas fa-check-square"></i> Select All';
        });
    });
</script>