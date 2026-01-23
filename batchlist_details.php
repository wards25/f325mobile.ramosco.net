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
                Pull-Out Summary -
            </span>
            <span class="h2 mb-0 fw-bold text-gray-800">
                <?= htmlspecialchars($batchnumber) ?>
            </span>
        </div>



        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-success btn-md" onclick="printBatch()">
                <i class="bi bi-printer me-1"></i> Print forpullout QTY
            </button>
            <button class="btn btn-success btn-md" onclick="printBatch()">
                <i class="bi bi-printer me-1"></i> Print Total QTY
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
                            <input type="text" id="ref-number" class="form-control"
                                value="<?= htmlspecialchars($batchnumber) ?>" readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label"><strong>Date Processed:</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($dateProcessed) ?>"
                                readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label"><strong>Hub:</strong></label>
                            <input type="text" class="form-control" 
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
                            SELECT
                                c.branchname,
                                c.franchise,
                                c.code,
                                r.f325number,
                                p.description,
                                r.forpullout,
                                p.uom,
                                r.costextended,
                                r.mdccode,
                                r.unitcost
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

                                $totalQty += (float) $row['forpullout'];
                                $cost_extended = (float) $row['unitcost'] * $row['forpullout'];
                                $subtotal += (float) $cost_extended;

                                echo "
                                <tr>
                                
                                    <td>{$row['franchise']} {$row['code']} - {$row['branchname']}</td>
                                    <td class='text-center'>{$row['f325number']}</td>
                                    <td>{$row['mdccode']} - {$row['description']}</td>
                                    <td class='text-end'>{$row['forpullout']}</td>
                                    <td class='text-center'>{$row['uom']}</td>
                                    <td class='text-end'>" . number_format($cost_extended, 2) . "</td>
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
            <form method="POST" enctype="multipart/form-data">

                <div class="row align-items-start">

                    <!-- LEFT: Upload Section -->
                    <div class="col-md-6">

                        <!-- LogP Images -->
                        <div class="mb-3">
                            <label class="fw-bold">Upload LogP Images</label>
                            <input type="file" id="logpImages" class="form-control" multiple accept="image/*">
                            <div id="logpPreview" class="d-flex flex-wrap mt-2"></div>
                        </div>

                        <!-- Pullout Summary Image -->
                        <div class="mb-3">
                            <label class="fw-bold">Upload Pullout Summary Image</label>
                            <input type="file" name="pullout_summary" id="summaryImage" class="form-control"
                                accept="image/*">

                            <!-- Preview -->
                            <div id="summaryPreview" class="mt-2"></div>
                        </div>

                    </div>

                    <!-- RIGHT: Summary Section -->
                    <div class="col-md-4 offset-md-2">

                        <div class="row mb-2 align-items-center">
                            <label class="col-6 text-end fw-bold">Subtotal:</label>
                            <div class="col-6">
                                <input type="text" class="form-control text-end bg-light"
                                    value="<?= number_format($subtotal, 2) ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-2 align-items-center">
                            <label class="col-6 text-end fw-bold">Total Quantity:</label>
                            <div class="col-6">
                                <input type="text" class="form-control text-end bg-light"
                                    value="<?= number_format($totalQty, 2) ?>" readonly>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- CENTERED BUTTON -->
                <div class="row mt-4">
                    <div class="col text-center">
                        <button class="btn btn-success px-5" type="submit">
                            <i class="fas fa-upload me-1"></i> Upload
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once("footer.php"); ?>
<script>
    function printBatch() {
        const batchnumber = "<?= htmlspecialchars($batchnumber) ?>";
        window.location.href =
            "print_batch_details.php?batchnumber=" +
            encodeURIComponent(batchnumber)
    }
    const logpInput = document.getElementById('logpImages');
    const logpPreview = document.getElementById('logpPreview');

    let logpFiles = []; // store selected files

    logpInput.addEventListener('change', function () {
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
</script>