<?php
session_start();
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_POST['upload']) || !isset($_FILES['csv_file'])) {
    header("Location: import_sku_list.php?status=err&debug=no_post");
    exit;
}

$file = $_FILES['csv_file'];

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv' || $file['error'] !== UPLOAD_ERR_OK) {
    header("Location: import_sku_list.php?status=err&debug=bad_file");
    exit;
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    header("Location: import_sku_list.php?status=err&debug=no_handle");
    exit;
}

// Strip UTF-8 BOM if present (Excel CSVs)
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

$header           = fgetcsv($handle);
$normalizedHeader = array_map('trim', $header);

$expectedHeaders = [
    'MDC CODE',           // [0]
    'MDC DESCRIPTION',    // [1]
    'BU',                 // [2]
    'PRODUCT PER LINE',   // [3]
    'BRAND',              // [4]
    'PROD./INSP. MEMO',   // [5]
    'MATERIAL DESCRIPTION', // [6]
    'MDC UNIT',           // [7]
    'SERVING',            // [8]
    'CONFIG',             // [9]
    'NESTLE PPL'          // [10]
];

if ($normalizedHeader !== $expectedHeaders) {
    error_log("SKU DEBUG: Header mismatch. Got: " . json_encode($normalizedHeader));
    fclose($handle);
    header("Location: import_sku_list.php?status=err&debug=bad_header");
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO tbl_sku_list (
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
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        mdc_description      = VALUES(mdc_description),
        bu                   = VALUES(bu),
        product_per_line     = VALUES(product_per_line),
        brand                = VALUES(brand),
        prod_insp_memo       = VALUES(prod_insp_memo),
        material_description = VALUES(material_description),
        mdc_unit             = VALUES(mdc_unit),
        serving              = VALUES(serving),
        config               = VALUES(config),
        nestle_ppl           = VALUES(nestle_ppl),
        updated_at           = NOW()
");

if (!$stmt) {
    error_log("SKU DEBUG: Prepare failed: " . $conn->error);
    fclose($handle);
    header("Location: import_sku_list.php?status=err&debug=prepare_fail");
    exit;
}

$insertedCount = 0;
$errorCount    = 0;

while (($row = fgetcsv($handle)) !== false) {
    if (count(array_filter($row)) === 0) continue;
    if (count($row) < 11) { $errorCount++; continue; }

    // Strip \r and trim every value
    $row = array_map(function($v) {
        return trim(str_replace(["\r", "\n", "\0"], '', $v));
    }, $row);

    // Map by index explicitly — no assumptions about variable order
    $mdc_code             = $row[0];
    $mdc_description      = $row[1];
    $bu                   = $row[2];
    $product_per_line     = $row[3];
    $brand                = $row[4];
    $prod_insp_memo       = $row[5];
    $material_description = $row[6];
    $mdc_unit             = $row[7];
    $serving              = $row[8];
    $config               = $row[9];
    $nestle_ppl           = $row[10];

    if (!is_numeric($mdc_code)) {
        error_log("SKU DEBUG: mdc_code not numeric: '{$mdc_code}'");
        $errorCount++;
        continue;
    }

    $prod_insp_memo_int = (int)$prod_insp_memo;
    $mdc_unit_int       = (int)$mdc_unit;
    $config_int         = (int)$config;

    // INSERT column order: mdccode(s) mdc_description(s) bu(s) product_per_line(s)
    //   brand(s) prod_insp_memo(i) material_description(s) mdc_unit(i)
    //   serving(s) config(i) nestle_ppl(s)
    $stmt->bind_param(
        "sssssisisss",
        $mdc_code,              // s  [0] mdccode
        $mdc_description,       // s  [1] mdc_description
        $bu,                    // s  [2] bu
        $product_per_line,      // s  [3] product_per_line
        $brand,                 // s  [4] brand
        $prod_insp_memo_int,    // i  [5] prod_insp_memo
        $material_description,  // s  [6] material_description
        $mdc_unit_int,          // i  [7] mdc_unit
        $serving,               // s  [8] serving
        $config_int,            // i  [9] config
        $nestle_ppl             // s  [10] nestle_ppl
    );

    if ($stmt->execute()) {
        $insertedCount++;
        error_log("SKU DEBUG: Inserted mdc_code={$mdc_code} nestle_ppl={$nestle_ppl}");
    } else {
        error_log("SKU DEBUG: Execute failed mdc_code={$mdc_code}: " . $stmt->error);
        $errorCount++;
    }
}

$stmt->close();
fclose($handle);
$conn->close();

if ($insertedCount > 0) {
    header("Location: import_sku_list.php?status=succ");
} else {
    header("Location: import_sku_list.php?status=err&debug=zero_inserted");
}
exit;