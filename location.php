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

<?php include 'nav.php' ?>
<div class="alert alert-success" role="alert" style="display: none;"></div>
<div class="container  min-vh-100 d-flex justify-content-center align-items-center">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header text-white text-center py-3 rounded-top-4" style="background-color: #915c83;">
            <h4 class="mb-0">Location</h4>
        </div>
        <div class="card-body p-4">

            <form class="form-location" onsubmit="return UpdateLocation();">
                <table class="tbl-location">
                    <tr>
                        <th class="tbl-location-th">List of Location</th>
                    </tr>
                    <tr>
                        <td class="tbl-location-td1 text">
                            <div style="overflow-y: auto;height: 100%;width: 100%;">
                                <table class="tbl-list-location">
                                    <tbody class="tbody-list-location"></tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="tbl-location-td2">
                            <button class="btn btn-primary btn-sm button-location-style mt-4 button-update-location">Save</button>
                            <!-- <button type="button" class="button-location-style"
                                onclick="UnloadLocation();">Cancel</button> -->
                        </td>
                    </tr>
                </table>
            </form>

        </div>
    </div>
</div>
<?php include 'footer.php' ?>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>