<?php
session_start();
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Any mysqli failure (bad prepare(), failed execute(), etc.) throws instead
// of silently returning false.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const IMPORT_RETAILER = 'CARBON-MT';

function redirect_with_status($status, $inserted = 0, $msg = '') {
    $url = "import_rtv.php?status=$status&inserted=$inserted";
    if ($msg !== '') {
        $url .= '&msg=' . urlencode($msg);
    }
    header("Location: $url");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['csv_file']['tmp_name'])) {
    redirect_with_status('err', 0, 'Please choose a CSV file to upload.');
}

$emaildate_raw = trim($_POST['emaildate'] ?? '');
if ($emaildate_raw === '') {
    redirect_with_status('err', 0, 'Email date is required.');
}
$emaildate_ts = strtotime($emaildate_raw);
if ($emaildate_ts === false) {
    redirect_with_status('err', 0, 'Invalid email date.');
}
$emaildate = date('Y-m-d', $emaildate_ts);

$file = $_FILES['csv_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect_with_status('err', 0, 'File upload failed.');
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    redirect_with_status('err', 0, 'Please upload a .csv file.');
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    redirect_with_status('err', 0, 'Could not read the uploaded file.');
}

$inserted = 0;
$error_message = ''; // set the moment any row fails — the import stops there

try {
    fgetcsv($handle); // skip header row

    // Current user's name, for tbl_history logging below.
    $user_stmt = $conn->prepare("SELECT fullname FROM tbl_users WHERE id = ? LIMIT 1");
    $user_stmt->bind_param("i", $_SESSION['id']);
    $user_stmt->execute();
    $current_user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();
    $actor_name = $current_user['fullname'] ?? 'Unknown user';

    // -------------------------------------------------------------------
    // Prepared statements — lookups
    // -------------------------------------------------------------------
    $census_stmt = $conn->prepare(
        "SELECT cluster, location, deducttype FROM tbl_census WHERE code = ? AND retailer = ? LIMIT 1"
    );
    $company_stmt = $conn->prepare(
        "SELECT vendorcode FROM tbl_company WHERE name = ? LIMIT 1"
    );
    $product_stmt = $conn->prepare(
        "SELECT category FROM tbl_product WHERE mdccode = ? AND active = 1 LIMIT 1"
    );
    $existing_f325_stmt = $conn->prepare(
        "SELECT id FROM tbl_f325number WHERE f325number = ? LIMIT 1"
    );

    $const_retailer = IMPORT_RETAILER;
    $census_stmt->bind_param("ss", $branch_code_bind, $const_retailer);

    // -------------------------------------------------------------------
    // Prepared statements — inserts
    // -------------------------------------------------------------------
    $insert_header_stmt = $conn->prepare(
        "INSERT INTO tbl_f325number
            (f325number, brcode, preparedby, issuedby, emaildate, f325date, vendor,
             tmnumber, drivername, platenumber, datesched, datecleared, arnumber, pageno,
             printremarks, logisticremarks, clearingremarks, cluster, location, deducttype,
             status, process, verificationdate, verificationreason, ilrno, stamped, cleared_time, retailer)
         VALUES
            (?, ?, '', '', ?, ?, ?,
             '', '', '', NULL, NULL, '', '',
             '', '', '', ?, ?, ?,
             'OPEN', 'UPLOADED', NULL, '', '', '', NULL, ?)"
    );
    $insert_header_stmt->bind_param(
        "sisssssss",
        $h_f325number, $h_brcode, $h_emaildate, $h_f325date, $h_vendor,
        $h_cluster, $h_location, $h_deducttype, $h_retailer
    );

    // Logs a tbl_history row for a converted/imported RTV — processnumber
    // ties it back to the F325 document, same convention used elsewhere
    // in the app (company_form_sucess.php, notepad-status-process.php).
    $history_stmt = $conn->prepare(
        "INSERT INTO tbl_history (processnumber, name, processed, dateprocessed, timeprocessed)
         VALUES (?, ?, ?, CURDATE(), CURTIME())"
    );

    $insert_raw_stmt = $conn->prepare(
        "INSERT INTO tbl_raw
            (f325number, mdccode, category, vendorcode, deducttype, dmpiclass, quantity, expiration,
             unitcost, costextended, reasoncode, arnumber, arreason, dmpireason, rcvdqty, dmpiref,
             deductref, deductqty, deductcostextended, datecleared, pulloutref, batchnumber_forpullout,
             batchnumber_forcharging, location, status, statusout, paymentstatus, skustatus, slstatus,
             skutype, forpullout, forcharging, retailer)
         VALUES
            (?, ?, ?, ?, ?, '', ?, '',
             ?, ?, '', '', '', 0, 0, '',
             '', 0, 0.00, NULL, '', NULL,
             NULL, ?, 'OPEN', '', '', 0, '',
             '', 0, 0, ?)"
    );
    $insert_raw_stmt->bind_param(
        "sssssiddss",
        $r_f325number, $r_mdccode, $r_category, $r_vendorcode, $r_deducttype,
        $r_quantity, $r_unitcost, $r_costextended, $r_location, $r_retailer
    );

    $row_num = 1; // header was row 1
    $headers_created_this_run = [];

    while (($data = fgetcsv($handle)) !== false) {
        $row_num++;

        try {
            // A fully blank line isn't a data error — skip it silently and
            // keep going, rather than treating it as the row that halts
            // the import.
            if (count($data) === 1 && trim($data[0]) === '') {
                continue;
            }

            $branch_code = trim($data[0] ?? '');
            $f325number  = trim($data[3] ?? '');
            $sku_code    = trim($data[4] ?? '');
            $item_qty    = trim($data[6] ?? '');
            $price_per   = trim($data[7] ?? '');
            $amount      = trim($data[8] ?? '');
            $rtv_date    = trim($data[10] ?? '');
            $company     = trim($data[11] ?? '');

            if ($branch_code === '' || $f325number === '' || $sku_code === '' || $company === '') {
                $error_message = "Row $row_num: missing branch code, RTV number, SKU code, or company.";
                break;
            }

            $quantity     = (int) str_replace(',', '', $item_qty);
            $unitcost     = (float) str_replace(',', '', $price_per);
            $costextended = (float) str_replace(',', '', $amount);

            $f325date_ts = strtotime($rtv_date);
            if ($f325date_ts === false) {
                $error_message = "Row $row_num: could not parse RTV date \"$rtv_date\".";
                break;
            }
            $f325date = date('Y-m-d', $f325date_ts);

            // Branch lookup — cluster/location/deducttype, scoped to Carbon-MT only
            $branch_code_bind = $branch_code;
            $census_stmt->execute();
            $census_row = $census_stmt->get_result()->fetch_assoc();
            if (!$census_row) {
                $error_message = "Row $row_num: branch code \"$branch_code\" not found for retailer " . IMPORT_RETAILER . ".";
                break;
            }

            // Company lookup — vendorcode
            $company_stmt->bind_param("s", $company);
            $company_stmt->execute();
            $company_row = $company_stmt->get_result()->fetch_assoc();
            if (!$company_row) {
                $error_message = "Row $row_num: company \"$company\" not found in tbl_company.";
                break;
            }
            $vendorcode = $company_row['vendorcode'];

            // Product lookup — category (active products only)
            $product_stmt->bind_param("s", $sku_code);
            $product_stmt->execute();
            $product_row = $product_stmt->get_result()->fetch_assoc();
            if (!$product_row) {
                $error_message = "Row $row_num: SKU code \"$sku_code\" not found in tbl_product (or is inactive).";
                break;
            }
            $category = $product_row['category'];

            // --- Header row (tbl_f325number) — only once per unique RTV number.
            // If it already exists in the DB, that's treated as an error —
            // the import stops here rather than skipping ahead.
            if (!isset($headers_created_this_run[$f325number])) {
                $existing_f325_stmt->bind_param("s", $f325number);
                $existing_f325_stmt->execute();
                $already_exists = $existing_f325_stmt->get_result()->fetch_assoc();

                if ($already_exists) {
                    $error_message = "Row $row_num: F325 number \"$f325number\" already exists in the system.";
                    break;
                }

                $h_f325number = $f325number;
                $h_brcode     = (int) $branch_code;
                $h_emaildate  = $emaildate;
                $h_f325date   = $f325date;
                $h_vendor     = $vendorcode;
                $h_cluster    = $census_row['cluster'];
                $h_location   = $census_row['location'];
                $h_deducttype = $census_row['deducttype'];
                $h_retailer   = IMPORT_RETAILER;
                $insert_header_stmt->execute();

                // Log the conversion/import of this RTV document.
                $history_processed = 'Converted';
                $history_stmt->bind_param("sss", $f325number, $actor_name, $history_processed);
                $history_stmt->execute();

                $headers_created_this_run[$f325number] = true;
            }

            // --- Line item row (tbl_raw) ---
            $r_f325number   = $f325number;
            $r_mdccode      = $sku_code;
            $r_category     = $category;
            $r_vendorcode   = $vendorcode;
            $r_deducttype   = $census_row['deducttype'];
            $r_quantity     = $quantity;
            $r_unitcost     = $unitcost;
            $r_costextended = $costextended;
            $r_location     = $census_row['location'];
            $r_retailer     = IMPORT_RETAILER;

            $insert_raw_stmt->execute();
            $inserted++;

        } catch (Throwable $row_error) {
            $error_message = "Row $row_num: " . $row_error->getMessage();
            break;
        }
    }

    $census_stmt->close();
    $company_stmt->close();
    $product_stmt->close();
    $existing_f325_stmt->close();
    $insert_header_stmt->close();
    $history_stmt->close();
    $insert_raw_stmt->close();
    fclose($handle);
    $conn->close();

} catch (Throwable $fatal_error) {
    fclose($handle);
    redirect_with_status('err', $inserted, 'Import stopped: ' . $fatal_error->getMessage());
}

if ($error_message !== '') {
    redirect_with_status($inserted > 0 ? 'partial' : 'err', $inserted, $error_message);
} else {
    redirect_with_status('succ', $inserted);
}