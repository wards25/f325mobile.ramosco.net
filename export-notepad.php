<?php
session_start();
include('dbconnect.php');

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Validate the date instead of dropping it straight into SQL.
$nameexport_raw = trim($_POST['name-export'] ?? '');
$nameexport_ts = strtotime($nameexport_raw);
if ($nameexport_ts === false) {
    http_response_code(400);
    echo "Invalid export date.";
    exit;
}
$nameexport = date('Y-m-d', $nameexport_ts);

// mime type
header('Content-Type: text/csv');
// tell browser what's the file name
header('Content-Disposition: attachment; filename="Printed Summary as of ' . date('m-d-Y') . '.csv"');
// no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
fputcsv($output, array('F325 Number', 'F325 Date', 'F325 Email Date', 'Code', 'Branch Name', 'Region', 'Prepared By', 'Issued By', 'Company'));

// ---- Permission scope (same pattern as print-notepad.php / load-notepad-list.php / export-notepad.php) ----
$user_id = (string) ($_SESSION['id'] ?? '');
$scope_retailers = [];
$scope_locations = [];
$scope_companies = []; // vendorcodes

function build_summary_placeholders($count)
{
    return implode(',', array_fill(0, $count, '?'));
}

$scope_perm_stmt = $conn->prepare(
    "SELECT DISTINCT p.retailer, l.location, c.vendorcode
     FROM tbl_permission p
     LEFT JOIN tbl_location l ON l.id = p.location_id
     LEFT JOIN tbl_company c ON c.id = p.company_id
     WHERE p.user_id = ?"
);
$scope_perm_stmt->bind_param("s", $user_id);
$scope_perm_stmt->execute();
$scope_perm_result = $scope_perm_stmt->get_result();
while ($row = $scope_perm_result->fetch_assoc()) {
    if (!empty($row['retailer']) && !in_array($row['retailer'], $scope_retailers, true)) {
        $scope_retailers[] = $row['retailer'];
    }
    if (!empty($row['location']) && !in_array($row['location'], $scope_locations, true)) {
        $scope_locations[] = $row['location'];
    }
    if (!empty($row['vendorcode']) && !in_array($row['vendorcode'], $scope_companies, true)) {
        $scope_companies[] = $row['vendorcode'];
    }
}
$scope_perm_stmt->close();

// No scope at all — export headers only, nothing this user can see.
if (empty($scope_retailers) || empty($scope_locations) || empty($scope_companies)) {
    fclose($output);
    $conn->close();
    exit;
}

// ---- Build the scoped query ----
// c.retailer = f.retailer is required on the census join — branch codes
// aren't unique across retailers, so without it this can pull the wrong
// branch/region for a given code (same bug fixed in search_process.php /
// load-notepad-list.php / print-pg-rtv.php).
$sql = "SELECT f.f325number, f.f325date, f.emaildate, f.brcode, f.preparedby, f.issuedby, f.vendor,
               ce.branchname, ce.region, co.nickname
        FROM tbl_f325number f
        LEFT JOIN tbl_census ce ON ce.code = f.brcode AND ce.retailer = f.retailer
        LEFT JOIN tbl_company co ON co.vendorcode = f.vendor
        WHERE f.status = 'PRINTED' AND f.process = 'UPLOADED' AND f.emaildate = ?";
$types = "s";
$params = [$nameexport];

$placeholders = build_summary_placeholders(count($scope_retailers));
$sql .= " AND f.retailer IN ($placeholders)";
$types .= str_repeat('s', count($scope_retailers));
$params = array_merge($params, $scope_retailers);

$placeholders = build_summary_placeholders(count($scope_companies));
$sql .= " AND f.vendor IN ($placeholders)";
$types .= str_repeat('s', count($scope_companies));
$params = array_merge($params, $scope_companies);

$upperLocations = array_map('strtoupper', $scope_locations);
$placeholders = build_summary_placeholders(count($upperLocations));
$sql .= " AND UPPER(TRIM(f.location)) IN ($placeholders)";
$types .= str_repeat('s', count($upperLocations));
$params = array_merge($params, $upperLocations);

$sql .= " ORDER BY f.vendor, f.brcode ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $branchname = $row['branchname'] ?: 'For Fill-Up';
    $region = $row['region'] ?: 'For Fill-Up';
    $vendor = $row['nickname'] ?: 'For Fill-Up';

    fputcsv($output, array(
        "'" . $row['f325number'], // leading quote keeps Excel treating this as text
        $row['f325date'],
        $row['emaildate'],
        $row['brcode'],
        $branchname,
        $region,
        $row['preparedby'],
        $row['issuedby'],
        $vendor,
    ));
}

$stmt->close();
fclose($output);
$conn->close();
?>