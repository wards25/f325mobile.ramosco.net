<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
}

$result = mysqli_query($conn, "SELECT admin, semiadmin FROM dbuser WHERE id = " . $_SESSION['id']);
$user = mysqli_fetch_assoc($result);

// If not admin, redirect to dashboard or another page
if ($user['admin'] != 1) {
    header("Location: unauthorized_access.php");
    exit;
}

// Get the ID of the user to update
if (!isset($_GET['id'])) {
    header("Location: account.php");
    exit;
}
$id = intval($_GET['id']);

// Fetch the specific user's data
$query = mysqli_query($conn, "SELECT * FROM dbuser WHERE id = '$id'");
$editUser = mysqli_fetch_assoc($query);
?>
<?php
include_once("nav.php");
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h4 class="mb-0">Update Permission</h4>
        <a href="account.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4">
            <form class="row g-2 p-4" id="updateUserForm">
                <input type="text" name="id" value="<?php echo $id; ?>" hidden>
                <div class="col-md-6">
                    <label class="form-label">Name:</label>
                    <input type="text" class="form-control input-withBorder input-form-field input-fname"
                        value="<?php echo htmlspecialchars($editUser['fname']); ?>" required readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Username:</label>
                    <input type="text" maxlength="15"
                        class="form-control input-withBorder input-form-field input-username"
                        value="<?php echo htmlspecialchars($editUser['username']); ?>" required readonly>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Status:</label>
                    <select class="form-select input-withBorder input-form-field input-active" required readonly>
                        <option disabled>Select user status</option>
                        <option value="1" <?php echo ($editUser['active'] == 1) ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo ($editUser['active'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">User Access:</label>
                    <select class="form-select input-withBorder input-form-field input-access" required readonly>
                        <option <?php echo ($editUser['admin'] == 0 && $editUser['semiadmin'] == 0) ? 'selected' : ''; ?>
                            disabled>Select user access</option>
                        <option value="1" <?php echo ($editUser['admin'] == 1) ? 'selected' : ''; ?>>Admin</option>
                        <option value="2" <?php echo ($editUser['semiadmin'] == 1) ? 'selected' : ''; ?>>Semi Admin
                        </option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Password:</label>
                    <input type="password" class="form-control input-withBorder input-form-field input-password"
                        maxlength="10" value="<?php echo htmlspecialchars($editUser['password']); ?>" required>
                </div>

                <div class="col-md-12 text-end mt-3">
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
                <hr class="my-4">

                <h5 class="mb-3">Access Permissions</h5>

                <!-- Unified Table for All Permissions -->
                <div class="table-responsive mb-4" style="max-height: 300px; overflow-y: auto;">
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
                            // Company Access Section
                            $company_query = mysqli_query($conn, "SELECT * FROM dbcompany");
                            while ($company = mysqli_fetch_assoc($company_query)) {
                                if ($editUser['comp' . $company['id']] == 1) {
                                    echo "
                                        <tr>
                                            <td>Company</td>
                                            <td><label class='form-check-label ms-2'>{$company['name']}</label></td>
                                            <td class='text-center'>
                                                <div class='form-check form-switch d-flex justify-content-center'>
                                                    <input class='form-check-input' type='checkbox' name='company[]' value='{$company['id']}' checked>
                                                </div>
                                            </td>
                                        </tr>";
                                }
                            }

                            // Location Access Section
                            $location_query = mysqli_query($conn, "SELECT * FROM dblocation");
                            while ($loc = mysqli_fetch_assoc($location_query)) {
                                if ($editUser['loc' . $loc['id']] == 1) {
                                    echo "
                                        <tr>
                                            <td>Location</td>
                                            <td><label class='form-check-label ms-2'>{$loc['location']}</label></td>
                                            <td class='text-center'>
                                                <div class='form-check form-switch d-flex justify-content-center'>
                                                    <input class='form-check-input' type='checkbox' name='location[]' value='{$loc['id']}' checked>
                                                </div>
                                            </td>
                                        </tr>";
                                }
                            }

                            // Module Access Section
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
                                'syssetting' => 'System Setting',
                            ];

                            foreach ($modules as $key => $label) {
                                if ($editUser[$key] == 1) {
                                    echo "
                                        <tr>
                                            <td>Module</td>
                                            <td>$label</td>
                                            <td class='text-center'>
                                                <div class='form-check form-switch d-flex justify-content-center'>
                                                    <input class='form-check-input' type='checkbox' name='$key' id='$key' checked>
                                                </div>
                                            </td>
                                        </tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once("footer.php"); ?>
<script>
$(document).ready(function() {
    $('#updateUserForm').on('submit', function(e) {
        e.preventDefault(); // prevent default form submission

        // Serialize form data
        var formData = $(this).serialize();

        $.ajax({
            url: 'update-user.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log(response);
            },
            error: function(xhr, status, error) {
                alert("An error occurred: " + error);
            }
        });
    });
});
</script>
