<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

?>

<?php include 'nav.php' ?>
<div class="container my-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header text-white text-center py-3 rounded-top-4" style="background-color: #915c83;">
            <h4 class="mb-0">Company Information</h4>
        </div>
        <div class="card-body p-4">

            <form class="form-location" onsubmit="return UpdateLocation();">
                <table class="tbl-location">
                    <tr>
                        <th class="tbl-location-th">Location</th>
                    </tr>
                    <tr>
                        <td class="tbl-location-td1">
                            <div style="overflow-y: auto;height: 100%;width: 100%;">
                                <table class="tbl-list-location">
                                    <tbody class="tbody-list-location"></tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="tbl-location-td2">
                            <button class="button-location-style">Save</button>
                            <button type="button" class="button-location-style"
                                onclick="UnloadLocation();">Cancel</button>
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