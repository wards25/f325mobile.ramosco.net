<?php
session_start();
include('dbconnect.php');

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// --- Increase limits for large exports ---
set_time_limit(0);
ini_set('memory_limit', '512M');
mysqli_query($conn, "SET SESSION net_read_timeout=3600");
mysqli_query($conn, "SET SESSION net_write_timeout=3600");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="F325 Per Principal Report as of ' . date('m-d-Y') . '.csv"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

// --- Build CSV header based on selected columns ---
$columns = [
    'f325number'   => 'F325 NUMBER',
    'skucode'      => 'SKU CODE',
    'description'  => 'DESCRIPTION',
    'category'     => 'CATEGORY',
    'brcode'       => 'BRCODE',
    'brname'       => 'BRNAME',
    'dmpiclass'    => 'DMPI CLASS',
    'dmpireason'   => 'DMPI REASON',
    'reasoncode'   => 'REASON CODE',
    'quantity'     => 'QTY',
    'rcvdqty'      => 'RCVD QTY',
    'expiration'   => 'EXPIRATION',
    'f325status'   => 'STATUS',
    'arnumber'     => 'AR NO',
    'arreason'     => 'AR REASON',
    'preparedby'   => 'PREPARED BY',
    'issuedby'     => 'ISSUED BY',
    'emaildate'    => 'EMAIL DATE',
    'f325date'     => 'F325 DATE',
    'company'      => 'COMPANY',
    'tmnumber'     => 'TM NO',
    'platenumber'  => 'PLATE NO',
    'driver'       => 'DRIVER',
    'scheddate'    => 'SCHED DATE',
    'cleardate'    => 'CLEARED DATE',
    'unitcost'     => 'UNIT COST',
    'costextended' => 'COST EXT',
    'print'        => 'PRINT',
    'log'          => 'LOGISTIC',
    'clearing'     => 'CLEARING',
    'cluster'      => 'CLUSTER',
    'f325location' => 'LOCATION',
    'process'      => 'PROCESS',
    'ilrno'        => 'ILR NO',
];

// Only include selected columns
$selectedColumns = [];
$header = [];
foreach ($columns as $key => $label) {
    if (isset($_POST[$key])) {
        $selectedColumns[] = $key;
        $header[] = $label;
    }
}

if (empty($selectedColumns)) {
    fclose($output);
    exit;
}

fputcsv($output, $header);

// --- Parse dates safely ---
// type="date" always sends Y-m-d, but handle other formats just in case
$fromRaw = $_POST['from'] ?? '';
$toRaw   = $_POST['to']   ?? '';

$fromDate = DateTime::createFromFormat('Y-m-d', $fromRaw)
          ?: DateTime::createFromFormat('d/m/Y', $fromRaw)
          ?: DateTime::createFromFormat('m/d/Y', $fromRaw);

$toDate   = DateTime::createFromFormat('Y-m-d', $toRaw)
          ?: DateTime::createFromFormat('d/m/Y', $toRaw)
          ?: DateTime::createFromFormat('m/d/Y', $toRaw);

$from = $fromDate ? $fromDate->format('Y-m-d') : date('Y-m-d');
$to   = $toDate   ? $toDate->format('Y-m-d')   : date('Y-m-d');

// --- Sanitize other inputs ---
$status    = mysqli_real_escape_string($conn, $_POST['status']    ?? 'all');
$principal = mysqli_real_escape_string($conn, $_POST['principal'] ?? 'all');
$location  = mysqli_real_escape_string($conn, $_POST['location']  ?? 'all');

// --- Build WHERE clause ---
$where = "f.emaildate BETWEEN '$from 00:00:00' AND '$to 23:59:59'";

if ($status !== 'all') {
    // These values belong to status_out
    $statusOutValues = ['FOR PULL-OUT', 'FOR CHARGING', 'PULL-OUT'];
    
    if (in_array($status, $statusOutValues)) {
        $where .= " AND r.statusout = '$status'";
    } else {
        $where .= " AND r.status = '$status'";
    }
}

if ($location !== 'all') {
    $where .= " AND f.location = '$location'";
}

// --- Principal filter ---
$principalFilter = ($principal !== 'all') ? "AND r.category = '$principal'" : "";

// --- Query ---
$sql = "
    SELECT
        r.f325number,
        r.mdccode,
        r.quantity,
        r.rcvdqty,
        r.expiration,
        r.status          AS row_status,
        r.statusout,
        r.arnumber,
        r.arreason,
        r.reasoncode,
        r.dmpireason      AS raw_dmpireason,
        r.unitcost,
        r.category        AS raw_category,

        f.brcode,
        f.preparedby,
        f.issuedby,
        f.emaildate,
        f.f325date,
        f.vendor,
        f.tmnumber,
        f.platenumber,
        f.drivername,
        f.datesched,
        f.datecleared,
        f.printremarks,
        f.logisticremarks,
        f.clearingremarks,
        f.cluster,
        f.location        AS f325_location,
        f.process,
        f.ilrno,

        p.description,
        p.category        AS product_category,
        p.dmpiclassification,

        c.branchname,

        co.nickname       AS company_nickname,

        CONCAT(dr.reasoncode, '-', dr.reason) AS full_dmpireason

    FROM dbf325number f
    INNER JOIN dbraw r
        ON r.f325number = f.f325number
        $principalFilter
    LEFT JOIN dbproduct p
        ON p.mdccode = r.mdccode
        AND p.vendor = f.vendor
    LEFT JOIN dbcensus c
        ON c.code = f.brcode
    LEFT JOIN dbcompany co
        ON co.vendorcode = f.vendor
    LEFT JOIN dbdmpireason dr
        ON dr.reasoncode = r.dmpireason
        AND r.dmpireason != 0

    WHERE $where
    ORDER BY r.id ASC
";

// --- Use unbuffered query to stream rows and avoid memory overload ---
mysqli_real_query($conn, $sql);
$result = mysqli_use_result($conn);

if (!$result) {
    fclose($output);
    exit;
}

// --- Stream rows directly to CSV ---
while ($row = mysqli_fetch_assoc($result)) {

    // Derived / fallback values
    $category    = !empty($row['product_category'])  ? $row['product_category']  : 'For Fill-up';
    $dmpiclass   = !empty($row['dmpiclassification']) ? $row['dmpiclassification'] : '';
    $branch      = !empty($row['branchname'])         ? $row['branchname']         : 'For Fill-up';
    $company     = $row['company_nickname'] ?? '';
    $fullreason  = ($row['raw_dmpireason'] == 0 || empty($row['full_dmpireason'])) ? '' : $row['full_dmpireason'];
    $datesched   = (!empty($row['datesched'])   && $row['datesched']   !== '0000-00-00') ? $row['datesched']   : '';
    $datecleared = (!empty($row['datecleared']) && $row['datecleared'] !== '0000-00-00') ? $row['datecleared'] : '';
    $statusOutValues = ['FOR PULL-OUT', 'FOR CHARGING', 'PULL-OUT'];
    $displayStatus   = in_array($row['statusout'], $statusOutValues)
                       ? $row['statusout']
                       : $row['row_status'];

    // Map all column keys to their values
    $valueMap = [
        'f325number'   => $row['f325number'],
        'skucode'      => $row['mdccode'],
        'description'  => $row['description'] ?? '',
        'category'     => $category,
        'brcode'       => $row['brcode'],
        'brname'       => $branch,
        'dmpiclass'    => $dmpiclass,
        'dmpireason'   => $fullreason,
        'reasoncode'   => $row['reasoncode'],
        'quantity'     => $row['quantity'],
        'rcvdqty'      => $row['rcvdqty'],
        'expiration'   => $row['expiration'],
        'f325status'   => $displayStatus,
        'arnumber'     => $row['arnumber'],
        'arreason'     => $row['arreason'],
        'preparedby'   => $row['preparedby'],
        'issuedby'     => $row['issuedby'],
        'emaildate'    => $row['emaildate'],
        'f325date'     => $row['f325date'],
        'company'      => $company,
        'tmnumber'     => $row['tmnumber'],
        'platenumber'  => $row['platenumber'],
        'driver'       => $row['drivername'],
        'scheddate'    => $datesched,
        'cleardate'    => $datecleared,
        'unitcost'     => $row['unitcost'],
        'costextended' => $row['quantity'] * $row['unitcost'],
        'print'        => $row['printremarks'],
        'log'          => $row['logisticremarks'],
        'clearing'     => $row['clearingremarks'],
        'cluster'      => $row['cluster'],
        'f325location' => $row['f325_location'],
        'process'      => $row['process'],
        'ilrno'        => $row['ilrno'],
    ];

    // Only output selected columns in correct order
    $csvRow = [];
    foreach ($selectedColumns as $key) {
        $csvRow[] = $valueMap[$key] ?? '';
    }

    fputcsv($output, $csvRow);

    // Flush output buffer periodically to avoid memory buildup
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

$result->close();
fclose($output);
$conn->close();
?>