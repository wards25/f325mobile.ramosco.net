<?php
session_start();
include('dbconnect.php');
$maintenanceModule = 'print_notepad';
include('maintenance_check.php');
?>
<?php include_once('header.php'); ?>
<?php include_once('nav.php'); ?>

<?php
// Scope for both the stat tile below and the filter card's Retailer/Company
// dropdowns — mirrors the same permission logic used in load-notepad-list.php.
$user_id = (string) ($_SESSION['id'] ?? '');
$scope_retailers = [];
$scope_locations = [];
$scope_companies = []; // vendorcodes

$scope_perm_stmt = $conn->prepare(
    "SELECT DISTINCT p.retailer, l.location, c.vendorcode
     FROM tbl_permission p
     LEFT JOIN tbl_location l ON l.id = p.location_id
     LEFT JOIN tbl_company c ON c.id = p.company_id
     WHERE p.user_id = ?"
);
$scope_perm_stmt->bind_param("s", $user_id);
$scope_perm_stmt->execute();
$scope_perm_result = $scope_perm_stmt->get_result();
while ($row = $scope_perm_result->fetch_assoc()) {
    if (!empty($row['retailer']) && !in_array($row['retailer'], $scope_retailers, true)) {
        $scope_retailers[] = $row['retailer'];
    }
    if (!empty($row['location']) && !in_array($row['location'], $scope_locations, true)) {
        $scope_locations[] = $row['location'];
    }
    if (!empty($row['vendorcode']) && !in_array($row['vendorcode'], $scope_companies, true)) {
        $scope_companies[] = $row['vendorcode'];
    }
}
$scope_perm_stmt->close();
sort($scope_retailers);

// Retailer -> company pairs, for the cascading Retailer/Company dropdown.
// One row per (retailer, company) combination this user has permission for.
$retailer_company_pairs = [];
$rc_stmt = $conn->prepare(
    "SELECT DISTINCT p.retailer, c.vendorcode, c.name
     FROM tbl_permission p
     JOIN tbl_company c ON c.id = p.company_id
     WHERE p.user_id = ? AND c.active = 1
     ORDER BY c.name ASC"
);
$rc_stmt->bind_param("s", $user_id);
$rc_stmt->execute();
$rc_result = $rc_stmt->get_result();
while ($row = $rc_result->fetch_assoc()) {
    $retailer_company_pairs[] = [
        'retailer' => $row['retailer'],
        'vendorcode' => $row['vendorcode'],
        'name' => $row['name'],
    ];
}
$rc_stmt->close();

function build_scope_placeholders($count)
{
    return implode(',', array_fill(0, $count, '?'));
}

$open_count = 0;
if (!empty($scope_retailers) && !empty($scope_locations) && !empty($scope_companies)) {
    $open_sql = "SELECT COUNT(*) AS c FROM tbl_f325number
                 WHERE status = 'open' AND emaildate BETWEEN '2025-01-01' AND NOW()";
    $open_types = "";
    $open_params = [];

    $open_sql .= " AND retailer IN (" . build_scope_placeholders(count($scope_retailers)) . ")";
    $open_types .= str_repeat('s', count($scope_retailers));
    $open_params = array_merge($open_params, $scope_retailers);

    $open_sql .= " AND location IN (" . build_scope_placeholders(count($scope_locations)) . ")";
    $open_types .= str_repeat('s', count($scope_locations));
    $open_params = array_merge($open_params, $scope_locations);

    $open_sql .= " AND vendor IN (" . build_scope_placeholders(count($scope_companies)) . ")";
    $open_types .= str_repeat('s', count($scope_companies));
    $open_params = array_merge($open_params, $scope_companies);

    $open_stmt = $conn->prepare($open_sql);
    $open_stmt->bind_param($open_types, ...$open_params);
    $open_stmt->execute();
    $open_count = (int) $open_stmt->get_result()->fetch_assoc()['c'];
    $open_stmt->close();
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --brand: #4f46e5;
        --brand-light: #eef0fe;
        --page-bg: #f4f5fb;
        --text-muted: #8a8fa3;
        --ok: #12805c;
        --ok-soft: #e7f6f0;
        --danger: #b3261e;
        --danger-soft: #fbebea;
        --amber: #b45309;
        --amber-soft: #fdf1e0;
        --line: #eceef5;
        --surface: #ffffff;
        --ink: #1f2130;
    }

    .notepad-page {
        font-family: 'Inter', -apple-system, sans-serif;
        background-color: var(--page-bg);
        min-height: 100vh;
        padding: 2rem 1.5rem;
    }

    .notepad-breadcrumb {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }

    .notepad-breadcrumb .current {
        color: var(--ink);
        font-weight: 600;
    }

    .notepad-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .notepad-title {
        font-weight: 700;
        font-size: 1.6rem;
        color: var(--ink);
        margin: 0;
    }

    .notepad-page .export-form {
        align-items: flex-end;
    }

    .notepad-page .lbl-style {
        font-weight: 600;
        font-size: 0.78rem;
        color: #3d3f4d;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        margin-bottom: 0.35rem;
        display: block;
    }

    .notepad-page .input-date-export {
        border: 1px solid #e3e5ef !important;
        border-radius: 0.6rem !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.85rem !important;
        background-color: #fbfbfe !important;
    }

    .notepad-page .input-date-export:focus {
        border-color: var(--brand) !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        background-color: #fff !important;
        outline: none;
    }

    .notepad-page .button-export-printed {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
        color: #fff !important;
        border-radius: 0.6rem !important;
        font-weight: 600 !important;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
    }

    .notepad-page .button-export-printed:hover {
        background-color: #4338ca !important;
        border-color: #4338ca !important;
    }

    /* ---- KPI tile ---- */
    .notepad-stat-tile {
        background: var(--surface);
        border: 1px solid var(--line);
        border-top: 3px solid var(--danger);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .notepad-stat-tile .stat-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        font-weight: 700;
        margin-bottom: 0.3rem;
    }

    .notepad-stat-tile .stat-num {
        font-weight: 800;
        font-size: 1.8rem;
        color: var(--ink);
        line-height: 1;
    }

    .notepad-stat-tile .stat-meta {
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-top: 0.4rem;
    }

    .notepad-stat-tile .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.85rem;
        background: var(--danger-soft);
        color: var(--danger);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    /* ---- Filter + results cards ---- */
    .notepad-filter-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
    }

    .filter-clear-btn {
        position: absolute;
        top: 0.9rem;
        right: 0.9rem;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid var(--line);
        background: #fff;
        color: var(--text-muted);
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .filter-clear-btn:hover {
        background: var(--danger-soft);
        color: var(--danger);
        border-color: var(--danger-soft);
    }

    .filter-clear-btn.show {
        display: flex;
    }

    .notepad-results-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        overflow: hidden;
        padding: 0.5rem 0.5rem 1rem;
    }

    .notepad-page .select-withNoBorder,
    .notepad-page .select-withBorder,
    .notepad-page .input-withBorder {
        border: 1px solid #e3e5ef !important;
        border-radius: 0.6rem !important;
        padding: 0.55rem 0.85rem !important;
        font-size: 0.9rem !important;
        background-color: #fbfbfe !important;
        width: 100%;
    }

    .notepad-page .select-withNoBorder:focus,
    .notepad-page .select-withBorder:focus,
    .notepad-page .input-withBorder:focus {
        border-color: var(--brand) !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        background-color: #fff !important;
        outline: none;
    }

    .notepad-page #listTable.tbl-border {
        width: 100%;
        margin: 0;
        border: none !important;
    }

    .notepad-page #listTable.tbl-border thead.thead-dark th {
        background-color: #fafbfd !important;
        color: var(--text-muted) !important;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 700;
        border-bottom: 1px solid var(--line) !important;
        padding: 0.75rem 1.25rem;
        white-space: nowrap;
    }

    .notepad-page #listTable.tbl-border tbody td {
        padding: 0.9rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid #f2f3f8;
        font-size: 0.88rem;
        color: var(--ink);
    }

    .notepad-page #listTable.tbl-border.table-striped tbody tr:nth-of-type(odd) {
        background-color: #fafbfd;
    }

    .notepad-page .tbl-list-order-tr {
        cursor: pointer;
    }

    .notepad-page .tbl-list-order-tr:hover {
        background-color: var(--brand-light) !important;
    }

    .notepad-page .badge-status {
        border-radius: 999px;
        padding: 0.3rem 0.75rem;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-block;
    }

    .notepad-page .badge-active {
        background-color: #e6f7ec;
        color: #1a9c4d;
    }

    .notepad-page .badge-printed {
        background-color: var(--brand-light);
        color: var(--brand);
    }

    .notepad-page .td-no-data {
        text-align: center;
        padding: 2.5rem 1rem !important;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    /* ---- Skeleton preloader for the results table ---- */
    .notepad-skeleton-row td {
        padding: 0.9rem 1.25rem !important;
        border-bottom: 1px solid #f2f3f8;
    }

    .notepad-skeleton-bar {
        height: 12px;
        border-radius: 6px;
        background: linear-gradient(90deg, #edeef4 25%, #f6f7fb 37%, #edeef4 63%);
        background-size: 400% 100%;
        animation: notepad-skeleton-shimmer 1.4s ease infinite;
    }

    @keyframes notepad-skeleton-shimmer {
        0% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0 50%;
        }
    }

    /* ---- Omni search box (same pattern as search.php) ---- */
    .omni-search-wrap {
        position: relative;
    }

    .omni-search-box {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid #e3e5ef;
        border-radius: 0.6rem;
        background-color: #fbfbfe;
        padding: 0.45rem 0.6rem;
        min-height: 46px;
    }

    .omni-search-box:focus-within {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        background-color: #fff;
    }

    .omni-search-box input {
        flex: 1 1 140px;
        border: none;
        outline: none;
        background: transparent;
        font-size: 0.92rem;
        padding: 0.2rem 0.3rem;
        min-width: 120px;
    }

    /* Chip needs top/right breathing room + position:relative so the
       remove button can sit pinned to its top-right corner. */
    .omni-chip {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: var(--brand-light);
        color: var(--brand);
        border-radius: 999px;
        padding: 0.3rem 0.7rem;
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
        margin-top: 0.5rem;
    }

    .omni-chip .chip-type {
        font-weight: 700;
        opacity: 0.75;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .omni-chip button {
        position: absolute;
        top: -7px;
        right: -7px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        border: 1px solid #fff;
        background: var(--brand);
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
        font-size: 0.6rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.25);
    }

    .omni-chip button:hover {
        background: var(--danger);
    }

    .omni-suggest {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 0.6rem;
        box-shadow: 0 8px 24px rgba(16, 24, 40, 0.12);
        z-index: 50;
        overflow: hidden;
        display: none;
    }

    .omni-suggest.show {
        display: block;
    }

    .omni-suggest-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.6rem 0.9rem;
        font-size: 0.85rem;
        color: #3d3f4d;
        cursor: pointer;
    }

    .omni-suggest-item:hover,
    .omni-suggest-item.active {
        background-color: var(--brand-light);
        color: var(--brand);
    }

    .omni-suggest-item .field-name {
        font-weight: 700;
    }

    .omni-suggest-item .field-value {
        color: var(--brand);
        font-weight: 700;
        font-style: italic;
    }

    .omni-suggest-item:not(:last-child) {
        border-bottom: 1px solid #f5f6fa;
    }

    /* ---- Modal ---- */
    #order-detail-modal .modal-content {
        border-radius: 1rem;
        border: none;
        overflow: hidden;
    }

    #order-detail-modal .modal-header {
        background: var(--ink);
        color: #fff;
        border-bottom: none;
        padding: 1rem 1.25rem;
    }

    #order-detail-modal .modal-header .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    #order-detail-modal .card {
        border: 1px solid var(--line);
        border-radius: 0.85rem;
        box-shadow: none;
    }

    #order-detail-modal .button-reopen {
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.85rem;
    }

    #order-detail-modal .button-print {
        background-color: var(--ok) !important;
        border-color: var(--ok) !important;
        color: #fff !important;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.85rem;
    }

    #order-detail-modal .button-print:hover {
        background-color: #0d6b4d !important;
        border-color: #0d6b4d !important;
    }

    #order-detail-modal .button-reprint {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
        color: #fff !important;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.85rem;
    }

    #order-detail-modal .button-reprint:hover {
        background-color: #4338ca !important;
        border-color: #4338ca !important;
    }

    #order-detail-modal .button-history {
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.85rem;
    }

    #order-detail-modal .table-light th {
        background-color: #fafbfd;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: var(--text-muted);
    }

    #order-detail-modal .modal-loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.85);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10;
        flex-direction: column;
        gap: 0.6rem;
        color: var(--text-muted);
        font-size: 0.88rem;
        font-weight: 600;
    }

    #order-detail-modal .modal-loading-overlay.show {
        display: flex;
    }

    /* Toast, matches the rest of the app */
    .app-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 2000;
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-left: 4px solid var(--ok);
        border-radius: 12px;
        padding: 14px 16px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        font-size: 0.9rem;
        color: var(--ink);
        max-width: 360px;
        transition: opacity 0.4s ease, transform 0.4s ease;
    }

    .app-toast .app-toast-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--ok-soft);
        color: var(--ok);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.85rem;
    }

    .app-toast .app-toast-close {
        border: none;
        background: none;
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-left: auto;
        cursor: pointer;
        line-height: 1;
        padding: 2px;
    }

    .app-toast.hide {
        opacity: 0;
        transform: translateY(-8px);
    }

    .app-toast.app-toast-error {
        border-left-color: var(--danger);
    }

    .app-toast.app-toast-error .app-toast-icon {
        background: var(--danger-soft);
        color: var(--danger);
    }
</style>

<div id="toastContainer"></div>

<div class="notepad-page">
    <div class="container-fluid">

        <div class="notepad-breadcrumb">F325 Modules &rsaquo; <span class="current">Print Notepad</span></div>

        <div class="notepad-header-row">
            <h1 class="notepad-title">Print Notepad</h1>

            <form class="d-flex align-items-end gap-2 export-form" method="POST" action="export-notepad.php"
                target="_blank">
                <div>
                    <label class="lbl-style mb-1">Date Export</label>
                    <input type="date" class="form-control input-withBorder input-date-export" name="name-export"
                        value="<?php echo date("Y-m-d"); ?>">
                </div>
                <button class="btn button-withBorder button-export-printed btn-sm">
                    <i class="fas fa-file-export me-1"></i> Export Printed Summary
                </button>
            </form>
        </div>

        <div class="row">
            <div class="col-xl-12 col-md-12 mb-4">
                <div class="notepad-stat-tile">
                    <div>
                        <div class="stat-label">Total F325 For Open Status</div>
                        <div class="stat-num">
                            <?php echo number_format($open_count); ?>
                        </div>
                        <div class="stat-meta">as of <?php echo date("h:i A"); ?></div>
                    </div>
                    <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
                </div>
            </div>
        </div>

        <div class="notepad-filter-card">
            <button type="button" id="filterClearBtn" title="Clear all filters" hidden>
            </button>
            <div class="row g-3">
                <div class="col-12">
                    <label class="lbl-style">Search</label>
                    <div class="omni-search-wrap">
                        <div class="omni-search-box" id="omniSearchBox">
                            <input type="text" id="omniInput" autocomplete="off"
                                placeholder="Type an F325 number or branch name/code…">
                        </div>
                        <div class="omni-suggest" id="omniSuggest"></div>
                    </div>
                </div>
                <div class="form-group col-md-4">
                    <label class="lbl-style">Retailer</label>
                    <select class="form-select select-withBorder select-retailer"
                        onchange="populateCompanyOptions(this.value); LoadNotepadList();">
                        <option value="">All</option>
                        <?php foreach ($scope_retailers as $r): ?>
                            <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label class="lbl-style">Status</label>
                    <select class="form-select select-withBorder select-status" onchange="LoadNotepadList();">
                        <option value="OPEN">Open</option>
                        <option value="PRINTED">Printed</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label class="lbl-style">Company</label>
                    <select class="form-select select-withBorder select-company" onchange="LoadNotepadList();">
                        <option value="">All</option>
                        <!-- populated by populateCompanyOptions() based on the selected retailer -->
                    </select>
                </div>
            </div>
        </div>

        <div class="notepad-results-card">
            <div class="table-responsive">
                <table id="listTable" class="tbl-border table table-striped" cellspacing="0" width="100%">
                    <thead class="thead-dark">
                        <tr>
                            <th class="tbl-list-order-th1">F325 #</th>
                            <th class="tbl-list-order-th2">Branch Name</th>
                            <th class="tbl-list-order-th3">Email Date</th>
                            <th class="tbl-list-order-th4">F325 Date</th>
                            <th class="tbl-list-order-th5">Vendor</th>
                            <th class="tbl-list-order-th6">Retailer</th>
                            <th class="tbl-list-order-th7">Status</th>
                        </tr>
                    </thead>
                    <tbody class="tbody-list-order"></tbody>
                    <tbody class="tbody-skeleton" style="display:none;">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <tr class="notepad-skeleton-row">
                                <td>
                                    <div class="notepad-skeleton-bar"></div>
                                </td>
                                <td>
                                    <div class="notepad-skeleton-bar"></div>
                                </td>
                                <td>
                                    <div class="notepad-skeleton-bar"></div>
                                </td>
                                <td>
                                    <div class="notepad-skeleton-bar"></div>
                                </td>
                                <td>
                                    <div class="notepad-skeleton-bar"></div>
                                </td>
                                <td>
                                    <div class="notepad-skeleton-bar"></div>
                                </td>
                                <td>
                                    <div class="notepad-skeleton-bar" style="width:60%;"></div>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Order Detail -->
        <div class="modal fade" id="order-detail-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="d-flex justify-content-between mb-0 w-100 align-items-center">
                            <div class="btn-group gap-2">
                                <button type="button" class="btn btn-outline-light button-reopen" style="display:none;"
                                    onclick="ReOpenNotepad();">Re-Open</button>
                                <button type="button" class="btn btn-sm button-print" style="display:none;"
                                    onclick="PrintNotepad();">Print</button>
                                <button type="button" class="btn btn-sm button-reprint" style="display:none;"
                                    onclick="PrintNotepad();">Re-print</button>

                                <!-- History Dropdown -->
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-light dropdown-toggle button-history"
                                        data-bs-toggle="dropdown">
                                        History
                                    </button>
                                    <div class="dropdown-menu p-2" style="max-height: 250px; overflow-y: auto;">
                                        <div class="history">
                                            <table class="table table-sm table-bordered mb-0 tbl-history">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Processed</th>
                                                        <th>Date & Time</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="tbody-history-list"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                    </div>
                    <div class="modal-body" style="position:relative;">
                        <div class="modal-loading-overlay" id="modalLoadingOverlay">
                            <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">
                            </div>
                            <span>Loading details…</span>
                        </div>
                        <div class="container py-4">
                            <!-- Vendor & Reference Info -->
                            <div class="row g-3 mb-4">
                                <!-- Vendor Info -->
                                <div class="col-md-8">
                                    <div class="card p-3">
                                        <h6 class="fw-bold mb-3">Vendor Details</h6>

                                        <div class="row mb-2">
                                            <div class="col-md-3 fw-bold">Branch:</div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control input-customer" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-md-3 fw-bold">Company:</div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control input-company" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-md-3 fw-bold">Email Date:</div>
                                            <div class="col-md-9">
                                                <input type="date" class="form-control input-emaildate" disabled>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-md-3 fw-bold">Issued By:</div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control input-issued" disabled>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-3 fw-bold">Prepared By:</div>
                                            <div class="col-md-9">
                                                <input type="text" class="form-control input-prepared" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reference Info -->
                                <div class="col-md-4">
                                    <div class="card p-3">
                                        <h6 class="fw-bold mb-3">Reference</h6>

                                        <div class="mb-2">
                                            <label class="fw-bold">F325 #</label>
                                            <input type="text" class="form-control input-ordernumber" disabled>
                                        </div>

                                        <div class="mb-2">
                                            <label class="fw-bold">F325 Date</label>
                                            <input type="date" class="form-control input-orderdate" disabled>
                                        </div>

                                        <div class="mb-2">
                                            <label class="fw-bold">Status</label>
                                            <input type="text" class="form-control input-status" disabled>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Order Detail Table -->
                            <div class="card p-3 mb-4">
                                <h6 class="fw-bold mb-3">Order Details</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>MDC Code</th>
                                                <th>Item Code</th>
                                                <th>Description</th>
                                                <th>BBD</th>
                                                <th>Reason Code</th>
                                                <th>Quantity</th>
                                                <th>UoM</th>
                                                <th>Unit Price</th>
                                                <th>Sub-Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="tbl-order-list"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Remarks & Subtotal -->
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <div class="card p-3">
                                        <label class="fw-bold">Remarks</label>
                                        <textarea class="form-control input-remarks input-remarks" rows="4"
                                            disabled></textarea>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card p-3">
                                        <label class="fw-bold">Subtotal</label>
                                        <input type="text" class="form-control input-subtotal" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include_once('footer.php'); ?>
<?php $conn->close(); ?>

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script>
    var notepadLoadTimer = null;
    var CAN_PRINT = <?php echo (int) (($_SESSION['print'] ?? 0) == '1' ? 1 : 0); ?>;

    // Every (retailer, company) combination this user has permission for.
    // Used to rebuild the Company dropdown whenever Retailer changes,
    // instead of showing every permitted company regardless of retailer.
    var RETAILER_COMPANY_PAIRS = <?php echo json_encode($retailer_company_pairs); ?>;

    // Rebuilds the Company <select> to only show companies under the given
    // retailer. Passing '' (All retailers) shows every company the user is
    // scoped to see, deduped by vendorcode.
    function populateCompanyOptions(retailerValue) {
        var sel = document.querySelector('.select-company');
        var previous = sel.value;
        sel.innerHTML = '<option value="">All</option>';

        var seen = {};
        RETAILER_COMPANY_PAIRS.forEach(function (pair) {
            if (retailerValue !== '' && pair.retailer !== retailerValue) return;
            if (seen[pair.vendorcode]) return;
            seen[pair.vendorcode] = true;

            var opt = document.createElement('option');
            opt.value = pair.vendorcode;
            opt.textContent = pair.name;
            sel.appendChild(opt);
        });

        // Keep the previous selection if it's still valid under the newly
        // selected retailer; otherwise it falls back to "All".
        if ([...sel.options].some(function (o) { return o.value === previous; })) {
            sel.value = previous;
        }
    }

    // ---- Toast helper ----
    function showToast(message, type) {
        var existing = document.getElementById('appToast');
        if (existing) existing.remove();

        var variantClass = type === 'danger' ? 'app-toast-error' : '';
        var iconClass = type === 'success' ? 'fa-check' : 'fa-triangle-exclamation';

        var toast = document.createElement('div');
        toast.id = 'appToast';
        toast.className = 'app-toast ' + variantClass;
        toast.innerHTML =
            '<span class="app-toast-icon"><i class="fas ' + iconClass + '"></i></span>' +
            '<span></span>' +
            '<button type="button" class="app-toast-close"><i class="fas fa-times"></i></button>';
        toast.querySelector('span:nth-child(2)').textContent = message;
        toast.querySelector('.app-toast-close').addEventListener('click', function () { toast.remove(); });
        document.getElementById('toastContainer').appendChild(toast);

        setTimeout(function () {
            if (!document.body.contains(toast)) return;
            toast.classList.add('hide');
            setTimeout(function () { toast.remove(); }, 400);
        }, 3500);
    }

    // ---- Omni-chip search box ----
    var SEARCH_FIELDS = [
        { key: 'f325number', label: 'F325 No' },
        { key: 'branch', label: 'Branch Name/Code' }
    ];
    var chips = [];
    var activeSuggestIndex = -1;
    var omniInput = document.getElementById('omniInput');
    var omniBox = document.getElementById('omniSearchBox');
    var omniSuggest = document.getElementById('omniSuggest');

    function renderChips() {
        omniBox.querySelectorAll('.omni-chip').forEach(function (el) { el.remove(); });
        chips.forEach(function (chip, idx) {
            var el = document.createElement('span');
            el.className = 'omni-chip';
            el.innerHTML =
                '<span class="chip-type">' + chip.label + '</span>' +
                '<span>' + escapeHtml(chip.value) + '</span>' +
                '<button type="button" aria-label="Remove"><i class="fas fa-times"></i></button>';
            el.querySelector('button').addEventListener('click', function () {
                chips.splice(idx, 1);
                renderChips();
                LoadNotepadList();
            });
            omniBox.insertBefore(el, omniInput);
        });
        document.getElementById('filterClearBtn').classList.toggle('show', chips.length > 0);
    }

    function clearAllFilters() {
        chips = [];
        renderChips();
        omniInput.value = '';
        omniSuggest.classList.remove('show');
        $('.select-retailer').val('');
        $('.select-status').val('OPEN');
        populateCompanyOptions('');
        LoadNotepadList();
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderSuggestions(value) {
        omniSuggest.innerHTML = '';
        activeSuggestIndex = -1;
        if (value === '') {
            omniSuggest.classList.remove('show');
            return;
        }
        SEARCH_FIELDS.forEach(function (field, idx) {
            var item = document.createElement('div');
            item.className = 'omni-suggest-item';
            item.dataset.idx = idx;
            item.innerHTML = 'Search <span class="field-name">' + field.label +
                '</span> for: <span class="field-value">' + escapeHtml(value) + '</span>';
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                addChip(field, value);
            });
            omniSuggest.appendChild(item);
        });
        omniSuggest.classList.add('show');
    }

    function addChip(field, value) {
        chips.push({ key: field.key, label: field.label, value: value });
        renderChips();
        omniInput.value = '';
        omniSuggest.classList.remove('show');
        omniInput.focus();
        LoadNotepadList();
    }

    omniInput.addEventListener('input', function () {
        renderSuggestions(this.value.trim());
    });

    omniInput.addEventListener('keydown', function (e) {
        var items = omniSuggest.querySelectorAll('.omni-suggest-item');
        if (e.key === 'ArrowDown' && items.length) {
            e.preventDefault();
            activeSuggestIndex = (activeSuggestIndex + 1) % items.length;
            items.forEach(function (it, i) { it.classList.toggle('active', i === activeSuggestIndex); });
        } else if (e.key === 'ArrowUp' && items.length) {
            e.preventDefault();
            activeSuggestIndex = (activeSuggestIndex - 1 + items.length) % items.length;
            items.forEach(function (it, i) { it.classList.toggle('active', i === activeSuggestIndex); });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var value = this.value.trim();
            if (value === '') return;
            var field = activeSuggestIndex >= 0 ? SEARCH_FIELDS[activeSuggestIndex] : SEARCH_FIELDS[0];
            addChip(field, value);
        } else if (e.key === 'Backspace' && this.value === '' && chips.length) {
            chips.pop();
            renderChips();
            LoadNotepadList();
        } else if (e.key === 'Escape') {
            omniSuggest.classList.remove('show');
        }
    });

    document.addEventListener('click', function (e) {
        if (!omniBox.contains(e.target) && !omniSuggest.contains(e.target)) {
            omniSuggest.classList.remove('show');
        }
    });

    function chipsToParam(key) {
        return chips.filter(function (c) { return c.key === key; })
            .map(function (c) { return c.value; })
            .join(',');
    }

    // ---- LoadNotepadList() using $.ajax ----
    function LoadNotepadList() {
        clearTimeout(notepadLoadTimer);
        notepadLoadTimer = setTimeout(function () {
            var payload = {
                status: $('.select-status').val(),
                company: $('.select-company').val(),
                retailer: $('.select-retailer').val(),
                f325number: chipsToParam('f325number'),
                branch: chipsToParam('branch')
            };

            $('.tbody-list-order').hide();
            $('.tbody-skeleton').show();

            $.ajax({
                url: 'load-notepad-list.php',
                type: 'POST',
                data: payload,
                dataType: 'html',
                success: function (html) {
                    $('.tbody-list-order').html(html).show();
                    $('.tbody-skeleton').hide();
                },
                error: function () {
                    $('.tbody-list-order').html(
                        '<tr><td class="text-center text-muted py-4" colspan="7">Could not load results. Please try again.</td></tr>'
                    ).show();
                    $('.tbody-skeleton').hide();
                }
            });
        }, 200);
    }

    // ---- Modal functions using $.ajax ----
    function openOrderDetailModal(f325id) {
        window.currentF325Id = f325id;

        var overlay = document.getElementById('modalLoadingOverlay');
        overlay.classList.add('show');

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('order-detail-modal'));
        modal.show();

        $.ajax({
            url: 'load-notepad-detail.php',
            type: 'POST',
            data: { id: f325id },
            dataType: 'json',
            success: function (data) {
                if (data.error) {
                    showToast(data.error, 'danger');
                    overlay.classList.remove('show');
                    modal.hide();
                    return;
                }

                $('#order-detail-modal .input-ordernumber').val(data.f325number);
                $('#order-detail-modal .input-customer').val(data.branchname);
                $('#order-detail-modal .input-company').val(data.vendorname);
                $('#order-detail-modal .input-emaildate').val(data.emaildate);
                $('#order-detail-modal .input-issued').val(data.issuedby);
                $('#order-detail-modal .input-prepared').val(data.preparedby);
                $('#order-detail-modal .input-orderdate').val(data.f325date);
                $('#order-detail-modal .input-status').val(data.status);
                $('#order-detail-modal .input-remarks').val(data.logisticremarks || data.remarks);

                window.currentRetailer = data.retailer;
                window.currentVcode = data.vcode;
                window.currentCompanyId = data.company_id;
                window.currentF325Number = data.f325number;

                toggleActionButtons(data.status);

                // load SKU lines
                $.ajax({
                    url: 'load-sku.php',
                    type: 'POST',
                    data: {
                        f325number: data.f325number,
                        vcode: data.vcode
                    },
                    dataType: 'html',
                    success: function (html) {
                        $('#order-detail-modal .tbl-order-list').html(html);

                        var subtotal = 0;
                        $('#order-detail-modal .subtotal-lines').each(function () {
                            subtotal += parseFloat($(this).attr('subtotal')) || 0;
                        });
                        $('#order-detail-modal .input-subtotal').val(
                            subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                        );

                        overlay.classList.remove('show');
                    },
                    error: function () {
                        overlay.classList.remove('show');
                        showToast('Could not load order line items.', 'danger');
                    }
                });
            },
            error: function () {
                overlay.classList.remove('show');
                showToast('Could not load record details.', 'danger');
                modal.hide();
            }
        });
    }

    function toggleActionButtons(status) {
        var isOpen = (status || '').toUpperCase() === 'OPEN';
        $('#order-detail-modal .button-print').toggle(isOpen && !!CAN_PRINT);
        $('#order-detail-modal .button-reopen').toggle(!isOpen && !!CAN_PRINT);
        $('#order-detail-modal .button-reprint').toggle(!isOpen && !!CAN_PRINT);
    }

    // ---- ReOpen and Print using $.ajax ----
    function ReOpenNotepad() {
        changeNotepadStatus('OPEN');
    }

    function PrintNotepad() {
        if (!window.currentF325Number) return;

        var retailer = window.currentRetailer || '';
        var companyId = window.currentCompanyId || 0;

        $.ajax({
            url: 'fetch_print_template.php',
            type: 'POST',
            data: {
                retailer: retailer,
                company_id: companyId
            },
            dataType: 'json',
            success: function (res) {
                var printScript = (res && res.template) ? res.template : 'print-notepad-details.php';
                var url = printScript + '?f325number=' + encodeURIComponent(window.currentF325Number);
                window.open(url, '_blank');
            },
            error: function () {
                var url = 'print-notepad-details.php?f325number=' + encodeURIComponent(window.currentF325Number);
                window.open(url, '_blank');
                showToast('Could not check for a custom print layout — used the default one instead.', 'danger');
            }
        });
    }

    function changeNotepadStatus(newStatus) {
        if (!window.currentF325Id) return;

        $.ajax({
            url: 'notepad-status-process.php',
            type: 'POST',
            data: {
                id: window.currentF325Id,
                new_status: newStatus
            },
            dataType: 'json',
            success: function (res) {
                if (!res.success) {
                    showToast(res.message || 'Could not update this record.', 'danger');
                    return;
                }
                showToast(res.message, 'success');
                $('#order-detail-modal .input-status').val(res.status);
                toggleActionButtons(res.status);

                var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('order-detail-modal'));
                modal.hide();
                setTimeout(function () {
                    window.location.reload();
                }, 800);
                LoadNotepadList();
            },
            error: function () {
                showToast('Could not update this record. Please try again.', 'danger');
            }
        });
    }

    // ---- Document ready ----
    $(document).ready(function () {
        populateCompanyOptions($('.select-retailer').val());
        LoadNotepadList();
        $(document).on('click', '.tbl-list-order-tr', function () {
            var f325id = $(this).attr('f325id');
            openOrderDetailModal(f325id);
        });
    });
</script>