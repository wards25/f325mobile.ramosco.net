<?php
session_start();
include('dbconnect.php');
include('header.php');
//for authentication
include_once('authcheck.php');
if (!isset($_SESSION['id'])) {
    header("Location: 404.php");
}
//end of authentication
$flash = null;
if (!empty($_SESSION['store_flash'])) {
    $flash = $_SESSION['store_flash'];
    unset($_SESSION['store_flash']);
}

$total_stores = $conn->query("SELECT COUNT(*) AS c FROM tbl_census")->fetch_assoc()['c'];
$active_stores = $conn->query("SELECT COUNT(*) AS c FROM tbl_census WHERE status = 1")->fetch_assoc()['c'];
$inactive_stores = $total_stores - $active_stores;

$regions_list = [];
$region_result = $conn->query("SELECT DISTINCT region FROM tbl_census WHERE region <> '' ORDER BY region ASC");
while ($r = $region_result->fetch_assoc()) {
    $regions_list[] = $r['region'];
}

$locations_list = [];
$location_result = $conn->query("SELECT DISTINCT location FROM tbl_location WHERE active = '1' ORDER BY location ASC");
while ($l = $location_result->fetch_assoc()) {
    $locations_list[] = $l['location'];
}

$franchises_list = [];
$franchise_result = $conn->query("SELECT DISTINCT franchise FROM tbl_census WHERE franchise <> '' ORDER BY franchise ASC");
while ($f = $franchise_result->fetch_assoc()) {
    $franchises_list[] = $f['franchise'];
}

$clusters_list = [];
$cluster_result = $conn->query("SELECT DISTINCT cluster FROM tbl_census WHERE cluster <> '' ORDER BY cluster ASC");
while ($c = $cluster_result->fetch_assoc()) {
    $clusters_list[] = $c['cluster'];
}

$deducttypes_list = [];
$deducttype_result = $conn->query("SELECT DISTINCT deducttype FROM tbl_census WHERE deducttype <> '' ORDER BY deducttype ASC");
while ($d = $deducttype_result->fetch_assoc()) {
    $deducttypes_list[] = $d['deducttype'];
}

// Active retailers for the Retailer dropdown in the Add/Edit Store modal.
$retailers_list = [];
$retailer_result = $conn->query("SELECT retailer_name FROM tbl_retailer WHERE status = 1 ORDER BY retailer_name ASC");
while ($rt = $retailer_result->fetch_assoc()) {
    $retailers_list[] = $rt['retailer_name'];
}
?>

<?php include_once('header.php'); ?>
<?php include_once('nav.php'); ?>

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

    .store-page {
        background-color: var(--page-bg);
        min-height: 100vh;
        padding: 2rem 1.5rem;
    }

    .store-breadcrumb {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }

    .store-breadcrumb .current {
        color: #2d2f3a;
        font-weight: 600;
    }

    .page-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .store-title {
        font-weight: 700;
        font-size: 1.6rem;
        color: #1f2130;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 0.6rem;
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

    .btn-outline-modern {
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.55rem 1.1rem;
        border: 1px solid #e3e5ef;
        color: #4a4c5a;
        background: #fff;
    }

    .btn-outline-modern:hover {
        background-color: #f6f7fb;
        color: #2d2f3a;
    }

    .stat-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #eceef5;
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .stat-card.stat-total .stat-icon { background: var(--brand-light); color: var(--brand); }
    .stat-card.stat-active .stat-icon { background: var(--ok-soft); color: var(--ok); }
    .stat-card.stat-inactive .stat-icon { background: var(--danger-soft); color: var(--danger); }

    .stat-card .stat-value {
        font-weight: 800;
        font-size: 1.8rem;
        color: #1f2130;
        line-height: 1;
    }

    .stat-card .stat-label {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-top: 0.4rem;
    }

    .store-card {
        background: #fff;
        border: 1px solid #eceef5;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        overflow: hidden;
    }

    .table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #eceef5;
        flex-wrap: wrap;
    }

    .toolbar-filters {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .filter-select {
        border: 1px solid #e3e5ef;
        border-radius: 0.6rem;
        padding: 0.5rem 0.85rem;
        font-size: 0.85rem;
        background-color: #fbfbfe;
        min-width: 170px;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }

    .search-box {
        position: relative;
        width: 280px;
    }

    .search-box input {
        border: 1px solid #e3e5ef;
        border-radius: 0.6rem;
        padding: 0.5rem 0.85rem 0.5rem 2.1rem;
        font-size: 0.85rem;
        width: 100%;
        background-color: #fbfbfe;
    }

    .search-box i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 0.85rem;
    }

    table.store-table {
        width: 100%;
        margin: 0;
    }

    table.store-table thead th {
        background-color: #fafbfd;
        color: var(--text-muted);
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 700;
        border-bottom: 1px solid #eceef5;
        padding: 0.75rem 1.25rem;
        white-space: nowrap;
    }

    table.store-table tbody td {
        padding: 0.9rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid #f2f3f8;
        font-size: 0.88rem;
        color: #2d2f3a;
    }

    table.store-table tbody tr:last-child td {
        border-bottom: none;
    }

    .store-name-cell .sname {
        font-weight: 700;
        color: #1f2130;
        line-height: 1.2;
    }

    .store-name-cell .scode {
        color: var(--text-muted);
        font-size: 0.78rem;
    }

    .badge-soft {
        border-radius: 999px;
        padding: 0.3rem 0.75rem;
        font-weight: 600;
        font-size: 0.75rem;
        background-color: var(--brand-light);
        color: var(--brand);
    }

    .badge-status {
        border-radius: 999px;
        padding: 0.3rem 0.75rem;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .badge-active {
        background-color: #e6f7ec;
        color: #1a9c4d;
    }

    .badge-inactive {
        background-color: #fdeeee;
        color: #d1373f;
    }

    .row-actions button {
        border: 1px solid #e3e5ef;
        background: #fff;
        border-radius: 0.5rem;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6b6f80;
        margin-left: 0.35rem;
    }

    .row-actions button:hover {
        background-color: #f6f7fb;
        color: var(--brand);
        border-color: var(--brand-light);
    }

    .row-actions .btn-deactivate:hover {
        color: #d1373f;
        border-color: #f6c9cb;
    }

    .row-actions .btn-activate:hover {
        color: #1a9c4d;
        border-color: #cdeedb;
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
    }

    .form-control-modern:focus,
    .form-select-modern:focus {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        background-color: #fff;
    }

    .form-section-label {
        font-weight: 700;
        font-size: 0.95rem;
        color: #1f2130;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eceef5;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-muted);
    }

    /* Toast-style notification, matches product-list.php / company.php */
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

    /* Confirm modal (deactivate / activate) */
    #confirmActionModal .modal-content {
        border-radius: 1rem;
        border: none;
        padding: 0.5rem;
    }

    #confirmActionModal .confirm-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    #confirmActionModal .confirm-title {
        font-weight: 700;
        font-size: 1.05rem;
        color: #1f2130;
        margin-bottom: 0.15rem;
    }

    #confirmActionModal .confirm-message {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    #confirmActionModal .modal-footer {
        border-top: 1px solid var(--line);
    }

    #confirmActionModal .btn-cancel {
        background: #6b7280;
        border-color: #6b7280;
        color: #fff;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.55rem 1.2rem;
    }

    #confirmActionModal .btn-cancel:hover {
        background: #565e6b;
        border-color: #565e6b;
        color: #fff;
    }

    #confirmActionModal .btn-confirm {
        border: none;
        color: #fff;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.55rem 1.2rem;
    }

    /* Skeleton preloader */
    .skeleton-row {
        display: grid;
        grid-template-columns: 1fr 2fr 1fr 1fr 0.8fr 0.8fr;
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
</style>

<div class="store-page">
    <div class="container-fluid">

        <div class="store-breadcrumb">Search &rsaquo; <span class="current">Store List</span></div>

        <div class="page-header-row">
            <h1 class="store-title">Store List</h1>
            <div class="header-actions">
                <a href="store_export_process.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download me-1"></i> Export
                </a>
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#importStoreModal">
                    <i class="bi bi-upload me-1"></i> Import 
                </button>
                <button class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#storeModal"
                    onclick="openAddStore()">
                    <i class="bi bi-shop-window me-1"></i> Add new store
                </button>
            </div>
        </div>

        <?php if ($flash):
            $toast_variant = $flash['type'] === 'success' ? '' : ($flash['type'] === 'danger' ? 'app-toast-error' : 'app-toast-warning');
            $toast_icon = $flash['type'] === 'success' ? 'bi-check-lg' : 'bi-exclamation-lg';
            ?>
            <div id="server-toast" class="app-toast <?php echo $toast_variant; ?>">
                <span class="app-toast-icon"><i class="bi <?php echo $toast_icon; ?>"></i></span>
                <span><?php echo htmlspecialchars($flash['msg']); ?></span>
                <button type="button" class="app-toast-close" onclick="document.getElementById('server-toast').remove();">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        <?php endif; ?>

        <div class="stat-cards">
            <div class="stat-card stat-total">
                <div class="stat-icon"><i class="bi bi-shop"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($total_stores); ?></div>
                    <div class="stat-label">Total stores</div>
                </div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($active_stores); ?></div>
                    <div class="stat-label">Active stores</div>
                </div>
            </div>
            <div class="stat-card stat-inactive">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($inactive_stores); ?></div>
                    <div class="stat-label">Inactive stores</div>
                </div>
            </div>
        </div>

        <div class="store-card">
            <div class="table-toolbar">
                <div class="toolbar-filters">
                    <select id="regionFilter" class="filter-select">
                        <option value="">All regions</option>
                        <?php foreach ($regions_list as $r): ?>
                            <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="locationFilter" class="filter-select">
                        <option value="">All locations</option>
                        <?php foreach ($locations_list as $l): ?>
                            <option value="<?php echo htmlspecialchars($l); ?>"><?php echo htmlspecialchars($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="storeSearch" placeholder="Search by code, branch, or region">
                </div>
            </div>

            <!-- Skeleton preloader: visible by default, hidden once the real table is ready -->
            <div id="storeSkeleton">
                <?php for ($i = 0; $i < 6; $i++): ?>
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

            <?php
            $result = $conn->query("SELECT * FROM tbl_census ORDER BY branchname ASC");
            ?>

            <div id="storeTableWrapper" style="display:none;">
                <?php if ($result->num_rows === 0): ?>
                    <div class="empty-state">
                        <div class="mb-2"><i class="bi bi-shop" style="font-size:1.8rem;"></i></div>
                        No stores yet. Click "Add new store" to create one.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="storeTable" class="store-table">
                            <thead>
                                <tr>
                                    <th>Branch Code</th>
                                    <th>Branch Name</th>
                                    <th>Region</th>
                                    <th>Location</th>
                                    <th>Retailer</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php $is_active = (int) $row['status'] === 1; ?>
                                    <tr>
                                        <td>
                                            <div class="store-name-cell">
                                                <div class="sname"><?= htmlspecialchars($row['code']); ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($row['branchname']); ?></td>
                                        <td><span class="badge-soft"><?= htmlspecialchars($row['region']); ?></span></td>
                                        <td><?= htmlspecialchars($row['location']); ?></td>
                                        <td><?= htmlspecialchars($row['retailer']); ?></td>
                                        <td>
                                            <span class="badge-status <?php echo $is_active ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="text-end row-actions">
                                            <button type="button" title="Edit"
                                                onclick='openEditStore(<?php echo json_encode($row); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($is_active): ?>
                                                <button type="button" class="btn-deactivate" title="Deactivate"
                                                    onclick="submitToggleStatus(<?php echo (int) $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['branchname'])); ?>', 0)">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-activate" title="Activate"
                                                    onclick="submitToggleStatus(<?php echo (int) $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['branchname'])); ?>', 1)">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- ADD / EDIT STORE MODAL -->
<div class="modal fade" id="storeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 1rem; border: none;">

            <div class="modal-header" style="border-bottom: 1px solid #eceef5;">
                <h5 class="modal-title fw-bold" id="storeModalLabel">Add New Store</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <form id="storeForm" method="POST" action="store-list-process.php">
                    <input type="hidden" name="form_action" value="save">
                    <input type="hidden" name="store_id" id="modal_store_id" value="">

                    <div class="form-section-label">Customer Info</div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label-modern">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="modal_code" maxlength="6" class="form-control form-control-modern" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label-modern">Branch Name <span class="text-danger">*</span></label>
                            <textarea name="branchname" id="modal_branchname" class="form-control form-control-modern" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-modern">Shipping Address <span class="text-danger">*</span></label>
                            <textarea name="shipping" id="modal_shipping" class="form-control form-control-modern" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-modern">Billing Address <span class="text-danger">*</span></label>
                            <textarea name="billing" id="modal_billing" class="form-control form-control-modern" required></textarea>
                        </div>
                    </div>

                    <div class="form-section-label">Customer Detail</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label-modern">Franchise <span class="text-danger">*</span></label>
                            <select name="franchise" id="modal_franchise" class="form-select form-select-modern" required>
                                <option value="">Select Franchise</option>
                                <?php foreach ($franchises_list as $f): ?>
                                    <option value="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-modern">Region <span class="text-danger">*</span></label>
                            <select name="region" id="modal_region" class="form-select form-select-modern" required>
                                <option value="">Select Region</option>
                                <?php foreach ($regions_list as $r): ?>
                                    <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-modern">Cluster <span class="text-danger">*</span></label>
                            <select name="cluster" id="modal_cluster" class="form-select form-select-modern" required>
                                <option value="">Select Cluster</option>
                                <?php foreach ($clusters_list as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-modern">Deduct Type <span class="text-danger">*</span></label>
                            <select name="deducttype" id="modal_deducttype" class="form-select form-select-modern" required>
                                <option value="">Select Deduct Type</option>
                                <?php foreach ($deducttypes_list as $d): ?>
                                    <option value="<?php echo htmlspecialchars($d); ?>"><?php echo htmlspecialchars($d); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-modern">Location <span class="text-danger">*</span></label>
                            <select name="location" id="modal_location" class="form-select form-select-modern" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations_list as $l): ?>
                                    <option value="<?php echo htmlspecialchars($l); ?>"><?php echo htmlspecialchars($l); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-modern">Retailer <span class="text-danger">*</span></label>
                            <select name="retailer" id="modal_retailer" class="form-select form-select-modern" required>
                                <option value="">Select Retailer</option>
                                <?php foreach ($retailers_list as $rt): ?>
                                    <option value="<?php echo htmlspecialchars($rt); ?>"><?php echo htmlspecialchars($rt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3" style="border-top:1px solid #eceef5;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- IMPORT CSV MODAL -->
<div class="modal fade" id="importStoreModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 1rem; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #eceef5;">
                <h5 class="modal-title fw-bold">Import Stores (CSV)</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="store-import-process.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted" style="font-size: 0.85rem;">
                        CSV must have a header row, with columns in this exact order:
                    </p>
                    <code style="font-size: 0.78rem;">code, branchname, shipping, billing, franchise, region, cluster, deducttype, location</code>
                    <div class="mt-3">
                        <label class="form-label-modern">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control form-control-modern" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #eceef5;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand">
                        <i class="bi bi-upload me-1"></i> Upload &amp; Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirm modal: deactivate / activate -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body d-flex gap-3 p-4">
                <div class="confirm-icon" id="confirmActionIcon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div>
                    <div class="confirm-title">Are you sure?</div>
                    <div class="confirm-message" id="confirmActionMessage"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-confirm" id="confirmActionButton">Yes, continue</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden toggle-status form -->
<form method="POST" action="store-list-process.php" id="toggleStatusForm">
    <input type="hidden" name="form_action" value="toggle_status">
    <input type="hidden" name="store_id" id="toggle_store_id">
    <input type="hidden" name="new_status" id="toggle_new_status">
</form>

<?php include_once('footer.php'); ?>
<?php $conn->close(); ?>
<script>
    $(document).ready(function () {
        var storeTable = $('#storeTable').DataTable({
            dom: 't<"d-flex justify-content-between align-items-center px-3 pb-3"ip>',
            pageLength: 10
        });

        $('#storeSearch').on('input', function () {
            storeTable.search(this.value).draw();
        });

        // Region filters column 2, Location filters column 3 — exact matches.
        $('#regionFilter').on('change', function () {
            var val = this.value;
            storeTable.column(2).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
        });
        $('#locationFilter').on('change', function () {
            var val = this.value;
            storeTable.column(3).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
        });

        // Swap the skeleton preloader out for the real table now that
        // DataTables has finished initializing it.
        document.getElementById('storeSkeleton').style.display = 'none';
        document.getElementById('storeTableWrapper').style.display = '';
    });

    setTimeout(function () {
        var toast = document.getElementById('server-toast');
        if (toast) {
            toast.classList.add('hide');
            setTimeout(function () { toast.remove(); }, 400);
        }
    }, 3500);

    function openAddStore() {
        document.getElementById('storeModalLabel').textContent = 'Add New Store';
        document.getElementById('modal_store_id').value = '';
        document.getElementById('storeForm').reset();
    }

    function openEditStore(store) {
        document.getElementById('storeModalLabel').textContent = 'Edit Store';
        document.getElementById('modal_store_id').value = store.id;
        document.getElementById('modal_code').value = store.code;
        document.getElementById('modal_branchname').value = store.branchname;
        document.getElementById('modal_shipping').value = store.shipping;
        document.getElementById('modal_billing').value = store.billing;
        document.getElementById('modal_franchise').value = store.franchise;
        document.getElementById('modal_region').value = store.region;
        document.getElementById('modal_cluster').value = store.cluster;
        document.getElementById('modal_deducttype').value = store.deducttype;
        document.getElementById('modal_location').value = store.location;
        document.getElementById('modal_retailer').value = store.retailer;

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('storeModal'));
        modal.show();
    }

    function submitToggleStatus(id, branchname, newStatus) {
        var isDeactivating = newStatus == 0;
        document.getElementById('confirmActionMessage').textContent =
            (isDeactivating ? 'Deactivate "' : 'Activate "') + branchname + '"?';

        var icon = document.getElementById('confirmActionIcon');
        var confirmBtn = document.getElementById('confirmActionButton');
        if (isDeactivating) {
            icon.style.background = 'var(--danger-soft)';
            icon.style.color = 'var(--danger)';
            confirmBtn.style.background = 'var(--danger)';
            confirmBtn.textContent = 'Yes, deactivate';
        } else {
            icon.style.background = 'var(--ok-soft)';
            icon.style.color = 'var(--ok)';
            confirmBtn.style.background = 'var(--ok)';
            confirmBtn.textContent = 'Yes, activate';
        }

        document.getElementById('toggle_store_id').value = id;
        document.getElementById('toggle_new_status').value = newStatus;

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal'));
        modal.show();
    }

    document.getElementById('confirmActionButton').addEventListener('click', function () {
        document.getElementById('toggleStatusForm').submit();
    });
</script>