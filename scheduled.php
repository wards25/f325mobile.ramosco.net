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
include_once('header.php');
include_once("nav.php");
?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h4 class="mb-0">Schedule</h4>
        <div>
            <button class="btn btn-sm btn-primary export-csv">Export CSV</button>
            <button class="btn btn-sm btn-success import-csv">Import CSV</button>
        </div>
    </div>

    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4">
            <div class="container mt-2">
                <div class="table-responsive">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <select class="form-select select-withNoBorder select-search">
                                <option value="f325number" placeholder="F325 Number...">F325 Number:</option>
                                <option value="brcode" placeholder="Branch Code...">Branch Code:</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <input type="text" class="form-control input-withBorder input-search"
                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');"
                                onkeyup="LoadNotepadList();" placeholder="F325 Number..." value="">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="lbl-style">Status:</label>
                            <select class="form-select select-withBorder select-status" onchange="LoadNotepadList();">
                                <option value="PRINTED">Printed</option>
                                <option value="SCHEDULED">Scheduled</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="lbl-style">Company:</label>
                            <select class="form-select select-withBorder select-company" onchange="LoadNotepadList();">
                                <option value="">All</option>
                                <?php
                                $company_query = "SELECT * FROM dbcompany WHERE active='1' ";

                                // company
                                $company = "vendorcode='' ";

                                for ($i = 1; $i <= 10; $i++) {
                                    //get vendor code of company
                                    $vendorcode_query = mysqli_query($conn, "SELECT * FROM dbcompany WHERE id='$i' ");
                                    $fetch_vendorcode = mysqli_fetch_array($vendorcode_query);

                                    if ($_SESSION['comp' . $fetch_vendorcode['id']]) {
                                        $company .= "OR vendorcode='" . $fetch_vendorcode['vendorcode'] . "'";
                                    }
                                }

                                $company_query .= "AND (" . $company . ") ";

                                $vendor_query = mysqli_query($conn, $company_query);
                                while ($fetch_vendor = mysqli_fetch_array($vendor_query)) {
                                    ?>
                                    <option value="<?php echo $fetch_vendor['vendorcode']; ?>">
                                        <?php echo $fetch_vendor['name']; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <table class="tbl-border table table-striped" cellspacing="0" width="100%">
                        <thead class="thead-dark">
                            <tr>
                                <th class="tbl-list-order-th1">F325 #</th>
                                <th class="tbl-list-order-th2">Branch Name</th>
                                <th class="tbl-list-order-th3">Email Date</th>
                                <th class="tbl-list-order-th4">F325 Date</th>
                                <th class="tbl-list-order-th5">Vendor</th>
                                <th class="tbl-list-order-th6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="tbody-list-order"></tbody>
                    </table>

                    <!-- View Order Detail -->
                    <div class="modal" id="order-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div class="btn-group gap-2">
                                            <button type="button" class="btn btn-outline-primary button-reopen-notepad"
                                                onclick="reScheduleNotepad()">Re-Open</button>
                                            <button type="button"
                                                class="btn btn-outline-primary button-schedule-notepad"
                                                onclick="scheduleNotepad()">Scheduled</button>
                                            <!-- History Dropdown -->
                                            <div class="btn-group">
                                                <button type="button"
                                                    class="btn btn-outline-secondary dropdown-toggle button-history"
                                                    data-bs-toggle="dropdown">
                                                    History
                                                </button>
                                                <div class="dropdown-menu p-2"
                                                    style="max-height: 250px; overflow-y: auto;">
                                                    <div class="history">
                                                        <table class="table table-sm table-bordered mb-0 tbl-history">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Name</th>
                                                                    <th>Processed</th>
                                                                    <th>Date & Time</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="tbody-history-list"></tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                        onclick="UnloadNotepadDetail()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="container py-4">
                                        <!-- Vendor & Reference Info -->
                                        <div class="row g-3 mb-4">
                                            <!-- Vendor Info -->
                                            <div class="col-md-12">
                                                <div class="card p-3">
                                                    <h6 class="fw-bold mb-3">Vendor Details</h6>

                                                    <div class="row mb-2">
                                                        <div class="col-md-3 fw-bold">Branch:</div>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control input-customer"
                                                                disabled>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-md-3 fw-bold">Company:</div>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control input-company"
                                                                disabled>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-md-3 fw-bold">Email Date:</div>
                                                        <div class="col-md-9">
                                                            <input type="date" class="form-control input-emaildate"
                                                                disabled>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class="col-md-3 fw-bold">Issued By:</div>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control input-issued"
                                                                disabled>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-3 fw-bold">Prepared By:</div>
                                                        <div class="col-md-9">
                                                            <input type="text" class="form-control input-prepared"
                                                                disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Transport Information -->
                                            <div class="col-md-8">
                                                <div class="card p-3">
                                                    <h6 class="fw-bold mb-3">Transport Details</h6>

                                                    <div class="mb-3">
                                                        <label class="fw-bold">TM Number<span class="text-danger">
                                                                *</span></label>
                                                        <input type="text" class="form-control input-tmnumber">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="fw-bold">Driver<span class="text-danger">
                                                                *</span></label>
                                                        <input type="text" class="form-control input-driver">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="fw-bold">Plate Number<span class="text-danger">
                                                                *</span></label>
                                                        <input type="text" class="form-control input-platenumber">
                                                    </div>

                                                    <div class="mb-2">
                                                        <label class="fw-bold">Date Scheduled<span class="text-danger">
                                                                *</span></label>
                                                        <input type="date" class="form-control input-datesched"
                                                            value="<?php echo date('Y-m-d'); ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Reference Info -->
                                            <div class="col-md-4">
                                                <div class="card p-3">
                                                    <h6 class="fw-bold mb-3">Reference</h6>

                                                    <div class="mb-2">
                                                        <label class="fw-bold">F325 #</label>
                                                        <input type="text" class="form-control input-ordernumber"
                                                            disabled>
                                                    </div>

                                                    <div class="mb-2">
                                                        <label class="fw-bold">F325 Date</label>
                                                        <input type="date" class="form-control input-orderdate"
                                                            disabled>
                                                    </div>

                                                    <div class="mb-2">
                                                        <label class="fw-bold">Status</label>
                                                        <input type="text" class="form-control input-status" disabled>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                        <!-- Order Detail Table -->
                                        <div class="card p-3 mb-4">
                                            <h6 class="fw-bold mb-3">Order Details</h6>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>MDC Code</th>
                                                            <th>Item Code</th>
                                                            <th>Description</th>
                                                            <th>BBD</th>
                                                            <th>Reason Code</th>
                                                            <th>Quantity</th>
                                                            <th>UoM</th>
                                                            <th>Unit Price</th>
                                                            <th>Sub-Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="tbl-order-list"></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Remarks & Subtotal -->
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <div class="card p-3">
                                                    <label class="fw-bold">Remarks</label>
                                                    <textarea class="form-control input-remarks input-remarks"
                                                        rows="4"></textarea>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="card p-3">
                                                    <label class="fw-bold">Subtotal</label>
                                                    <input type="text" class="form-control input-subtotal" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once("footer.php"); ?>
<?php $conn->close(); ?>

<script>
    $(document).ready(function () {
        exportCSV();
    });

    function exportCSV() {
        $(".export-csv").on("click", function () {
            window.location.href = "/exportprinted.php";
        });
    }
</script>