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
}
$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);
?>
<script type="text/javascript">
    $(document).ready(function () {
        CheckAdmin();
    });
</script>
<?php
include_once("nav.php");
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h4 class="mb-0">User Accounts</h4>
        <button class="btn btn-warning rounded" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill me-2"></i> Add User
        </button>

    </div>
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4">
            <div class="container mt-4">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle user-table">
                        <thead class="table-success">
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include('dbconnect.php');
                            $query = mysqli_query($conn, "SELECT * FROM dbuser ORDER BY fname ASC");

                            while ($row = mysqli_fetch_assoc($query)) {
                                $role = '';
                                if ($row['admin'])
                                    $role = 'Admin';
                                elseif ($row['semiadmin'])
                                    $role = 'Semi-Admin';
                                else
                                    $role = 'User';

                                $status = $row['active']
                                    ? '<span class="badge bg-success">Active</span>'
                                    : '<span class="badge bg-danger">Inactive</span>';

                                echo "
                                        <tr>
                                        <td>{$row['fname']}</td>
                                        <td>{$row['username']}</td>
                                        <td>{$role}</td>
                                        <td>{$status}</td>
                                        <td class='text-center'>
                                            <button class='btn btn-sm btn-primary me-1' onclick='EditUser({$row['id']})'>Edit</button>
                                        </td>
                                        </tr>
                                    ";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- modal for new user -->
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content ">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title fw-bold" id="addUserModalLabel">
                                <i class="bi bi-person-plus-fill me-2"></i> Add New User
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form class="form-user row g-2 p-4" onsubmit="return NewUser();">
                            <table class="tbl-user">
                                <div class="col-md-6">
                                    <label class="form-label">Name:</label>
                                    <input type="text"
                                        class="form-control input-withBorder input-form-field input-fname" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Username:</label>
                                    <input type="text"
                                        class="form-control input-withBorder input-form-field input-username" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status:</label>
                                    <select type="password" class="form-select input-withBorder input-form-field "
                                        required>
                                        <option selected disabled>select user status</option>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">User Access:</label>
                                    <select type="password" class="form-select input-withBorder input-form-field "
                                        required>
                                        <option selected disabled>select user access</option>
                                        <option value="1">Admin</option>
                                        <option value="1">Semi Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Password:</label>
                                    <input type="password"
                                        class="form-control input-withBorder input-form-field input-password"
                                        maxlength="10" required>
                                </div>
                                <td class="tbl-user-td3" colspan="4">
                                    <div style="overflow-y: auto;height: 100%;width: 100%;">
                                        <table class="tbl-user-access">
                                            <tr>
                                                <th class="tbl-user-access-th" colspan="10">Location Access</th>
                                            </tr>
                                            <tr>
                                                <?php
                                                for ($iloc1 = 1; $iloc1 <= 5; $iloc1++) {
                                                    // get location name
                                                    $location_query = mysqli_query($conn, "SELECT * FROM dblocation WHERE id='$iloc1'");
                                                    $fetch_location = mysqli_fetch_array($location_query);
                                                    ?>
                                                    <td class="tbl-user-access-td1">
                                                        <input type="checkbox"
                                                            class="input-checkbox input-form-field input-loc<?php echo $fetch_location['id']; ?>"
                                                            value="1">
                                                    </td>
                                                    <td class="tbl-user-access-td2">
                                                        <?php echo $fetch_location['location']; ?>
                                                    </td>
                                                    <?php
                                                }
                                                ?>
                                            </tr>
                                            <tr>
                                                <?php
                                                for ($iloc2 = 6; $iloc2 <= 10; $iloc2++) {
                                                    // get location name
                                                    $location_query = mysqli_query($conn, "SELECT * FROM dblocation WHERE id='$iloc2'");
                                                    $fetch_location = mysqli_fetch_array($location_query);
                                                    ?>
                                                    <td class="tbl-user-access-td1">
                                                        <input type="checkbox"
                                                            class="input-checkbox input-form-field input-loc<?php echo $fetch_location['id']; ?>"
                                                            value="1">
                                                    </td>
                                                    <td class="tbl-user-access-td2">
                                                        <?php echo $fetch_location['location']; ?>
                                                    </td>
                                                    <?php
                                                }
                                                ?>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                                </tr>
                                <tr>
                                    <td class="tbl-user-td1" colspan="4" style="text-align:right;">
                                        <button type="button" class="btn btn-sm btn-secondary px-4 button-user"
                                            onclick="UnloadUser();">Cancel</button>
                                        <button class="btn btn-sm btn-primary px-4 button-user button-user-save" type="button">Save</button>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include_once("footer.php");
?>
<?php $conn->close(); ?>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script>
    $(document).ready(function () {
        $('.user-table').DataTable({
            pageLength: 10,
            lengthChange: true,
            searching: true,
            ordering: true,
            responsive: true,
            language: {
                search: "Search user:",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
            }
        });
    });

</script>


<script src="js/jquery.js"></script>
<script type="text/javascript" src="js/index.js"></script>


<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Custom scripts for all pages-->
<script src="js/sb-admin-2.min.js"></script>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables + Bootstrap 5 integration -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<!-- DataTables scripts -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- bootstrap icon -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">