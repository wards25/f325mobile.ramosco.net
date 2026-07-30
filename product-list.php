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
if (!empty($_SESSION['product_flash'])) {
    $flash = $_SESSION['product_flash'];
    unset($_SESSION['product_flash']);
}

$total_products = $conn->query("SELECT COUNT(*) AS c FROM tbl_product")->fetch_assoc()['c'];
$active_products = $conn->query("SELECT COUNT(*) AS c FROM tbl_product WHERE active = 1")->fetch_assoc()['c'];
$inactive_products = $total_products - $active_products;

$categories_list = [];
$category_result = $conn->query("SELECT DISTINCT category FROM tbl_product WHERE category <> '' ORDER BY category ASC");
while ($cat = $category_result->fetch_assoc()) {
    $categories_list[] = $cat['category'];
}

$companies_list = [];
$company_result = $conn->query("SELECT vendorcode, name FROM tbl_company WHERE active = 1 ORDER BY name ASC");
while ($comp = $company_result->fetch_assoc()) {
    $companies_list[] = $comp;
}

// Active retailers for the Retailer dropdown in the Add/Edit Product modal
// and for the Retailer filter/column in the table.
$retailers_list = [];
$retailer_result = $conn->query("SELECT retailer_name FROM tbl_retailer WHERE status = 1 ORDER BY retailer_name ASC");
while ($rt = $retailer_result->fetch_assoc()) {
    $retailers_list[] = $rt['retailer_name'];
}
?>

<?php include('header.php'); ?>
<?php include('nav.php'); ?>

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

    .product-page {
        background-color: var(--page-bg);
        min-height: 100vh;
        padding: 2rem 1.5rem;
    }

    .product-breadcrumb {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }

    .product-breadcrumb .current {
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

    .product-title {
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

    .product-card {
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

    table.product-table {
        width: 100%;
        margin: 0;
    }

    table.product-table thead th {
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

    table.product-table tbody td {
        padding: 0.9rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid #f2f3f8;
        font-size: 0.88rem;
        color: #2d2f3a;
    }

    table.product-table tbody tr:last-child td {
        border-bottom: none;
    }

    .product-name-cell .pname {
        font-weight: 700;
        color: #1f2130;
        line-height: 1.2;
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

    .row-actions .btn-delete:hover {
        color: #d1373f;
        border-color: #f6c9cb;
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

    .toggle-row {
        background-color: var(--page-bg);
        border: 1px solid #eceef5;
        border-radius: 0.75rem;
        padding: 0.9rem 1.1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .form-check-input:checked {
        background-color: var(--brand);
        border-color: var(--brand);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-muted);
    }

    /* Toast-style notification, matches company.php */
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

    /* Delete confirmation modal (matches company.php) */
    #deleteConfirmModal .modal-content {
        border-radius: 1rem;
        border: none;
        padding: 0.5rem;
    }

    #deleteConfirmModal .confirm-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--danger-soft);
        color: var(--danger);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    #deleteConfirmModal .confirm-title {
        font-weight: 700;
        font-size: 1.05rem;
        color: #1f2130;
        margin-bottom: 0.15rem;
    }

    #deleteConfirmModal .confirm-message {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    #deleteConfirmModal .modal-footer {
        border-top: 1px solid var(--line);
    }

    #deleteConfirmModal .btn-cancel {
        background: #6b7280;
        border-color: #6b7280;
        color: #fff;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.55rem 1.2rem;
    }

    #deleteConfirmModal .btn-cancel:hover {
        background: #565e6b;
        border-color: #565e6b;
        color: #fff;
    }

    #deleteConfirmModal .btn-confirm-delete {
        background: var(--danger);
        border-color: var(--danger);
        color: #fff;
        border-radius: 0.6rem;
        font-weight: 600;
        font-size: 0.88rem;
        padding: 0.55rem 1.2rem;
    }

    #deleteConfirmModal .btn-confirm-delete:hover {
        background: #96201a;
        border-color: #96201a;
        color: #fff;
    }

    /* Skeleton preloader — shown until the real table has finished
       rendering/initializing as a DataTable, then swapped out. */
    .skeleton-row {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr 2fr 0.9fr 0.9fr 1fr 0.8fr 0.8fr;
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

<div class="product-page">
    <div class="container-fluid">

        <div class="product-breadcrumb">Search &rsaquo; <span class="current">Product List</span></div>

        <div class="page-header-row">
            <h1 class="product-title">Product List</h1>
            <div class="header-actions">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importProductModal">
                    <i class="bi bi-upload me-1"></i> Import CSV
                </button>
                <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#productModal"
                    onclick="openAddProduct()">
                    <i class="bi bi-box-seam me-1"></i> Add new product
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
                <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($total_products); ?></div>
                    <div class="stat-label">Total products</div>
                </div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($active_products); ?></div>
                    <div class="stat-label">Active products</div>
                </div>
            </div>
            <div class="stat-card stat-inactive">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="stat-value"><?php echo number_format($inactive_products); ?></div>
                    <div class="stat-label">Inactive products</div>
                </div>
            </div>
        </div>

        <div class="product-card">
            <div class="table-toolbar">
                <div class="toolbar-filters">
                    <select id="companyFilter" class="filter-select">
                        <option value="">All companies</option>
                        <?php foreach ($companies_list as $comp): ?>
                            <option value="<?php echo htmlspecialchars($comp['vendorcode']); ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">All categories</option>
                        <?php foreach ($categories_list as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="retailerFilter" class="filter-select">
                        <option value="">All retailers</option>
                        <?php foreach ($retailers_list as $rt): ?>
                            <option value="<?php echo htmlspecialchars($rt); ?>"><?php echo htmlspecialchars($rt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="productSearch" placeholder="Search by code, description, or vendor">
                </div>
            </div>

            <!-- Skeleton preloader: visible by default, hidden once the real table is ready -->
            <div id="productSkeleton">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="skeleton-row">
                        <div class="skeleton-bar"></div>
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
            $result = $conn->query("SELECT * FROM tbl_product ORDER BY description ASC");
            ?>

            <div id="productTableWrapper" style="display:none;">
                <?php if ($result->num_rows === 0): ?>
                    <div class="empty-state">
                        <div class="mb-2"><i class="bi bi-box-seam" style="font-size:1.8rem;"></i></div>
                        No products yet. Click "Add new product" to create one.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="productTable" class="product-table">
                            <thead>
                                <tr>
                                    <th>MDC Code</th>
                                    <th>Item Code</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Vendor</th>
                                    <th>Retailer</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php $is_active = (int) $row['active'] === 1; ?>
                                    <tr>
                                        <td>
                                            <div class="product-name-cell">
                                                <div class="pname"><?= htmlspecialchars($row['mdccode']); ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($row['itemcode']); ?></td>
                                        <td><?= htmlspecialchars($row['description']); ?></td>
                                        <td><span class="badge-soft"><?= htmlspecialchars($row['category']); ?></span></td>
                                        <td><?= htmlspecialchars($row['vendor']); ?></td>
                                        <td><?= htmlspecialchars($row['retailer'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge-status <?php echo $is_active ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="text-end row-actions">
                                            <button type="button" title="Edit"
                                                onclick='openEditProduct(<?php echo json_encode($row); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn-delete" title="Delete"
                                                onclick="submitDeleteProduct(<?php echo (int) $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['description'])); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

<!-- ADD / EDIT PRODUCT MODAL -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 1rem; border: none;">

            <div class="modal-header" style="border-bottom: 1px solid #eceef5;">
                <h5 class="modal-title fw-bold" id="productModalLabel">Add New Product</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="product-list-process.php">
                <div class="modal-body p-4">
                    <input type="hidden" name="form_action" value="save">
                    <input type="hidden" name="product_id" id="modal_product_id" value="">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label-modern">MDC Code <span class="text-danger">*</span></label>
                            <input type="text" name="mdccode" id="modal_mdccode" class="form-control form-control-modern" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-modern">Item Code <span class="text-danger">*</span></label>
                            <input type="text" name="itemcode" id="modal_itemcode" class="form-control form-control-modern" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label-modern">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="modal_description" class="form-control form-control-modern" rows="3" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-modern">Category <span class="text-danger">*</span></label>
                            <select name="category" id="modal_category" class="form-select form-select-modern" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-modern">UOM</label>
                            <select name="uom" id="modal_uom" class="form-select form-select-modern">
                                <option value="">Select UOM</option>
                                <?php
                                $uom_query = $conn->query("SELECT DISTINCT uom FROM tbl_product WHERE uom <> '' ORDER BY uom ASC");
                                while ($u = $uom_query->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($u['uom']) . "'>" . htmlspecialchars($u['uom']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-modern">Company <span class="text-danger">*</span></label>
                            <select name="company" id="modal_company" class="form-select form-select-modern" required>
                                <option value="">Select Company</option>
                                <?php foreach ($companies_list as $comp): ?>
                                    <option value="<?php echo htmlspecialchars($comp['vendorcode']); ?>"><?php echo htmlspecialchars($comp['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-modern">Retailer <span class="text-danger">*</span></label>
                            <select name="retailer" id="modal_retailer" class="form-select form-select-modern" required>
                                <option value="">Select Retailer</option>
                                <?php foreach ($retailers_list as $rt): ?>
                                    <option value="<?php echo htmlspecialchars($rt); ?>"><?php echo htmlspecialchars($rt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="toggle-row mt-3">
                        <div>
                            <div class="fw-semibold" style="font-size:0.88rem;">Active</div>
                            <div class="text-muted" style="font-size:0.78rem;">Show this product in active listings</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="active" id="modal_active" value="1"
                                role="switch" style="width: 2.6em; height: 1.4em;" checked>
                        </div>
                    </div>

                </div>

                <div class="modal-footer" style="border-top: 1px solid #eceef5;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- IMPORT CSV MODAL -->
<div class="modal fade" id="importProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 1rem; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #eceef5;">
                <h5 class="modal-title fw-bold">Import Products (CSV)</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="product_import_process.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <p class="text-muted" style="font-size: 0.85rem;">
                        CSV must have a header row, with columns in this exact order:
                    </p>
                    <code style="font-size: 0.78rem;">mdccode, itemcode, description, category, uom, vendorcode</code>
                    <div class="mt-3">
                        <label class="form-label-modern">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" accept=".csv" class="form-control form-control-modern" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #eceef5;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Upload &amp; Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body d-flex gap-3 p-4">
                <div class="confirm-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div>
                    <div class="confirm-title">Are you sure?</div>
                    <div class="confirm-message" id="deleteConfirmMessage">This cannot be undone.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-confirm-delete" id="deleteConfirmButton">Yes, delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden delete form -->
<form method="POST" action="product-list-process.php" id="deleteProductForm">
    <input type="hidden" name="form_action" value="delete">
    <input type="hidden" name="product_id" id="delete_product_id">
</form>

<?php include('footer.php'); ?>
<?php $conn->close(); ?>
<script>
    $(document).ready(function () {
        var productTable = $('#productTable').DataTable({
            dom: 't<"d-flex justify-content-between align-items-center px-3 pb-3"ip>',
            pageLength: 10,
            ordering: true
        });

        $('#productSearch').on('input', function () {
            productTable.search(this.value).draw();
        });

        $('#companyFilter').on('change', function () {
            var val = this.value;
            productTable.column(4).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
        });
        $('#categoryFilter').on('change', function () {
            var val = this.value;
            productTable.column(3).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
        });
        $('#retailerFilter').on('change', function () {
            var val = this.value;
            productTable.column(5).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
        });

        document.getElementById('productSkeleton').style.display = 'none';
        document.getElementById('productTableWrapper').style.display = '';
    });

    setTimeout(function () {
        var toast = document.getElementById('server-toast');
        if (toast) {
            toast.classList.add('hide');
            setTimeout(function () { toast.remove(); }, 400);
        }
    }, 3500);

    function openAddProduct() {
        document.getElementById('productModalLabel').innerText = 'Add New Product';
        document.getElementById('modal_product_id').value = '';
        document.getElementById('modal_mdccode').value = '';
        document.getElementById('modal_itemcode').value = '';
        document.getElementById('modal_description').value = '';
        document.getElementById('modal_category').value = '';
        document.getElementById('modal_uom').value = '';
        document.getElementById('modal_company').value = '';
        document.getElementById('modal_retailer').value = '';
        document.getElementById('modal_active').checked = true;
    }

    function openEditProduct(product) {
        document.getElementById('productModalLabel').innerText = 'Edit Product';
        document.getElementById('modal_product_id').value = product.id;
        document.getElementById('modal_mdccode').value = product.mdccode;
        document.getElementById('modal_itemcode').value = product.itemcode;
        document.getElementById('modal_description').value = product.description;
        document.getElementById('modal_category').value = product.category;
        document.getElementById('modal_uom').value = product.uom;
        document.getElementById('modal_company').value = product.vendor;
        document.getElementById('modal_retailer').value = product.retailer;
        document.getElementById('modal_active').checked = parseInt(product.active) === 1;

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal'));
        modal.show();
    }

    function submitDeleteProduct(id, description) {
        document.getElementById('deleteConfirmMessage').innerText = 'Delete "' + description + '"? This cannot be undone.';
        document.getElementById('delete_product_id').value = id;
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    document.getElementById('deleteConfirmButton').addEventListener('click', function () {
        document.getElementById('deleteProductForm').submit();
    });
</script>