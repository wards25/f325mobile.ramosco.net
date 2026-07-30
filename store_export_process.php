<?php
session_start();
include_once("dbconnect.php");

$result = $conn->query("SELECT code, branchname, shipping, billing, franchise, region, cluster, deducttype, location, retailer , status FROM tbl_census ORDER BY branchname ASC");

$filename = 'stores_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// Header row — same column order the CSV importer expects
fputcsv($out, ['Code', 'Branch Name', 'Shipping Address', 'Billing Address', 'Franchise', 'Region', 'Cluster', 'Deduct Type', 'Location','Retailer', 'Status']);

while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        $row['code'],
        $row['branchname'],
        $row['shipping'],
        $row['billing'],
        $row['franchise'],
        $row['region'],
        $row['cluster'],
        $row['deducttype'],
        $row['location'],
        $row['retailer'],
        ((int) $row['status'] === 1) ? 'Active' : 'Inactive',
    ]);
}

fclose($out);
$conn->close();
exit;