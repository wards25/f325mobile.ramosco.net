<?php
session_start();
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$category   = isset($_POST['category'])   ? mysqli_real_escape_string($conn, $_POST['category'])   : '';
$company    = isset($_POST['company'])    ? mysqli_real_escape_string($conn, $_POST['company'])     : '';
$vendorcode = isset($_POST['vendorcode']) ? mysqli_real_escape_string($conn, $_POST['vendorcode'])  : '';
$location   = isset($_POST['location'])   ? mysqli_real_escape_string($conn, $_POST['location'])    : '';
$items      = isset($_POST['items'])      ? $_POST['items'] : []; // array of f325number|mdccode

if (empty($items)) {
    http_response_code(400);
    exit("No items selected.");
}

// Build safe IN clause from selected f325numbers
$f325_list = [];
foreach ($items as $item) {
    $parts = explode('|', $item);
    $f325 = mysqli_real_escape_string($conn, $parts[0]);
    $f325_list[] = "'" . $f325 . "'";
}
$f325_filter = implode(',', $f325_list);

// Optional location filter
$location_clause = '';
if (!empty($location)) {
    $location_clause = "AND r.location = '$location'";
}

$sql = "
    SELECT 
        c2.branchname,
        r.f325number,
        r.location,
        r.quantity as qty,
        r.costextended,
        r.reasoncode,
        r.expiration,
        CONCAT(p.mdccode, ' - ', p.description) AS mdccode_description,
        s.prod_insp_memo
    FROM dbraw r
    INNER JOIN dbcompany c
        ON r.vendorcode = c.vendorcode
    INNER JOIN dbproduct p
        ON r.mdccode = p.mdccode
        AND r.vendorcode = p.vendor
    LEFT JOIN dbf325number f
        ON r.f325number = f.f325number
    LEFT JOIN dbcensus c2
        ON f.brcode = c2.code
    LEFT JOIN tbl_sku_list s
        ON r.mdccode = s.mdccode
    WHERE
        r.forpullout >= 1
        AND r.batchnumber_forpullout IS NULL
        AND r.category = '$category'
        AND c.nickname = '$company'
        AND p.vendor = '$vendorcode'
        AND r.f325number IN ($f325_filter)
        $location_clause
    ORDER BY r.location ASC, r.f325number ASC
";

$result = mysqli_query($conn, $sql);
if (!$result) {
    http_response_code(500);
    exit("Query error: " . mysqli_error($conn));
}

// --- Stream CSV ---
$dateStr  = date('Y-m-d');
$filename = "FOR_pullout_{$company}_{$category}_{$dateStr}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
fputcsv($output, ['Branch', 'F325 Number', 'Material Code','MDC Code' ,'Location', 'Qty', 'Cost Extended', 'Reason Code', 'Expiration']);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['branchname']  ?? 'N/A',
        $row['f325number'],
        $row['prod_insp_memo'] ?? '0',
        $row['mdccode_description'] ?? 'N/A',
        $row['location'],
        $row['qty'],
        $row['costextended'],
        $row['reasoncode']  ?? '',
        !empty($row['expiration']) ? date('Y-m-d', strtotime($row['expiration'])) : 'N/A'
    ]);
}

fclose($output);
exit();
?>