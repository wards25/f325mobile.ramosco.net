<?php
session_start();
include_once("dbconnect.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['csv_file']['tmp_name'])) {
    $_SESSION['store_flash'] = ['type' => 'danger', 'msg' => 'Please choose a CSV file to upload.'];
    header("Location: store-list.php");
    exit;
}

$file = $_FILES['csv_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['store_flash'] = ['type' => 'danger', 'msg' => 'File upload failed. Please try again.'];
    header("Location: store-list.php");
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    $_SESSION['store_flash'] = ['type' => 'danger', 'msg' => 'Please upload a .csv file.'];
    header("Location: store-list.php");
    exit;
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    $_SESSION['store_flash'] = ['type' => 'danger', 'msg' => 'Could not read the uploaded file.'];
    header("Location: store-list.php");
    exit;
}

// Skip the header row
fgetcsv($handle);

$stmt = $conn->prepare(
    "INSERT INTO tbl_census (code, branchname, shipping, billing, franchise, region, cluster, deducttype, location, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
);
$stmt->bind_param("sssssssss", $code, $branchname, $shipping, $billing, $franchise, $region, $cluster, $deducttype, $location);

$inserted = 0;
$skipped = 0;

while (($data = fgetcsv($handle)) !== false) {
    // Skip completely blank lines
    if (count($data) === 1 && trim($data[0]) === '') {
        continue;
    }

    // Expected column order: code, branchname, shipping, billing, franchise, region, cluster, deducttype, location
    $code = trim($data[0] ?? '');
    $branchname = trim($data[1] ?? '');
    $shipping = trim($data[2] ?? '');
    $billing = trim($data[3] ?? '');
    $franchise = trim($data[4] ?? '');
    $region = trim($data[5] ?? '');
    $cluster = trim($data[6] ?? '');
    $deducttype = trim($data[7] ?? '');
    $location = trim($data[8] ?? '');

    if ($code === '' || $branchname === '' || $shipping === '' || $billing === '' ||
        $franchise === '' || $region === '' || $cluster === '' || $deducttype === '' || $location === '') {
        $skipped++;
        continue;
    }

    if ($stmt->execute()) {
        $inserted++;
    } else {
        $skipped++;
    }
}

$stmt->close();
fclose($handle);
$conn->close();

if ($inserted > 0 && $skipped === 0) {
    $_SESSION['store_flash'] = ['type' => 'success', 'msg' => "Imported $inserted store(s) successfully."];
} elseif ($inserted > 0 && $skipped > 0) {
    $_SESSION['store_flash'] = ['type' => 'warning', 'msg' => "Imported $inserted store(s). Skipped $skipped row(s) with missing required fields."];
} else {
    $_SESSION['store_flash'] = ['type' => 'danger', 'msg' => "No stores were imported. Check that your CSV columns are in the right order."];
}

header("Location: store-list.php");
exit;