<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}
$res = mysqli_query($conn, "SELECT * FROM tbl_users WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);

$user_id = (string) intval($_SESSION['id']);

function build_in_placeholders($count)
{
    return implode(',', array_fill(0, $count, '?'));
}

$scope_cache_key = 'scope_cache_' . $user_id;

if (!isset($_SESSION[$scope_cache_key]) || isset($_GET['refresh_scope'])) {
    $user_retailers = [];
    $stmt = $conn->prepare("SELECT DISTINCT retailer FROM tbl_permission WHERE user_id = ? AND retailer != '' ORDER BY retailer ASC");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $retailer_result = $stmt->get_result();
    while ($row = $retailer_result->fetch_assoc()) {
        $user_retailers[] = $row['retailer'];
    }
    $stmt->close();

    $_SESSION[$scope_cache_key] = $user_retailers;
} else {
    $user_retailers = $_SESSION[$scope_cache_key];
}

// Full permitted companies/locations across ALL of the user's retailers
// (used to populate the Search page's filter dropdowns).
$permitted_companies = [];
$permitted_locations = [];
if (!empty($user_retailers)) {
    $placeholders = build_in_placeholders(count($user_retailers));
    $stmt = $conn->prepare("SELECT DISTINCT company_name, location_name FROM tbl_permission WHERE user_id = ? AND retailer IN ($placeholders)");
    $stmt->bind_param(str_repeat('s', count($user_retailers) + 1), $user_id, ...$user_retailers);
    $stmt->execute();
    $perm_result = $stmt->get_result();
    $seen_companies = [];
    $seen_locations = [];
    while ($row = $perm_result->fetch_assoc()) {
        if ($row['company_name'] !== '' && $row['company_name'] !== null && !isset($seen_companies[$row['company_name']])) {
            $seen_companies[$row['company_name']] = true;
            $permitted_companies[] = $row['company_name'];
        }
        if ($row['location_name'] !== '' && $row['location_name'] !== null && !isset($seen_locations[$row['location_name']])) {
            $seen_locations[$row['location_name']] = true;
            $permitted_locations[] = $row['location_name'];
        }
    }
    $stmt->close();
    sort($permitted_companies);
    sort($permitted_locations);
}
?>

<?php
include_once("nav.php");
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
    }

    body {
        font-family: 'Inter', -apple-system, sans-serif;
    }

    .search-page {
        background-color: var(--page-bg);
        min-height: 100vh;
        padding: 2rem 1.5rem;
    }

    .search-breadcrumb {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }

    .search-breadcrumb .current {
        color: #2d2f3a;
        font-weight: 600;
    }

    .search-title {
        font-weight: 700;
        font-size: 1.6rem;
        color: #1f2130;
        margin: 0 0 1.5rem;
    }

    .filter-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-label-modern {
        font-weight: 600;
        font-size: 0.82rem;
        color: #3d3f4d;
        margin-bottom: 0.4rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .form-control-modern,
    .form-select-modern {
        border: 1px solid #e3e5ef;
        border-radius: 0.6rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.92rem;
        background-color: #fbfbfe;
        width: 100%;
    }

    .form-control-modern:focus,
    .form-select-modern:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        background-color: #fff;
        outline: none;
    }

    .field-hint {
        font-size: 0.74rem;
        color: var(--text-muted);
        margin-top: 0.3rem;
    }

    .btn-brand {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
        color: #fff !important;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.6rem 1.3rem;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
    }

    .btn-brand:hover {
        background-color: #4338ca !important;
        border-color: #4338ca !important;
        color: #fff !important;
    }

    .btn-brand:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .results-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        overflow: hidden;
    }

    .results-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--line);
        font-weight: 700;
        font-size: 0.95rem;
        color: #1f2130;
    }

    .results-card-header .sub {
        font-weight: 500;
        color: var(--text-muted);
        font-size: 0.82rem;
    }

    .results-card-header .sub.text-warning {
        color: var(--amber) !important;
    }

    table.results-table {
        width: 100%;
        margin: 0;
    }

    table.results-table thead th {
        background-color: #fafbfd;
        color: var(--text-muted);
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 700;
        border-bottom: 1px solid var(--line);
        padding: 0.75rem 1.25rem;
        white-space: nowrap;
    }

    table.results-table tbody td {
        padding: 0.9rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid #f2f3f8;
        font-size: 0.88rem;
        color: #2d2f3a;
    }

    table.results-table tbody tr:last-child td {
        border-bottom: none;
    }

    .badge-status {
        border-radius: 999px;
        padding: 0.3rem 0.75rem;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-block;
    }

    .badge-cleared { background-color: #e6f7ec; color: #1a9c4d; }
    .badge-disposed { background-color: #fdeeee; color: #d1373f; }

    .row-action-btn {
        border: 1px solid #e3e5ef;
        background: #fff;
        border-radius: 0.5rem;
        padding: 0.4rem 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #4a4c5a;
        text-decoration: none;
        margin-right: 0.35rem;
    }

    .row-action-btn:hover {
        background-color: #f6f7fb;
        color: var(--brand);
        border-color: var(--brand-light);
        text-decoration: none;
    }

    .row-action-btn.view:hover { color: var(--brand); border-color: var(--brand-light); }
    .row-action-btn.bypass { background: var(--ok-soft); border-color: var(--ok-soft); color: var(--ok); }
    .row-action-btn.bypass:hover { background: #d5f0e2; color: var(--ok); }
    .row-action-btn.reprint { background: var(--amber-soft); border-color: var(--amber-soft); color: var(--amber); }
    .row-action-btn.reprint:hover { background: #fce6bd; color: var(--amber); }

    .empty-state,
    .prompt-state {
        text-align: center;
        padding: 3.5rem 1rem;
        color: var(--text-muted);
    }

    .prompt-state i,
    .empty-state i {
        font-size: 1.8rem;
        display: block;
        margin-bottom: 0.6rem;
    }

    /* Skeleton preloader — shown while an AJAX page of results is loading */
    .skeleton-row {
        display: grid;
        grid-template-columns: 1.1fr 1.1fr 0.9fr 0.8fr 0.8fr 0.8fr;
        gap: 1.25rem;
        align-items: center;
        padding: 0.95rem 1.25rem;
        border-bottom: 1px solid #f2f3f8;
    }

    .skeleton-bar {
        height: 12px;
        border-radius: 6px;
        background: linear-gradient(90deg, #edeef4 25%, #f6f7fb 37%, #edeef4 63%);
        background-size: 400% 100%;
        animation: skeleton-shimmer 1.4s ease infinite;
    }

    @keyframes skeleton-shimmer {
        0% { background-position: 100% 50%; }
        100% { background-position: 0 50%; }
    }

    .dt-pagination-wrap {
        padding: 1rem 1.25rem;
    }

    .filter-hint-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.9rem;
    }

    .btn-clear-modern {
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.6rem 1.1rem;
        border: 1px solid #e3e5ef;
        color: #4a4c5a;
        background: #fff;
    }

    .btn-clear-modern:hover {
        background-color: #f6f7fb;
        color: #2d2f3a;
    }

    /* Toast-style notification, matches product-list.php */
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
        color: #1f2130;
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

    .app-toast.app-toast-warning {
        border-left-color: var(--amber);
    }

    .app-toast.app-toast-warning .app-toast-icon {
        background: var(--amber-soft);
        color: var(--amber);
    }

    /* ---- Odoo-style unified search box ---- */
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

    .omni-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: var(--brand-light);
        color: var(--brand);
        border-radius: 999px;
        padding: 0.3rem 0.5rem 0.3rem 0.7rem;
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .omni-chip .chip-type {
        font-weight: 700;
        opacity: 0.75;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .omni-chip button {
        border: none;
        background: none;
        color: var(--brand);
        opacity: 0.7;
        cursor: pointer;
        display: flex;
        align-items: center;
        padding: 0;
        line-height: 1;
        font-size: 0.75rem;
    }

    .omni-chip button:hover {
        opacity: 1;
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

    /* ---- Built-in DataTables search box, restyled to match the rest of the UI ---- */
    .dt-toolbar {
        padding: 0.85rem 1.25rem 0;
    }

    .dataTables_filter {
        margin: 0;
    }

    .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.82rem;
        color: var(--text-muted);
        font-weight: 600;
        margin: 0;
    }

    .dataTables_filter input {
        border: 1px solid #e3e5ef;
        border-radius: 0.6rem;
        padding: 0.5rem 0.85rem;
        font-size: 0.85rem;
        background-color: #fbfbfe;
        width: 240px;
    }

    .dataTables_filter input:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        background-color: #fff;
    }
</style>

<div id="toastContainer"></div>

<div class="search-page">
    <div class="container-fluid">

        <div class="search-breadcrumb">Search &rsaquo; <span class="current">Search F325</span></div>
        <h1 class="search-title">Search F325</h1>

        <div class="filter-card">
            <form id="searchForm" onsubmit="return runSearch(event);">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label-modern">Search</label>
                        <div class="omni-search-wrap">
                            <div class="omni-search-box" id="omniSearchBox">
                                <input type="text" id="omniInput" autocomplete="off"
                                    placeholder="Type an F325 number or branch name/code…">
                            </div>
                            <div class="omni-suggest" id="omniSuggest"></div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 align-items-end mt-1">
                    <div class="col-md-3">
                        <label class="form-label-modern">Retailer</label>
                        <select class="form-select-modern" name="retailer" id="retailerFilter">
                            <option value="all">All retailers</option>
                            <?php foreach ($user_retailers as $r): ?>
                                <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-modern">Company</label>
                        <select class="form-select-modern" name="company" id="companyFilter">
                            <option value="all">All companies</option>
                            <?php foreach ($permitted_companies as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-modern">Location</label>
                        <select class="form-select-modern" name="location" id="locationFilter">
                            <option value="all">All locations</option>
                            <?php foreach ($permitted_locations as $l): ?>
                                <option value="<?php echo htmlspecialchars($l); ?>"><?php echo htmlspecialchars($l); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-modern">Status</label>
                        <select class="form-select-modern" name="status" id="statusFilter">
                            <option value="all">All statuses</option>
                            <option value="cleared">Cleared</option>
                            <option value="disposed">Disposed</option>
                            <option value="open">Open</option>
                            <option value="printed">Printed</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                </div>

                <div class="filter-hint-row">
                    <div class="field-hint mb-0">
                        Type a value, pick what to search it as, then add as many as you need before searching.
                        Results are always limited to the retailers, companies, and locations you have access to.
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-clear-modern" id="clearFiltersBtn" onclick="clearFilters()">
                            <i class="fas fa-rotate-left me-1"></i> Clear filters
                        </button>
                        <button type="submit" class="btn-brand" id="searchBtn">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="results-card" id="resultsCard" style="display:none;">
            <div class="results-card-header">
                <span id="resultsHeaderMain">Results</span>
                <span class="sub text-warning" id="resultsHeaderSub"></span>
            </div>

            <!-- Skeleton preloader: shown while each page of results is being fetched -->
            <div id="searchSkeleton" style="display:none;">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="skeleton-row">
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar"></div>
                        <div class="skeleton-bar" style="width:60%;"></div>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="table-responsive" id="tableWrapper" style="display:none;">
                <table id="resultsTable" class="results-table" width="100%">
                    <thead>
                        <tr>
                            <th>Email Date</th>
                            <th>Document No.</th>
                            <th>Branch</th>
                            <th>Retailer</th>
                            <th>View</th>
                            <th>Status / Bypass</th>
                            <th>Re-print</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="results-card" id="promptCard">
            <div class="prompt-state">
                <i class="fas fa-search"></i>
                Enter an F325 number and/or branch above and click Search to see results.
            </div>
        </div>

    </div>
</div>

<?php
include_once("footer.php");
?>

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script>
    // Whether the current user is allowed to bypass (mirrors $_SESSION['clearing'] server-side).
    var CAN_CLEAR = <?php echo (int) (($_SESSION['clearing'] ?? 0) == '1' ? 1 : 0); ?>;

    var resultsTable = null;

    // ---- Toast notification helper (same look/behavior as product-list.php) ----
    function showToast(message, type) {
        // type: 'success' | 'danger' | 'warning'
        var existing = document.getElementById('appToast');
        if (existing) {
            existing.remove();
        }

        var variantClass = type === 'danger' ? 'app-toast-error' : (type === 'warning' ? 'app-toast-warning' : '');
        var iconClass = type === 'success' ? 'fa-check' : 'fa-triangle-exclamation';

        var toast = document.createElement('div');
        toast.id = 'appToast';
        toast.className = 'app-toast ' + variantClass;
        toast.innerHTML =
            '<span class="app-toast-icon"><i class="fas ' + iconClass + '"></i></span>' +
            '<span></span>' +
            '<button type="button" class="app-toast-close"><i class="fas fa-xmark"></i></button>';
        toast.querySelector('span:nth-child(2)').textContent = message;
        toast.querySelector('.app-toast-close').addEventListener('click', function () {
            toast.remove();
        });

        document.getElementById('toastContainer').appendChild(toast);

        setTimeout(function () {
            if (!document.body.contains(toast)) return;
            toast.classList.add('hide');
            setTimeout(function () { toast.remove(); }, 400);
        }, 3500);
    }
    var SEARCH_FIELDS = [
        { key: 'f325number', label: 'F325 No' },
        { key: 'branch', label: 'Branch Name' },
        { key: 'branch', label: 'Branch Code' }
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
                '<button type="button" aria-label="Remove"><i class="fas fa-xmark"></i></button>';
            el.querySelector('button').addEventListener('click', function () {
                chips.splice(idx, 1);
                renderChips();
            });
            omniBox.insertBefore(el, omniInput);
        });
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
                // mousedown (not click) so it fires before the input's blur event
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

    function clearFilters() {
        chips = [];
        renderChips();
        omniInput.value = '';
        omniSuggest.classList.remove('show');
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('locationFilter').value = 'all';
        document.getElementById('retailerFilter').value = 'all';
        document.getElementById('companyFilter').value = 'all';

        document.getElementById('resultsCard').style.display = 'none';
        document.getElementById('promptCard').style.display = '';

        if (resultsTable !== null) {
            resultsTable.destroy();
            $('#resultsTable tbody').empty();
            resultsTable = null;
        }
    }

    function runSearch(e) {
        e.preventDefault();

        // If the user typed something but never picked a suggestion, treat it
        // as an F325 No search by default rather than silently dropping it.
        var leftover = omniInput.value.trim();
        if (leftover !== '') {
            addChip(SEARCH_FIELDS[0], leftover);
        }

        if (chips.length === 0) {
            showToast('Please enter an F325 number and/or a branch name/code to search.', 'warning');
            return false;
        }

        var f325number = chipsToParam('f325number');
        var branch = chipsToParam('branch');

        document.getElementById('promptCard').style.display = 'none';
        document.getElementById('resultsCard').style.display = '';
        document.getElementById('resultsHeaderMain').textContent =
            document.getElementById('statusFilter').selectedOptions[0].text + ' F325 List';

        var subParts = chips.map(function (c) { return c.label + ': ' + c.value; });
        var retailerText = document.getElementById('retailerFilter').selectedOptions[0].text;
        var companyText = document.getElementById('companyFilter').selectedOptions[0].text;
        if (document.getElementById('retailerFilter').value !== 'all') {
            subParts.push('Retailer: ' + retailerText);
        }
        if (document.getElementById('companyFilter').value !== 'all') {
            subParts.push('Company: ' + companyText);
        }
        subParts.push('Loc: ' + document.getElementById('locationFilter').selectedOptions[0].text);
        document.getElementById('resultsHeaderSub').textContent = ' | ' + subParts.join(' / ');

        if (resultsTable === null) {
            initResultsTable();
        } else {
            resultsTable.draw();
        }

        return false;
    }

    function initResultsTable() {
        resultsTable = $('#resultsTable').DataTable({
            processing: false, // we drive our own skeleton instead of DataTables' default overlay
            serverSide: true,
            searching: true, // enables the built-in DataTables quick-search box, wired to the server below
            ajax: {
                url: 'search_process.php',
                type: 'GET',
                data: function (d) {
                    d.f325number = chipsToParam('f325number');
                    d.branch = chipsToParam('branch');
                    d.status = document.getElementById('statusFilter').value;
                    d.location = document.getElementById('locationFilter').value;
                    d.retailer = document.getElementById('retailerFilter').value;
                    d.company = document.getElementById('companyFilter').value;
                    // d.search.value is already populated by DataTables from the quick-search box above.
                }
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            dom: '<"dt-toolbar d-flex justify-content-end"f>t<"dt-pagination-wrap d-flex justify-content-between align-items-center"ip>',
            language: {
                emptyTable: 'No F325 records found for this search.',
                zeroRecords: 'No F325 records found for this search.',
                search: '',
                searchPlaceholder: 'Quick search within these results…'
            },
            columns: [
                { data: 'emaildate' },
                { data: 'f325number' },
                {
                    data: null,
                    orderable: false,
                    render: function (row) {
                        if (row.branchname) {
                            return row.brcode + (row.branchname ? ' - ' + row.branchname : '');
                        }
                        return row.brcode || '';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (row) {
                        return '<a href="javascript:void(0)" class="row-action-btn view" ' +
                            'onclick="window.open(\'view_f325.php?f325number=' + encodeURIComponent(row.f325number) + '\')">' +
                            '<i class="fas fa-eye"></i> View</a>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (row) {
                        var status = (row.status || '').toUpperCase();
                        if (CAN_CLEAR && ['OPEN', 'SCHEDULED', 'PRINTED'].indexOf(status) !== -1) {
                            return '<a href="javascript:void(0)" class="row-action-btn bypass" ' +
                                'onclick="window.open(\'view_scheduled.php?f325number=' + encodeURIComponent(row.f325number) +
                                '&emaildate=' + encodeURIComponent(row.emaildate) +
                                '&company=' + encodeURIComponent(row.vendor) + '\')">' +
                                '<i class="fas fa-forward"></i> Bypass</a>';
                        } else if (status === 'CLEARED') {
                            return '<span class="badge-status badge-cleared">Cleared</span>';
                        } else {
                            return '<span class="badge-status badge-disposed">Disposed</span>';
                        }
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (row) {
                        return '<a href="javascript:void(0)" class="row-action-btn reprint" ' +
                            'onclick="window.open(\'print-notepad-details.php?f325number=' + encodeURIComponent(row.f325number) +
                            '&action=RE-PRINT\')"><i class="fas fa-print"></i> Re-print</a>';
                    }
                }
            ]
        });

        // Toggle skeleton vs. table on every AJAX round-trip (initial load + pagination + sort + quick search).
        $('#resultsTable')
            .on('preXhr.dt', function () {
                document.getElementById('tableWrapper').style.display = 'none';
                document.getElementById('searchSkeleton').style.display = '';
                document.getElementById('searchBtn').disabled = true;
            })
            .on('xhr.dt', function () {
                document.getElementById('searchSkeleton').style.display = 'none';
                document.getElementById('tableWrapper').style.display = '';
                document.getElementById('searchBtn').disabled = false;
            })
            .on('error.dt', function () {
                document.getElementById('searchSkeleton').style.display = 'none';
                document.getElementById('tableWrapper').style.display = '';
                document.getElementById('searchBtn').disabled = false;
                showToast('Something went wrong while searching. Please try again.', 'danger');
            });
    }
</script>