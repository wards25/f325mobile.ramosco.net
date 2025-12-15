<?php
session_start();
include_once('dbconnect.php');

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Check if current user is admin
$result = mysqli_query($conn, "SELECT admin FROM dbuser WHERE id = " . $_SESSION['id']);
$user = mysqli_fetch_assoc($result);
if (!$user || $user['admin'] != 1) {
    header("Location: unauthorized_access.php");
    exit;
}

// User id to update
$id = intval($_POST['id']);

// Get all company IDs from dbcompany
$company_query = mysqli_query($conn, "SELECT id FROM dbcompany");
$companyAccess = [];
while ($row = mysqli_fetch_assoc($company_query)) {
    $companyAccess['comp' . $row['id']] = 0;
}

// Mark checked companies
if (isset($_POST['company']) && is_array($_POST['company'])) {
    foreach ($_POST['company'] as $compId) {
        $col = 'comp' . intval($compId);
        if (array_key_exists($col, $companyAccess)) {
            $companyAccess[$col] = 1;
        }
    }
}

// List of modules
$modules = [
    'store', 'inventory', 'import', 'importdop', 'print', 'schedule', 'clearing', 'manual',
    'fordeduct', 'borfapps', 'dmpiraw', 'deduction', 'deductdoc', 'paiddeduction',
    'payment', 'returntosupplier', 'pulloutdoc', 'report', 'syssetting'
];

$moduleAccess = array_fill_keys($modules, 0);

// Mark checked modules
if (isset($_POST['modules']) && is_array($_POST['modules'])) {
    foreach ($_POST['modules'] as $moduleKey) {
        if (in_array($moduleKey, $modules)) {
            $moduleAccess[$moduleKey] = 1;
        }
    }
}

// Prepare SQL set parts
$setParts = [];
foreach ($companyAccess as $col => $val) {
    $setParts[] = "$col = $val";
}
foreach ($moduleAccess as $col => $val) {
    $setParts[] = "$col = $val";
}

$sql = "UPDATE dbuser SET " . implode(", ", $setParts) . " WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    header("Location: account.php?status=permissions_updated");
    exit;
} else {
    echo "Error updating permissions: " . mysqli_error($conn);
}
