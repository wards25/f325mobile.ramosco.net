<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
}
$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);

$user_id = (string) intval($_SESSION['id']);

$user_retailers = [];
$stmt = $conn->prepare("SELECT DISTINCT retailer FROM tbl_permission WHERE user_id = ? AND retailer != '' ORDER BY retailer ASC");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$retailer_result = $stmt->get_result();
while ($row = $retailer_result->fetch_assoc()) {
    $user_retailers[] = $row['retailer'];
}
$stmt->close();

// Only trust a retailer value the user actually has a scope for.
$selected_retailer = isset($_GET['retailer']) ? trim($_GET['retailer']) : '';
if ($selected_retailer !== '' && !in_array($selected_retailer, $user_retailers, true)) {
    $selected_retailer = '';
}

// Companies this user is scoped to under the selected retailer. Used to filter the
// two tables below that carry a company-like field (dbcompany.nickname / dbpullout.company).
$permitted_companies = [];
if ($selected_retailer !== '') {
    $stmt = $conn->prepare("SELECT DISTINCT company_name FROM tbl_permission WHERE user_id = ? AND retailer = ? AND company_name != ''");
    $stmt->bind_param("ss", $user_id, $selected_retailer);
    $stmt->execute();
    $company_result = $stmt->get_result();
    while ($row = $company_result->fetch_assoc()) {
        $permitted_companies[] = $row['company_name'];
    }
    $stmt->close();
}

function build_in_placeholders($count)
{
    return implode(',', array_fill(0, $count, '?'));
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
        min-width: 190px;
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
                <select name="retailer" id="retailerFilter" onchange="document.getElementById('retailerFilterForm').submit();">
                    <option value="">All retailers</option>
                    <?php foreach ($user_retailers as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= $selected_retailer === $r ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r) ?>
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
                    <div class="stat-num">
                        <?php
                        $open_query = mysqli_query($conn, "SELECT * FROM dbf325number WHERE status = 'open' AND emaildate BETWEEN '2025-01-01' AND NOW()");
                        $open_count = mysqli_num_rows($open_query);
                        echo number_format($open_count);
                        ?>
                    </div>
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
                    <div class="stat-num">
                        <?php
                        $scheduled_query = mysqli_query($conn, "SELECT * FROM dbf325number WHERE status = 'scheduled' AND emaildate BETWEEN '2025-01-01' AND NOW()");
                        $scheduled_count = mysqli_num_rows($scheduled_query);
                        echo number_format($scheduled_count);
                        ?>
                    </div>
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
                    <div class="stat-num">
                        <?php
                        $cleared_query = mysqli_query($conn, "SELECT * FROM dbf325number WHERE status = 'cleared' AND emaildate BETWEEN '2025-01-01' AND NOW()");
                        $cleared_count = mysqli_num_rows($cleared_query);
                        echo number_format($cleared_count);
                        ?>
                    </div>
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
                    <div class="stat-num">
                        <?php
                        $printed_query = mysqli_query($conn, "SELECT * FROM dbf325number WHERE status = 'disposed' AND emaildate BETWEEN '2024-01-01' AND NOW()");
                        $printed_count = mysqli_num_rows($printed_query);
                        echo number_format($printed_count);
                        ?>
                    </div>
                    <div class="stat-meta">as of <?php echo date("h:i A"); ?> &middot; <a href="disposed.php">View</a>
                    </div>
                </div>
                <div class="stat-icon"><i class="fas fa-trash"></i></div>
            </div>
        </div>
    </div>

    <!-- Shortlanded Summary -->
    <div class="data-card mb-4">
        <div class="data-card-header">SHORTLANDED SUMMARY</div>
        <div class="table-responsive">
            <table id="shortlandedDataTable" class="table table-striped" width="100%" cellspacing="0">
                <thead class="text-center">
                    <tr>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Amount Unpaid</th>
                        <th>Days Unpaid</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    <?php
                    // Base query, filtered to the selected retailer's permitted companies when applicable.
                    $shortlanded_sql = "
                        SELECT
                            c.nickname,
                            f.location,
                            f.f325number,
                            SUM(sl.costextended) AS total_cost,
                            MAX(DATEDIFF(CURDATE(), sl.checkdate)) AS days_unpaid
                        FROM sl_list sl
                        INNER JOIN dbf325number f ON sl.f325no = f.f325number
                        INNER JOIN tbl_company c ON sl.vendor = c.vendorcode
                        WHERE sl.paymentstatus = 'UNPAID'
                    ";
                    $shortlanded_types = '';
                    $shortlanded_params = [];

                    if ($selected_retailer !== '') {
                        if (count($permitted_companies) > 0) {
                            $placeholders = build_in_placeholders(count($permitted_companies));
                            $shortlanded_sql .= " AND c.nickname IN ($placeholders)";
                            $shortlanded_types .= str_repeat('s', count($permitted_companies));
                            $shortlanded_params = array_merge($shortlanded_params, $permitted_companies);
                        } else {
                            // Retailer selected but this user has no company scopes under it — show nothing.
                            $shortlanded_sql .= " AND 1=0";
                        }
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
                    } else {
                        echo "<tr><td colspan='4'>No unpaid records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- End Shortlanded Summary -->

    <!-- Pull-out Aging Summary -->
    <div class="data-card mb-4" id="pulloutAgingTable">
        <div class="data-card-header-pullout">FOR PULL-OUT SUMMARY</div>
        <div class="table-responsive">
            <table id="pulloutDataTable" class="table table-striped" width="100%" cellspacing="0">
                <thead class="text-center">
                    <tr>
                        <th>Reference</th>
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
                        SELECT reference, principal, company, location, dateprocessed,
                               DATEDIFF(CURDATE(), dateprocessed) AS days_aging
                        FROM dbpullout
                        WHERE status = 'FOR PULL-OUT' and dateprocessed BETWEEN '2026-01-01' AND NOW()
                    ";
                    $pullout_types = '';
                    $pullout_params = [];

                    if ($selected_retailer !== '') {
                        if (count($permitted_companies) > 0) {
                            $placeholders = build_in_placeholders(count($permitted_companies));
                            $pullout_sql .= " AND company IN ($placeholders)";
                            $pullout_types .= str_repeat('s', count($permitted_companies));
                            $pullout_params = array_merge($pullout_params, $permitted_companies);
                        } else {
                            $pullout_sql .= " AND 1=0";
                        }
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
                                    <?php echo htmlspecialchars($row['dateprocessed']); ?></td>
                                <td data-order="<?php echo $days; ?>">
                                    <span class="aging-chip <?php echo $tone; ?>">
                                        <i class="fas <?php echo $icon; ?>"></i><?php echo $days; ?> days
                                    </span>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='6'>No pull-out records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- End Pull-out Aging Summary -->

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
        $('#shortlandedDataTable').DataTable($.extend({}, dtOptions, { order: [[3, 'asc']] }));
        $('#pulloutDataTable').DataTable($.extend({}, dtOptions, { order: [[5, 'asc']] }));
    });
</script>

</body>

</html>