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
<style>
    .preview-wrapper {
        position: relative;
        display: inline-block;
        margin: 6px;
    }

    .preview-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .remove-btn {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #dc3545;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        font-size: 14px;
        cursor: pointer;
        line-height: 18px;
    }
</style>
<div class="container-fluid">

    <div
        class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-4 gap-3">
        <div class="d-flex align-items-center">
            <span class="h4 mb-0 text-gray-800 me-2">
                Paid Summary -
            </span>
            <span class="h2 mb-0 fw-bold text-gray-800">
                <?= htmlspecialchars($batchnumber) ?>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- History Button -->
            <button class="btn btn-info btn-md" data-bs-toggle="modal" data-bs-target="#historyModal">
                <i class="bi bi-clock-history me-1"></i> View History
            </button>

        </div>



        <!-- <div class="d-flex align-items-center gap-2">
            <button class="btn btn-success btn-md" onclick="printBatch('pullout')">
                <i class="bi bi-printer me-1"></i> Print Pull-Out QTY
            </button>

            <button class="btn btn-success btn-md" onclick="printBatch('total')">
                <i class="bi bi-printer me-1"></i> Print Total QTY
            </button>
        </div> -->
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <?php
                // Fetch all columns from dbpullout for the given batchnumber
                $query = "SELECT * FROM dbpullout WHERE reference = '$batchnumber' LIMIT 1";
                $result = mysqli_query($conn, $query);
                if (!$result) {
                    die("Query failed: " . mysqli_error($conn));
                }

                // Fetch associative array of all fields
                $row = mysqli_fetch_assoc($result);

                // Assign variables from fetched row, with fallback to empty string
                $preparedby = $row['preparedby'] ?? $preparedBy;
                $dateProcessed = $row['dateprocessed'] ?? date('Y-m-d');
                $reference = $row['reference'] ?? '';
                $principal = $row['principal'] ?? '';
                $vendorcode = $row['company'] ?? '';
                $preparedByValue = $row['preparedby'] ?? $preparedBy;
                $dateProcessedValue = $row['dateprocessed'] ?? $dateProcessed;
                $location = $row['location'] ?? '';

                // Fetch company data based on vendorcode
                $queryCompany = "SELECT * FROM dbcompany WHERE vendorcode = '$vendorcode' LIMIT 1";
                $resultCompany = mysqli_query($conn, $queryCompany);

                if (!$resultCompany) {
                    die("Query failed: " . mysqli_error($conn));
                }
                $companyRow = mysqli_fetch_assoc($resultCompany);
                $company = $companyRow['name'] ?? '';
                ?>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label"><strong>Reference #:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($reference) ?>"
                                readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label"><strong>Principal Name:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($principal) ?>"
                                readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong>Prepared By:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($preparedByValue) ?>"
                                readonly>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label class="form-label"><strong>Date Processed:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($dateProcessedValue) ?>"
                                readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label"><strong>Company:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($company) ?>" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label"><strong>Location:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($location) ?>" readonly>
                        </div>

                    </div>
                </div>

                <table class="table table-bordered table-sm" width="100%">
                    <thead class="table-info text-dark text-center">
                        <tr>
                            <th>Branch Name</th>
                            <th>F325 Number</th>
                            <th>Description</th>
                            <th>Pullout Qty</th>
                            <th>UoM</th>
                            <th>Cost Extended</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        $totalQty = 0;
                        $subtotal = 0;
                        $cost_extended = 0;

                        $query = "
                            SELECT DISTINCT
                                c.branchname,
                                c.franchise,
                                c.code,
                                r.f325number,
                                CONCAT(r.mdccode, ' - ', p.description) AS description,
                                r.forcharging,
                                p.uom,
                                r.costextended,
                                r.mdccode,
                                r.unitcost
                            FROM dbraw r

                            -- Join f325number, pick one row per f325number
                            LEFT JOIN (
                                SELECT f325number, MAX(brcode) AS brcode
                                FROM dbf325number
                                GROUP BY f325number
                            ) f ON r.f325number = f.f325number

                            -- Join branch info, aggregate to satisfy ONLY_FULL_GROUP_BY
                            LEFT JOIN (
                                SELECT 
                                    code, 
                                    MAX(franchise) AS franchise, 
                                    MAX(branchname) AS branchname
                                FROM dbcensus
                                GROUP BY code
                            ) c ON f.brcode = c.code

                            -- Join product info, aggregate description and uom
                            LEFT JOIN (
                                SELECT mdccode, MAX(description) AS description, MAX(uom) AS uom
                                FROM dbproduct
                                GROUP BY mdccode
                            ) p ON r.mdccode = p.mdccode

                            WHERE r.batchnumber_forcharging = '$batchnumber';
                            ";


                        $result = mysqli_query($conn, $query);
                        if (!$result) {
                            die("Query failed: " . mysqli_error($conn));
                        }
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {

                                $totalQty += (float) $row['forcharging'];
                                $cost_extended = (float) $row['unitcost'] * $row['forcharging'];
                                $subtotal += (float) $cost_extended;

                                echo "
                                <tr>
                                
                                    <td>{$row['franchise']} {$row['code']} - {$row['branchname']}</td>
                                    <td class='text-center'>{$row['f325number']}</td>
                                    <td>{$row['mdccode']} - {$row['description']}</td>
                                    <td class='text-end'>{$row['forcharging']}</td>
                                    <td class='text-center'>{$row['uom']}</td>
                                    <td class='text-end'>" . number_format($row['unitcost'], 2) . "</td>
                                </tr>";
                            }
                        }
                        ?>

                    </tbody>
                </table>
            </div>
            <hr>
            <input type="hidden" name="batchnumber" value="<?= htmlspecialchars($batchnumber) ?>">
            <div class="row g-4 align-items-start">

                <!-- LEFT: Upload Section -->
                <div class="col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-bold text-center">
                            Pull-Out Details
                        </div>

                        <?php
                        $batchQuery = "
                            SELECT custodian_name, logp_number, charge_date
                            FROM dbpullout
                            WHERE reference = '$batchnumber'
                            LIMIT 1
                        ";
                        $batchResult = mysqli_query($conn, $batchQuery);
                        if (!$batchResult) {
                            die("Query failed: " . mysqli_error($conn));
                        }
                        $batch = mysqli_fetch_assoc($batchResult);

                        $custodian_name = $batch['custodian_name'] ?? '';
                        $logpNumber = $batch['logp_number'] ?? '';
                        $pulloutDate = $batch['charge_date'] ?? '';

                        ?>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="fw-bold">Charged Date</label>
                                    <input type="date" name="pullout_date" class="form-control"
                                        value="<?= htmlspecialchars($pulloutDate) ?>" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label class="fw-bold">BO Custodian Name</label>
                                    <input type="text" class="form-control" name="custodian_name"
                                        value="<?= htmlspecialchars($custodian_name) ?>" readonly>
                                </div>

                                <div class="col-md-12">
                                    <label class="fw-bold">LOGP #</label>
                                    <input type="text" class="form-control" name="logpnumber"
                                        value="<?= htmlspecialchars($logpNumber) ?>" readonly >
                                </div>

                                <div class="d-flex gap-2 mb-3">
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#logpModal">
                                        <i class="bi bi-image"></i> View LOGP Attachments
                                    </button>

                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#pulloutModal">
                                        <i class="bi bi-image"></i> View Print-Out Summary
                                    </button>
                                </div>
                                <?php
                                $attachmentsQuery = "
                                    SELECT *
                                    FROM tbl_attachments
                                    WHERE batchnumber = '$batchnumber'
                                    ORDER BY document_type, sequence_no ASC
";
                                $attachmentsResult = mysqli_query($conn, $attachmentsQuery);

                                $logpFiles = [];
                                $pulloutFiles = [];

                                while ($row = mysqli_fetch_assoc($attachmentsResult)) {
                                    if ($row['document_type'] === 'LOGP') {
                                        $logpFiles[] = $row;
                                    } else {
                                        $pulloutFiles[] = $row;
                                    }
                                }

                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Summary Section -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header fw-bold text-center">
                            Summary
                        </div>

                        <div class="card-body">

                            <div class="mb-3">
                                <label class="fw-bold">Subtotal</label>
                                <input type="text" class="form-control text-end fw-bold bg-light"
                                    value="<?= number_format($subtotal, 2) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="fw-bold">Total Quantity</label>
                                <input type="text" class="form-control text-end fw-bold bg-light"
                                    value="<?= number_format($totalQty, 2) ?>" readonly>
                            </div>

                        </div>
                    </div>
                </div>

            </div>


            <!-- CENTERED BUTTON -->
            <!-- <div class="row mt-4">
                    <div class="col text-center">
                        <button class="btn btn-success px-5" type="submit">
                            <i class="fas fa-upload me-1"></i> Upload
                        </button>
                    </div>
                </div> -->
        </div>
    </div>
</div>
<!-- LOGP IMAGE MODAL -->
<div class="modal fade" id="logpModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content logp-modal-content">

            <div class="modal-header">
                <h5 class="modal-title">LOGP Attachments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center logp-modal-body">
                <div id="logpImageWrapper"></div>
            </div>

            <div class="modal-footer justify-content-center logp-modal-footer">
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item">
                            <button class="page-link" id="logpPrev">Previous</button>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link" id="logpPageIndicator">1 / 1</span>
                        </li>
                        <li class="page-item">
                            <button class="page-link" id="logpNext">Next</button>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>
</div>

<!-- print out sumarry Images -->
<div class="modal fade" id="pulloutModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content logp-modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Print-out Summary Attachments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center logp-modal-body">
                <div id="pulloutImageWrapper"></div>
            </div>

            <div class="modal-footer justify-content-center logp-modal-footer">
                <nav>
                    <ul class="pagination mb-0">
                        <li class="page-item">
                            <button class="page-link" id="pulloutPrev">Previous</button>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link" id="pulloutPageIndicator">1 / 1</span>
                        </li>
                        <li class="page-item">
                            <button class="page-link" id="pulloutNext">Next</button>
                        </li>
                    </ul>
                </nav>
            </div>

        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Pull-Out History - <?= htmlspecialchars($batchnumber) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-info text-center">
                            <tr>
                                <th>F325 Number</th>
                                <th>Processed</th>
                                <th>User</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                           $historyQuery = "
                                SELECT h.*
                                FROM dbhistory h
                                INNER JOIN dbraw r 
                                    ON h.processnumber = r.f325number
                                    OR h.processnumber = r.batchnumber_forcharging
                                WHERE r.batchnumber_forcharging = '$batchnumber'
                                ORDER BY h.dateprocessed DESC, h.timeprocessed DESC
                            ";

                            $historyResult = mysqli_query($conn, $historyQuery);

                            if (mysqli_num_rows($historyResult) > 0) {
                                while ($h = mysqli_fetch_assoc($historyResult)) {

                                    echo "<tr>
                                        <td class='text-center'>{$h['processnumber']}</td>
                                        <td>{$h['processed']}</td>
                                        <td>{$h['name']}</td>
                                        <td class='text-center'>{$h['dateprocessed']}</td>
                                        <td class='text-center'>{$h['timeprocessed']}</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr>
                                        <td colspan='5' class='text-center text-muted'>
                                            No history found
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
</div>

<?php include_once("footer.php"); ?>
<script>
    const logpImages = <?= json_encode(array_map(function ($f) {
        return [
            'path' => $f['path'],
            'sequence' => str_pad($f['sequence_no'], 3, '0', STR_PAD_LEFT)
        ];
    }, $logpFiles)) ?>;

    const pulloutImages = <?= json_encode(array_map(function ($f) {
        return [
            'path' => $f['path'],
            'sequence' => str_pad($f['sequence_no'], 3, '0', STR_PAD_LEFT)
        ];
    }, $pulloutFiles)) ?>;



    let logpCurrentPage = 0;

    const logpWrapper = document.getElementById('logpImageWrapper');
    const logpPrev = document.getElementById('logpPrev');
    const logpNext = document.getElementById('logpNext');
    const logpIndicator = document.getElementById('logpPageIndicator');

    function renderLogpPage() {
        if (!logpImages.length) {
            logpWrapper.innerHTML = '<p class="text-muted">No LOGP attachments found.</p>';
            logpIndicator.textContent = '0 / 0';
            return;
        }

        const img = logpImages[logpCurrentPage];

        logpWrapper.innerHTML = `
        <div class="preview-wrapper d-inline-block">
            <a href="${img.path}" target="_blank">
                <img src="${img.path}" class="preview-img" style="width:300px;height:auto;">
            </a>
        </div>
    `;

        logpIndicator.textContent = `${logpCurrentPage + 1} / ${logpImages.length}`;

        logpPrev.parentElement.classList.toggle(
            'disabled',
            logpCurrentPage === 0
        );

        logpNext.parentElement.classList.toggle(
            'disabled',
            logpCurrentPage === logpImages.length - 1
        );
    }

    logpPrev.addEventListener('click', () => {
        if (logpCurrentPage > 0) {
            logpCurrentPage--;
            renderLogpPage();
        }
    });

    logpNext.addEventListener('click', () => {
        if (logpCurrentPage < logpImages.length - 1) {
            logpCurrentPage++;
            renderLogpPage();
        }
    });

    document.getElementById('logpModal').addEventListener('shown.bs.modal', () => {
        logpCurrentPage = 0;
        renderLogpPage();
    });

    let pulloutCurrentPage = 0;

    const pulloutWrapper = document.getElementById('pulloutImageWrapper');
    const pulloutPrev = document.getElementById('pulloutPrev');
    const pulloutNext = document.getElementById('pulloutNext');
    const pulloutIndicator = document.getElementById('pulloutPageIndicator');

    function renderPulloutPage() {
        if (!pulloutImages.length) {
            pulloutWrapper.innerHTML = '<p class="text-muted">No Print-out attachments found.</p>';
            pulloutIndicator.textContent = '0 / 0';
            return;
        }

        const img = pulloutImages[pulloutCurrentPage];

        pulloutWrapper.innerHTML = `
        <div class="preview-wrapper d-inline-block">
            <a href="${img.path}" target="_blank">
                <img src="${img.path}" class="preview-img" style="width:300px;height:auto; object-fit: cover; border-radius: 0px;">
            </a>
        </div>
    `;

        pulloutIndicator.textContent = `${pulloutCurrentPage + 1} / ${pulloutImages.length}`;

        pulloutPrev.parentElement.classList.toggle(
            'disabled',
            pulloutCurrentPage === 0
        );

        pulloutNext.parentElement.classList.toggle(
            'disabled',
            pulloutCurrentPage === pulloutImages.length - 1
        );
    }

    pulloutPrev.addEventListener('click', () => {
        if (pulloutCurrentPage > 0) {
            pulloutCurrentPage--;
            renderPulloutPage();
        }
    });

    pulloutNext.addEventListener('click', () => {
        if (pulloutCurrentPage < pulloutImages.length - 1) {
            pulloutCurrentPage++;
            renderPulloutPage();
        }
    });

    document.getElementById('pulloutModal').addEventListener('shown.bs.modal', () => {
        pulloutCurrentPage = 0;
        renderPulloutPage();
    });
</script>