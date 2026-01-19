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
        <div class="d-flex align-items-center">
            <span class="h4 mb-0 text-gray-800 me-2">
                Pull-Out Summary -
            </span>
            <span class="h2 mb-0 fw-bold text-gray-800">
                <?= htmlspecialchars($batchnumber) ?>
            </span>
        </div>



        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-success btn-md" onclick="printBatch()">
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
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label"><strong>Principal Name:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($principal) ?>"
                                readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label"><strong>Company:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($company) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><strong>Prepared By:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($preparedBy) ?>"
                                readonly>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label"><strong>Reference #:</strong></label>
                            <input type="text" id="ref-number" class="form-control" value="<?= htmlspecialchars($referenceNo) ?>"
                                readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label"><strong>Date Processed:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($dateProcessed) ?>"
                                readonly>
                        </div>
                    </div>
                </div>

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
                                r.costextended,
                                r.mdccode
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
                                    <td>{$row['mdccode']} - {$row['description']}</td>
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
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <div class="row mb-2 align-items-center">
                            <label class="col-6 text-end fw-bold">Subtotal:</label>
                            <div class="col-6">
                                <input type="text" class="form-control text-end"
                                    value="<?= number_format($subtotal, 2) ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-2 align-items-center">
                            <label class="col-6 text-end fw-bold">Total Quantity:</label>
                            <div class="col-6">
                                <input type="text" class="form-control text-end"
                                    value="<?= number_format($totalQty, 2) ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<?php include_once("footer.php"); ?>
<script>
    function printBatch() {
        const batchnumber = "<?= htmlspecialchars($batchnumber) ?>";
        const referenceNo = $("#ref-number");
        window.location.href =
            "print_batch_details.php?batchnumber=" +
            encodeURIComponent(batchnumber) + 
            "&referenceNum=" + encodeURIComponent(referenceNo);
    }
</script>