<?php
session_start();
include_once("dbconnect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

const IMPORT_RETAILER = 'CARBON-MT';

// ---- Cap how much we validate in one go, so a huge CSV can't hang the request ----
const MAX_VALIDATE_ROWS = 2000;

if (empty($_FILES['csv_file']['tmp_name'])) {
    echo json_encode(['error' => 'No file was received.']);
    exit;
}

$file = $_FILES['csv_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'File upload failed.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['error' => 'Please upload a .csv file.']);
    exit;
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['error' => 'Could not read the uploaded file.']);
    exit;
}

// ---- Prepared statements — same lookups import_rtv_process.php uses,
//      plus a company "active" check (matches the legacy "Vendor is not
//      active" message). ----
$census_stmt = $conn->prepare(
    "SELECT cluster, location, deducttype FROM tbl_census WHERE code = ? AND retailer = ? LIMIT 1"
);
$company_stmt = $conn->prepare(
    "SELECT vendorcode, active FROM tbl_company WHERE name = ? LIMIT 1"
);
$product_stmt = $conn->prepare(
    "SELECT category FROM tbl_product WHERE mdccode = ? AND active = 1 LIMIT 1"
);
$existing_f325_stmt = $conn->prepare(
    "SELECT id FROM tbl_f325number WHERE f325number = ? LIMIT 1"
);

$const_retailer = IMPORT_RETAILER;
$census_stmt->bind_param("ss", $branch_code_bind, $const_retailer);

fgetcsv($handle); // skip header row

$row_num = 1; // header was row 1
$rows = [];
$errorCount = 0;
$totalRows = 0;
$f325_seen_in_file = []; // f325number => true, so we only flag "already exists" once per RTV

while (($data = fgetcsv($handle)) !== false) {
    $row_num++;

    // A fully blank line isn't a data row — skip it, don't count it.
    if (count($data) <= 1 && trim($data[0] ?? '') === '') {
        continue;
    }

    $totalRows++;
    if ($totalRows > MAX_VALIDATE_ROWS) {
        // Stop validating further rows but still return what we found so far.
        break;
    }

    $branch_code = trim($data[0] ?? '');
    $f325number  = trim($data[3] ?? '');
    $sku_code    = trim($data[4] ?? '');
    $rtv_date    = trim($data[10] ?? '');
    $company     = trim($data[11] ?? '');

    $status = 'ok';
    $reason = '';

    do {
        if ($branch_code === '' || $f325number === '' || $sku_code === '' || $company === '') {
            $status = 'error';
            $reason = 'Missing branch code, RTV number, SKU code, or company.';
            break;
        }

        if (strtotime($rtv_date) === false) {
            $status = 'error';
            $reason = "Could not parse RTV date \"$rtv_date\".";
            break;
        }

        // Branch lookup, scoped to this retailer.
        $branch_code_bind = $branch_code;
        $census_stmt->execute();
        $census_row = $census_stmt->get_result()->fetch_assoc();
        if (!$census_row) {
            $status = 'error';
            $reason = "Branch code \"$branch_code\" not found for retailer " . IMPORT_RETAILER . ".";
            break;
        }

        // Company lookup + active check.
        $company_stmt->bind_param("s", $company);
        $company_stmt->execute();
        $company_row = $company_stmt->get_result()->fetch_assoc();
        if (!$company_row) {
            $status = 'error';
            $reason = "Company \"$company\" not found in tbl_company.";
            break;
        }
        if ((int) $company_row['active'] !== 1) {
            $status = 'error';
            $reason = 'Vendor is not active.';
            break;
        }

        // Product lookup (active only).
        $product_stmt->bind_param("s", $sku_code);
        $product_stmt->execute();
        $product_row = $product_stmt->get_result()->fetch_assoc();
        if (!$product_row) {
            $status = 'error';
            $reason = "SKU code \"$sku_code\" not found in tbl_product (or is inactive).";
            break;
        }

        // Duplicate F325 number — only check the first time we see it in
        // this file (later line items for the same RTV are expected).
        if (!isset($f325_seen_in_file[$f325number])) {
            $existing_f325_stmt->bind_param("s", $f325number);
            $existing_f325_stmt->execute();
            $already_exists = $existing_f325_stmt->get_result()->fetch_assoc();
            if ($already_exists) {
                $status = 'error';
                $reason = "F325 number \"$f325number\" already exists in the system.";
                break;
            }
            $f325_seen_in_file[$f325number] = true;
        }
    } while (false);

    if ($status === 'error') {
        $errorCount++;
    }

    $rows[] = [
        'row' => $row_num,
        'branchcode' => $branch_code,
        'f325number' => $f325number,
        'sku' => $sku_code,
        'company' => $company,
        'status' => $status,
        'reason' => $reason,
    ];
}

$census_stmt->close();
$company_stmt->close();
$product_stmt->close();
$existing_f325_stmt->close();
fclose($handle);
$conn->close();

echo json_encode([
    'totalRows' => $totalRows,
    'errorCount' => $errorCount,
    'rows' => $rows,
]);
exit;