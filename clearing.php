<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION['fname'] ?? '';

// Delete this user's stale cleared_list rows — moved after the auth check
// (it used to run unconditionally, before we even knew who was logged in).
$del_stmt = $conn->prepare("DELETE FROM cleared_list WHERE user = ?");
$del_stmt->bind_param("s", $username);
$del_stmt->execute();
$del_stmt->close();

$res = mysqli_query($conn, "SELECT * FROM tbl_users WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);

// ---- Permission scope (same pattern as print-notepad.php / load-notepad-list.php) ----
$user_id = (string) ($_SESSION['id'] ?? '');
$scope_retailers = [];
$scope_locations = [];
$scope_companies = []; // vendorcodes

function build_clearing_placeholders($count)
{
    return implode(',', array_fill(0, $count, '?'));
}

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
sort($scope_companies);

// Companies for the filter dropdown — scoped to what this user can see,
// not every active company in the system.
$company_options = [];
if (!empty($scope_companies)) {
    $placeholders = build_clearing_placeholders(count($scope_companies));
    $stmt = $conn->prepare("SELECT vendorcode, name FROM tbl_company WHERE active = 1 AND vendorcode IN ($placeholders) ORDER BY name ASC");
    $stmt->bind_param(str_repeat('s', count($scope_companies)), ...$scope_companies);
    $stmt->execute();
    $comp_result = $stmt->get_result();
    while ($row = $comp_result->fetch_assoc()) {
        $company_options[] = $row;
    }
    $stmt->close();
}

// Branches for the filter dropdown — scoped to the user's permitted
// retailers + locations, not every branch in tbl_census.
$branch_options = [];
if (!empty($scope_retailers) && !empty($scope_locations)) {
    $retailerPlaceholders = build_clearing_placeholders(count($scope_retailers));
    $upperLocations = array_map('strtoupper', $scope_locations);
    $locationPlaceholders = build_clearing_placeholders(count($upperLocations));
    $stmt = $conn->prepare(
        "SELECT code, branchname FROM tbl_census
         WHERE retailer IN ($retailerPlaceholders) AND UPPER(TRIM(location)) IN ($locationPlaceholders)
         ORDER BY branchname ASC"
    );
    $types = str_repeat('s', count($scope_retailers)) . str_repeat('s', count($upperLocations));
    $params = array_merge($scope_retailers, $upperLocations);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $branch_result = $stmt->get_result();
    while ($row = $branch_result->fetch_assoc()) {
        $branch_options[] = $row;
    }
    $stmt->close();
}

// ---- Stat tile count — scoped, not global ----
$scheduled_count = 0;
if (!empty($scope_retailers) && !empty($scope_locations) && !empty($scope_companies)) {
    $sql = "SELECT COUNT(*) AS c FROM tbl_f325number
            WHERE status = 'SCHEDULED' AND emaildate BETWEEN '2025-01-01' AND NOW()";
    $types = "";
    $params = [];

    $placeholders = build_clearing_placeholders(count($scope_retailers));
    $sql .= " AND retailer IN ($placeholders)";
    $types .= str_repeat('s', count($scope_retailers));
    $params = array_merge($params, $scope_retailers);

    $placeholders = build_clearing_placeholders(count($scope_companies));
    $sql .= " AND vendor IN ($placeholders)";
    $types .= str_repeat('s', count($scope_companies));
    $params = array_merge($params, $scope_companies);

    $upperLocations = array_map('strtoupper', $scope_locations);
    $placeholders = build_clearing_placeholders(count($upperLocations));
    $sql .= " AND UPPER(TRIM(location)) IN ($placeholders)";
    $types .= str_repeat('s', count($upperLocations));
    $params = array_merge($params, $upperLocations);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $scheduled_count = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
}

// ---- Only trust a selected company/branch if it's actually in scope ----
$selected_company = '';
$selected_brcode = '';
if (isset($_POST['view'])) {
    $requested_company = trim($_POST['company'] ?? 'all');
    if ($requested_company !== 'all' && in_array($requested_company, $scope_companies, true)) {
        $selected_company = $requested_company;
    }

    $requested_brcode = trim($_POST['brcode'] ?? 'all');
    $branch_codes_in_scope = array_column($branch_options, 'code');
    if ($requested_brcode !== 'all' && in_array($requested_brcode, $branch_codes_in_scope, false)) {
        $selected_brcode = $requested_brcode;
    }
}

// ---- Build the scoped results query ----
$rows = [];
if (!empty($scope_retailers) && !empty($scope_locations) && !empty($scope_companies)) {
    $sql = "SELECT f.f325number, f.emaildate, f.datesched, f.verificationdate, f.location, f.vendor, f.brcode,
                   c.name AS company_name, b.branchname
            FROM tbl_f325number f
            LEFT JOIN tbl_company c ON c.vendorcode = f.vendor
            LEFT JOIN tbl_census b ON b.code = f.brcode AND b.retailer = f.retailer
            WHERE f.status = 'scheduled' AND f.emaildate BETWEEN '2025-01-01' AND NOW()";
    $types = "";
    $params = [];

    $placeholders = build_clearing_placeholders(count($scope_retailers));
    $sql .= " AND f.retailer IN ($placeholders)";
    $types .= str_repeat('s', count($scope_retailers));
    $params = array_merge($params, $scope_retailers);

    $company_scope = $selected_company !== '' ? [$selected_company] : $scope_companies;
    $placeholders = build_clearing_placeholders(count($company_scope));
    $sql .= " AND f.vendor IN ($placeholders)";
    $types .= str_repeat('s', count($company_scope));
    $params = array_merge($params, $company_scope);

    $upperLocations = array_map('strtoupper', $scope_locations);
    $placeholders = build_clearing_placeholders(count($upperLocations));
    $sql .= " AND UPPER(TRIM(f.location)) IN ($placeholders)";
    $types .= str_repeat('s', count($upperLocations));
    $params = array_merge($params, $upperLocations);

    if ($selected_brcode !== '') {
        $sql .= " AND f.brcode = ?";
        $types .= "s";
        $params[] = $selected_brcode;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}

$flash = null;
if (!empty($_GET['status'])) {
    switch ($_GET['status']) {
        case 'succ':
            $flash = ['type' => 'success', 'icon' => 'fa-check', 'msg' => 'F325 cleared successfully.'];
            break;
        case 'verify':
            $flash = ['type' => 'success', 'icon' => 'fa-check', 'msg' => 'F325 sent for verification.'];
            break;
        case 'dispose':
            $flash = ['type' => 'success', 'icon' => 'fa-check', 'msg' => 'F325 disposed successfully.'];
            break;
        case 'err':
            $flash = ['type' => 'danger', 'icon' => 'fa-triangle-exclamation', 'msg' => 'No data encoded.'];
            break;
    }
}
?>

<?php include_once("nav.php"); ?>

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

    .clearing-page {
        font-family: 'Inter', -apple-system, sans-serif;
        background-color: var(--page-bg);
        min-height: 100vh;
        padding: 2rem 1.5rem;
    }

    .clearing-breadcrumb {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }

    .clearing-breadcrumb .current {
        color: var(--ink);
        font-weight: 600;
    }

    .clearing-title {
        font-weight: 700;
        font-size: 1.6rem;
        color: var(--ink);
        margin: 0 0 1.5rem;
    }

    /* ---- KPI tile ---- */
    .clearing-stat-tile {
        background: var(--surface);
        border: 1px solid var(--line);
        border-top: 3px solid var(--amber);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .clearing-stat-tile .stat-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        font-weight: 700;
        margin-bottom: 0.3rem;
    }

    .clearing-stat-tile .stat-num {
        font-weight: 800;
        font-size: 1.8rem;
        color: var(--ink);
        line-height: 1;
    }

    .clearing-stat-tile .stat-meta {
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-top: 0.4rem;
    }

    .clearing-stat-tile .stat-meta a {
        color: var(--brand);
        font-weight: 600;
        text-decoration: none;
    }

    .clearing-stat-tile .stat-meta a:hover {
        text-decoration: underline;
    }

    .clearing-stat-tile .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.85rem;
        background: var(--amber-soft);
        color: var(--amber);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    /* ---- Filter + results cards ---- */
    .clearing-filter-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .clearing-page .form-label-modern {
        font-weight: 600;
        font-size: 0.78rem;
        color: #3d3f4d;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        margin-bottom: 0.35rem;
        display: block;
    }

    .clearing-page .form-select,
    .clearing-page select.select2-hidden-accessible + span .select2-selection {
        border: 1px solid #e3e5ef !important;
        border-radius: 0.6rem !important;
        padding: 0.55rem 0.85rem !important;
        height: auto !important;
        font-size: 0.9rem !important;
        background-color: #fbfbfe !important;
    }

    .clearing-page .form-select:focus {
        border-color: var(--brand) !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        background-color: #fff !important;
    }

    .clearing-page .btn-brand {
        background-color: var(--brand) !important;
        border-color: var(--brand) !important;
        color: #fff !important;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.6rem 1.3rem;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
    }

    .clearing-page .btn-brand:hover {
        background-color: #4338ca !important;
        border-color: #4338ca !important;
        color: #fff !important;
    }

    .clearing-results-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        overflow: hidden;
    }

    .clearing-page table.results-table {
        width: 100%;
        margin: 0;
    }

    .clearing-page table.results-table thead th {
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

    .clearing-page table.results-table tbody td {
        padding: 0.9rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid #f2f3f8;
        font-size: 0.88rem;
        color: var(--ink);
    }

    .clearing-page table.results-table tbody tr.row-flagged {
        background-color: var(--amber-soft);
    }

    .clearing-page .days-chip {
        display: inline-block;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-weight: 600;
        font-size: 0.78rem;
        background: var(--danger-soft);
        color: var(--danger);
    }

    .clearing-page .row-action-btn {
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
        cursor: pointer;
    }

    .clearing-page .row-action-btn:hover {
        background-color: var(--brand-light);
        color: var(--brand);
        border-color: var(--brand-light);
    }

    .clearing-page .verif-date {
        color: var(--amber);
        font-weight: 600;
    }

    .clearing-page .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-muted);
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

    .select2-container .select2-selection--single {
        height: auto !important;
    }

    .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
        line-height: 1.6 !important;
        padding-top: 2px;
    }
</style>

<?php if ($flash): ?>
    <div id="server-toast" class="app-toast <?php echo $flash['type'] === 'danger' ? 'app-toast-error' : ''; ?>">
        <span class="app-toast-icon"><i class="fas <?php echo $flash['icon']; ?>"></i></span>
        <span><?php echo htmlspecialchars($flash['msg']); ?></span>
        <button type="button" class="app-toast-close" onclick="document.getElementById('server-toast').remove();">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <script>
        setTimeout(function () {
            var toast = document.getElementById('server-toast');
            if (toast) {
                toast.classList.add('hide');
                setTimeout(function () { toast.remove(); }, 400);
            }
        }, 3500);
    </script>
<?php endif; ?>

<div class="clearing-page">
    <div class="container-fluid">

        <div class="clearing-breadcrumb">F325 Modules &rsaquo; <span class="current">Clearing</span></div>
        <h1 class="clearing-title">Clearing</h1>

        <div class="clearing-stat-tile">
            <div>
                <div class="stat-label">Total F325 For Clear Status</div>
                <div class="stat-num"><?php echo number_format($scheduled_count); ?></div>
                <div class="stat-meta">as of <?php echo date("h:i A"); ?> &middot; <a href="cleared.php">F325 Cleared</a></div>
            </div>
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        </div>

        <form method="POST" action="clearing.php">
            <div class="clearing-filter-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label-modern">Company</label>
                        <select class="form-select" name="company" required>
                            <option value="all">All companies</option>
                            <?php foreach ($company_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option['vendorcode']); ?>"
                                    <?php echo $selected_company === $option['vendorcode'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-modern">Branch</label>
                        <select class="form-control branchcode" name="brcode" id="search_code" required>
                            <option value="all">All branches</option>
                            <?php foreach ($branch_options as $option): ?>
                                <option value="<?php echo htmlspecialchars($option['code']); ?>"
                                    <?php echo $selected_brcode === $option['code'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($option['code'] . ' - ' . $option['branchname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="view" class="btn-brand w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="clearing-results-card">
            <div class="table-responsive">
                <table class="results-table" id="dataTable" width="100%">
                    <thead>
                        <tr>
                            <th>Sched Date</th>
                            <th>Document No.</th>
                            <th>Company</th>
                            <th>Branch</th>
                            <th>Location</th>
                            <th>Days From Sched</th>
                            <th>Clear F325</th>
                            <th>For Verif.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="8" class="empty-state">No scheduled F325 records found for this filter.</td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $now = new DateTime(date('Y-m-d'));
                            foreach ($rows as $row):
                                $datetime1 = new DateTime($row['datesched']);
                                $diff = $datetime1->diff($now)->format('%a');
                                $is_flagged = !empty($row['verificationdate']) && $row['verificationdate'] !== '0000-00-00';
                                ?>
                                <tr class="<?php echo $is_flagged ? 'row-flagged' : ''; ?>">
                                    <td><?php echo htmlspecialchars($row['datesched']); ?></td>
                                    <td><?php echo htmlspecialchars($row['f325number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['company_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['branchname'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td><span class="days-chip"><?php echo (int) $diff; ?> Days</span></td>
                                    <td>
                                        <a class="row-action-btn"
                                            onclick="window.open('view_scheduled.php?f325number=<?php echo urlencode($row['f325number']); ?>&emaildate=<?php echo urlencode($row['emaildate']); ?>&company=<?php echo urlencode($row['vendor']); ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                    <td class="verif-date">
                                        <?php echo $is_flagged ? htmlspecialchars($row['verificationdate']) : ''; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include_once("footer.php"); ?>

</body>

</html>
<script>
    $(document).ready(function () {
        $('#search_code').select2({
            theme: "bootstrap"
        });
    });
</script>