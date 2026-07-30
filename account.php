<?php
session_start();

include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

// Prepared statement — was previously string-concatenated with a session value.
$stmt = $conn->prepare("DELETE FROM cleared_list WHERE user = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->close();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$result = mysqli_query($conn, "SELECT user_type FROM tbl_users WHERE id = " . intval($_SESSION['id']));
$user = mysqli_fetch_assoc($result);

// if ($user['user_type'] !== 'Admin') {
//     header("Location: unauthorized_access.php");
//     exit;
// }

include_once("nav.php");
$module_groups = [
    "Data" => [
        "census" => "Census",
        "productlist" => "Product list",
        "storelist" => "Store list",
        "import_notepad" => "Import notepad",
        "import_rtv" => "Import RTV",
        "import_pu_charge" => "Import pull-out charge",
        "import_nestle_sku" => "Import Nestle SKU",
    ],
    "Processing" => [
        "print" => "Print",
        "schedule" => "Schedule",
        "clearing" => "Clearing",
        "shortlanded" => "Shortlanded",
        "manual" => "Manual",
        "pullout" => "Pull-out",
        "deduct" => "Deduction",
    ],
    "Admin" => [
        "report" => "Report",
        "borf" => "BORF",
        "settings" => "Settings",
    ],
];

// Retailers a user's access can be scoped to — now sourced from tbl_retailer.
$retailers = [];
$retailer_query = mysqli_query($conn, "SELECT * FROM tbl_retailer WHERE status = 1 ORDER BY retailer_name ASC");
while ($row = mysqli_fetch_assoc($retailer_query)) {
    $retailers[] = $row;
}

// Companies now carry their own retailer column so the Company dropdown
// can be filtered client-side to only the companies under the chosen retailer.
$companies = [];
$company_query = mysqli_query($conn, "SELECT id, name, retailer FROM tbl_company WHERE active = 1 ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($company_query)) {
    $companies[] = $row;
}

$locations = [];
$location_query = mysqli_query($conn, "SELECT id, location FROM tbl_location WHERE active = 1 ORDER BY location ASC");
if(!$location_query){
die(mysqli_error($conn));
}
while ($row = mysqli_fetch_assoc($location_query)) {
    $locations[] = $row;
}

// Stat cards
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM tbl_users"))['c'];
$active_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM tbl_users WHERE status = 1"))['c'];
$inactive_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM tbl_users WHERE status = 0"))['c'];

// Small helper: initials + a stable color for the avatar circle, and a role-specific pill class.
function user_initials($name)
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '')
            $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $initials !== '' ? $initials : '?';
}
function avatar_color($seed)
{
    $palette = ['#4f46e5', '#0ea5a4', '#d97706', '#db2777', '#2563eb', '#16a34a'];
    $index = crc32($seed) % count($palette);
    return $palette[$index];
}
function role_pill_class($user_type)
{
    switch ($user_type) {
        case 'Admin':
            return 'pill-role-admin';
        default:
            return 'pill-role-user';
    }
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --ink: #1c1c1e;
        --ink-muted: #6b6f76;
        --line: #e7e7ea;
        --surface: #ffffff;
        --canvas: #f7f7f8;
        --accent: #4f46e5;
        --accent-soft: #eef0fd;
        --ok: #12805c;
        --ok-soft: #e7f6f0;
        --danger: #b3261e;
        --danger-soft: #fbebea;
        --amber: #b45309;
        --amber-soft: #fdf1e0;
        --purple: #6d28d9;
        --purple-soft: #f1ecfd;
        --radius: 14px;
    }

    body {
        background: var(--canvas);
        font-family: 'Inter', -apple-system, sans-serif;
        color: var(--ink);
    }

    .accounts-wrap {
        max-width: 1200px;
    }

    .breadcrumb-row {
        font-size: 0.85rem;
        color: var(--ink-muted);
        margin-bottom: 4px;
    }

    .breadcrumb-row a {
        color: var(--ink-muted);
        text-decoration: none;
    }

    .breadcrumb-row .current {
        color: var(--ink);
        font-weight: 500;
    }

    .accounts-head h4 {
        font-weight: 700;
        letter-spacing: -0.01em;
        margin: 0;
        font-size: 1.5rem;
    }

    .btn-accent {
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 0.55rem 1.1rem;
        font-weight: 500;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-accent:hover {
        background: #4338ca;
        color: #fff;
    }

    .btn-accent:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }

    .btn-spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-top-color: #fff;
        animation: btn-spin 0.7s linear infinite;
        margin-right: 6px;
        vertical-align: -2px;
    }

    @keyframes btn-spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Stat cards */
    .stat-card {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        padding: 18px 20px;
        flex: 1;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .stat-card.stat-total .stat-icon { background: var(--accent-soft); color: var(--accent); }
    .stat-card.stat-active .stat-icon { background: var(--ok-soft); color: var(--ok); }
    .stat-card.stat-inactive .stat-icon { background: var(--danger-soft); color: var(--danger); }

    .stat-card .stat-number {
        font-size: 1.7rem;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .stat-card .stat-label {
        color: var(--ink-muted);
        font-size: 0.85rem;
        margin-top: 2px;
    }

    .stat-card .stat-delta {
        font-size: 0.78rem;
        color: var(--ok);
        font-weight: 600;
    }

    .panel {
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: var(--radius);
        overflow: hidden;
    }

    /* Toolbar: role tabs + search */
    .toolbar {
        padding: 14px 16px;
        border-bottom: 1px solid var(--line);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .role-tabs {
        display: flex;
        gap: 4px;
    }

    .role-tab {
        border: none;
        background: none;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.87rem;
        font-weight: 500;
        color: var(--ink-muted);
        cursor: pointer;
    }

    .role-tab.active {
        background: var(--accent-soft);
        color: var(--accent);
    }

    .search-wrap {
        position: relative;
        min-width: 240px;
    }

    .search-wrap input {
        border: 1px solid var(--line);
        border-radius: 9px;
        padding: 8px 12px 8px 34px;
        font-size: 0.87rem;
        width: 100%;
    }

    .search-wrap input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-soft);
    }

    .search-wrap i {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--ink-muted);
        font-size: 0.85rem;
    }

    table.modern-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    table.modern-table thead th {
        text-align: left;
        font-weight: 500;
        font-size: 0.75rem;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: var(--ink-muted);
        background: var(--canvas);
        padding: 12px 16px;
        border-bottom: 1px solid var(--line);
    }

    table.modern-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--line);
        vertical-align: middle;
    }

    table.modern-table tbody tr:last-child td {
        border-bottom: none;
    }

    table.modern-table tbody tr:hover {
        background: #fafafa;
    }

    .user-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .avatar-circle {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .name-cell {
        font-weight: 500;
    }

    .username-cell {
        color: var(--ink-muted);
        font-size: 0.8rem;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 500;
    }

    .pill-role-admin {
        background: var(--amber-soft);
        color: var(--amber);
    }

    .pill-role-semiadmin {
        background: var(--purple-soft);
        color: var(--purple);
    }

    .pill-role-user {
        background: var(--accent-soft);
        color: var(--accent);
    }

    .pill-active {
        background: var(--ok-soft);
        color: var(--ok);
    }

    .pill-inactive {
        background: var(--danger-soft);
        color: var(--danger);
    }

    .pill-count {
        background: var(--canvas);
        color: var(--ink-muted);
        border: 1px solid var(--line);
    }

    .action-icons {
        display: flex;
        align-items: center;
        gap: 6px;
        justify-content: flex-end;
    }

    .icon-btn {
        border: 1px solid var(--line);
        background: var(--surface);
        border-radius: 8px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--ink-muted);
        font-size: 0.85rem;
    }

    .icon-btn:hover {
        border-color: var(--accent);
        color: var(--accent);
    }

    .icon-btn.danger:hover {
        border-color: var(--danger);
        color: var(--danger);
    }

    /* Modal */
    #userModal .modal-content {
        border: none;
        border-radius: 18px;
    }

    #userModal .modal-header {
        border-bottom: 1px solid var(--line);
        padding: 20px 24px;
    }

    #userModal .modal-title {
        font-weight: 600;
        font-size: 1.05rem;
    }

    #userModal .nav-tabs {
        border-bottom: 1px solid var(--line);
        gap: 4px;
    }

    #userModal .nav-tabs .nav-link {
        border: none;
        color: var(--ink-muted);
        font-size: 0.88rem;
        font-weight: 500;
        padding: 8px 4px;
        margin-right: 20px;
        border-bottom: 2px solid transparent;
    }

    #userModal .nav-tabs .nav-link.active {
        color: var(--accent);
        border-bottom-color: var(--accent);
        background: none;
    }

    #userModal label.form-label {
        font-size: 0.82rem;
        font-weight: 500;
        color: var(--ink-muted);
    }

    #userModal .form-control,
    #userModal .form-select {
        border: 1px solid var(--line);
        border-radius: 9px;
        font-size: 0.9rem;
        padding: 8px 10px;
    }

    #userModal .form-control:focus,
    #userModal .form-select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-soft);
    }

    .module-group-label {
        font-size: 0.72rem;
        letter-spacing: 0.04em;
        color: var(--ink-muted);
        font-weight: 600;
    }

    .module-card {
        border: 1px solid var(--line);
        border-radius: 10px;
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.87rem;
    }

    .module-card:has(.module-check:checked) {
        border-color: var(--accent);
        background: var(--accent-soft);
    }

    /* Bootstrap's .form-check-input assumes a .form-check wrapper (float:left; margin-left:-1.5em).
       Without that wrapper here, the checkbox shifts left and sits on top of the label text.
       Reset it to a normal inline box so flex + gap handles the layout instead. */
    .module-card input.module-check {
        float: none;
        margin: 0;
        position: static;
        flex-shrink: 0;
    }

    .module-card span {
        flex: 1;
    }

    #tab-scope table.modern-table thead th,
    #tab-scope table.modern-table tbody td {
        padding: 8px 12px;
    }

    #tab-scope .form-select {
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }

    .empty-note {
        color: var(--ink-muted);
        font-size: 0.85rem;
        padding: 10px 4px;
    }

    /* Toast-style success notification, replaces the plain Bootstrap alert banner */
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
        color: var(--ink-muted);
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
</style>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div id="success-alert" class="app-toast">
        <span class="app-toast-icon"><i class="bi bi-check-lg"></i></span>
        <span>User updated successfully!</span>
        <button type="button" class="app-toast-close" onclick="document.getElementById('success-alert').remove();">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
<?php endif; ?>

<div class="container accounts-wrap my-5">
    <div class="breadcrumb-row">
        <a href="index.php">Dashboard</a> &nbsp;›&nbsp; <span class="current">Users</span>
    </div>
    <div class="d-flex justify-content-between align-items-center accounts-head mb-4">
        <h4>Users</h4>
        <button class="btn-accent" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openUserModal(null)">
            <i class="bi bi-plus-lg"></i> Add new user
        </button>
    </div>

    <div class="d-flex gap-3 mb-4 flex-wrap">
        <div class="stat-card stat-total">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="stat-number"><?= (int) $total_users ?></div>
                <div class="stat-label">Total users</div>
            </div>
        </div>
        <div class="stat-card stat-active">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div>
                <div class="stat-number"><?= (int) $active_users ?></div>
                <div class="stat-label">Active users</div>
            </div>
        </div>
        <div class="stat-card stat-inactive">
            <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
            <div>
                <div class="stat-number"><?= (int) $inactive_users ?></div>
                <div class="stat-label">Inactive users</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="toolbar">
            <div class="role-tabs">
                <button type="button" class="role-tab active" data-role="all" onclick="filterByRole(this)">All</button>
                <button type="button" class="role-tab" data-role="Admin" onclick="filterByRole(this)">Admins</button>
                <button type="button" class="role-tab" data-role="User" onclick="filterByRole(this)">Users</button>
            </div>
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="userSearch" placeholder="Search by name or username">
            </div>
        </div>

        <div class="table-responsive">
            <table class="modern-table user-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Access</th>
                        <th class="text-end" style="width:140px;">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = mysqli_query($conn, "SELECT * FROM tbl_users ORDER BY fullname ASC");
                    while ($row = mysqli_fetch_assoc($query)) {

                        $status_pill = $row['status']
                            ? '<span class="pill pill-active">Active</span>'
                            : '<span class="pill pill-inactive">Inactive</span>';

                        $enabled_count = 0;
                        foreach ($module_groups as $fields) {
                            foreach ($fields as $key => $label) {
                                if (!empty($row[$key]))
                                    $enabled_count++;
                            }
                        }

                        $perm_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tbl_permission WHERE user_id = " . intval($row['id']));
                        $perm_count = mysqli_fetch_assoc($perm_query)['total'];

                        $initials = user_initials($row['fullname']);
                        $color = avatar_color($row['username']);
                        $role_class = role_pill_class($row['user_type']);
                        $status_attr = $row['status'] ? 1 : 0;

                        echo "<tr data-user-id='{$row['id']}' data-status='{$status_attr}'>
                            <td>
                                <div class='user-cell'>
                                    <div class='avatar-circle' style='background:{$color};'>{$initials}</div>
                                    <div>
                                        <div class='name-cell'>{$row['fullname']}</div>
                                        <div class='username-cell'>@{$row['username']}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class='pill {$role_class}'>{$row['user_type']}</span></td>
                            <td class='status-cell'>{$status_pill}</td>
                            <td>
                                <span class='pill pill-count me-1'>{$enabled_count} modules</span>
                                <span class='pill pill-count'>{$perm_count} scopes</span>
                            </td>
                            <td class='text-end'>
                                <div class='action-icons'>
                                    <button type='button' class='icon-btn' title='Manage'
                                        data-bs-toggle='modal' data-bs-target='#userModal'
                                        onclick='openUserModal({$row['id']})'>
                                        <i class='bi bi-pencil'></i>
                                    </button>
                                    <button type='button' class='icon-btn' title='Toggle active/inactive'
                                        onclick='toggleStatus({$row['id']}, this)'>
                                        <i class='bi bi-arrow-repeat'></i>
                                    </button>
                                    <button type='button' class='icon-btn danger' title='Delete'
                                        onclick='deleteUser({$row['id']}, this)'>
                                        <i class='bi bi-trash'></i>
                                    </button>
                                </div>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Single modal: add or edit a user, their module access, and their retailer/company/location scopes -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="userModalTitle">
                    <i class="bi bi-person-gear me-2"></i> Add user
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-details"
                            type="button">
                            Details
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-modules" type="button">
                            Module access
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-scope" type="button">
                            Retailer / company / location
                        </button>
                    </li>
                </ul>

                <form id="userForm" onsubmit="return saveUser(event);">
                    <input type="hidden" id="user_id" name="user_id">

                    <div class="tab-content">
                        <!-- Details -->
                        <div class="tab-pane fade show active" id="tab-details">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full name</label>
                                    <input type="text" id="fullname" class="form-control"
                                        style="text-transform:uppercase" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" id="username" maxlength="75" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Password</label>
                                    <input type="password" id="password" maxlength="100" class="form-control"
                                        placeholder="Leave blank to keep current">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Role</label>
                                    <select id="user_type" class="form-select" required>
                                        <option value="Admin">Admin</option>
                                        <option value="User">User</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select id="status" class="form-select" required>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Module access -->
                        <div class="tab-pane fade" id="tab-modules">
                            <?php foreach ($module_groups as $group_label => $fields): ?>
                                <div class="mb-4">
                                    <div class="module-group-label text-uppercase mb-2"><?= $group_label ?></div>
                                    <div class="row row-cols-2 row-cols-md-3 g-2">
                                        <?php foreach ($fields as $key => $label): ?>
                                            <div class="col">
                                                <label class="module-card mb-0" for="mod_<?= $key ?>">
                                                    <input class="form-check-input module-check m-0" type="checkbox"
                                                        id="mod_<?= $key ?>" data-field="<?= $key ?>">
                                                    <span><?= $label ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Retailer / company / location scopes -->
                        <div class="tab-pane fade" id="tab-scope">
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Retailer</label>
                                    <select id="scope_retailer" class="form-select" onchange="populateCompanyOptions(this.value)">
                                        <?php foreach ($retailers as $r): ?>
                                            <option value="<?= htmlspecialchars($r['retailer_name']) ?>"><?= htmlspecialchars($r['retailer_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Company</label>
                                    <select id="scope_company" class="form-select">
                                        <!-- populated by populateCompanyOptions() based on selected retailer -->
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Location</label>
                                    <select id="scope_location" class="form-select">
                                        <?php foreach ($locations as $l): ?>
                                            <option value="<?= $l['id'] ?>"
                                                data-name="<?= htmlspecialchars($l['location']) ?>">
                                                <?= htmlspecialchars($l['location']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="addScopeRow()">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="panel">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Retailer</th>
                                            <th>Company</th>
                                            <th>Location</th>
                                            <th class="text-end" style="width:50px;">&nbsp;</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scopeRows">
                                        <!-- rows added by addScopeRow() / populated by openUserModal() -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="empty-note" id="scopeEmptyNote">No access scopes added yet.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid var(--line);">
                        <button type="button" class="btn-manage btn btn-secondary"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveUserBtn" class="btn-accent">
                            <span id="saveUserBtnText">Save changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reusable confirm dialog — replaces window.confirm() for destructive actions like delete -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content rounded-4">
            <div class="modal-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <span style="width:40px;height:40px;border-radius:50%;background:var(--danger-soft);color:var(--danger);
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </span>
                    <div>
                        <h6 class="fw-semibold mb-1" id="confirmModalTitle">Are you sure?</h6>
                        <p class="mb-0" id="confirmModalMessage" style="color: var(--ink-muted); font-size: 0.9rem;">
                        </p>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid var(--line);">
                    <button type="button" class="btn btn-secondary" id="confirmModalCancelBtn"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn text-white" id="confirmModalConfirmBtn"
                        style="background: var(--danger); border: none; border-radius: 10px; padding: 0.55rem 1.1rem; font-weight: 500; font-size: 0.9rem;">
                        Yes, delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<span class="span-notify-alert"></span>
<?php include_once("footer.php"); ?>
<?php $conn->close(); ?>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="bi bi-arrow-up"></i>
</a>

<script>
    // Full company list from PHP, each carrying its retailer, so the Company
    // dropdown can be rebuilt client-side whenever the Retailer changes.
    const ALL_COMPANIES = <?php echo json_encode($companies); ?>;
    const ALL_RETAILERS = <?php echo json_encode(array_column($retailers, 'retailer_name')); ?>;

    // Rebuilds the Company <select> to only show companies under the given retailer.
    function populateCompanyOptions(retailerValue, selectedCompanyId = null) {
        const sel = document.getElementById('scope_company');
        sel.innerHTML = '';
        ALL_COMPANIES
            .filter(c => c.retailer === retailerValue)
            .forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                opt.dataset.name = c.name;
                if (selectedCompanyId !== null && String(c.id) === String(selectedCompanyId)) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });
    }

    // Reusable toast notification — replaces window.alert() calls throughout this page.
    // type: 'success' | 'error' | 'warning'
    function showToast(message, type = 'success') {
        const existing = document.getElementById('js-toast');
        if (existing) existing.remove();

        const icons = { success: 'bi-check-lg', error: 'bi-exclamation-lg', warning: 'bi-exclamation-triangle' };
        const variantClass = { success: '', error: 'app-toast-error', warning: 'app-toast-warning' };

        const toast = document.createElement('div');
        toast.id = 'js-toast';
        toast.className = `app-toast ${variantClass[type] || ''}`.trim();
        toast.innerHTML = `
        <span class="app-toast-icon"><i class="bi ${icons[type] || icons.success}"></i></span>
        <span>${message}</span>
        <button type="button" class="app-toast-close" onclick="this.closest('.app-toast').remove();">
            <i class="bi bi-x-lg"></i>
        </button>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 400);
        }, 3500);
    }

    // Promise-based confirm dialog backed by #confirmModal — resolves true/false
    // instead of blocking with window.confirm(). Optional confirmLabel lets callers
    // customize the destructive button's text (defaults to "Yes, delete").
    function showConfirm(message, confirmLabel = 'Yes, delete') {
        return new Promise(resolve => {
            const modalEl = document.getElementById('confirmModal');
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            document.getElementById('confirmModalMessage').textContent = message;
            const confirmBtn = document.getElementById('confirmModalConfirmBtn');
            confirmBtn.textContent = confirmLabel;

            let settled = false;
            const settle = (result) => {
                if (settled) return;
                settled = true;
                resolve(result);
                modalEl.removeEventListener('hidden.bs.modal', onHidden);
                confirmBtn.removeEventListener('click', onConfirm);
            };
            const onConfirm = () => { settle(true); bsModal.hide(); };
            const onHidden = () => settle(false);

            confirmBtn.addEventListener('click', onConfirm);
            modalEl.addEventListener('hidden.bs.modal', onHidden);
            bsModal.show();
        });
    }

    var userTable;
    $(document).ready(function () {
        userTable = $('.user-table').DataTable({
            dom: 't<"d-flex justify-content-between align-items-center px-3 pb-3"ip>',
            pageLength: 10
        });

        // Company dropdown starts filtered to whichever retailer is selected first.
        const initialRetailer = document.getElementById('scope_retailer').value;
        populateCompanyOptions(initialRetailer);
    });

    setTimeout(function () {
        var alert = document.getElementById('success-alert');
        if (alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }
    }, 1000);

    // Search box (custom UI, wired into the DataTable's own search)
    document.getElementById('userSearch').addEventListener('input', function () {
        userTable.search(this.value).draw();
    });
    document.getElementById('fullname').addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });

    // Role tab filter — exact match on the Role column
    function filterByRole(el) {
        document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
        var role = el.dataset.role;
        userTable.column(1).search(role === 'all' ? '' : '^' + role + '$', true, false).draw();
    }

    // Adds one retailer/company/location row to the scope table using the three
    // dropdowns above the table. Mirrors one future row in tbl_permission.
    function addScopeRow() {
        const retailer = document.getElementById('scope_retailer').value;
        const companySel = document.getElementById('scope_company');
        const locationSel = document.getElementById('scope_location');

        const company_id = companySel.value;
        const company_name = companySel.selectedOptions[0]?.dataset.name || '';
        const location_id = locationSel.value;
        const location_name = locationSel.selectedOptions[0]?.dataset.name || '';

        if (!company_id) {
            showToast('This retailer has no companies to assign yet.', 'warning');
            return;
        }

        // Prevent adding the exact same retailer/company/location combo twice
        const isDuplicate = [...document.getElementById('scopeRows').children].some(tr =>
            tr.dataset.retailer === retailer &&
            tr.dataset.companyId === company_id &&
            tr.dataset.locationId === location_id
        );
        if (isDuplicate) {
            showToast('This retailer, company, and location combination has already been added.', 'warning');
            return;
        }

        appendScopeRow(retailer, company_id, company_name, location_id, location_name);
    }

    function appendScopeRow(retailer, company_id, company_name, location_id, location_name) {
        const tbody = document.getElementById('scopeRows');
        const tr = document.createElement('tr');
        tr.dataset.retailer = retailer;
        tr.dataset.companyId = company_id;
        tr.dataset.companyName = company_name;
        tr.dataset.locationId = location_id;
        tr.dataset.locationName = location_name;
        tr.innerHTML = `
        <td>${retailer}</td>
        <td>${company_name}</td>
        <td>${location_name}</td>
        <td class="text-end">
            <button type="button" class="icon-btn danger" onclick="this.closest('tr').remove(); toggleScopeEmptyNote();">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;
        tbody.appendChild(tr);
        toggleScopeEmptyNote();
    }

    function toggleScopeEmptyNote() {
        const hasRows = document.getElementById('scopeRows').children.length > 0;
        document.getElementById('scopeEmptyNote').style.display = hasRows ? 'none' : 'block';
    }
    function applyAdminDefaults() {
        document.querySelectorAll('.module-check').forEach(cb => cb.checked = true);

        const locationOpts = [...document.getElementById('scope_location').options];

        document.getElementById('scopeRows').innerHTML = '';

        // Admins get every retailer/company/location combination, not just
        // whatever the Company dropdown currently happens to be filtered to.
        ALL_RETAILERS.forEach(retailer => {
            ALL_COMPANIES.filter(c => c.retailer === retailer).forEach(c => {
                locationOpts.forEach(lOpt => {
                    appendScopeRow(retailer, c.id, c.name, lOpt.value, lOpt.dataset.name || lOpt.textContent);
                });
            });
        });
    }

    document.getElementById('user_type').addEventListener('change', function () {
        if (this.value === 'Admin') {
            applyAdminDefaults();
        }
    });

    // Resets the modal for "Add user", or loads an existing user's details,
    // module flags, and tbl_permission scopes for "Manage".
    function openUserModal(id) {
        document.getElementById('userForm').reset();
        document.getElementById('scopeRows').innerHTML = '';
        toggleScopeEmptyNote();
        document.querySelectorAll('.module-check').forEach(cb => cb.checked = false);
        populateCompanyOptions(document.getElementById('scope_retailer').value);

        const saveBtn = document.getElementById('saveUserBtn');
        const saveBtnText = document.getElementById('saveUserBtnText');
        saveBtn.disabled = false;
        saveBtnText.textContent = 'Save changes';

        if (!id) {
            document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus-fill me-2"></i> Add user';
            document.getElementById('user_id').value = '';
            document.getElementById('password').required = true;
            return;
        }

        document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-gear me-2"></i> Manage user';
        document.getElementById('password').required = false;
        document.getElementById('user_id').value = id;

        fetch('fetch_user.php?id=' + encodeURIComponent(id))
            .then(res => res.json())
            .then(data => {
                if (data.error) { throw new Error(data.error); }

                document.getElementById('fullname').value = data.user.fullname;
                document.getElementById('username').value = data.user.username;
                document.getElementById('user_type').value = data.user.user_type;
                document.getElementById('status').value = data.user.status;

                document.querySelectorAll('.module-check').forEach(cb => {
                    cb.checked = !!data.user[cb.dataset.field];
                });

                (data.scopes || []).forEach(s => {
                    appendScopeRow(s.retailer, s.company_id, s.company_name, s.location_id, s.location_name);
                });
            })
            .catch(() => {
                showToast('Could not load this user. Please try again.', 'error');
            });
    }

    // Submits details + module flags + scope rows together.
    // save-user.php upserts tbl_users, then replaces this user's
    // tbl_permission rows with the current scope table contents.
    function saveUser(e) {
        e.preventDefault();

        const saveBtn = document.getElementById('saveUserBtn');
        const saveBtnText = document.getElementById('saveUserBtnText');

        // Guard against double-submits (e.g. double-click, or Enter + click)
        if (saveBtn.disabled) return false;
        saveBtn.disabled = true;
        saveBtnText.innerHTML = '<span class="btn-spinner"></span>Saving...';

        const modules = {};
        document.querySelectorAll('.module-check').forEach(cb => {
            modules[cb.dataset.field] = cb.checked ? 1 : 0;
        });

        const scopes = [...document.getElementById('scopeRows').children].map(tr => ({
            retailer: tr.dataset.retailer,
            company_id: tr.dataset.companyId,
            company_name: tr.dataset.companyName,
            location_id: tr.dataset.locationId,
            location_name: tr.dataset.locationName
        }));

        const payload = {
            id: document.getElementById('user_id').value,
            fullname: document.getElementById('fullname').value,
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            user_type: document.getElementById('user_type').value,
            status: document.getElementById('status').value,
            modules: modules,
            scopes: scopes
        };

        fetch('account_form_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    // Leaving the page anyway — keep the button disabled/spinning until the redirect lands.
                    window.location.href = 'account.php?success=1';
                } else {
                    showToast(res.message || 'Could not save this user.', 'error');
                    saveBtn.disabled = false;
                    saveBtnText.textContent = 'Save changes';
                }
            })
            .catch(() => {
                showToast('Could not save this user. Please try again.', 'error');
                saveBtn.disabled = false;
                saveBtnText.textContent = 'Save changes';
            });

        return false;
    }

    // Flip a user's active/inactive status in place, without a full page reload.
    function toggleStatus(id, btn) {
        fetch('account_status_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    showToast(res.message || 'Could not update status.', 'error');
                    return;
                }
                const row = btn.closest('tr');
                const cell = row.querySelector('.status-cell');
                cell.innerHTML = res.status == 1
                    ? '<span class="pill pill-active">Active</span>'
                    : '<span class="pill pill-inactive">Inactive</span>';
                row.dataset.status = res.status;
            })
            .catch(() => showToast('Could not update status. Please try again.', 'error'));
    }

    // Deletes a user (and their tbl_permission scopes) after confirmation.
    async function deleteUser(id, btn) {
        const ok = await showConfirm('Delete this user? This cannot be undone.', 'Yes, delete');
        if (!ok) return;

        fetch('delete-user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    showToast(res.message || 'Could not delete this user.', 'error');
                    return;
                }
                userTable.row(btn.closest('tr')).remove().draw();
            })
            .catch(() => showToast('Could not delete this user. Please try again.', 'error'));
    }
</script>