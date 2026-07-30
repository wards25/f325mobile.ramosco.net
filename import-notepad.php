<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
}

$result = mysqli_query($conn, "SELECT admin, semiadmin FROM dbuser WHERE id = " . $_SESSION['id']);
$user = mysqli_fetch_assoc($result);

// If not admin, redirect to dashboard or another page
// if ($user['admin'] != 1) {
//     header("Location: unauthorized_access.php");
//     exit;
// }
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
                $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> Import F325 Notepad successfully.';
                ?>
                <!--<meta http-equiv="refresh" content="2.7;url=scheduled.php">-->
                <?php
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
    <!-- Email Date -->
    <div class="div-emaildate mt-3 mb-3">
        <label for="emailDate" class="form-label">Email Date:</label>
        <input type="date" class="form-control w-auto email-date" name="emaildate" value="<?php echo date("Y-m-d"); ?>">
        <label for="emailDate" class="form-label">Retailer</label>
        <input type="text" class="form-control w-auto" value="MERCURY DRUG" readonly>
    </div>
    <!-- Drag & Drop Area -->
    <div class="upload-area mb-3 drop-area"
        style="border: 3px dashed #6c757d; padding: 40px; text-align:center; border-radius:10px;">
        <i class="bi bi-cloud-arrow-up-fill icon-upload"></i>
        <h5>Drag & Drop files here</h5>
        <p class="text-muted">or click below to select files</p>
    </div>
    <input type="file" class="form-control mb-3" id="fileInput" multiple>
    <!-- Results Table -->
    <table id="resultsTable" class="table table-striped table-hover tbl-list mt-3">
        <thead class="table-dark">
            <tr>
                <th>File Name</th>
                <th>F325 Number</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody class="tbody-list"></tbody>
    </table>
    <button class="btn btn-warning mt-4 mb-4 text-center btn-upload">Upload</button>
    <button class="btn btn-outline-secondary btn-clear">Clear</button>
</div>

<?php include_once('footer.php'); ?>
<script>
    $(document).ready(function () {

        let dataTable = $('#resultsTable').DataTable({
            responsive: true,
            pageLength: 10,
            ordering: true,
        });

        const $dropArea = $(".drop-area");
        const $fileInput = $("#fileInput");

        $dropArea.on("click", () => $fileInput.trigger("click"));
        
        $(".btn-clear").on("click", function () {
            dataTable.clear().draw();
            $("#fileInput").val("");
            $(".btn-upload")
                .prop("disabled", false)
                .addClass("btn-warning")
                .removeClass("btn-secondary");

        });

        $fileInput.on("change", function () {
            verifyUPloads(this.files);
        });

        $dropArea.on("dragover", function (e) {
            e.preventDefault();
            $dropArea.addClass("drag-over").css("border-color", "#0d6efd");
        });

        $dropArea.on("dragleave", function () {
            $dropArea.removeClass("drag-over").css("border-color", "#6c757d");
        });

        $dropArea.on("drop", function (e) {
            e.preventDefault();
            $dropArea.removeClass("drag-over").css("border-color", "#6c757d");

            const files = e.originalEvent.dataTransfer.files;
            let dt = new DataTransfer();
            for (let i = 0; i < files.length; i++) {
                dt.items.add(files[i]);
            }
            document.getElementById("fileInput").files = dt.files;

            verifyUPloads(files);
        });
        function verifyUPloads(files) {
            showPreloader();
            const formData = new FormData();
            $.each(files, (i, file) => formData.append("files[]", file));

            $.ajax({
                url: "import-notepad-verify.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    hidePreloader();
                    let disableUpload = false;
                    response.forEach(row => {
                        const f325Exists = checkIfF325Exists(row.f325);
                        const newRow = dataTable.row.add([row.filename, row.f325, row.status]).draw(false).node();

                        if (row.status.toLowerCase() === "ready") {
                            $(newRow).addClass("table-success");
                        } else if (row.status.toLowerCase() === "uploaded") {
                            $(newRow).addClass("table-warning");
                            disableUpload = true;
                        }
                        else if (row.status.toLowerCase() === "branch not exist") {
                            $(newRow).addClass("table-danger");
                            disableUpload = true;
                        }
                    });
                    dataTable.draw();
                    $(".btn-upload").prop("disabled", disableUpload);
                    if (disableUpload) {
                        $(".btn-upload").addClass("btn-secondary").removeClass("btn-warning");
                    } else {
                        $(".btn-upload").addClass("btn-warning").removeClass("btn-secondary");
                    }
                },
                error: function () {
                    hidePreloader();
                    alert("Upload failed");
                }
            });
        }

        function checkIfF325Exists(f325Number) {
            let exists = false;
            dataTable.rows().every(function () {
                const rowData = this.data();
                if (rowData[1] === f325Number) {
                    exists = true;
                    alert("F325 Number " + f325Number + " already exists in the table.");
                }
            });
            return exists;
        }
        $(".btn-upload").on("click", function () {
            showPreloader();
            let formData = new FormData();
            formData.append("emaildate", $(".email-date").val());

            // Get real uploaded files again
            let files = $("#fileInput")[0].files;

            if (files.length === 0) {
                alert("No files uploaded.");
                return;
            }
            for (let i = 0; i < files.length; i++) {
                formData.append("files[]", files[i]);
            }

            $.ajax({
                url: "import-notepad-insert.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    hidePreloader();
                    window.location.href = window.location.pathname + "?status=succ";
                }
            });
        });

        function showPreloader() {
            $("#preloader").fadeIn(200);
        }

        function hidePreloader() {
            $("#preloader").fadeOut(200);
        }
    });
    window.setTimeout(function () {
        $(".alert").fadeTo(500, 0).slideUp(500, function () {
            $(this).remove();
        });
    }, 2000);
</script>

<?php $conn->close(); ?>