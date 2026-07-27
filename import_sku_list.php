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

$skuQuery = mysqli_query($conn, "
    SELECT
        mdccode,
        mdc_description,
        bu,
        product_per_line,
        brand,
        prod_insp_memo,
        material_description,
        mdc_unit,
        serving,
        config,
        nestle_ppl,
        uploaded_at,
        updated_at
    FROM tbl_sku_list
    ORDER BY mdccode ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        #preloader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,.2);
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
</head>
<body>

<?php include_once('nav.php'); ?>

<div id="preloader">
    <div class="preloader-content">
        <div class="spinner-border text-primary" role="status" style="width:2rem;height:2rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Uploading file, please wait...</p>
    </div>
</div>

<div class="container mt-4">

<?php
if (!empty($_GET['status'])) {

    $inserted = (int)($_GET['inserted'] ?? 0);
    $errors   = (int)($_GET['errors'] ?? 0);

    switch ($_GET['status']) {

        case 'succ':
            $statusType = 'alert-success';
            $statusMsg  = '<i class="fa fa-check-circle"></i> <strong>Success!</strong> ';
            break;

        case 'err':
            $statusType = 'alert-danger';
            $statusMsg  = '<i class="fa fa-exclamation-triangle"></i> <strong>Error!</strong> Upload failed.';
            break;

        default:
            $statusType = '';
            $statusMsg = '';
    }

    if (!empty($statusMsg)) {
        echo '
        <div class="alert '.$statusType.' alert-dismissible fade show">
            '.$statusMsg.'
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>';
    }
}
?>

    <!-- Upload Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <form action="import_sku_list_insert.php"
                  method="POST"
                  enctype="multipart/form-data"
                  id="uploadForm">

                <div class="input-group">

                    <input type="file"
                           class="form-control"
                           name="csv_file"
                           accept=".csv"
                           required>

                    <input type="hidden" name="upload" value="1">

                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-upload"></i>
                        Upload CSV
                    </button>

                </div>

                <small class="text-muted mt-2 d-block">
                    Required columns:
                    MDC CODE,
                    MDC DESCRIPTION,
                    BU,
                    PRODUCT PER LINE,
                    BRAND,
                    PROD./INSP. MEMO,
                    MATERIAL DESCRIPTION,
                    MDC UNIT,
                    SERVING,
                    CONFIG,
                    NESTLE PPL
                </small>

            </form>

        </div>
    </div>

    <!-- SKU Table -->
    <div class="card shadow">

        <div class="card-header">
            SKU Master List
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-striped"
                       id="dataTableSku"
                       width="100%">

                    <thead class="table-info text-center">
                        <tr>
                            <th>MDC CODE</th>
                            <th>MDC DESCRIPTION</th>
                            <th>BU</th>
                            <th>PRODUCT PER LINE</th>
                            <th>BRAND</th>
                            <th>PROD./INSP. MEMO</th>
                            <th>MATERIAL DESCRIPTION</th>
                            <th>MDC UNIT</th>
                            <th>SERVING</th>
                            <th>CONFIG</th>
                            <th>NESTLE PPL</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($row = mysqli_fetch_assoc($skuQuery)) { ?>

                        <tr>
                            <td><?= htmlspecialchars($row['mdccode']) ?></td>
                            <td><?= htmlspecialchars($row['mdc_description']) ?></td>
                            <td><?= htmlspecialchars($row['bu']) ?></td>
                            <td><?= htmlspecialchars($row['product_per_line']) ?></td>
                            <td><?= htmlspecialchars($row['brand']) ?></td>
                            <td><?= htmlspecialchars($row['prod_insp_memo']) ?></td>
                            <td><?= htmlspecialchars($row['material_description']) ?></td>
                            <td><?= htmlspecialchars($row['mdc_unit']) ?></td>
                            <td><?= htmlspecialchars($row['serving']) ?></td>
                            <td><?= htmlspecialchars($row['config']) ?></td>
                            <td><?= htmlspecialchars($row['nestle_ppl']) ?></td>
                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php include_once('footer.php'); ?>

<script>
document.getElementById('uploadForm').addEventListener('submit', function() {
    document.getElementById('preloader').style.display = 'block';
});

$(document).ready(function() {
    $('#dataTableSku').DataTable({
        pageLength: 10,
        order: [[0, 'asc']]
    });
});
</script>

</body>
</html>

<?php
mysqli_close($conn);
?>