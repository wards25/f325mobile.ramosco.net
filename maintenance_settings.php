<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id']) || $_SESSION['admin'] != 1) {
    header("Location: index.php");
    exit();
}

include_once("nav.php");

$username = $_SESSION['fname'];

// --- Handle toggle ---
if (isset($_POST['toggle_maintenance'])) {
    $moduleKey = mysqli_real_escape_string($conn, $_POST['module_key']);
    $newStatus = intval($_POST['new_status']);
    $message   = mysqli_real_escape_string($conn, $_POST['message'] ?? '');
    $updatedAt = date('Y-m-d H:i:s');

    mysqli_query($conn, "
        UPDATE tbl_maintenance
        SET is_maintenance = $newStatus,
            message = '$message',
            updated_by = '$username',
            updated_at = '$updatedAt'
        WHERE module_key = '$moduleKey'
    ");

    $label = $newStatus == 1 ? "Maintenance enabled" : "Maintenance disabled";
    $_SESSION['maint_success'] = "$label for <b>$moduleKey</b>.";

    header("Location: maintenance_settings.php");
    exit();
}

// --- Fetch all modules ---
$result  = mysqli_query($conn, "SELECT * FROM tbl_maintenance ORDER BY id ASC");
$modules = [];
$fullWebsite = null;
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['module_key'] === 'full_website') {
        $fullWebsite = $row;
    } else {
        $modules[] = $row;
    }
}

$activeCount = array_sum(array_column($modules, 'is_maintenance'));
?>

<style>
    .maint-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .maint-stats {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .stat-pill {
        background: #fff;
        border: 1.5px solid #e8ddf0;
        border-radius: 20px;
        padding: 6px 16px;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .stat-pill .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .stat-pill .dot.green  { background: #4caf50; }
    .stat-pill .dot.orange { background: #f5a623; }
    .stat-pill .dot.red    { background: #e74c3c; }

    /* Search */
    .search-bar-wrap {
        position: relative;
        margin-bottom: 16px;
    }

    .search-bar-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        font-size: 13px;
    }

    .module-search {
        width: 100%;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px 10px 36px;
        font-size: 13px;
        color: #333;
        background: #fff;
        transition: border-color 0.2s;
    }

    .module-search:focus {
        outline: none;
        border-color: #915c83;
        box-shadow: 0 0 0 3px rgba(145,92,131,0.1);
    }

    /* Cards */
    .maint-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        overflow: hidden;
        margin-bottom: 10px;
        border: 1.5px solid #f0e6f5;
        transition: box-shadow 0.2s, border-color 0.2s;
    }

    .maint-card:hover {
        box-shadow: 0 4px 20px rgba(145,92,131,0.1);
    }

    .maint-card.is-active { border-color: #f5a623; background: #fffbf5; }
    .maint-card.is-full   { border-color: #e74c3c; background: #fff8f8; }

    .maint-card-body {
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .module-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .module-icon.off  { background: #f3ebf7; color: #915c83; }
    .module-icon.on   { background: #fff3e0; color: #f5a623; }
    .module-icon.full { background: #ffeaea; color: #e74c3c; }

    .module-info { flex: 1; min-width: 0; }

    .module-title {
        font-size: 14px;
        font-weight: 700;
        color: #2d1f2e;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .module-key-badge {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 1px;
        background: #f0e6f5;
        color: #915c83;
        padding: 2px 8px;
        border-radius: 10px;
        text-transform: uppercase;
    }

    .module-key-badge.full { background: #ffeaea; color: #e74c3c; }

    .module-message {
        font-size: 12px;
        color: #999;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 500px;
    }

    .module-meta {
        font-size: 11px;
        color: #bbb;
        margin-top: 3px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 20px;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .status-badge.online       { background: #e8f5e9; color: #2e7d32; }
    .status-badge.offline      { background: #fff3e0; color: #e65100; }
    .status-badge.full-offline { background: #ffeaea; color: #c62828; }

    .btn-toggle {
        border: none;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-toggle.enable      { background: #fff3e0; color: #e65100; border: 1.5px solid #ffcc80; }
    .btn-toggle.enable:hover { background: #e65100; color: #fff; border-color: #e65100; }

    .btn-toggle.disable      { background: #e8f5e9; color: #2e7d32; border: 1.5px solid #a5d6a7; }
    .btn-toggle.disable:hover { background: #2e7d32; color: #fff; border-color: #2e7d32; }

    .btn-toggle.enable-full      { background: #ffeaea; color: #c62828; border: 1.5px solid #ef9a9a; }
    .btn-toggle.enable-full:hover { background: #c62828; color: #fff; border-color: #c62828; }

    .section-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #915c83;
        margin: 20px 0 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e8ddf0;
    }

    .alert-maint {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #a5d6a7;
    }

    .modal-label {
        font-size: 12px;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        display: block;
    }

    .modal-input {
        width: 100%;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 13px;
        color: #333;
        background: #fafafa;
        transition: border-color 0.2s;
        resize: vertical;
    }

    .modal-input:focus {
        outline: none;
        border-color: #915c83;
        box-shadow: 0 0 0 3px rgba(145,92,131,0.1);
    }

    .no-results {
        text-align: center;
        padding: 32px;
        color: #bbb;
        font-size: 13px;
        display: none;
    }

    .no-results i {
        font-size: 28px;
        display: block;
        margin-bottom: 8px;
    }
</style>

<div class="container-fluid">

    <div class="maint-header">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tools" style="color:#915c83;"></i> Maintenance Manager
        </h1>
        <div class="maint-stats">
            <div class="stat-pill">
                <span class="dot green"></span>
                <?php echo count($modules) - $activeCount; ?> Online
            </div>
            <div class="stat-pill">
                <span class="dot orange"></span>
                <?php echo $activeCount; ?> In Maintenance
            </div>
            <?php if ($fullWebsite && $fullWebsite['is_maintenance']): ?>
            <div class="stat-pill">
                <span class="dot red"></span>
                Full Maintenance Active
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['maint_success'])): ?>
        <div class="alert-maint">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['maint_success']; unset($_SESSION['maint_success']); ?>
        </div>
    <?php endif; ?>

    <!-- Full Website Maintenance -->
    <div class="section-label"><i class="fas fa-globe"></i> Full Website</div>

    <?php if ($fullWebsite): ?>
        <?php
            $isOn      = $fullWebsite['is_maintenance'] == 1;
            $cardClass = $isOn ? 'is-full' : '';
            $iconClass = $isOn ? 'full' : 'off';
            $icon      = $isOn ? 'fa-exclamation-triangle' : 'fa-globe';
        ?>
        <div class="maint-card <?php echo $cardClass; ?>">
            <div class="maint-card-body">
                <div class="module-icon <?php echo $iconClass; ?>">
                    <i class="fas <?php echo $icon; ?>"></i>
                </div>
                <div class="module-info">
                    <div class="module-title">
                        <?php echo htmlspecialchars($fullWebsite['module_name']); ?>
                        <span class="module-key-badge full">full_website</span>
                        <?php if ($isOn): ?>
                            <span class="status-badge full-offline"><i class="fas fa-circle" style="font-size:7px;"></i> ACTIVE</span>
                        <?php else: ?>
                            <span class="status-badge online"><i class="fas fa-circle" style="font-size:7px;"></i> ONLINE</span>
                        <?php endif; ?>
                    </div>
                    <div class="module-message"><?php echo htmlspecialchars($fullWebsite['message']); ?></div>
                    <?php if ($fullWebsite['updated_by']): ?>
                        <div class="module-meta">Last updated by <b><?php echo htmlspecialchars($fullWebsite['updated_by']); ?></b> on <?php echo $fullWebsite['updated_at']; ?></div>
                    <?php endif; ?>
                </div>
                <button class="btn-toggle <?php echo $isOn ? 'disable' : 'enable-full'; ?>"
                    onclick="openModal('<?php echo $fullWebsite['module_key']; ?>', '<?php echo htmlspecialchars($fullWebsite['module_name'], ENT_QUOTES); ?>', <?php echo $isOn ? 0 : 1; ?>, '<?php echo htmlspecialchars($fullWebsite['message'], ENT_QUOTES); ?>', true)">
                    <i class="fas <?php echo $isOn ? 'fa-check-circle' : 'fa-power-off'; ?>"></i>
                    <?php echo $isOn ? 'Disable Full Maintenance' : 'Enable Full Maintenance'; ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Module Maintenance -->
    <div class="section-label"><i class="fas fa-cube"></i> Modules</div>

    <!-- Search -->
    <div class="search-bar-wrap">
        <i class="fas fa-search"></i>
        <input type="text" class="module-search" id="moduleSearch" placeholder="Search modules...">
    </div>

    <div id="moduleList">
        <?php foreach ($modules as $mod): ?>
            <?php
                $isOn      = $mod['is_maintenance'] == 1;
                $cardClass = $isOn ? 'is-active' : '';
                $iconClass = $isOn ? 'on' : 'off';
                $icon      = $isOn ? 'fa-wrench' : 'fa-cube';
            ?>
            <div class="maint-card <?php echo $cardClass; ?>" data-name="<?php echo strtolower($mod['module_name']); ?> <?php echo strtolower($mod['module_key']); ?>">
                <div class="maint-card-body">
                    <div class="module-icon <?php echo $iconClass; ?>">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="module-info">
                        <div class="module-title">
                            <?php echo htmlspecialchars($mod['module_name']); ?>
                            <span class="module-key-badge"><?php echo htmlspecialchars($mod['module_key']); ?></span>
                            <?php if ($isOn): ?>
                                <span class="status-badge offline"><i class="fas fa-circle" style="font-size:7px;"></i> MAINTENANCE</span>
                            <?php else: ?>
                                <span class="status-badge online"><i class="fas fa-circle" style="font-size:7px;"></i> ONLINE</span>
                            <?php endif; ?>
                        </div>
                        <div class="module-message"><?php echo htmlspecialchars($mod['message']); ?></div>
                        <?php if ($mod['updated_by']): ?>
                            <div class="module-meta">Last updated by <b><?php echo htmlspecialchars($mod['updated_by']); ?></b> on <?php echo $mod['updated_at']; ?></div>
                        <?php endif; ?>
                    </div>
                    <button class="btn-toggle <?php echo $isOn ? 'disable' : 'enable'; ?>"
                        onclick="openModal('<?php echo $mod['module_key']; ?>', '<?php echo htmlspecialchars($mod['module_name'], ENT_QUOTES); ?>', <?php echo $isOn ? 0 : 1; ?>, '<?php echo htmlspecialchars($mod['message'], ENT_QUOTES); ?>', false)">
                        <i class="fas <?php echo $isOn ? 'fa-check-circle' : 'fa-wrench'; ?>"></i>
                        <?php echo $isOn ? 'Disable Maintenance' : 'Enable Maintenance'; ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="no-results" id="noResults">
            <i class="fas fa-search"></i>
            No modules found matching your search.
        </div>
    </div>

</div>

<!-- Confirm Modal -->
<div class="modal fade" id="maintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px; border:none;">
            <div class="modal-header" style="border-bottom:1px solid #f0e6f5;">
                <h5 class="modal-title" id="maintModalTitle" style="color:#2d1f2e; font-weight:700;"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="maintenance_settings.php">
                <div class="modal-body">
                    <input type="hidden" name="toggle_maintenance" value="1">
                    <input type="hidden" name="module_key" id="modal_module_key">
                    <input type="hidden" name="new_status" id="modal_new_status">

                    <p id="modal_confirm_text" style="font-size:13px; color:#666; margin-bottom:16px;"></p>

                    <div id="message_wrap">
                        <label class="modal-label">Maintenance Message <small style="color:#aaa;">(shown to users)</small></label>
                        <textarea class="modal-input" name="message" id="modal_message" rows="3"
                            placeholder="Message shown to users during maintenance..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0e6f5;">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm" id="modal_submit_btn"
                        style="background:#915c83; color:#fff; border-radius:8px; padding:8px 20px; border:none;">
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal(moduleKey, moduleName, newStatus, currentMessage, isFull) {
        document.getElementById('modal_module_key').value = moduleKey;
        document.getElementById('modal_new_status').value = newStatus;
        document.getElementById('modal_message').value    = currentMessage;

        const isEnabling = newStatus == 1;

        const title = isEnabling
            ? `Enable Maintenance — ${moduleName}`
            : `Disable Maintenance — ${moduleName}`;

        const text = isEnabling
            ? `Are you sure you want to put <b>${moduleName}</b> under maintenance? Non-admin users will be redirected to the maintenance page.`
            : `Are you sure you want to bring <b>${moduleName}</b> back online? Users will be able to access it again.`;

        let btnColor = '#2e7d32';
        if (isEnabling) btnColor = isFull ? '#c62828' : '#e65100';

        document.getElementById('maintModalTitle').textContent       = title;
        document.getElementById('modal_confirm_text').innerHTML      = text;
        document.getElementById('modal_submit_btn').style.background = btnColor;
        document.getElementById('modal_submit_btn').textContent      = isEnabling
            ? (isFull ? 'Yes, Enable Full Maintenance' : 'Yes, Enable Maintenance')
            : 'Yes, Bring Online';

        // Only show message textarea when enabling maintenance
        document.getElementById('message_wrap').style.display = isEnabling ? 'block' : 'none';

        new bootstrap.Modal(document.getElementById('maintModal')).show();
    }

    // Module search
    document.getElementById('moduleSearch').addEventListener('input', function () {
        const search = this.value.toLowerCase().trim();
        const cards  = document.querySelectorAll('#moduleList .maint-card');
        let visible  = 0;

        cards.forEach(card => {
            const name  = card.getAttribute('data-name') || '';
            const match = name.includes(search);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
    });
</script>

<?php include_once("footer.php"); ?>