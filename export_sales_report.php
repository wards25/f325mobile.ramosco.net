<?php
session_start();
include_once("dbconnect.php");
include_once("api/salesapp_conn.php");

if (!isset($_POST['so_date_from']) || !isset($_POST['so_date_to'])) {
    die("Invalid request.");
}

$date_from = mysqli_real_escape_string($conn, $_POST['so_date_from']);
$date_to   = mysqli_real_escape_string($conn, $_POST['so_date_to']);

// Fetch raw data from main DB
$query = "
    SELECT
        s.prod_insp_memo AS StockCode,
        f.f325number AS SO_NUM,
        f.f325date AS SO_DATE,
        r.quantity AS QTY,
        r.costextended AS COST_EXTENDED,
        f.brcode AS BRANCH_CODE,
        r.mdccode AS SKU,
        s.mdc_description AS DESCRIPTION,
        s.material_description AS PIS_DESCRIPTION,
        s.bu AS BU,
        s.product_per_line AS PPL,
        s.brand AS BRAND,
        s.prod_insp_memo AS PROD_INSP_MEMO,
        s.config AS CONVERSION,
        s.nestle_ppl AS NESTLE_PPL,
        c.code,
        c.branchname AS BRANCHNAME,
        f.vendor,
        r.vendorcode
    FROM dbraw r
    LEFT JOIN dbf325number f ON r.f325number = f.f325number
    LEFT JOIN tbl_sku_list s ON r.mdccode = s.mdccode
    LEFT JOIN dbcensus c ON f.brcode = c.code
    WHERE f.f325date BETWEEN '$date_from' AND '$date_to' AND f.vendor = 24134 AND r.vendorcode = 24134
    ORDER BY f.f325date ASC, f.f325number ASC
";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

// Pre-load census data from salesapp DB into an array keyed by br_code
$census = [];
$census_query = "SELECT code, branch, location, region, area, storetype, storesize, rsm, bms FROM census";
$census_result = mysqli_query($salesapp_conn, $census_query);
if ($census_result) {
    while ($crow = mysqli_fetch_assoc($census_result)) {
        $census[$crow['code']] = $crow;
    }
}

// Helpers
function getWeekOfMonth($date_str) {
    $date = new DateTime($date_str);
    $day = (int)$date->format('j');
    return (int)ceil($day / 7);
}

function getCalendarWeek($date_str) {
    $date = new DateTime($date_str);
    return $date->format('W');
}

// Output CSV
$filename = "Nestle_Sales_Report_" . $date_from . "_to_" . $date_to . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

// Row 2: Column headers
fputcsv($output, [
    'StockCode', 'SKU', 'DESCRIPTION', 'PIS Description', 'BU', 'PPL', 'BRAND',
    'COMPANY', 'SO #', 'INVOICE #', 'S.O. DATE', 'INVOICE DATE',
    'WEEK', 'WEEK',
    'LOCATION', 'MTD REGION', 'HUB', 'CLUTCH REGION', 'NESTLE AREA', 'CLUTCH AREA',
    'REGION 2', 'CODE', 'OUTLET',
    'STATUS', 'CONVERSION', 'CONVERTED QTY', 'QTY', 'UOM', 'VAT EX',
    'RSM', 'BMS', 'RCM', 'STORE TYPE', 'STORE SIZE', 'B.U.', 'PPL'
]);

// Data rows
while ($row = mysqli_fetch_assoc($result)) {
    $so_date    = $row['SO_DATE'];
    $qty        = -(float)$row['QTY'];
    $conversion = !empty($row['CONVERSION']) ? (float)$row['CONVERSION'] : 1;
    $converted  =  ($qty != 0) ? -($conversion / abs($qty)) : 0;
    $vat_ex     = !empty($row['COST_EXTENDED'])
                ? -round((float)$row['COST_EXTENDED'] / 1.12, 4)
                : '';
    $week_month = !empty($so_date) ? getWeekOfMonth($so_date) : '';
    $cal_week   = !empty($so_date) ? getCalendarWeek($so_date) : '';

    // Look up census from salesapp DB
    $br_code = $row['BRANCH_CODE'];
    $c = isset($census[$br_code]) ? $census[$br_code] : [];

    $branch_name = $c['branch'] ?? 'no data';
    $location    = $c['location']    ?? '';   // HUB
    $region      = $c['region']      ?? '';   // MTD REGION + REGION 2
    $area        = $c['area']        ?? '';   // CLUTCH REGION
    $storetype   = $c['storetype']   ?? '';
    $storesize   = $c['storesize']   ?? '';
    $rsm         = $c['rsm']         ?? '';
    $bms         = $c['bms']         ?? '';

    fputcsv($output, [
        $row['StockCode'] ?? 'No Stock Code',
        $row['SKU'],
        $row['DESCRIPTION'] ?? 'No Description',
        $row['PIS_DESCRIPTION'] ?? 'No PIS Description',
        $row['BU'],
        $row['PPL'] ?? 'No PPL',
        $row['BRAND'] ?? 'No Brand',
        'CLUTCH',           // COMPANY
        $row['SO_NUM'],     // SO #
        '',                 // INVOICE #
        $so_date,           // S.O. DATE
        '',                 // INVOICE DATE
        $week_month,        // WEEK of month
        $cal_week,          // CALENDAR WEEK
        $location,          // LOCATION (= location)
        $region,            // MTD REGION (= region)
        $location,          // HUB (= location)
        $area,              // CLUTCH REGION (= area)
        '',                 // NESTLE AREA (blank)
        $area,                 // CLUTCH AREA (=area)
        $region,            // REGION 2 (= region)
        $row['BRANCH_CODE'],           // CODE
        $row['BRANCHNAME'],       // OUTLET
        'CREDIT',           // STATUS
        $conversion,        // CONVERSION
        $converted,         // CONVERTED QTY
        $qty,               // QTY
        'PC',               // UOM
        $vat_ex,            // VAT EX
        $rsm,               // RSM
        $bms,               // BMS
        '',                 // RCM (not in salesapp — leave blank or add if available)
        $storetype,         // STORE TYPE
        $storesize,         // STORE SIZE
        $row['BU'],         // B.U.
        $row['NESTLE_PPL'], // PPL
    ]);
}

fclose($output);
mysqli_close($salesapp_conn);
exit;
?>