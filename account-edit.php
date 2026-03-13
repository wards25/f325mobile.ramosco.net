<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

$username = $_SESSION['fname'];
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Only admins
$result = mysqli_query($conn, "SELECT admin, semiadmin FROM dbuser WHERE id = " . $_SESSION['id']);
$user = mysqli_fetch_assoc($result);
if ($user['admin'] != 1) {
    header("Location: unauthorized_access.php");
    exit;
}

// Get the user to edit
if (!isset($_GET['id'])) {
    header("Location: account.php");
    exit;
}
$id = intval($_GET['id']);

// Default: fetch current user permissions
$editUser = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM dbuser WHERE id = '$id'"));

// Handle copy permissions
if (isset($_POST['copy_permissions']) && !empty($_POST['copy_user_id'])) {
    $copyUserId = intval($_POST['copy_user_id']);
    $copyUser = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM dbuser WHERE id = '$copyUserId'"));
    if ($copyUser) {
        // Copy companies
        $company_query = mysqli_query($conn, "SELECT id FROM dbcompany");
        while ($c = mysqli_fetch_assoc($company_query)) {
            $editUser['comp' . $c['id']] = $copyUser['comp' . $c['id']];
        }

        // Copy modules
        $modules = [
            'store',
            'inventory',
            'import',
            'importdop',
            'print',
            'schedule',
            'clearing',
            'manual',
            'fordeduct',
            'borfapps',
            'dmpiraw',
            'deduction',
            'deductdoc',
            'paiddeduction',
            'payment',
            'returntosupplier',
            'pulloutdoc',
            'report',
            'syssetting'
        ];
        foreach ($modules as $mod) {
            $editUser[$mod] = $copyUser[$mod];
        }
    }
}

// Handle update permissions
if (isset($_POST['save_changes'])) {
    // Reset all company permissions first
    $company_query = mysqli_query($conn, "SELECT id FROM dbcompany");
    while ($c = mysqli_fetch_assoc($company_query)) {
        $compField = 'comp' . $c['id'];
        $value = (isset($_POST['company']) && in_array($c['id'], $_POST['company'])) ? 1 : 0;
        mysqli_query($conn, "UPDATE dbuser SET $compField = $value WHERE id = $id");
    }

    // Reset all modules
    $modules = [
        'store',
        'inventory',
        'import',
        'importdop',
        'print',
        'schedule',
        'clearing',
        'manual',
        'fordeduct',
        'borfapps',
        'dmpiraw',
        'deduction',
        'deductdoc',
        'paiddeduction',
        'payment',
        'returntosupplier',
        'pulloutdoc',
        'report',
        'syssetting'
    ];
    foreach ($modules as $mod) {
        $value = (isset($_POST['modules']) && in_array($mod, $_POST['modules'])) ? 1 : 0;
        mysqli_query($conn, "UPDATE dbuser SET $mod = $value WHERE id = $id");
    }

    header("Location: account-edit.php?id=$id&success=1");
    exit;
}

include_once("nav.php");
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h4 class="mb-0">Update Permission</h4>
        <a href="account.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div id="success-alert" class='alert alert-success'>Permissions updated successfully!</div>
    <?php endif; ?>


    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4">
            <form class="row g-2 p-4" method="POST">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="col-md-6">
                    <label class="form-label">Name:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($editUser['fname']); ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Username:</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($editUser['username']); ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status:</label>
                    <input type="text" class="form-control" value="<?php echo ($editUser['active'] == 1) ? 'Active' : 'Inactive'; ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">User Access:</label>
                    <input type="text" class="form-control" value="<?php
                                                                    echo ($editUser['admin'] == 1) ? 'Admin' : (($editUser['semiadmin'] == 1) ? 'Semi Admin' : 'User'); ?>" readonly>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Password:</label>
                    <input type="password" class="form-control" value="<?php echo htmlspecialchars($editUser['password']); ?>" readonly>
                </div>

                <hr class="my-4">

                <!-- Copy Permissions -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Copy Permissions From:</label>
                        <div class="input-group">
                            <select class="form-select" name="copy_user_id">
                                <option value="" selected disabled>Select a user</option>
                                <?php
                                $users_query = mysqli_query($conn, "SELECT id, fname, username FROM dbuser WHERE id != $id");
                                while ($u = mysqli_fetch_assoc($users_query)) {
                                    echo "<option value='{$u['id']}'>{$u['fname']}</option>";
                                }
                                ?>
                            </select>
                            <button type="submit" name="copy_permissions" class="btn btn-md btn-success ms-3"><i class="bi bi-clipboard"></i> Copy</button>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Access Permissions</h5>

                <div class="table-responsive mb-4" style="max-height: 300px; overflow-y:auto;">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Name</th>
                                <th class="text-center">Access</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Companies
                            $company_query = mysqli_query($conn, "SELECT * FROM dbcompany");
                            while ($company = mysqli_fetch_assoc($company_query)) {
                                $checked = ($editUser['comp' . $company['id']] == 1) ? 'checked' : '';
                                echo "
                                <tr>
                                    <td>Company</td>
                                    <td>{$company['name']}</td>
                                    <td class='text-center'>
                                        <input type='checkbox' name='company[]' value='{$company['id']}' $checked>
                                    </td>
                                </tr>";
                            }

                            // Modules
                            $modules = [
                                'store' => 'Store',
                                'inventory' => 'Inventory',
                                'import' => 'Import',
                                'importdop' => 'Import DOP',
                                'print' => 'Print',
                                'schedule' => 'Schedule',
                                'clearing' => 'Clearing',
                                'manual' => 'Manual',
                                'fordeduct' => 'For Deduction',
                                'borfapps' => 'BORF Apps',
                                'dmpiraw' => 'DMI Praw',
                                'deduction' => 'Deduction',
                                'deductdoc' => 'Deduct Doc',
                                'paiddeduction' => 'Paid Deduction',
                                'payment' => 'Payment',
                                'returntosupplier' => 'Return to Supplier',
                                'pulloutdoc' => 'Pullout Doc',
                                'report' => 'Report',
                                'syssetting' => 'System Setting'
                            ];

                            foreach ($modules as $key => $label) {
                                $checked = ($editUser[$key] == 1) ? 'checked' : '';
                                echo "
                                <tr>
                                    <td>Module</td>
                                    <td>$label</td>
                                    <td class='text-center'>
                                        <input type='checkbox' name='modules[]' value='$key' $checked>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-12 text-end mt-3">
                    <button type="submit" name="save_changes" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once("footer.php"); ?>
<script>
    setTimeout(function() {
        var alert = document.getElementById('success-alert');
        if (alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500); 
        }
    }, 1000);
</script>