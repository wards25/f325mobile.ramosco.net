<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$result = mysqli_query($conn, "SELECT admin, semiadmin FROM dbuser WHERE id = " . $_SESSION['id']);
$user = mysqli_fetch_assoc($result);

$maintenanceModule = 'import_sku_list.php';
include('maintenance_check.php');
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #4f46e5;
            --brand-light: #eef0fe;
            --page-bg: #f4f5fb;
            --text-muted: #8a8fa3;
            --ok: #12805c;
            --ok-soft: #e7f6f0;
            --danger: #b3261e;
            --danger-soft: #fbebea;
            --amber: #b45309;
            --amber-soft: #fdf1e0;
            --line: #eceef5;
            --surface: #ffffff;
            --ink: #1f2130;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: var(--page-bg);
        }

        .rtv-page {
            padding: 2rem 1.5rem;
        }

        .rtv-breadcrumb {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .rtv-breadcrumb .current {
            color: var(--ink);
            font-weight: 600;
        }

        .rtv-title {
            font-weight: 700;
            font-size: 1.6rem;
            color: var(--ink);
            margin: 0 0 1.25rem;
        }

        .upload-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
            padding: 2rem 2.25rem;
        }

        .form-label-modern {
            font-weight: 600;
            font-size: 0.82rem;
            color: #3d3f4d;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .form-control-modern {
            border: 1px solid #e3e5ef;
            border-radius: 0.6rem;
            padding: 0.55rem 0.85rem;
            font-size: 0.92rem;
            background-color: #fbfbfe;
            width: 100%;
        }

        .form-control-modern:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
            background-color: #fff;
            outline: none;
        }

        .btn-brand {
            background-color: var(--brand) !important;
            border-color: var(--brand) !important;
            color: #fff !important;
            border-radius: 0.6rem;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 0.6rem 1.3rem;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        }

        .btn-brand:hover {
            background-color: #4338ca !important;
            border-color: #4338ca !important;
            color: #fff !important;
        }

        /* ---- Drag & drop zone ---- */
        .dropzone {
            border: 2px dashed #d3d6e5;
            border-radius: 1rem;
            background: #fbfbfe;
            padding: 4.5rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease;
        }

        .dropzone:hover {
            border-color: var(--brand);
            background: var(--brand-light);
        }

        .dropzone.dragover {
            border-color: var(--brand);
            background: var(--brand-light);
        }

        .dropzone-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--brand);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.25rem;
        }

        .dropzone-title {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--ink);
        }

        .dropzone-sub {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }

        /* Kept in normal document flow (not display:none) so the browser's
           native "required" validation popup can still anchor to it. */
        .visually-hidden-file {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .selected-file-chip {
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--brand-light);
            color: var(--brand);
            border-radius: 0.6rem;
            padding: 0.55rem 0.85rem;
            font-size: 0.86rem;
            font-weight: 600;
            width: fit-content;
        }

        .selected-file-chip .chip-remove {
            border: none;
            background: none;
            color: var(--brand);
            opacity: 0.7;
            cursor: pointer;
            font-size: 0.8rem;
            padding: 0 0 0 0.3rem;
        }

        .selected-file-chip .chip-remove:hover {
            opacity: 1;
        }

        /* ---- Client-side (AJAX) validation preview ---- */
        .preview-section {
            margin-top: 1.5rem;
        }

        .preview-skeleton-row {
            display: grid;
            grid-template-columns: 0.6fr 1fr 2.4fr;
            gap: 1.25rem;
            align-items: center;
            padding: 0.85rem 0;
            border-bottom: 1px solid #f2f3f8;
        }

        .preview-skeleton-bar {
            height: 12px;
            border-radius: 6px;
            background: linear-gradient(90deg, #edeef4 25%, #f6f7fb 37%, #edeef4 63%);
            background-size: 400% 100%;
            animation: skeleton-shimmer 1.4s ease infinite;
        }

        @keyframes skeleton-shimmer {
            0% { background-position: 100% 50%; }
            100% { background-position: 0 50%; }
        }

        .preview-summary {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.85rem 1rem;
            border-radius: 0.7rem;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 0.9rem;
        }

        .preview-summary.ok {
            background: var(--ok-soft);
            color: var(--ok);
        }

        .preview-summary.warn {
            background: var(--danger-soft);
            color: var(--danger);
        }

        table.preview-error-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        table.preview-error-table thead th {
            text-align: left;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--text-muted);
            background: #fafbfd;
            padding: 8px 14px;
            border-bottom: 1px solid var(--line);
        }

        table.preview-error-table tbody td {
            padding: 8px 14px;
            border-bottom: 1px solid #f2f3f8;
            vertical-align: top;
            color: var(--ink);
        }

        table.preview-error-table tbody tr:last-child td {
            border-bottom: none;
        }

        table.preview-error-table .err-row-num {
            font-weight: 700;
            white-space: nowrap;
        }

        table.preview-error-table .err-field {
            white-space: nowrap;
            color: var(--danger);
            font-weight: 600;
        }

        .preview-error-wrap {
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.25rem 0.7rem;
            font-weight: 600;
            font-size: 0.78rem;
            white-space: nowrap;
        }

        .status-pill.ok {
            background: var(--ok-soft);
            color: var(--ok);
        }

        .status-pill.err {
            background: var(--danger-soft);
            color: var(--danger);
        }

        #previewTable_wrapper {
            padding: 0.75rem 1rem 1rem;
        }

        #previewTable_wrapper .dataTables_filter input,
        #previewTable_wrapper .dataTables_length select {
            border: 1px solid #e3e5ef;
            border-radius: 0.5rem;
            padding: 0.3rem 0.6rem;
            font-size: 0.85rem;
        }

        #previewTable_wrapper .dataTables_info,
        #previewTable_wrapper .dataTables_length,
        #previewTable_wrapper .dataTables_filter {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        table.preview-rows-table {
            width: 100%;
            font-size: 0.85rem;
        }

        table.preview-rows-table thead th {
            background: #1f2130;
            color: #fff;
            text-align: left;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 0.78rem;
        }

        table.preview-rows-table tbody td {
            padding: 9px 14px;
            border-bottom: 1px solid #f2f3f8;
            color: var(--ink);
            vertical-align: middle;
        }

        table.preview-rows-table tbody tr.row-error {
            background: #fff9f8;
        }

        #preloader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, .5);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 9999;
        }

        #preloader .preloader-content {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100%;
            gap: 0.75rem;
            color: var(--ink);
            font-size: 0.9rem;
        }

        /* Toast-style notification, matches company.php / product-list.php / store-list.php */
        .app-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-left: 4px solid var(--ok);
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            font-size: 0.9rem;
            color: var(--ink);
            max-width: 380px;
            transition: opacity 0.4s ease, transform 0.4s ease;
        }

        .app-toast .app-toast-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--ok-soft);
            color: var(--ok);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.85rem;
        }

        .app-toast .app-toast-close {
            border: none;
            background: none;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-left: auto;
            cursor: pointer;
            line-height: 1;
            padding: 2px;
        }

        .app-toast.hide {
            opacity: 0;
            transform: translateY(-8px);
        }

        .app-toast.app-toast-error {
            border-left-color: var(--danger);
        }

        .app-toast.app-toast-error .app-toast-icon {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .app-toast.app-toast-warning {
            border-left-color: var(--amber);
        }

        .app-toast.app-toast-warning .app-toast-icon {
            background: var(--amber-soft);
            color: var(--amber);
        }
    </style>
</head>
<body>

<?php include_once('nav.php'); ?>

<div id="preloader">
    <div class="preloader-content">
        <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mb-0">Uploading file, please wait...</p>
    </div>
</div>

<div class="rtv-page">

    <div class="rtv-breadcrumb">Importing &rsaquo; <span class="current">Import RTV</span></div>
    <h1 class="rtv-title">Import RTV</h1>

<?php
if (!empty($_GET['status'])) {

    $inserted = (int)($_GET['inserted'] ?? 0);

    switch ($_GET['status']) {

        case 'succ':
            $toastVariant = '';
            $toastIcon = 'fa-check';
            $toastMsg  = "<strong>Success!</strong> Imported $inserted RTV item(s).";
            break;

        case 'partial':
            $toastVariant = 'app-toast-warning';
            $toastIcon = 'fa-exclamation-triangle';
            $toastMsg  = "<strong>Import stopped.</strong> Imported $inserted row(s) before an error occurred.";
            if (!empty($_GET['msg'])) {
                $toastMsg .= ' ' . htmlspecialchars($_GET['msg']);
            }
            break;

        case 'err':
            $toastVariant = 'app-toast-error';
            $toastIcon = 'fa-exclamation-triangle';
            $toastMsg  = '<strong>Error!</strong> Upload failed.';
            if (!empty($_GET['msg'])) {
                $toastMsg .= ' ' . htmlspecialchars($_GET['msg']);
            }
            break;

        default:
            $toastVariant = '';
            $toastMsg = '';
    }

    if (!empty($toastMsg)) {
        echo '
        <div id="server-toast" class="app-toast ' . $toastVariant . '">
            <span class="app-toast-icon"><i class="fa ' . $toastIcon . '"></i></span>
            <span>' . $toastMsg . '</span>
            <button type="button" class="app-toast-close" onclick="document.getElementById(\'server-toast\').remove();">
                <i class="fa fa-times"></i>
            </button>
        </div>';
    }
}
?>

    <script>
        setTimeout(function () {
            var toast = document.getElementById('server-toast');
            if (toast) {
                toast.classList.add('hide');
                setTimeout(function () { toast.remove(); }, 400);
            }
        }, 4000);
    </script>

    <div class="upload-card">
        <form action="import_rtv_process.php"
              method="POST"
              enctype="multipart/form-data"
              id="uploadForm"
              onsubmit="return handleUploadSubmit();">

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label-modern">Email Date</label>
                    <input type="date" class="form-control-modern" value="<?php echo date("Y-m-d"); ?>" name="emaildate" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label-modern">Retailer</label>
                    <input type="text" class="form-control-modern" value = 'CARBON-MT' readonly>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label-modern">CSV File</label>

                <div class="dropzone" id="dropzone">
                    <div class="dropzone-icon"><i class="fa fa-cloud-upload-alt"></i></div>
                    <div class="dropzone-title">Drag &amp; Drop file here</div>
                    <div class="dropzone-sub">or click below to select a file</div>
                </div>

                <!-- Kept in normal flow (not display:none) so the browser can still
                     show its native "required" validation message on this input. -->
                <input type="file" class="visually-hidden-file" name="csv_file" id="csvFileInput" accept=".csv" required>

                <div class="selected-file-chip" id="selectedFileChip" style="display:none;">
                    <i class="fa fa-file-csv"></i>
                    <span id="selectedFileName"></span>
                    <button type="button" class="chip-remove" id="clearFileBtn" title="Remove"><i class="fa fa-times"></i></button>
                </div>

                <div class="preview-section" id="previewSection" style="display:none;">
                    <div id="previewSkeleton">
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="preview-skeleton-row">
                                <div class="preview-skeleton-bar"></div>
                                <div class="preview-skeleton-bar"></div>
                                <div class="preview-skeleton-bar" style="width:70%;"></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div id="previewResults" style="display:none;">
                        <div id="previewSummary" class="preview-summary"></div>
                        <div class="preview-error-wrap" id="previewErrorWrap" style="display:none;">
                            <table id="previewTable" class="preview-rows-table" width="100%">
                                <thead>
                                    <tr>
                                        <th>Row</th>
                                        <th>Branch Code</th>
                                        <th>F325 Number</th>
                                        <th>SKU</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="previewErrorBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="upload" value="1">

            <div class="mt-4">
                <button class="btn-brand" type="submit" id="uploadBtn">
                    <i class="fa fa-upload me-1"></i> Upload CSV
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var dropzone = document.getElementById('dropzone');
            var fileInput = document.getElementById('csvFileInput');
            var chip = document.getElementById('selectedFileChip');
            var chipName = document.getElementById('selectedFileName');
            var clearBtn = document.getElementById('clearFileBtn');

            dropzone.addEventListener('click', function () {
                fileInput.click();
            });

            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', function () {
                dropzone.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                dropzone.classList.remove('dragover');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    updateFileDisplay();
                }
            });

            fileInput.addEventListener('change', updateFileDisplay);

            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.value = '';
                chip.style.display = 'none';
                hidePreview();
            });

            function updateFileDisplay() {
                if (!fileInput.files.length) {
                    chip.style.display = 'none';
                    hidePreview();
                    return;
                }
                var f = fileInput.files[0];
                var ext = f.name.split('.').pop().toLowerCase();
                if (ext !== 'csv') {
                    showToastQuick('Please select a .csv file.', 'error');
                    fileInput.value = '';
                    chip.style.display = 'none';
                    hidePreview();
                    return;
                }
                chipName.textContent = f.name;
                chip.style.display = 'flex';
                validateFile(f);
            }

            function hidePreview() {
                document.getElementById('previewSection').style.display = 'none';
            }

            function validateFile(file) {
                var previewSection = document.getElementById('previewSection');
                var skeleton = document.getElementById('previewSkeleton');
                var results = document.getElementById('previewResults');

                previewSection.style.display = '';
                skeleton.style.display = '';
                results.style.display = 'none';

                var formData = new FormData();
                formData.append('csv_file', file);

                fetch('import_rtv_validate.php', { method: 'POST', body: formData })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        skeleton.style.display = 'none';
                        results.style.display = '';
                        renderPreviewResults(data);
                    })
                    .catch(function () {
                        skeleton.style.display = 'none';
                        results.style.display = '';
                        var summary = document.getElementById('previewSummary');
                        summary.className = 'preview-summary warn';
                        summary.innerHTML = '<i class="fa fa-exclamation-triangle"></i> Could not validate this file. It will still be checked when you click Upload.';
                        document.getElementById('previewErrorWrap').style.display = 'none';
                    });
            }

            var previewDataTable = null;

            function renderPreviewResults(data) {
                var summary = document.getElementById('previewSummary');
                var errorWrap = document.getElementById('previewErrorWrap');
                var errorBody = document.getElementById('previewErrorBody');
                errorBody.innerHTML = '';

                if (data.error) {
                    summary.className = 'preview-summary warn';
                    summary.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + data.error;
                    errorWrap.style.display = 'none';
                    return;
                }

                var rows = data.rows || [];
                var totalRows = data.totalRows || 0;
                var errorCount = data.errorCount || 0;

                if (errorCount === 0) {
                    summary.className = 'preview-summary ok';
                    summary.innerHTML = '<i class="fa fa-check-circle"></i> All ' + totalRows + ' row(s) look valid. Ready to upload.';
                } else {
                    summary.className = 'preview-summary warn';
                    summary.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' +
                        errorCount + ' of ' + totalRows + ' row(s) have issues. Fix these in the CSV before uploading — ' +
                        'the actual import stops at the first row it finds a problem with.';
                }

                rows.forEach(function (r) {
                    var tr = document.createElement('tr');
                    if (r.status === 'error') {
                        tr.className = 'row-error';
                    }
                    var statusHtml = r.status === 'error'
                        ? '<span class="status-pill err"><i class="fa fa-triangle-exclamation"></i> ' + escapeHtml(r.reason) + '</span>'
                        : '<span class="status-pill ok"><i class="fa fa-check"></i> Valid</span>';

                    tr.innerHTML =
                        '<td>' + escapeHtml(String(r.row)) + '</td>' +
                        '<td>' + escapeHtml(r.branchcode) + '</td>' +
                        '<td>' + escapeHtml(r.f325number) + '</td>' +
                        '<td>' + escapeHtml(r.sku) + '</td>' +
                        '<td>' + escapeHtml(r.company) + '</td>' +
                        '<td>' + statusHtml + '</td>';
                    errorBody.appendChild(tr);
                });

                errorWrap.style.display = '';

                // Re-init the DataTable each time a new file is validated.
                if (previewDataTable) {
                    previewDataTable.destroy();
                    previewDataTable = null;
                }
                if (rows.length > 0 && window.jQuery && $.fn.DataTable) {
                    previewDataTable = $('#previewTable').DataTable({
                        pageLength: 10,
                        lengthMenu: [10, 25, 50, 100],
                        order: [[0, 'asc']],
                        language: {
                            search: "",
                            searchPlaceholder: "Search…",
                            info: "Showing _START_–_END_ of _TOTAL_ row(s)",
                            paginate: { previous: "Prev", next: "Next" }
                        }
                    });
                }
            }

            function escapeHtml(str) {
                var div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }
            function showToastQuick(message, type) {
                var existing = document.getElementById('client-toast');
                if (existing) existing.remove();
                var toast = document.createElement('div');
                toast.id = 'client-toast';
                toast.className = 'app-toast' + (type === 'error' ? ' app-toast-error' : '');
                toast.innerHTML = '<span class="app-toast-icon"><i class="fa fa-exclamation-triangle"></i></span>' +
                    '<span>' + message + '</span>' +
                    '<button type="button" class="app-toast-close" onclick="this.closest(\'.app-toast\').remove();"><i class="fa fa-times"></i></button>';
                document.body.appendChild(toast);
                setTimeout(function () {
                    toast.classList.add('hide');
                    setTimeout(function () { toast.remove(); }, 400);
                }, 3000);
            }
        })();

        function handleUploadSubmit() {
            document.getElementById('preloader').style.display = 'block';
            return true;
        }
    </script>

</div>

<?php include_once('footer.php'); ?>

</div>
<!-- End of Main Content -->

</div>
<!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fa fa-angle-up"></i>
</a>

</body>
</html>

<?php
mysqli_close($conn);
?>