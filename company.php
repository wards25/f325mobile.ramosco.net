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

<?php
include_once("nav.php");
?>
<div class="container my-5">
  <div class="card shadow-lg border-0 rounded-4">
    <div class="card-header text-white text-center py-3 rounded-top-4" style="background-color: #915c83;">
      <h4 class="mb-0">Company Information</h4>
    </div>
    <div class="card-body p-4">

      <form class="form-company" onsubmit="return UpdateCompany();">

        <!-- Company Dropdown -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Company</label>
          <select class="form-select select-list-company" onchange="SelectCompany();" required></select>
        </div>

        <!-- Name and Nickname -->
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Name</label>
            <input type="text" class="form-control input-companyname" placeholder="Enter full company name" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nickname</label>
            <input type="text" class="form-control input-nickname" placeholder="Short name" required>
          </div>
        </div>

        <!-- Reference and Vendor Codes -->
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Reference Code</label>
            <input type="text" class="form-control input-referencecode" 
              oninput="this.value=this.value.replace(/[^0-9.]/g,'').replace(/(\..*?)\..*/g,'$1');"
              placeholder="e.g. 12345" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Vendor Code</label>
            <input type="text" class="form-control input-vendorcode" placeholder="e.g. VEND01" required>
          </div>
        </div>

        <!-- Company Address -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Company Address</label>
          <textarea class="form-control textarea-address" rows="3" placeholder="Enter company address"></textarea>
        </div>

        <!-- Process Section -->
        <div class="mb-3 border rounded-3 p-3 bg-light">
          <h6 class="fw-bold mb-3"><i class="bi bi-gear me-2"></i>Process Settings</h6>
          <div class="form-check form-check-inline">
            <input class="form-check-input input-active" type="checkbox" value="1" id="active">
            <label class="form-check-label" for="active">Active</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input input-bypass" type="checkbox" value="1" id="bypass">
            <label class="form-check-label" for="bypass">Bypass Deduct</label>
          </div>
        </div>

        <!-- Buttons -->
        <div class="text-end">
          <button type="submit" class="btn btn-primary px-4 me-2 button-update-company">
            <i class="bi bi-save me-1"></i> Update
          </button>
          <button type="button" class="btn btn-outline-secondary px-4 button-unload-company" onclick="UnloadCompany();">
            Cancel
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<?php
include_once("footer.php");
?>

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