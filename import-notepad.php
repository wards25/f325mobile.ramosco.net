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
if ($user['admin'] != 1) {
    header("Location: unauthorized_access.php");
    exit;
}
?>
<?php include_once('header.php'); ?>
<?php include_once('nav.php'); ?>
<div class="container mt-4">
    <!-- Email Date -->
    <div class="div-emaildate mt-3 mb-3">
        <label for="emailDate" class="form-label">Email Date:</label>
        <input type="date" class="form-control w-auto email-date" name="emaildate" value="<?php echo date("Y-m-d"); ?>">
    </div>
    <!-- Drag & Drop Area -->
    <div class="upload-area mb-3 drop-area"
        style="border: 3px dashed #6c757d; padding: 40px; text-align:center; border-radius:10px;">
        <i class="bi bi-cloud-arrow-up-fill icon-upload"></i>
        <h5>Drag & Drop files here</h5>
        <p class="text-muted">or click below to select files</p>
    </div>
    <input type="file" class="form-control mb-3" multiple id="fileInput">
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
    <button class="btn btn-outline-secondary">Clear</button>
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
            const formData = new FormData();
            $.each(files, (i, file) => formData.append("files[]", file));

            $.ajax({
                url: "import-notepad-verify.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    response.forEach(row => {
                        const f325Exists = checkIfF325Exists(row.f325);
                        if (!f325Exists) {
                            dataTable.row.add([row.filename, row.f325, row.status]);
                        }
                    });
                    dataTable.draw();
                },
                error: function () {
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
                    alert("Insert Complete:\n" + response);
                }
            });
        });
    });

</script>

<?php $conn->close(); ?>