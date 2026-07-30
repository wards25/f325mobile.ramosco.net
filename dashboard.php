<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
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

// Only trust a retailer value the user actually has a scope for.
$selected_retailer = isset($_GET['retailer']) ? trim($_GET['retailer']) : '';
if ($selected_retailer !== '' && !in_array($selected_retailer, $user_retailers, true)) {
    $selected_retailer = '';
}

$retailer_scope = $selected_retailer !== '' ? [$selected_retailer] : $user_retailers;
$permitted_companies = [];
$permitted_locations = [];
if (!empty($retailer_scope)) {
    $placeholders = build_in_placeholders(count($retailer_scope));
    $stmt = $conn->prepare("SELECT DISTINCT company_name, location_name FROM tbl_permission WHERE user_id = ? AND retailer IN ($placeholders)");
    $stmt->bind_param(str_repeat('s', count($retailer_scope) + 1), $user_id, ...$retailer_scope);
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

// Only trust a company value the user actually has a scope for.
$selected_company = isset($_GET['company']) ? trim($_GET['company']) : '';
if ($selected_company !== '' && !in_array($selected_company, $permitted_companies, true)) {
    $selected_company = '';
}

// Only trust a location value the user actually has a scope for.
$selected_location = isset($_GET['location']) ? trim($_GET['location']) : '';
if ($selected_location !== '' && !in_array($selected_location, $permitted_locations, true)) {
    $selected_location = '';
}

// Final scopes used by the queries below.
$company_scope = $selected_company !== '' ? [$selected_company] : $permitted_companies;
$location_scope = $selected_location !== '' ? [$selected_location] : $permitted_locations;

function count_f325_all($conn, $retailer_scope, $location_scope = [])
{
    $empty = ['open' => 0, 'scheduled' => 0, 'cleared' => 0, 'disposed' => 0];
    if (empty($retailer_scope)) {
        return $empty;
    }

    $placeholders = build_in_placeholders(count($retailer_scope));
    $sql = "SELECT
                SUM(CASE WHEN status = 'open' AND emaildate BETWEEN '2025-01-01' AND NOW() THEN 1 ELSE 0 END) AS open_c,
                SUM(CASE WHEN status = 'scheduled' AND emaildate BETWEEN '2025-01-01' AND NOW() THEN 1 ELSE 0 END) AS scheduled_c,
                SUM(CASE WHEN status = 'cleared' AND emaildate BETWEEN '2025-01-01' AND NOW() THEN 1 ELSE 0 END) AS cleared_c,
                SUM(CASE WHEN status = 'disposed' AND emaildate BETWEEN '2024-01-01' AND NOW() THEN 1 ELSE 0 END) AS disposed_c
            FROM tbl_f325number
            WHERE emaildate BETWEEN '2024-01-01' AND NOW()
              AND retailer IN ($placeholders)";
    $types = str_repeat('s', count($retailer_scope));
    $params = $retailer_scope;

    if (!empty($location_scope)) {
        $placeholders3 = build_in_placeholders(count($location_scope));
        $sql .= " AND UPPER(TRIM(location)) IN ($placeholders3)";
        $types .= str_repeat('s', count($location_scope));
        $params = array_merge($params, array_map(fn($v) => strtoupper(trim($v)), $location_scope));
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [
        'open' => (int) $row['open_c'],
        'scheduled' => (int) $row['scheduled_c'],
        'cleared' => (int) $row['cleared_c'],
        'disposed' => (int) $row['disposed_c'],
    ];
}

// Helper: turn a day count into an aging-chip tone
function agingTone($days)
{
    if ($days >= 30)
        return 'late';
    if ($days >= 15)
        return 'warn';
    return 'ok';
}

// Helper: matching Font Awesome icon for each tone
function agingIcon($tone)
{
    switch ($tone) {
        case 'late':
            return 'fa-exclamation-triangle';
        case 'warn':
            return 'fa-exclamation-triangle';
        default:
            return 'fa-check-circle';
    }
}

$f325_counts = count_f325_all($conn, $retailer_scope, $location_scope);
$open_count = $f325_counts['open'];
$scheduled_count = $f325_counts['scheduled'];
$cleared_count = $f325_counts['cleared'];
$printed_count = $f325_counts['disposed'];
?>

<?php
include_once("nav.php");
?>

<style>
    :root {
        --ink: #0F2A3D;
        --teal: #1D7874;
        --amber: #E8A33D;
        --coral: #D64550;
        --green: #2E9E6D;
        --blue: #2E6E9E;
        --slate: #435363;
        --bg-soft: #F5F7F9;
        --line: #E3E8EC;
    }

    /* Apply Inter everywhere EXCEPT Font Awesome icons, which need their own icon font */
    .aging-dashboard,
    .aging-dashboard *:not(.fas):not(.far):not(.fab):not(.fa) {
        font-family: 'Inter', sans-serif;
    }

    .aging-dashboard h1,
    .aging-dashboard .data-card-header,
    .aging-dashboard .stat-label {
        font-family: 'Manrope', sans-serif;
    }

    .aging-dashboard .stat-num,
    .aging-dashboard .aging-chip {
        font-family: 'IBM Plex Mono', monospace;
    }

    .aging-dashboard {
        background: var(--bg-soft);
        padding: 1.75rem;
        border-radius: 14px;
    }

    .page-title {
        font-weight: 800;
        color: var(--ink);
        letter-spacing: -0.02em;
        font-family: 'Manrope', sans-serif;
    }

    .page-subtitle {
        color: var(--slate);
        font-size: .9rem;
        margin-top: -.35rem;
    }

    /* ---- Retailer filter ---- */
    .retailer-filter {
        display: flex;
        align-items: center;
        gap: .55rem;
        flex-wrap: wrap;
    }

    .retailer-filter label {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--slate);
        font-weight: 700;
        margin-bottom: 0;
        white-space: nowrap;
    }

    .retailer-filter select {
        border: 1px solid var(--line);
        border-radius: 8px;
        padding: .4rem .7rem;
        font-size: .85rem;
        color: var(--ink);
        background: #fff;
        min-width: 170px;
    }

    .retailer-filter select:focus {
        outline: 2px solid var(--teal);
        outline-offset: 1px;
    }

    /* ---- KPI tiles ---- */
    .stat-tile {
        background: #fff;
        border: 1px solid var(--line);
        border-top: 3px solid var(--accent, var(--teal));
        border-radius: 10px;
        padding: 1.1rem 1.35rem;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .stat-tile:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(15, 42, 61, .08);
    }

    .stat-tile .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 9px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--accent-soft, rgba(29, 120, 116, .1));
        color: var(--accent, var(--teal));
        font-size: 1.05rem;
    }

    .stat-tile .stat-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--slate);
        font-weight: 700;
        margin-bottom: .3rem;
    }

    .stat-tile .stat-num {
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .stat-tile .stat-meta {
        font-size: .72rem;
        color: #8b98a5;
        margin-top: .4rem;
    }

    .stat-tile .stat-meta a {
        color: var(--teal);
        text-decoration: none;
        font-weight: 600;
    }

    .stat-tile .stat-meta a:hover {
        text-decoration: underline;
    }

    .stat-tile.tone-warning {
        --accent: var(--amber);
        --accent-soft: rgba(232, 163, 61, .12);
    }

    .stat-tile.tone-primary {
        --accent: var(--blue);
        --accent-soft: rgba(46, 110, 158, .1);
    }

    .stat-tile.tone-success {
        --accent: var(--green);
        --accent-soft: rgba(46, 158, 109, .1);
    }

    .stat-tile.tone-danger {
        --accent: var(--coral);
        --accent-soft: rgba(214, 69, 80, .1);
    }

    .stat-tile.tone-info {
        --accent: var(--teal);
        --accent-soft: rgba(29, 120, 116, .1);
    }

    /* ---- data cards / tables ---- */
    .data-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
    }

    .data-card .data-card-header {
        background: var(--ink);
        color: #fff;
        padding: .85rem 1.25rem;
        font-weight: 700;
        font-size: .95rem;
        letter-spacing: .01em;
        text-align: left;
    }

    .data-card .data-card-header-pullout {
        background: var(--blue);
        color: #fff;
        padding: .85rem 1.25rem;
        font-weight: 700;
        font-size: .95rem;
        letter-spacing: .01em;
        text-align: left;
    }

    .data-card .table {
        margin-bottom: 0;
    }

    .data-card thead th {
        background: #EDF1F3;
        color: var(--slate);
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 700;
        border-bottom: 2px solid var(--line);
    }

    /* ---- table tabs (Shortlanded / Pull-out) ---- */
    .table-tabs {
        display: flex;
        align-items: center;
        gap: 1.75rem;
        padding: 1rem 1.25rem 0;
        border-bottom: 1px solid var(--line);
        background: #fff;
    }

    .table-tab {
        border: none;
        background: none;
        padding: 0 0 .85rem;
        font-size: .88rem;
        font-weight: 600;
        color: #9aa5b1;
        border-bottom: 2px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        cursor: pointer;
    }

    .table-tab i {
        font-size: .82rem;
    }

    .table-tab:hover {
        color: var(--slate);
    }

    .table-tab.active {
        color: var(--ink);
        border-bottom-color: var(--blue);
    }

    .table-tab-pane {
        display: none;
    }

    .table-tab-pane.active {
        display: block;
    }

    .data-card tbody td {
        vertical-align: middle;
        color: var(--ink);
        font-size: .87rem;
    }

    .data-card tbody tr:hover {
        background: #F8FAFB;
    }

    /* ---- signature element: aging chip (icon + day count) ---- */
    .aging-chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .25rem .7rem;
        border-radius: 999px;
        font-weight: 600;
        font-size: .8rem;
        white-space: nowrap;
    }

    .aging-chip i {
        font-size: .85rem;
    }

    .aging-chip.ok {
        background: rgba(46, 158, 109, .12);
        color: #1e7a52;
    }

    .aging-chip.warn {
        background: rgba(232, 163, 61, .15);
        color: #96650f;
    }

    .aging-chip.late {
        background: rgba(214, 69, 80, .12);
        color: #a92f39;
    }

    /* ---- DataTables theming to match ---- */
    .aging-dashboard .dataTables_wrapper {
        padding: 1rem 1.25rem 1.25rem;
    }

    .aging-dashboard .dataTables_filter input,
    .aging-dashboard .dataTables_length select {
        border: 1px solid var(--line);
        border-radius: 6px;
        padding: .3rem .55rem;
        font-size: .85rem;
    }

    .aging-dashboard .dataTables_filter input:focus,
    .aging-dashboard .dataTables_length select:focus {
        outline: 2px solid var(--teal);
        outline-offset: 1px;
    }

    .aging-dashboard .dataTables_info {
        color: var(--slate);
        font-size: .8rem;
    }

    .aging-dashboard .dataTables_paginate .paginate_button {
        border-radius: 6px !important;
        margin-left: 2px;
        color: var(--ink) !important;
        border: 1px solid transparent !important;
    }

    .aging-dashboard .dataTables_paginate .paginate_button.current {
        background: var(--ink) !important;
        color: #fff !important;
        border-color: var(--ink) !important;
    }

    .aging-dashboard .dataTables_paginate .paginate_button:hover {
        background: var(--bg-soft) !important;
        color: var(--ink) !important;
    }

    .aging-dashboard table.dataTable thead th {
        position: relative;
    }

    /* ---- Skeleton loader ---- */
    .skeleton-wrap {
        padding: 1rem 1.25rem 1.25rem;
    }

    .skeleton-row {
        display: flex;
        gap: 1rem;
        padding: .65rem 0;
        border-bottom: 1px solid var(--line);
    }

    .skeleton-cell {
        height: 14px;
        border-radius: 4px;
        flex: 1;
        background: linear-gradient(90deg, #EDF1F3 25%, #F8FAFB 37%, #EDF1F3 63%);
        background-size: 400% 100%;
        animation: skeleton-loading 1.4s ease infinite;
    }

    @keyframes skeleton-loading {
        0% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0 50%;
        }
    }

    .table-responsive.is-loading .real-table {
        display: none;
    }

    .table-responsive:not(.is-loading) .skeleton-wrap {
        display: none;
    }
</style>

<!-- Begin Page Content -->
<div class="container-fluid aging-dashboard">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 page-title">Dashboard</h1>
        </div>
        <?php if (count($user_retailers) > 0): ?>
            <form method="GET" class="retailer-filter" id="retailerFilterForm">
                <label for="retailerFilter">Retailer</label>
                <select name="retailer" id="retailerFilter"
                    onchange="document.getElementById('retailerFilterForm').submit();">
                    <option value="">All retailers</option>
                    <?php foreach ($user_retailers as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= $selected_retailer === $r ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="companyFilter">Company</label>
                <select name="company" id="companyFilter"
                    onchange="document.getElementById('retailerFilterForm').submit();">
                    <option value="">All companies</option>
                    <?php foreach ($permitted_companies as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $selected_company === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="locationFilter">Location</label>
                <select name="location" id="locationFilter"
                    onchange="document.getElementById('retailerFilterForm').submit();">
                    <option value="">All locations</option>
                    <?php foreach ($permitted_locations as $l): ?>
                        <option value="<?= htmlspecialchars($l) ?>" <?= $selected_location === $l ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </div>

    <!-- Content Row -->
    <div class="row">

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-tile tone-warning">
                <div>
                    <div class="stat-label">F325 Open</div>
                    <div class="stat-num" data-count="<?php echo (int) $open_count; ?>">0</div>
                    <div class="stat-meta">as of <?php echo date("h:i A"); ?> &middot; <a
                            href="print-notepad.php">View</a></div>
                </div>
                <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-tile tone-primary">
                <div>
                    <div class="stat-label">F325 Scheduled</div>
                    <div class="stat-num" data-count="<?php echo (int) $scheduled_count; ?>">0</div>
                    <div class="stat-meta">as of <?php echo date("h:i A"); ?> &middot; <a href="scheduled.php">View</a>
                    </div>
                </div>
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-tile tone-success">
                <div>
                    <div class="stat-label">F325 Cleared</div>
                    <div class="stat-num" data-count="<?php echo (int) $cleared_count; ?>">0</div>
                    <div class="stat-meta">as of <?php echo date("h:i A"); ?> &middot; <a href="cleared.php">View</a>
                    </div>
                </div>
                <div class="stat-icon"><i class="fas fa-stamp"></i></div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-tile tone-danger">
                <div>
                    <div class="stat-label">F325 Disposed</div>
                    <div class="stat-num" data-count="<?php echo (int) $printed_count; ?>">0</div>
                    <div class="stat-meta">as of <?php echo date("h:i A"); ?> &middot; <a href="disposed.php">View</a>
                    </div>
                </div>
                <div class="stat-icon"><i class="fas fa-trash"></i></div>
            </div>
        </div>
    </div>

    <!-- Shortlanded / Pull-out (tabbed) -->
    <div class="data-card mb-4">
        <div class="data-card-header">AGING SUMMARY:
            <?php if ($selected_retailer !== ''): ?>
                <span class="text-warning">
                    <?= htmlspecialchars(strtoupper($selected_retailer)) ?>
                </span>
            <?php endif; ?>
            <?php if ($selected_company !== ''): ?>
                <span class="text-warning">
                    &middot; <?= htmlspecialchars(strtoupper($selected_company)) ?>
                </span>
            <?php endif; ?>
            <?php if ($selected_location !== ''): ?>
                <span class="text-warning">
                    &middot; <?= htmlspecialchars(strtoupper($selected_location)) ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="table-tabs">
            <button type="button" class="table-tab active" data-tab="shortlanded" onclick="switchDashboardTab(this)">
                <i class="fas fa-file-invoice-dollar"></i> Shortlanded
            </button>
            <button type="button" class="table-tab" data-tab="pullout" onclick="switchDashboardTab(this)">
                <i class="fas fa-dolly"></i> For pull-out
            </button>
        </div>

        <div class="table-tab-pane active" id="pane-shortlanded">
            <div class="table-responsive is-loading">
                <div class="skeleton-wrap">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="skeleton-row">
                            <div class="skeleton-cell" style="flex:0.8"></div>
                            <div class="skeleton-cell" style="flex:1.4"></div>
                            <div class="skeleton-cell" style="flex:1"></div>
                            <div class="skeleton-cell" style="flex:0.9"></div>
                            <div class="skeleton-cell" style="flex:1"></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <table id="shortlandedDataTable" class="table table-striped real-table" width="100%" cellspacing="0">
                    <thead class="text-center">
                        <tr>
                            <th>Retailer</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Amount Unpaid</th>
                            <th>Days Unpaid</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php
                        $shortlanded_sql = "
                        SELECT
                            c.nickname,
                            c.name,
                            f.location,
                            f.f325number,
                            f.retailer,
                            SUM(sl.costextended) AS total_cost,
                            MAX(DATEDIFF(CURDATE(), sl.checkdate)) AS days_unpaid
                        FROM sl_list sl
                        INNER JOIN tbl_f325number f ON sl.f325no = f.f325number
                        INNER JOIN tbl_company c ON sl.vendor = c.vendorcode
                        WHERE sl.paymentstatus = 'UNPAID'
                    ";
                        $shortlanded_types = '';
                        $shortlanded_params = [];

                        if (!empty($retailer_scope)) {
                            $placeholders = build_in_placeholders(count($retailer_scope));
                            $shortlanded_sql .= " AND c.retailer IN ($placeholders)";
                            $shortlanded_types .= str_repeat('s', count($retailer_scope));
                            $shortlanded_params = array_merge($shortlanded_params, $retailer_scope);
                        } else {
                            // No retailer access at all — show nothing.
                            $shortlanded_sql .= " AND 1=0";
                        }

                        if (!empty($company_scope)) {
                            $placeholders = build_in_placeholders(count($company_scope));
                            $shortlanded_sql .= " AND UPPER(TRIM(c.name)) IN ($placeholders)";
                            $shortlanded_types .= str_repeat('s', count($company_scope));
                            $shortlanded_params = array_merge($shortlanded_params, array_map(fn($v) => strtoupper(trim($v)), $company_scope));
                        } else {
                            $shortlanded_sql .= " AND 1=0";
                        }

                        if (!empty($location_scope)) {
                            $placeholders = build_in_placeholders(count($location_scope));
                            $shortlanded_sql .= " AND UPPER(TRIM(f.location)) IN ($placeholders)";
                            $shortlanded_types .= str_repeat('s', count($location_scope));
                            $shortlanded_params = array_merge($shortlanded_params, array_map(fn($v) => strtoupper(trim($v)), $location_scope));
                        } else {
                            $shortlanded_sql .= " AND 1=0";
                        }

                        $shortlanded_sql .= " GROUP BY sl.vendor, c.nickname, f.location, f.f325number ORDER BY days_unpaid DESC";

                        if ($shortlanded_types !== '') {
                            $stmt = $conn->prepare($shortlanded_sql);
                            $stmt->bind_param($shortlanded_types, ...$shortlanded_params);
                            $stmt->execute();
                            $summary_query = $stmt->get_result();
                        } else {
                            $summary_query = mysqli_query($conn, $shortlanded_sql);
                        }

                        if (!$summary_query) {
                            die("Query failed: " . mysqli_error($conn));
                        }

                        if (mysqli_num_rows($summary_query) > 0) {
                            while ($row = mysqli_fetch_assoc($summary_query)) {
                                $days = (int) $row['days_unpaid'];
                                $tone = agingTone($days);
                                $icon = agingIcon($tone);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['retailer'] ?? 'N/A'); ?></td>
                                    <td class="text-left"><?php echo htmlspecialchars($row['nickname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['location'] ?? 'N/A'); ?></td>
                                    <td data-order="<?php echo (float) $row['total_cost']; ?>">
                                        ₱<?php echo number_format($row['total_cost'], 2); ?></td>
                                    <td data-order="<?php echo $days; ?>">
                                        <span class="aging-chip <?php echo $tone; ?>">
                                            <i class="fas <?php echo $icon; ?>"></i><?php echo $days; ?> days
                                        </span>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Shortlanded pane -->

        <div class="table-tab-pane" id="pane-pullout">
            <div class="table-responsive is-loading">
                <div class="skeleton-wrap">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="skeleton-row">
                            <div class="skeleton-cell" style="flex:1"></div>
                            <div class="skeleton-cell" style="flex:1"></div>
                            <div class="skeleton-cell" style="flex:1"></div>
                            <div class="skeleton-cell" style="flex:1"></div>
                            <div class="skeleton-cell" style="flex:1"></div>
                            <div class="skeleton-cell" style="flex:0.8"></div>
                        </div>
                    <?php endfor; ?>
                </div>

                <table id="pulloutDataTable" class="table table-striped real-table" width="100%" cellspacing="0">
                    <thead class="text-center">
                        <tr>
                            <th>Batch Number</th>
                            <th>Principal</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Date Processed</th>
                            <th>Days Aging</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php
                        $pullout_sql = "
                        SELECT p.reference, p.principal, c.name AS company, c.nickname, p.location, p.dateprocessed,
                               DATEDIFF(CURDATE(), p.dateprocessed) AS days_aging
                        FROM tbl_pullout p
                        INNER JOIN tbl_company c ON p.company = c.vendorcode
                        WHERE p.status = 'FOR PULL-OUT' and p.dateprocessed BETWEEN '2026-01-01' AND NOW()
                    ";
                        $pullout_types = '';
                        $pullout_params = [];

                        if (!empty($company_scope)) {
                            $placeholders = build_in_placeholders(count($company_scope));
                            $pullout_sql .= " AND UPPER(TRIM(c.name)) IN ($placeholders)";
                            $pullout_types .= str_repeat('s', count($company_scope));
                            $pullout_params = array_merge($pullout_params, array_map(fn($v) => strtoupper(trim($v)), $company_scope));
                        } else {
                            $pullout_sql .= " AND 1=0";
                        }

                        if (!empty($location_scope)) {
                            $placeholders = build_in_placeholders(count($location_scope));
                            $pullout_sql .= " AND UPPER(TRIM(p.location)) IN ($placeholders)";
                            $pullout_types .= str_repeat('s', count($location_scope));
                            $pullout_params = array_merge($pullout_params, array_map(fn($v) => strtoupper(trim($v)), $location_scope));
                        } else {
                            $pullout_sql .= " AND 1=0";
                        }

                        $pullout_sql .= " ORDER BY days_aging DESC";

                        if ($pullout_types !== '') {
                            $stmt2 = $conn->prepare($pullout_sql);
                            $stmt2->bind_param($pullout_types, ...$pullout_params);
                            $stmt2->execute();
                            $aging_query = $stmt2->get_result();
                        } else {
                            $aging_query = mysqli_query($conn, $pullout_sql);
                        }

                        if (!$aging_query) {
                            die("Query failed: " . mysqli_error($conn));
                        }

                        if (mysqli_num_rows($aging_query) > 0) {
                            while ($row = mysqli_fetch_assoc($aging_query)) {
                                $days = (int) $row['days_aging'];
                                $tone = agingTone($days);
                                $icon = agingIcon($tone);
                                ?>
                                <tr>
                                    <td class="text-left"><?php echo htmlspecialchars($row['reference']); ?></td>
                                    <td class="text-left"><?php echo htmlspecialchars($row['principal']); ?></td>
                                    <td><?php echo htmlspecialchars($row['company']); ?></td>
                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td data-order="<?php echo strtotime($row['dateprocessed']); ?>">
                                        <?php echo htmlspecialchars($row['dateprocessed']); ?>
                                    </td>
                                    <td data-order="<?php echo $days; ?>">
                                        <span class="aging-chip <?php echo $tone; ?>">
                                            <i class="fas <?php echo $icon; ?>"></i><?php echo $days; ?> days
                                        </span>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- End Pull-out pane -->
    </div>
    <!-- End Shortlanded / Pull-out card -->

</div>
<!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

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
    if (typeof jQuery === 'undefined') {
        document.write('<script src="https://code.jquery.com/jquery-3.7.1.min.js"><\/script>');
    }
</script>
<script>
    $(function () {
        var dtOptions = {
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            language: {
                search: "",
                searchPlaceholder: "Search...",
                info: "Showing _START_–_END_ of _TOTAL_",
                paginate: { previous: "Prev", next: "Next" }
            }
        };

        function revealTable($wrapper) {
            $wrapper.removeClass('is-loading');
        }

        // Init immediately and reveal as soon as DataTables is ready
        // (no artificial delay — the skeleton shows only for as long as
        // actual render/init takes).
        if (!$.fn.dataTable.isDataTable('#shortlandedDataTable')) {
            $('#shortlandedDataTable').DataTable($.extend({}, dtOptions, { order: [[3, 'asc']] }));
        }
        revealTable($('#shortlandedDataTable').closest('.table-responsive'));

        if (!$.fn.dataTable.isDataTable('#pulloutDataTable')) {
            $('#pulloutDataTable').DataTable($.extend({}, dtOptions, { order: [[5, 'asc']] }));
        }
        revealTable($('#pulloutDataTable').closest('.table-responsive'));

        // ---- Count-up animation for KPI tiles ----
        $('.stat-num[data-count]').each(function () {
            var $el = $(this);
            var target = parseInt($el.attr('data-count'), 10) || 0;
            var duration = 900; // ms
            var start = null;

            function step(timestamp) {
                if (!start) start = timestamp;
                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3); // ease-out
                var current = Math.floor(eased * target);
                $el.text(current.toLocaleString());
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    $el.text(target.toLocaleString());
                }
            }
            requestAnimationFrame(step);
        });
    });

    function switchDashboardTab(el) {
        document.querySelectorAll('.table-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');

        const tab = el.dataset.tab;
        document.querySelectorAll('.table-tab-pane').forEach(p => p.classList.remove('active'));
        document.getElementById('pane-' + tab).classList.add('active');

        if (tab === 'shortlanded' && $.fn.dataTable.isDataTable('#shortlandedDataTable')) {
            $('#shortlandedDataTable').DataTable().columns.adjust().draw(false);
        }
        if (tab === 'pullout' && $.fn.dataTable.isDataTable('#pulloutDataTable')) {
            $('#pulloutDataTable').DataTable().columns.adjust().draw(false);
        }
    }
</script>

</body>

</html>