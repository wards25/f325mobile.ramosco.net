<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id']) || $_SESSION['report'] == '0') {
    header("Location: index.php");
    exit();
}
$maintenanceModule = 'exportprincipal';
include('maintenance_check.php');
include_once("nav.php");
?>

<style>
    .export-wrapper {
        background: #f0f4f8;
        min-height: 100vh;
        padding: 30px 0;
    }

    .export-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
        padding: 32px;
        margin-bottom: 24px;
    }

    .section-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #915c83;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e8ddf0;
    }

    .form-label-clean {
        font-size: 12px;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        display: block;
    }

    .form-control-clean {
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 13px;
        color: #333;
        transition: border-color 0.2s;
        width: 100%;
        background: #fafafa;
    }

    .form-control-clean:focus {
        outline: none;
        border-color: #915c83;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(145, 92, 131, 0.1);
    }

    /* Column Picker Grid */
    .columns-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }

    .col-checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f8f5fa;
        border: 1.5px solid #e8ddf0;
        border-radius: 8px;
        padding: 9px 12px;
        cursor: pointer;
        transition: all 0.15s;
        user-select: none;
    }

    .col-checkbox-item:hover {
        border-color: #915c83;
        background: #f3ebf7;
    }

    .col-checkbox-item.selected {
        border-color: #915c83;
        background: #f0e6f5;
    }

    .col-checkbox-item input[type="checkbox"] {
        accent-color: #915c83;
        width: 15px;
        height: 15px;
        cursor: pointer;
    }

    .col-checkbox-item label {
        font-size: 12px;
        font-weight: 500;
        color: #444;
        cursor: pointer;
        margin: 0;
    }

    .select-all-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .btn-select-all {
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
        padding: 5px 14px;
        border-radius: 6px;
        border: 1.5px solid #915c83;
        color: #915c83;
        background: transparent;
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-select-all:hover {
        background: #915c83;
        color: #fff;
    }

    .selected-count {
        font-size: 12px;
        color: #888;
    }

    .selected-count span {
        color: #915c83;
        font-weight: 700;
    }

    /* Export Button */
    .btn-export {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, #915c83, #7a4a6e);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-export:hover {
        background: linear-gradient(135deg, #7a4a6e, #633d59);
        box-shadow: 0 4px 16px rgba(145, 92, 131, 0.35);
        transform: translateY(-1px);
    }

    .btn-export:active {
        transform: translateY(0);
    }

    .reminder-box {
        background: #fff8e1;
        border: 1px solid #ffe082;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 12px;
        color: #7a6000;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Loading overlay */
    #loadingOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0.85);
        z-index: 9999;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
    }

    #loadingOverlay.active {
        display: flex;
    }

    .spinner {
        width: 48px;
        height: 48px;
        border: 4px solid #e8ddf0;
        border-top-color: #915c83;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .loading-text {
        font-size: 14px;
        font-weight: 600;
        color: #915c83;
    }

    .loading-sub {
        font-size: 12px;
        color: #999;
    }

    @media (max-width: 768px) {
        .columns-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<!-- Loading Overlay -->
<div id="loadingOverlay">
    <div class="spinner"></div>
    <div class="loading-text">Generating Export...</div>
    <div class="loading-sub">Please wait, this may take a few minutes.</div>
</div>

<div class="container-fluid export-wrapper">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-export" style="color:#915c83;"></i> Export F325 Report
        </h1>
    </div>

    <form method="POST" action="exportprincipal_process.php" id="exportForm">

        <!-- Date & Filters -->
        <div class="export-card">
            <div class="section-title"><i class="fas fa-filter"></i> Filters</div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label-clean">Email Date From</label>
                    <input type="date" class="form-control-clean" name="from" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label-clean">Email Date To</label>
                    <input type="date" class="form-control-clean" name="to" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label-clean">Status</label>
                    <select class="form-control-clean" name="status" required>
                        <option value="all">ALL</option>
                        <option value="cancelled">CANCELLED</option>
                        <option value="cleared">CLEARED</option>
                        <option value="disposed">DISPOSED</option>
                        <option value="FOR CHARGING">FOR CHARGING</option>
                        <option value="FOR PULL-OUT">FOR PULL-OUT</option>
                        <option value="PAID">PAID</option>
                        <option value="PULL-OUT">PULL-OUT</option>
                        <option value="open">OPEN</option>
                        <option value="printed">PRINTED</option>
                        <option value="scheduled">SCHEDULED</option>
                        <option value="uploaded">UPLOADED</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label-clean">Location</label>
                    <select class="form-control-clean" name="location" required>
                        <option value="all">ALL</option>
                        <option value="cainta">CAINTA</option>
                        <option value="cdo">CDO</option>
                        <option value="cebu">CEBU</option>
                        <option value="davao">DAVAO</option>
                        <option value="iloilo">ILOILO</option>
                        <option value="pangasinan">PANGASINAN</option>
                        <option value="south luzon">SOUTH LUZON</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label-clean">Principal</label>
                    <select class="form-control-clean" name="principal" required>
                        <option value="all">ALL</option>
                        <?php
                        $query = "SELECT DISTINCT category FROM dbproduct GROUP BY category ORDER BY category ASC";
                        $result = $conn->query($query);
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['category']) . '">' . htmlspecialchars($row['category']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Column Selector -->
        <div class="export-card">
            <div class="section-title"><i class="fas fa-columns"></i> Select Columns to Export</div>

            <div class="select-all-bar">
                <button type="button" class="btn-select-all" id="toggleAllBtn">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <button type="button" class="btn-select-all" id="clearAllBtn" style="border-color:#ccc; color:#888;">
                    <i class="fas fa-times"></i> Clear All
                </button>
                <span class="selected-count">
                    <span id="selectedCount">0</span> of <span id="visibleCount">34</span> columns selected
                </span>

                <!-- Search box -->
                <div style="margin-left:auto; position:relative;">
                    <input type="text" id="columnSearch" placeholder="Search columns..." style="
                    border: 1.5px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 6px 12px 6px 32px;
                    font-size: 12px;
                    color: #333;
                    background: #fafafa;
                    outline: none;
                    width: 200px;
                    transition: border-color 0.2s;
                " onfocus="this.style.borderColor='#915c83'" onblur="this.style.borderColor='#e2e8f0'">
                    <i class="fas fa-search"
                        style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#aaa; font-size:11px; pointer-events:none;"></i>
                </div>
            </div>

            <!-- No results message -->
            <div id="noColumnsFound" style="display:none; text-align:center; padding:20px; color:#aaa; font-size:13px;">
                <i class="fas fa-search" style="font-size:20px; margin-bottom:8px; display:block;"></i>
                No columns found matching your search.
            </div>

            <div class="columns-grid" id="columnsGrid">
                <?php
                $columns = [
                    'f325number' => 'F325 Number',
                    'skucode' => 'SKU Code',
                    'description' => 'Description',
                    'category' => 'Category',
                    'brcode' => 'Branch Code',
                    'brname' => 'Branch Name',
                    'dmpiclass' => 'DMPI Class',
                    'dmpireason' => 'DMPI Reason',
                    'reasoncode' => 'Reason Code',
                    'quantity' => 'Quantity',
                    'rcvdqty' => 'Received Qty',
                    'expiration' => 'Expiration',
                    'f325status' => 'Status',
                    'arnumber' => 'AR Number',
                    'arreason' => 'AR Reason',
                    'preparedby' => 'Prepared By',
                    'issuedby' => 'Issued By',
                    'emaildate' => 'Email Date',
                    'f325date' => 'F325 Date',
                    'company' => 'Company',
                    'tmnumber' => 'TM Number',
                    'platenumber' => 'Plate Number',
                    'driver' => 'Driver Name',
                    'scheddate' => 'Sched Date',
                    'cleardate' => 'Cleared Date',
                    'unitcost' => 'Unit Cost',
                    'costextended' => 'Cost Extended',
                    'print' => 'Print Remarks',
                    'log' => 'Log Remarks',
                    'clearing' => 'Clearing Remarks',
                    'cluster' => 'Cluster',
                    'f325location' => 'Location',
                    'process' => 'Process',
                    'ilrno' => 'ILR No',
                ];

                $i = 1;
                foreach ($columns as $name => $label) {
                    echo '
            <div class="col-checkbox-item" onclick="toggleCheckbox(this)" data-label="' . strtolower($label) . '">
                <input type="checkbox" name="' . $name . '" id="col_' . $i . '">
                <label for="col_' . $i . '">' . $label . '</label>
            </div>';
                    $i++;
                }
                ?>
            </div>
        </div>

        <!-- Export Button -->
        <div class="export-card">
            <div class="reminder-box">
                <i class="fas fa-clock"></i>
                <span>Exportation may take a few minutes depending on data volume. Please be patient.</span>
            </div>
            <button type="submit" class="btn-export" id="exportBtn">
                <i class="fas fa-download"></i> Export to CSV
            </button>
        </div>

    </form>
</div>

<script>
    // Toggle individual checkbox item
    function toggleCheckbox(item) {
        const cb = item.querySelector('input[type="checkbox"]');
        cb.checked = !cb.checked;
        item.classList.toggle('selected', cb.checked);
        updateCount();
    }

    // Update selected count
    function updateCount() {
        const checked = document.querySelectorAll('.col-checkbox-item input:checked').length;
        document.getElementById('selectedCount').textContent = checked;
    }

    // Select All
    document.getElementById('toggleAllBtn').addEventListener('click', function () {
        document.querySelectorAll('.col-checkbox-item').forEach(item => {
            item.querySelector('input').checked = true;
            item.classList.add('selected');
        });
        updateCount();
    });

    // Clear All
    document.getElementById('clearAllBtn').addEventListener('click', function () {
        document.querySelectorAll('.col-checkbox-item').forEach(item => {
            item.querySelector('input').checked = false;
            item.classList.remove('selected');
        });
        updateCount();
    });

    // Handle export with spinner that auto-hides
    document.getElementById('exportForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const checked = document.querySelectorAll('.col-checkbox-item input:checked').length;
        if (checked === 0) {
            alert('Please select at least one column to export.');
            return;
        }

        // Show spinner
        document.getElementById('loadingOverlay').classList.add('active');

        // Submit form normally (not via iframe) so CSV downloads
        this.submit();

        // Hide spinner after 5 seconds — browser starts download by then
        setTimeout(() => {
            document.getElementById('loadingOverlay').classList.remove('active');
        }, 5000);
    });

    // Prevent checkbox label click from double-toggling
    document.querySelectorAll('.col-checkbox-item label, .col-checkbox-item input').forEach(el => {
        el.addEventListener('click', function (e) {
            e.stopPropagation();
            const item = this.closest('.col-checkbox-item');
            const cb = item.querySelector('input');
            item.classList.toggle('selected', cb.checked);
            updateCount();
        });
    });
    // Column search
    document.getElementById('columnSearch').addEventListener('input', function () {
        const search = this.value.toLowerCase().trim();
        const items = document.querySelectorAll('.col-checkbox-item');
        let visibleCount = 0;

        items.forEach(item => {
            const label = item.getAttribute('data-label');
            const match = label.includes(search);
            item.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Show/hide no results message
        document.getElementById('noColumnsFound').style.display = visibleCount === 0 ? 'block' : 'none';
        document.getElementById('columnsGrid').style.display = visibleCount === 0 ? 'none' : 'grid';

        // Update visible count
        document.getElementById('visibleCount').textContent = visibleCount === 34 ? '34' : visibleCount + ' found';
    });

    updateCount();
</script>

<?php include_once("footer.php"); ?>