<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");
?>

<?php include_once('nav.php'); ?>
<div class="container">
    <!-- Menu Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <input type="file" name="file" class="form-control" multiple id="uploadfile">
        </div>
    </div>

    <!-- Upload / Drag and Drop Area -->
    <div class="upload-area mb-3" id="uploadfile">
        <table class="table table-striped table-hover tbl-list">
            <thead>
                <tr>
                    <th>F325 Name</th>
                    <th>Rename To</th>
                </tr>
            </thead>
            <tbody class="tbody-list"></tbody>
        </table>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <button type="button" class="btn btn-success button-style add-file">Add File</button>
        </div>
        <div class="col-md-3 mb-2">
            <button type="button" class="btn btn-primary button-style check-file" onclick="VerrifyPO();"
                disabled>Verify</button>
        </div>
        <div class="col-md-3 mb-2">
            <button type="button" class="btn btn-warning button-style convert-file" onclick="ConvertFile();"
                disabled>Convert / Import</button>
        </div>
        <div class="col-md-3 mb-2">
            <button type="button" class="btn btn-danger button-style clear-file" onclick="FileDelete();"
                disabled>Clear</button>
        </div>
    </div>

    <!-- Duplicate / Not Registered Vendor Table -->
    <div class="card">
        <div class="card-header bg-warning text-dark">
            Duplicate / Not Registered Vendor
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 tbl-duplicate">
                    <thead class="table-light">
                        <tr>
                            <th>Duplicate / Not Registered Vendor</th>
                            <th>Process</th>
                        </tr>
                    </thead>
                    <tbody class="tbody-duplicate"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Email Date -->
    <div class="div-emaildate">
        <label for="emailDate" class="form-label">Email Date:</label>
        <input type="date" class="form-control w-auto" id="emailDate" value="<?php echo date("Y-m-d"); ?>">
    </div>

    <!-- Notify Alert
    <span class="span-notify-alert"></span> -->
</div>

<!-- Loading Screen
<div class="loaderscreen d-flex">
    <div class="loader-message">
        <img class="loader-formessage" src="icon/loader.gif">
        <span class="span-loadingmessage"></span>
    </div>
</div> -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="js/jquery.min.js"></script>
<script src="js/index.js"></script>
<?php $conn->close(); ?>