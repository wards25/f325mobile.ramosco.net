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

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <!-- Left side: action buttons -->
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#confirmBatchModal">
                            <i class="fas fa-plus-circle"></i> Create Batch
                        </button>
                        <button id="toggleSelect" type="button" class="btn btn-success">
                            <i class="fas fa-check-square"></i> Select All
                        </button>
                        <button id="exportCsvBtn" type="button" class="btn btn-warning text-dark">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                    </div>

                    <!-- Right side: location filter -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="locationFilter" class="fw-bold mb-0">
                            <i class="fas fa-map-marker-alt"></i> Filter by Location:
                        </label>
                        <select id="locationFilter" class="form-select form-select-sm w-auto">
                            <option value="">All Locations</option>
                            <?php
                            $query = "SELECT id, location FROM dblocation WHERE active = 1 ORDER BY location ASC";
                            $result = mysqli_query($conn, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $loc_id = $row['id'];
                                if (!empty($_SESSION['loc' . $loc_id]) && $_SESSION['loc' . $loc_id] == 1) {
                                    echo "<option value='" . htmlspecialchars($row['location']) . "'>"
                                        . htmlspecialchars($row['location']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

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
                                    r.unitcost * r.rcvdqty AS total_cost,
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
                                    AND r.batchnumber_forpullout IS NULL
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
<!-- Preloader Overlay -->
<div id="batchPreloader"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); background:rgba(0,0,0,0.35);">
    <div style="text-align:center;">
        <svg width="62" height="62" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="26" cy="26" r="22" stroke="rgba(255,255,255,0.2)" stroke-width="4" />
            <circle cx="26" cy="26" r="22" stroke="#ffffff" stroke-width="4" stroke-linecap="round"
                stroke-dasharray="34.5 103.7">
                <animateTransform attributeName="transform" type="rotate" from="0 26 26" to="360 26 26" dur="0.85s"
                    repeatCount="indefinite" />
            </circle>
        </svg>
        <p id="batchPreloaderText"
            style="font-size:15px; font-weight:600; color:#ffffff; margin:16px 0 4px; letter-spacing:0.3px;"></p>
        <p style="font-size:12px; color:rgba(255,255,255,0.65); margin:0;">Do not close this page.</p>
    </div>
</div>
<?php include_once("footer.php"); ?>
<script>
    $(window).on('load', function () {
        const confirmBtn = document.getElementById("confirmCreateBatch");
        const form = document.getElementById("create-batch-pullout");
        const toggleBtn = document.getElementById("toggleSelect");

        const table = $('#dataTable').DataTable();

        // ✅ Add summary bar below the table
        $('#dataTable_wrapper').append(`
            <div id="selectionSummary" class="mt-3 p-3 bg-light border rounded d-flex gap-4 align-items-center" style="display:none!important;">
                <span class="fw-bold text-muted"><i class="fas fa-box"></i> Selected Items: <span id="summaryCount" class="text-dark">0</span></span>
                <span class="fw-bold text-muted"><i class="fas fa-cubes"></i> Total Pullout Qty: <span id="summaryQty" class="text-primary">0</span></span>
                <span class="fw-bold text-muted"><i class="fas fa-peso-sign"></i> Total Amount: <span id="summaryTotal" class="text-success">₱0.00</span></span>
            </div>
        `);

        function updateSummary() {
            let count = 0;
            let totalQty = 0;
            let totalAmount = 0;

            table.rows().every(function () {
                const row = this.node();
                const checkbox = row.querySelector('.row-checkbox');
                if (checkbox && checkbox.checked) {
                    const cells = row.querySelectorAll('td');
                    // column index 4 = Pullout Qty, column index 5 = Total
                    const qty = parseFloat(cells[4].innerText) || 0;
                    const amount = parseFloat(cells[5].innerText.replace(/[₱,]/g, '')) || 0;

                    count++;
                    totalQty += qty;
                    totalAmount += amount;
                }
            });

            $('#summaryCount').text(count);
            $('#summaryQty').text(totalQty.toLocaleString());
            $('#summaryTotal').text('₱' + totalAmount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            // Show/hide summary bar
            if (count > 0) {
                $('#selectionSummary').show();
            } else {
                $('#selectionSummary').hide();
            }
        }

        // ✅ Trigger summary on any checkbox change
        $('#dataTable tbody').on('change', '.row-checkbox', function () {
            updateSummary();
        });

        // Location filter — column index 2 is "Location"
        $('#locationFilter').on('change', function () {
            const selectedLocation = $(this).val();

            table.column(2).search(
                selectedLocation ? '^' + $.fn.dataTable.util.escapeRegex(selectedLocation) + '$' : '',
                true,
                false
            ).draw();

            // Reset select-all toggle when filter changes
            allSelected = false;
            toggleBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
            updateSummary();
        });

        confirmBtn.addEventListener("click", function () {
            $('#form-hidden-items').remove();

            const hiddenContainer = $('<div id="form-hidden-items"></div>');

            table.rows().every(function () {
                const row = this.node();
                const checkbox = row.querySelector('.row-checkbox');
                if (checkbox && checkbox.checked) {
                    hiddenContainer.append(
                        `<input type="hidden" name="items[]" value="${checkbox.value}">`
                    );
                }
            });

            $(form).find('input[name="items[]"]').prop('disabled', true);
            $(form).append(hiddenContainer);
            const overlay = document.getElementById('batchPreloader');
            overlay.style.display = 'flex';
            document.body.style.cursor = 'not-allowed';
            document.body.style.pointerEvents = 'none';
            overlay.style.pointerEvents = 'all';
            overlay.style.cursor = 'not-allowed';

            const messages = [
                'Creating batch...',
                'Still processing...',
                'Just a few more seconds...',
                'Hold still...',
                'Almost there...',
                'Do not close this page...'
            ];

            let i = 0;
            const textEl = document.getElementById('batchPreloaderText');
            textEl.textContent = messages[0];
            textEl.style.transition = 'opacity 0.3s ease';

            setInterval(function () {
                i = (i + 1) % messages.length;
                textEl.style.opacity = '0';
                setTimeout(function () {
                    textEl.textContent = messages[i];
                    textEl.style.opacity = '1';
                }, 300);
            }, 5500);
            form.submit();
        });

        let allSelected = false;

        toggleBtn.addEventListener("click", function () {
            allSelected = !allSelected;

            table.rows({ search: 'applied' }).every(function () {
                const row = this.node();
                const checkbox = row.querySelector('.row-checkbox');
                if (checkbox) {
                    checkbox.checked = allSelected;
                }
            });

            toggleBtn.innerHTML = allSelected ?
                '<i class="fas fa-times-square"></i> Unselect All' :
                '<i class="fas fa-check-square"></i> Select All';

            updateSummary();
        });
        // export to csv 
        document.getElementById('exportCsvBtn').addEventListener('click', function () {
            const selectedItems = [];

            table.rows({ search: 'applied' }).every(function () {
                const row = this.node();
                const checkbox = row.querySelector('.row-checkbox');
                if (checkbox && checkbox.checked) {
                    selectedItems.push(checkbox.value);
                }
            });

            if (selectedItems.length === 0) {
                alert('No selected items to export. Please select at least one row.');
                return;
            }

            // Build a hidden form and POST it — triggers file download directly
            $('#_csvExportForm').remove();

            const form = $('<form>', {
                id: '_csvExportForm',
                method: 'POST',
                action: 'exportforpulldetails.php',
                style: 'display:none'
            });

            const fields = {
                category: '<?php echo addslashes($category); ?>',
                company: '<?php echo addslashes($company); ?>',
                vendorcode: '<?php echo addslashes($vendorcode); ?>',
                location: $('#locationFilter').val()
            };

            // Append scalar fields
            $.each(fields, function (key, val) {
                form.append($('<input>', { type: 'hidden', name: key, value: val }));
            });

            // Append selected items array
            selectedItems.forEach(function (val) {
                form.append($('<input>', { type: 'hidden', name: 'items[]', value: val }));
            });

            $('body').append(form);
            form.submit();   // Browser triggers download, page stays intact
        });
    });
</script>