<?php
session_start();
include('dbconnect.php');

mysqli_set_charset($conn, 'utf8mb4');

// ─── ALL AVAILABLE COLUMNS ────────────────────────────────────────────────────
// Maps POST checkbox name => [ sql_expression, csv_header_label ]
$allColumns = [
    // dbborflist
    'col_borfnumber'       => ['l.borfnumber',         'BORF Number'],
    'col_userprocessed'    => ['l.userprocessed',       'User Processed'],
    'col_f325number'       => ['l.f325number',          'F325 Number'],
    'col_brcode'           => ['l.brcode',              'Branch Code'],
    'col_preparedby'       => ['l.preparedby',          'Prepared By'],
    'col_issuedby'         => ['l.issuedby',            'Issued By'],
    'col_emaildate'        => ['l.emaildate',           'Email Date'],
    'col_f325date'         => ['l.f325date',            'F325 Date'],
    'col_vendor'           => ['l.vendor',              'Vendor'],
    'col_tmnumber'         => ['l.tmnumber',            'TM Number'],
    'col_drivername'       => ['l.drivername',          'Driver Name'],
    'col_platenumber'      => ['l.platenumber',         'Plate Number'],
    'col_datesched'        => ['l.datesched',           'Date Scheduled'],
    'col_list_datecleared' => ['l.datecleared',         'Date Cleared'],
    'col_list_arnumber'    => ['l.arnumber',            'AR Number'],
    'col_pageno'           => ['l.pageno',              'Page No'],
    'col_printremarks'     => ['l.printremarks',        'Print Remarks'],
    'col_logisticremarks'  => ['l.logisticremarks',     'Logistic Remarks'],
    'col_clearingremarks'  => ['l.clearingremarks',     'Clearing Remarks'],
    'col_cluster'          => ['l.cluster',             'Cluster'],
    'col_list_location'    => ['l.location',            'Location'],
    'col_list_status'      => ['l.status',              'Status'],
    'col_process'          => ['l.process',             'Process'],
    'col_stamped'          => ['l.stamped',             'Stamped'],

    // dbborfraw
    'col_mdccode'            => ['r.mdccode',             'MDC Code'],
    'col_category'           => ['r.category',            'Category'],
    'col_dmpiclass'          => ['r.dmpiclass',           'DMPI Class'],
    'col_quantity'           => ['r.quantity',            'Quantity'],
    'col_expiration'         => ['r.expiration',          'Expiration'],
    'col_unitcost'           => ['r.unitcost',            'Unit Cost'],
    'col_costextended'       => ['r.costextended',        'Cost Extended'],
    'col_reasoncode'         => ['r.reasoncode',          'Reason Code'],
    'col_raw_arnumber'       => ['r.arnumber',            'AR Number (Raw)'],
    'col_arreason'           => ['r.arreason',            'AR Reason'],
    'col_dmpireason'         => ['r.dmpireason',          'DMPI Reason'],
    'col_rcvdqty'            => ['r.rcvdqty',             'Received Qty'],
    'col_dmpiref'            => ['r.dmpiref',             'DMPI Ref'],
    'col_deductref'          => ['r.deductref',           'Deduct Ref'],
    'col_deductqty'          => ['r.deductqty',           'Deduct Qty'],
    'col_deductcostextended' => ['r.deductcostextended',  'Deduct Cost Extended'],
    'col_raw_datecleared'    => ['r.datecleared',         'Date Cleared (Raw)'],
    'col_pulloutref'         => ['r.pulloutref',          'Pullout Ref'],
    'col_raw_location'       => ['r.location',            'Location (Raw)'],
    'col_raw_status'         => ['r.status',              'Status (Raw)'],
    'col_statusout'          => ['r.statusout',           'Status Out'],
    'col_paymentstatus'      => ['r.paymentstatus',       'Payment Status'],
];

// ─── DETERMINE SELECTED COLUMNS ───────────────────────────────────────────────
$selectedCols = [];

foreach ($allColumns as $postKey => $colDef) {
    if (isset($_POST[$postKey])) {
        $selectedCols[$postKey] = $colDef;
    }
}

// Fallback: if nothing selected, export all columns
if (empty($selectedCols)) {
    $selectedCols = $allColumns;
}

// ─── BUILD SELECT CLAUSE ──────────────────────────────────────────────────────
$selectParts = [];
$colIndex    = 1;
$headers     = [];
$aliases     = [];

foreach ($selectedCols as $postKey => $colDef) {
    list($sqlExpr, $label) = $colDef;
    $alias          = 'c' . $colIndex;
    $selectParts[]  = "$sqlExpr AS $alias";
    $headers[]      = $label;
    $aliases[]      = $alias;
    $colIndex++;
}

$selectClause = implode(', ', $selectParts);

// ─── OPTIONAL FILTERS ─────────────────────────────────────────────────────────
$conditions = [];

if (!empty($_POST['borfnumber'])) {
    $conditions[] = "l.borfnumber = '" . mysqli_real_escape_string($conn, trim($_POST['borfnumber'])) . "'";
}
if (!empty($_POST['status']) && $_POST['status'] !== 'all') {
    $conditions[] = "l.status = '" . mysqli_real_escape_string($conn, trim($_POST['status'])) . "'";
}
if (!empty($_POST['date_from'])) {
    $conditions[] = "l.emaildate >= '" . mysqli_real_escape_string($conn, trim($_POST['date_from'])) . "'";
}
if (!empty($_POST['date_to'])) {
    $conditions[] = "l.emaildate <= '" . mysqli_real_escape_string($conn, trim($_POST['date_to'])) . "'";
}
if (!empty($_POST['cluster'])) {
    $conditions[] = "l.cluster = '" . mysqli_real_escape_string($conn, trim($_POST['cluster'])) . "'";
}
if (!empty($_POST['location']) && $_POST['location'] !== 'all') {
    $conditions[] = "l.location = '" . mysqli_real_escape_string($conn, trim($_POST['location'])) . "'";
}

$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ─── QUERY ────────────────────────────────────────────────────────────────────
$sql = "
    SELECT $selectClause
    FROM dbborflist l
    LEFT JOIN dbborfraw r ON r.f325number = l.f325number
    $whereClause
    ORDER BY l.id ASC, r.id ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}

// ─── CSV OUTPUT ───────────────────────────────────────────────────────────────
$filename = 'BORF_Export_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM so Excel opens correctly
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// ─── HEADER ROW ───────────────────────────────────────────────────────────────
fputcsv($output, $headers);

// ─── DATA ROWS ────────────────────────────────────────────────────────────────
if (mysqli_num_rows($result) === 0) {
    fputcsv($output, ['No records found for the selected filters.']);
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, array_values($row));
    }
}

mysqli_free_result($result);
mysqli_close($conn);
fclose($output);
exit;