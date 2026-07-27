<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id']) || $_SESSION['report'] == '0') {
    header("Location: index.php");
    exit();
}

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
        box-shadow: 0 2px 16px rgba(0,0,0,0.08);
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
        background: #ddeaf7;
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
        box-shadow: 0 0 0 3px rgba(46,109,164,0.1);
    }

    /* Column Group Headers */
    .col-group-label {
        grid-column: 1 / -1;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: #915c83;
        padding: 10px 0 4px;
        border-bottom: 1.5px solid #ddeaf7;
        margin-bottom: 4px;
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
        background: #f5f8fc;
        border: 1.5px solid #ddeaf7;
        border-radius: 8px;
        padding: 9px 12px;
        cursor: pointer;
        transition: all 0.15s;
        user-select: none;
    }

    .col-checkbox-item:hover {
        border-color: #915c83;
        background: #eaf1fb;
    }

    .col-checkbox-item.selected {
        border-color: #915c83;
        background: #e0edf9;
    }

    .col-checkbox-item input[type="checkbox"] {
        accent-color: #915c83;
        width: 15px;
        height: 15px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .col-checkbox-item label {
        font-size: 12px;
        font-weight: 500;
        color: #444;
        cursor: pointer;
        margin: 0;
        line-height: 1.3;
    }

    .select-all-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        flex-wrap: wrap;
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

    .btn-group-toggle {
        font-size: 10px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 5px;
        border: 1.5px solid #bbb;
        color: #666;
        background: transparent;
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-group-toggle:hover {
        border-color: #915c83;
        color: #915c83;
    }

    .selected-count {
        font-size: 12px;
        color: #888;
        margin-left: auto;
    }

    .selected-count span {
        color: #915c83;
        font-weight: 700;
    }

    /* Export Button */
    .btn-export {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, #915c83);
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
        background: linear-gradient(135deg, #1a4f7a, #123a5a);
        box-shadow: 0 4px 16px rgba(46,109,164,0.35);
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
        background: rgba(255,255,255,0.85);
        z-index: 9999;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
    }

    #loadingOverlay.active { display: flex; }

    .spinner {
        width: 48px;
        height: 48px;
        border: 4px solid #ddeaf7;
        border-top-color: #915c83;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

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
        .columns-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<!-- Loading Overlay -->
<div id="loadingOverlay">
    <div class="spinner"></div>
    <div class="loading-text">Generating Export...</div>
    <div class="loading-sub">Please wait, this may take a few moments.</div>
</div>

<div class="container-fluid export-wrapper">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-export" style="color:#915c83;"></i> Export BORF Report
        </h1>
    </div>

    <form method="POST" action="exportsummary.php" id="exportForm">

        <!-- Filters -->
        <div class="export-card">
            <div class="section-title"><i class="fas fa-filter"></i> Filters</div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label-clean">Email Date From</label>
                    <input type="date" class="form-control-clean" name="date_from">
                </div>
                <div class="col-md-3">
                    <label class="form-label-clean">Email Date To</label>
                    <input type="date" class="form-control-clean" name="date_to">
                </div>
                <div class="col-md-3">
                    <label class="form-label-clean">Status</label>
                    <select class="form-control-clean" name="status">
                        <option value="all">ALL</option>
                        <option value="draft">DRAFT</option>
                        <option value="printed">PRINTED</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label-clean">Location</label>
                    <select class="form-control-clean" name="location">
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

            <!-- <div class="row">
                <div class="col-md-3">
                    <label class="form-label-clean">BORF Number</label>
                    <input type="text" class="form-control-clean" name="borfnumber" placeholder="e.g. BORF-001">
                </div>
                <div class="col-md-3">
                    <label class="form-label-clean">Cluster</label>
                    <input type="text" class="form-control-clean" name="cluster" placeholder="e.g. North">
                </div>
            </div> -->
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
                    <span id="selectedCount">0</span> of <span id="totalCount">0</span> columns selected
                </span>

                <!-- Search -->
                <div style="margin-left:auto; position:relative;">
                    <input type="text" id="columnSearch" placeholder="Search columns..." style="
                        border:1.5px solid #e2e8f0; border-radius:8px;
                        padding:6px 12px 6px 32px; font-size:12px;
                        color:#333; background:#fafafa; outline:none;
                        width:200px; transition:border-color 0.2s;
                    " onfocus="this.style.borderColor='#915c83'" onblur="this.style.borderColor='#e2e8f0'">
                    <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;font-size:11px;pointer-events:none;"></i>
                </div>
            </div>

            <!-- No results -->
            <div id="noColumnsFound" style="display:none; text-align:center; padding:20px; color:#aaa; font-size:13px;">
                <i class="fas fa-search" style="font-size:20px; margin-bottom:8px; display:block;"></i>
                No columns found matching your search.
            </div>

            <div class="columns-grid" id="columnsGrid">

                <!-- GROUP: BORF List Info -->
                <div class="col-group-label">
                    BORF List Info
                    <button type="button" class="btn-group-toggle" data-group="list" style="margin-left:10px;">Select Group</button>
                </div>

                <?php
                $listColumns = [
                    'borfnumber'       => 'BORF Number',
                    'userprocessed'    => 'User Processed',
                    'f325number'       => 'F325 Number',
                    'brcode'           => 'Branch Code',
                    'preparedby'       => 'Prepared By',
                    'issuedby'         => 'Issued By',
                    'emaildate'        => 'Email Date',
                    'f325date'         => 'F325 Date',
                    'vendor'           => 'Vendor',
                    'tmnumber'         => 'TM Number',
                    'drivername'       => 'Driver Name',
                    'platenumber'      => 'Plate Number',
                    'datesched'        => 'Date Scheduled',
                    'list_datecleared' => 'Date Cleared',
                    'list_arnumber'    => 'AR Number',
                    'pageno'           => 'Page No',
                    'printremarks'     => 'Print Remarks',
                    'logisticremarks'  => 'Logistic Remarks',
                    'clearingremarks'  => 'Clearing Remarks',
                    'cluster'          => 'Cluster',
                    'list_location'    => 'Location',
                    'list_status'      => 'Status',
                    'process'          => 'Process',
                    'stamped'          => 'Stamped',
                ];

                $i = 1;
                foreach ($listColumns as $name => $label) {
                    echo '<div class="col-checkbox-item" onclick="toggleCheckbox(this)" data-label="' . strtolower($label) . '" data-group="list">
                        <input type="checkbox" name="col_' . $name . '" id="col_' . $i . '">
                        <label for="col_' . $i . '">' . $label . '</label>
                    </div>';
                    $i++;
                }
                ?>

                <!-- GROUP: BORF Raw Details -->
                <div class="col-group-label">
                    BORF Raw Details
                    <button type="button" class="btn-group-toggle" data-group="raw" style="margin-left:10px;">Select Group</button>
                </div>

                <?php
                $rawColumns = [
                    'mdccode'              => 'MDC Code',
                    'category'             => 'Category',
                    'dmpiclass'            => 'DMPI Class',
                    'quantity'             => 'Quantity',
                    'expiration'           => 'Expiration',
                    'unitcost'             => 'Unit Cost',
                    'costextended'         => 'Cost Extended',
                    'reasoncode'           => 'Reason Code',
                    'raw_arnumber'         => 'AR Number (Raw)',
                    'arreason'             => 'AR Reason',
                    'dmpireason'           => 'DMPI Reason',
                    'rcvdqty'              => 'Received Qty',
                    'dmpiref'              => 'DMPI Ref',
                    'deductref'            => 'Deduct Ref',
                    'deductqty'            => 'Deduct Qty',
                    'deductcostextended'   => 'Deduct Cost Extended',
                    'raw_datecleared'      => 'Date Cleared (Raw)',
                    'pulloutref'           => 'Pullout Ref',
                    'raw_location'         => 'Location (Raw)',
                    'raw_status'           => 'Status (Raw)',
                    'statusout'            => 'Status Out',
                    'paymentstatus'        => 'Payment Status',
                ];

                foreach ($rawColumns as $name => $label) {
                    echo '<div class="col-checkbox-item" onclick="toggleCheckbox(this)" data-label="' . strtolower($label) . '" data-group="raw">
                        <input type="checkbox" name="col_' . $name . '" id="col_' . $i . '">
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
                <span>Exportation may take a few moments depending on data volume. Please be patient.</span>
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
        const total   = document.querySelectorAll('.col-checkbox-item').length;
        const checked = document.querySelectorAll('.col-checkbox-item input:checked').length;
        document.getElementById('selectedCount').textContent = checked;
        document.getElementById('totalCount').textContent    = total;
    }

    // Select All
    document.getElementById('toggleAllBtn').addEventListener('click', function () {
        document.querySelectorAll('.col-checkbox-item:not([style*="display: none"])').forEach(item => {
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

    // Group toggles
    document.querySelectorAll('.btn-group-toggle').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const group = this.getAttribute('data-group');
            const items = document.querySelectorAll(`.col-checkbox-item[data-group="${group}"]`);
            const allChecked = [...items].every(i => i.querySelector('input').checked);
            items.forEach(item => {
                item.querySelector('input').checked = !allChecked;
                item.classList.toggle('selected', !allChecked);
            });
            this.textContent = allChecked ? 'Select Group' : 'Clear Group';
            updateCount();
        });
    });

    // Prevent label/checkbox double-toggle
    document.querySelectorAll('.col-checkbox-item label, .col-checkbox-item input').forEach(el => {
        el.addEventListener('click', function (e) {
            e.stopPropagation();
            const item = this.closest('.col-checkbox-item');
            const cb   = item.querySelector('input');
            item.classList.toggle('selected', cb.checked);
            updateCount();
        });
    });

    // Column search
    document.getElementById('columnSearch').addEventListener('input', function () {
        const search = this.value.toLowerCase().trim();
        const items  = document.querySelectorAll('.col-checkbox-item');
        let visible  = 0;

        items.forEach(item => {
            const label = item.getAttribute('data-label');
            const match = label.includes(search);
            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        document.getElementById('noColumnsFound').style.display = visible === 0 ? 'block' : 'none';
        document.getElementById('columnsGrid').style.display    = visible === 0 ? 'none' : 'grid';
    });

    // Form submit with validation
    document.getElementById('exportForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const checked = document.querySelectorAll('.col-checkbox-item input:checked').length;
        if (checked === 0) {
            alert('Please select at least one column to export.');
            return;
        }

        document.getElementById('loadingOverlay').classList.add('active');
        this.submit();

        setTimeout(() => {
            document.getElementById('loadingOverlay').classList.remove('active');
        }, 5000);
    });

    updateCount();
</script>

<?php include_once("footer.php"); ?>