<?php
session_start();
include_once('dbconnect.php');

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// mime type
header('Content-Type: text/csv');
// tell browser what's the file name
header('Content-Disposition: attachment; filename="F325 Printed as of ' . date('m-d-Y') . '.csv"');
// no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
fputcsv($output, array('F325 Number', 'Code', 'TM #', 'Driver Name', 'Plate Number', 'Date Schedule', 'Remarks', 'Cluster'));

$user_id = (string) ($_SESSION['id'] ?? '');
$scope_retailers = [];
$scope_locations = [];
$scope_companies = []; // vendorcodes

function build_export_placeholders($count)
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
$sql = "SELECT f325number, brcode, tmnumber, drivername, platenumber, cluster
        FROM tbl_f325number
        WHERE status = 'PRINTED'";
$types = "";
$params = [];

$placeholders = build_export_placeholders(count($scope_retailers));
$sql .= " AND retailer IN ($placeholders)";
$types .= str_repeat('s', count($scope_retailers));
$params = array_merge($params, $scope_retailers);

$placeholders = build_export_placeholders(count($scope_companies));
$sql .= " AND vendor IN ($placeholders)";
$types .= str_repeat('s', count($scope_companies));
$params = array_merge($params, $scope_companies);

$upperLocations = array_map('strtoupper', $scope_locations);
$placeholders = build_export_placeholders(count($upperLocations));
$sql .= " AND UPPER(TRIM(location)) IN ($placeholders)";
$types .= str_repeat('s', count($upperLocations));
$params = array_merge($params, $upperLocations);

$sql .= " ORDER BY vendor, brcode ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        "'" . $row['f325number'], // leading quote forces Excel to keep this as text, not a number
        $row['brcode'],
        $row['tmnumber'],
        $row['drivername'],
        $row['platenumber'],
        '', // Date Schedule — not sourced anywhere in the original either; left blank as-is
        '', // Remarks — same
        $row['cluster'],
    ));
}

$stmt->close();
fclose($output);
$conn->close();
?>