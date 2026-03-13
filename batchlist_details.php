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

    /* PRELOADER */
    #preloader {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 9999;
    }

    #preloader .preloader-content {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        height: 100%;
    }
</style>
<div id="preloader">
    <div class="preloader-content">
        <div class="spinner-border text-primary" style="width:3rem;height:3rem;"></div>
        <div class="mt-3 fw-bold">Uploading attachments...</div>
    </div>
</div>
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

            <button class="btn btn-primary btn-sm" onclick="printBatch('pullout')">
                <i class="bi bi-printer me-1"></i> Print Pull-Out QTY
            </button>

            <button class="btn btn-success btn-sm" onclick="printBatch('total')">
                <i class="bi bi-printer me-1"></i> Print Total QTY
            </button>

            <!-- History Button -->
            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#historyModal">
                <i class="bi bi-clock-history me-1"></i> View History
            </button>

        </div>
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
                                r.forpullout,
                                p.uom,
                                r.costextended,
                                r.mdccode,
                                r.unitcost
                            FROM dbraw r

                            LEFT JOIN (
                                SELECT f325number, MAX(brcode) AS brcode
                                FROM dbf325number
                                GROUP BY f325number
                            ) f ON r.f325number = f.f325number

                            LEFT JOIN (
                                SELECT 
                                    code, 
                                    MAX(franchise) AS franchise, 
                                    MAX(branchname) AS branchname
                                FROM dbcensus
                                GROUP BY code
                            ) c ON f.brcode = c.code

                            LEFT JOIN (
                                SELECT mdccode, MAX(description) AS description, MAX(uom) AS uom
                                FROM dbproduct
                                GROUP BY mdccode
                            ) p ON r.mdccode = p.mdccode

                            WHERE r.batchnumber_forpullout = '$batchnumber';
                            ";

                        $result = mysqli_query($conn, $query);
                        if (!$result) {
                            die("Query failed: " . mysqli_error($conn));
                        }
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {

                                $totalQty += (float) $row['forpullout'];
                                $cost_extended = (float) $row['unitcost'] * $row['forpullout'];
                                $subtotal += (float) $cost_extended;

                                echo "
                                <tr>
                                    <td>{$row['franchise']} {$row['code']} - {$row['branchname']}</td>
                                    <td class='text-center'>{$row['f325number']}</td>
                                    <td>{$row['description']}</td>
                                    <td class='text-end'>{$row['forpullout']}</td>
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
            <form id="pullout-upload" method="POST" enctype="multipart/form-data" action="upload_pullout_attachment.php">
                <input type="hidden" name="batchnumber" value="<?= htmlspecialchars($batchnumber) ?>">
                <div class="row g-4 align-items-start">

                    <!-- LEFT: Upload Section -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light fw-bold text-center">
                                Pull-Out Details
                            </div>

                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-bold">Pull-Out Date <span class="text-danger">*</span></label>
                                        <input type="date" name="pullout_date" class="form-control" required
                                            value="<?= date('Y-m-d') ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-bold">Driver Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="drivername" required>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="fw-bold">LOGP # <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="logpnumber" required>
                                    </div>

                                    <!-- LogP Images -->
                                    <div class="col-12">
                                        <label class="fw-bold">Upload LogP Images <span
                                                class="text-danger">*</span></label>
                                        <input type="file" id="logpImages" name="logp_images[]" class="form-control"
                                            multiple accept="image/*" required>
                                        <div id="logpPreview" class="d-flex flex-wrap mt-2"></div>
                                    </div>

                                    <!-- Pullout Summary Images -->
                                    <div class="col-12">
                                        <label class="fw-bold">Upload Pull-Out Summary Images <span
                                                class="text-danger">*</span></label>
                                        <input type="file" name="pullout_summary[]" id="summaryImage"
                                            class="form-control" multiple accept="image/*" required>
                                        <div id="summaryPreview" class="d-flex flex-wrap mt-2"></div>
                                    </div>

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
                <div class="row mt-4">
                    <div class="col text-center">
                        <button class="btn btn-success btn-sm" type="submit">
                            <i class="fas fa-upload me-1"></i> Complete Pull-Out
                        </button>
                    </div>
                </div>
            </form>
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
                                    OR h.processnumber = r.batchnumber_forpullout
                                WHERE r.batchnumber_forpullout = '$batchnumber'
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
    function printBatch(type) {
        const batchnumber = "<?= htmlspecialchars($batchnumber) ?>";
        const hub = "<?= htmlspecialchars($location) ?>";
        const company = "<?= htmlspecialchars($vendorcode) ?>";
        const dateProcessed = "<?= htmlspecialchars($dateProcessed) ?>";
        window.location.href =
            "print_batch_details.php?batchnumber=" +
            encodeURIComponent(batchnumber) +
            "&vendor=" + encodeURIComponent(company) +
            "&type=" + type +
            "&hub=" + encodeURIComponent(hub) +
            "&date_processed=" + encodeURIComponent(dateProcessed);
    }

    const logpInput = document.getElementById('logpImages');
    const logpPreview = document.getElementById('logpPreview');
    const summaryInput = document.getElementById('summaryImage');
    const summaryPreview = document.getElementById('summaryPreview');

    let logpFiles = []; // store selected files

    logpInput.addEventListener('change', function() {
        Array.from(this.files).forEach(file => {
            if (file.type.startsWith('image/')) {
                logpFiles.push(file);
            }
        });

        updateLogpPreview();
        updateLogpInput();
    });

    function updateLogpPreview() {
        logpPreview.innerHTML = '';

        logpFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = e => {
                const wrapper = document.createElement('div');
                wrapper.className = 'preview-wrapper';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-img';

                const btn = document.createElement('button');
                btn.className = 'remove-btn';
                btn.innerHTML = '&times;';
                btn.onclick = () => {
                    logpFiles.splice(index, 1);
                    updateLogpPreview();
                    updateLogpInput();
                };

                wrapper.appendChild(img);
                wrapper.appendChild(btn);
                logpPreview.appendChild(wrapper);
            };

            reader.readAsDataURL(file);
        });
    }

    function updateLogpInput() {
        const dataTransfer = new DataTransfer();
        logpFiles.forEach(file => dataTransfer.items.add(file));
        logpInput.files = dataTransfer.files;
    }

    let summaryFiles = []; // store selected files

    summaryInput.addEventListener('change', function() {
        Array.from(this.files).forEach(file => {
            if (file.type.startsWith('image/')) {
                summaryFiles.push(file);
            }
        });

        updateSummaryPreview();
        updateSummaryInput();
    });

    function updateSummaryPreview() {
        summaryPreview.innerHTML = '';

        summaryFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = e => {
                const wrapper = document.createElement('div');
                wrapper.className = 'preview-wrapper';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-img';

                const btn = document.createElement('button');
                btn.className = 'remove-btn';
                btn.innerHTML = '&times;';
                btn.onclick = () => {
                    summaryFiles.splice(index, 1);
                    updateSummaryPreview();
                    updateSummaryInput();
                };

                wrapper.appendChild(img);
                wrapper.appendChild(btn);
                summaryPreview.appendChild(wrapper);
            };

            reader.readAsDataURL(file);
        });
    }

    function updateSummaryInput() {
        const dataTransfer = new DataTransfer();
        summaryFiles.forEach(file => dataTransfer.items.add(file));
        summaryInput.files = dataTransfer.files;
    }
    document.getElementById("pullout-upload").addEventListener("submit", function() {
        const loader = document.getElementById("preloader");
        loader.style.display = "block";

    });
</script>