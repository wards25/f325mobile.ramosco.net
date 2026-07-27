<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

// delete prdlist in db
mysqli_query($conn, "DELETE FROM cleared_list WHERE user = '$username'");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}
$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);

// ---------------------------------------------------------------------
// Flash message (set by company_form_sucess.php after save/delete)
// ---------------------------------------------------------------------
$flash = null;
if (!empty($_SESSION['company_flash'])) {
    $flash = $_SESSION['company_flash'];
    unset($_SESSION['company_flash']);
}

// ---------------------------------------------------------------------
// Data for the table + stat cards
// ---------------------------------------------------------------------
$companies = [];
$company_res = mysqli_query($conn, "SELECT * FROM tbl_company ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($company_res)) {
    $companies[] = $row;
}
$total_companies = count($companies);
$active_companies = count(array_filter($companies, fn($c) => (int) $c['active'] === 1));
$inactive_companies = $total_companies - $active_companies;

// ---------------------------------------------------------------------
// Retailers for the dropdown (tbl_retailer)
// ---------------------------------------------------------------------
$retailers = [];
$retailer_res = mysqli_query($conn, "SELECT * FROM tbl_retailer WHERE status = 1 ORDER BY retailer_name ASC");
while ($row = mysqli_fetch_assoc($retailer_res)) {
    $retailers[] = $row;
}
?>

<?php
include_once("nav.php");
?>

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

    /* Toast-style notification, matches account.php */
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

    .company-page {
        background-color: var(--page-bg);
        min-height: 100vh;
        padding: 2rem 1.5rem;
    }

    .company-breadcrumb {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-bottom: 0.25rem;
    }

    .company-breadcrumb .current {
        color: #2d2f3a;
        font-weight: 600;
    }

    .page-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .company-title {
        font-weight: 700;
        font-size: 1.6rem;
        color: #1f2130;
        margin: 0;
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

    .stat-card.stat-total .stat-icon {
        background: var(--brand-light);
        color: var(--brand);
    }

    .stat-card.stat-active .stat-icon {
        background: var(--ok-soft);
        color: var(--ok);
    }

    .stat-card.stat-inactive .stat-icon {
        background: var(--danger-soft);
        color: var(--danger);
    }

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

    .company-card {
        background: #fff;
        border: 1px solid #eceef5;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        overflow: hidden;
    }

    .table-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #eceef5;
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

    table.company-table {
        width: 100%;
        margin: 0;
    }

    table.company-table thead th {
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

    table.company-table tbody td {
        padding: 0.9rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid #f2f3f8;
        font-size: 0.88rem;
        color: #2d2f3a;
    }

    table.company-table tbody tr:last-child td {
        border-bottom: none;
    }

    .company-name-cell .cname {
        font-weight: 700;
        color: #1f2130;
        line-height: 1.2;
    }

    .company-name-cell .cnick {
        color: var(--text-muted);
        font-size: 0.78rem;
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

    .form-control-modern:focus {
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

    /* Delete confirmation modal */
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
</style>

<div class="company-page">
    <div class="container-fluid">

        <div class="company-breadcrumb">Dashboard &rsaquo; <span class="current">Company</span></div>

        <div class="page-header-row">
            <h1 class="company-title">Company</h1>
            <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#companyModal"
                onclick="openAddCompany()">
                <i class="bi bi-plus-lg me-1"></i> Add new company
            </button>
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
                <div class="stat-icon"><i class="bi bi-building"></i></div>
                <div>
                    <div class="stat-value"><?php echo $total_companies; ?></div>
                    <div class="stat-label">Total companies</div>
                </div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value"><?php echo $active_companies; ?></div>
                    <div class="stat-label">Active companies</div>
                </div>
            </div>
            <div class="stat-card stat-inactive">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="stat-value"><?php echo $inactive_companies; ?></div>
                    <div class="stat-label">Inactive companies</div>
                </div>
            </div>
        </div>

        <div class="company-card">
            <div class="table-toolbar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="companySearch" placeholder="Search by name, nickname, or vendor code">
                </div>
            </div>

            <?php if (empty($companies)): ?>
                <div class="empty-state">
                    <div class="mb-2"><i class="bi bi-building" style="font-size:1.8rem;"></i></div>
                    No companies yet. Click "Add new company" to create one.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="company-table" id="companyTable">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Vendor Code</th>
                                <th>Ref Code</th>
                                <th>Retailer</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $c): ?>
                                <?php
                                $is_active = (int) $c['active'] === 1;
                                ?>
                                <tr>
                                    <td>
                                        <div class="company-name-cell">
                                            <div class="cname"><?php echo htmlspecialchars($c['name']); ?></div>
                                            <div class="cnick"><?php echo htmlspecialchars($c['nickname']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['vendorcode']); ?></td>
                                    <td><?php echo htmlspecialchars($c['refcode']); ?></td>
                                    <td><?php echo htmlspecialchars($c['retailer']); ?></td>
                                    <td>
                                        <span
                                            class="badge-status <?php echo $is_active ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end row-actions">
                                        <button type="button" title="Edit"
                                            onclick='openEditCompany(<?php echo json_encode($c); ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn-delete" title="Delete"
                                            onclick="submitDeleteCompany(<?php echo (int) $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['name'])); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Add / Edit Company Modal -->
<div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 1rem; border: none;">
            <form method="POST" action="company_form_process.php">
                <div class="modal-header" style="border-bottom: 1px solid #eceef5;">
                    <h5 class="modal-title fw-bold" id="companyModalLabel">Add New Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="form_action" value="save">
                    <input type="hidden" name="company_id" id="modal_company_id" value="">

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label-modern">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="modal_name" class="form-control form-control-modern"
                                maxlength="500" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-modern">Nickname <span class="text-danger">*</span></label>
                            <input type="text" name="nickname" id="modal_nickname"
                                class="form-control form-control-modern" maxlength="20" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label-modern">Vendor Code <span class="text-danger">*</span></label>
                            <input type="text" name="vendorcode" id="modal_vendorcode"
                                class="form-control form-control-modern" maxlength="11" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-modern">Reference Code <span class="text-danger">*</span></label>
                            <input type="text" name="refcode" id="modal_refcode" inputmode="numeric"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'');"
                                class="form-control form-control-modern" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label-modern">Retailer <span class="text-danger">*</span></label>
                            <select name="retailer" id="modal_retailer" class="form-select form-select-modern" required>
                                <option value="" disabled selected>Select retailer</option>
                                <?php foreach ($retailers as $r): ?>
                                    <option value="<?php echo htmlspecialchars($r['retailer_name']); ?>">
                                        <?php echo htmlspecialchars($r['retailer_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-modern">Company Address <span class="text-danger">*</span></label>
                        <textarea name="address" id="modal_address" class="form-control form-control-modern" rows="3"
                            maxlength="200"></textarea>
                    </div>

                    <div class="toggle-row">
                        <div>
                            <div class="fw-semibold" style="font-size:0.88rem;">Active</div>
                            <div class="text-muted" style="font-size:0.78rem;">Enable this company for processing</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="active" id="modal_active" value="1"
                                role="switch" style="width: 2.6em; height: 1.4em;">
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

<!-- Hidden delete form -->
<form method="POST" action="company_form_process.php" id="deleteCompanyForm">
    <input type="hidden" name="form_action" value="delete">
    <input type="hidden" name="company_id" id="delete_company_id">
</form>

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

<script>
    // Auto-dismiss the server-rendered flash toast after a few seconds.
    setTimeout(function () {
        var toast = document.getElementById('server-toast');
        if (toast) {
            toast.classList.add('hide');
            setTimeout(function () { toast.remove(); }, 400);
        }
    }, 3500);

    function openAddCompany() {
        document.getElementById('companyModalLabel').innerText = 'Add New Company';
        document.getElementById('modal_company_id').value = '';
        document.getElementById('modal_name').value = '';
        document.getElementById('modal_nickname').value = '';
        document.getElementById('modal_vendorcode').value = '';
        document.getElementById('modal_refcode').value = '';
        document.getElementById('modal_retailer').selectedIndex = 0;
        document.getElementById('modal_address').value = '';
        document.getElementById('modal_active').checked = false;
    }

    function openEditCompany(company) {
        document.getElementById('companyModalLabel').innerText = 'Edit Company';
        document.getElementById('modal_company_id').value = company.id;
        document.getElementById('modal_name').value = company.name;
        document.getElementById('modal_nickname').value = company.nickname;
        document.getElementById('modal_vendorcode').value = company.vendorcode;
        document.getElementById('modal_refcode').value = company.refcode;
        document.getElementById('modal_retailer').value = company.retailer;
        document.getElementById('modal_address').value = company.address;
        document.getElementById('modal_active').checked = parseInt(company.active) === 1;

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('companyModal'));
        modal.show();
    }

    function submitDeleteCompany(id, name) {
        document.getElementById('deleteConfirmMessage').innerText = 'Delete "' + name + '"? This cannot be undone.';
        document.getElementById('delete_company_id').value = id;
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteConfirmModal'));
        modal.show();
    }

    document.getElementById('deleteConfirmButton').addEventListener('click', function () {
        document.getElementById('deleteCompanyForm').submit();
    });

    var companyTable;
    $(document).ready(function () {
        if ($('#companyTable').length) {
            companyTable = $('#companyTable').DataTable({
                dom: 't<"d-flex justify-content-between align-items-center px-3 pb-3"ip>',
                pageLength: 10,
                order: [[0, 'asc']]
            });
        }
    });

    var companySearchInput = document.getElementById('companySearch');
    if (companySearchInput) {
        companySearchInput.addEventListener('input', function () {
            if (companyTable) {
                companyTable.search(this.value).draw();
            }
        });
    }
</script>

<?php
include_once("footer.php");
?>