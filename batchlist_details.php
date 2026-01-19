<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['batchnumber'])) {
    echo "<div class='alert alert-danger'>Batch number not specified.</div>";
    exit();
}

$batchnumber = mysqli_real_escape_string($conn, $_GET['batchnumber']);

include_once("nav.php");
?>

<div class="container-fluid">

    <div
        class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-4 gap-3">
        <h1 class="h3 mb-0 text-gray-800">
            Pull-Out Summary – <?= htmlspecialchars($batchnumber) ?>
        </h1>

        <div class="d-flex align-items-center gap-2">
            <input type="date" id="date-processed" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>"
                style="max-width: 160px;">

            <button class="btn btn-outline-success btn-sm" onclick="printBatch()">
                <i class="bi bi-printer me-1"></i> Print
            </button>

            <!-- <button class="btn btn-primary btn-sm">
                <i class="bi bi-box-arrow-up me-1"></i> Pull Out
            </button> -->
        </div>
    </div>



    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <?php
                $preparedBy = $_SESSION['fname'] ?? '';
                $referenceNo = date('Y') . '-' . rand(1000, 9999);
                $dateProcessed = $_GET['date_processed'] ?? date('Y-m-d');
                $headerQuery = "
                    SELECT 
                        r.category AS principal,
                        c.name
                    FROM dbraw r
                    LEFT JOIN dbcompany c ON r.vendorcode = c.vendorcode
                    WHERE r.batchnumber = '$batchnumber'
                    LIMIT 1
                ";
                $headerResult = mysqli_query($conn, $headerQuery);
                $header = mysqli_fetch_assoc($headerResult);

                $principal = $header['principal'] ?? '';
                $company = $header['name'] ?? '';
                ?>
                <table class="header-table">
                    <tr>
                        <td class="info-left">
                            <p><strong>Principal Name:</strong> <?= htmlspecialchars($principal) ?></p>
                            <p><strong>Company:</strong> <?= htmlspecialchars($company) ?></p>
                            <p><strong>Prepared By:</strong> <?= htmlspecialchars($preparedBy) ?></p>
                        </td>
                        <td class="info-right">
                            <p><strong>Reference #:</strong> <?= $referenceNo ?></p>
                            <p><strong>Date Processed:</strong> <?= $dateProcessed ?></p>
                        </td>
                    </tr>
                </table>
                <table class="table table-bordered table-sm" width="100%">
                    <thead class="table-info text-dark text-center">
                        <tr>
                            <th>Branch Name</th>
                            <th>F325 Number</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>UoM</th>
                            <th>Cost Extended</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        $totalQty = 0;
                        $subtotal = 0;

                        $query = "
                            SELECT 
                                c.branchname,
                                c.franchise,
                                c.code,
                                r.f325number,
                                p.description,
                                r.quantity,
                                p.uom,
                                r.costextended
                            FROM dbraw r

                            LEFT JOIN (
                                SELECT f325number, brcode
                                FROM dbf325number
                                GROUP BY f325number
                            ) f ON r.f325number = f.f325number

                            LEFT JOIN (
                                SELECT code, franchise, branchname
                                FROM dbcensus
                                GROUP BY code
                            ) c ON f.brcode = c.code

                            LEFT JOIN (
                                SELECT mdccode, description, uom
                                FROM dbproduct
                                GROUP BY mdccode
                            ) p ON r.mdccode = p.mdccode

                            WHERE r.batchnumber = '$batchnumber';
                            ";


                        $result = mysqli_query($conn, $query);
                        if (!$result) {
                            die("Query failed: " . mysqli_error($conn));
                        }
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {

                                $totalQty += (float) $row['quantity'];
                                $subtotal += (float) $row['costextended'];

                                echo "
                                <tr>
                                
                                    <td>{$row['franchise']} {$row['code']} - {$row['branchname']}</td>
                                    <td class='text-center'>{$row['f325number']}</td>
                                    <td>{$row['description']}</td>
                                    <td class='text-end'>{$row['quantity']}</td>
                                    <td class='text-center'>{$row['uom']}</td>
                                    <td class='text-end'>" . number_format($row['costextended'], 2) . "</td>
                                </tr>";
                            }
                        } else {
                            echo "
                            <tr>
                                <td colspan='6' class='text-center text-muted'>
                                    No records found for this batch.
                                </td>
                            </tr>";
                        }
                        ?>

                    </tbody>
                </table>
            </div>

            <hr>

            <!-- SUMMARY -->
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <table class="table table-borderless">
                        <tr>
                            <th class="text-end">Subtotal:</th>
                            <td class="text-end"><?= number_format($subtotal, 2) ?></td>
                        </tr>
                        <tr>
                            <th class="text-end">Total Quantity:</th>
                            <td class="text-end"><?= number_format($totalQty, 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include_once("footer.php"); ?>
<script>
    function printBatch() {
        const batchnumber = "<?= htmlspecialchars($batchnumber) ?>";
        const dateProcessed = document.getElementById("date-processed").value;

        window.location.href =
            "print_batch_details.php?batchnumber=" +
            encodeURIComponent(batchnumber) +
            "&date_processed=" +
            encodeURIComponent(dateProcessed);
    }
</script>