<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
}

$result = mysqli_query($conn, "SELECT admin, semiadmin FROM dbuser WHERE id = " . $_SESSION['id']);
$user = mysqli_fetch_assoc($result);

$maintenanceModule = 'import_forpull_forcharge';
include('maintenance_check.php');
?>
<style>
    #preloader {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        z-index: 9999;
    }

    #preloader .preloader-content {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        height: 100%;
    }
</style>

<?php include_once('header.php'); ?>
<?php include_once('nav.php'); ?>

<!-- Preloader Overlay -->
<div id="preloader">
    <div class="preloader-content">
        <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Uploading files, please wait...</p>
    </div>
</div>

<div class="container mt-4">
    <?php
    // Get status message
    if (!empty($_GET['status'])) {
        switch ($_GET['status']) {
            case 'succ':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> Import Forpullout and Forcharging successfully.';
                break;
            case 'ce':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> F325 for verification.';
                break;
            case 'dispose':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> F325 disposed successfully.';
                break;
            case 'err':
                $statusType = 'alert-danger';
                $statusMsg = '<i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> No data encoded.';
                break;
            default:
                $statusType = '';
                $statusMsg = '';
        }
    }
    ?>

    <!-- Display status message -->
    <?php if (!empty($statusMsg)) { ?>
        <div class="alert <?php echo $statusType; ?> alert-dismissable fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <?php echo $statusMsg; ?>
        </div>
    <?php } ?>

    <!-- File Upload Section -->
    <div class="card p-3 mb-4">
        <label for="uploadForm" class="form-label fw-semibold text-primary">Select CSV File</label>
        <form action="import_forpull_forcharge_insert.php" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="input-group mb-2">
                <input type="file" class="form-control" id="fileInput" name="csv_file" accept=".csv">
                <input type="hidden" name="upload" value="1">
                <button class="btn btn-primary" type="submit" id="upload-csv-btn" disabled>
                    <i class="fa fa-upload"></i> Upload
                </button>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill mb-3"
                    id="error-count"></span>
                <span class="badge bg-warning-subtle text-warning border border-warning rounded-pill mb-3"
                    id="warning-count"></span>
                <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-info text-dark text-center">
                        <tr>
                            <th>f325 No.</th>
                            <th>SKU Code</th>
                            <th>Description</th>
                            <th>QTY</th>
                            <th>Cost Extended</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Principal</th>
                            <th>For Pullout QTY</th>
                            <th>For Charge QTY.</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="data-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include_once('footer.php'); ?>

    <script>
        $(document).ready(function () {
            $('#fileInput').change(function () {
                var fileInput = $('#fileInput')[0].files[0];
                if (!fileInput) return;

                var formData = new FormData();
                formData.append('csv_file', fileInput);
                $('#preloader').show();

                $.ajax({
                    url: 'import_csv_verify.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        // console.log(response);
                        if ($.fn.DataTable.isDataTable('#dataTable')) {
                            $('#dataTable').DataTable().destroy();
                        }
                        // display data to table 
                        $('#data-body').html(response);

                        // reinitialize table 
                        $('#dataTable').DataTable({
                            destroy: true,
                            pageLength: 5,
                            lengthMenu: [5, 10, 25, 50, 100],
                            paging: true,
                            ordering: false,
                            autoWidth: false,
                            scrollX: true
                        });

                        if (response.includes('ERROR') || response.includes('WARNING')) {
                            var errorCount = (response.match(/ERROR/g) || []).length;
                            var warningCount = (response.match(/WARNING/g) || []).length;
                            $('#error-count').text("Errors: " + errorCount);
                            $('#warning-count').text("Warnings: " + warningCount);

                            $("#upload-csv-btn").prop('disabled', true);
                        } else {
                            $("#upload-csv-btn").prop('disabled', false);
                        }
                        $('#preloader').hide();
                    },
                    error: function () {
                        alert('File upload failed!');
                        $('#fileInput').prop('disabled', false);
                        $('#preloader').hide();
                    }
                });
            });
            
            // Show preloader on form submit
            $('#uploadForm').submit(function () {
                $('#preloader').show();
                $('#preloader-text').text('Uploading records, please wait...');
            });
        });
    </script>
    <?php $conn->close(); ?>