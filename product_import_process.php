<?php
session_start();
include_once("dbconnect.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['csv_file']['tmp_name'])) {
    $_SESSION['product_flash'] = ['type' => 'danger', 'msg' => 'Please choose a CSV file to upload.'];
    header("Location: product-list.php");
    exit;
}

$file = $_FILES['csv_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['product_flash'] = ['type' => 'danger', 'msg' => 'File upload failed. Please try again.'];
    header("Location: product-list.php");
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    $_SESSION['product_flash'] = ['type' => 'danger', 'msg' => 'Please upload a .csv file.'];
    header("Location: product-list.php");
    exit;
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    $_SESSION['product_flash'] = ['type' => 'danger', 'msg' => 'Could not read the uploaded file.'];
    header("Location: product-list.php");
    exit;
}

// Skip the header row
fgetcsv($handle);

$stmt = $conn->prepare(
    "INSERT INTO tbl_product (mdccode, itemcode, description, category, uom, vendor, active)
     VALUES (?, ?, ?, ?, ?, ?, 1)"
);
$stmt->bind_param("ssssss", $mdccode, $itemcode, $description, $category, $uom, $vendor);

$inserted = 0;
$skipped = 0;

while (($data = fgetcsv($handle)) !== false) {
    // Skip completely blank lines
    if (count($data) === 1 && trim($data[0]) === '') {
        continue;
    }

    // Expected column order: mdccode, itemcode, description, category, uom, vendorcode
    $mdccode = trim($data[0] ?? '');
    $itemcode = trim($data[1] ?? '');
    $description = trim($data[2] ?? '');
    $category = trim($data[3] ?? '');
    $uom = trim($data[4] ?? '');
    $vendor = trim($data[5] ?? '');

    if ($mdccode === '' || $itemcode === '' || $description === '' || $category === '' || $vendor === '') {
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
    $_SESSION['product_flash'] = ['type' => 'success', 'msg' => "Imported $inserted product(s) successfully."];
} elseif ($inserted > 0 && $skipped > 0) {
    $_SESSION['product_flash'] = ['type' => 'warning', 'msg' => "Imported $inserted product(s). Skipped $skipped row(s) with missing required fields."];
} else {
    $_SESSION['product_flash'] = ['type' => 'danger', 'msg' => "No products were imported. Check that your CSV columns are in the right order."];
}

header("Location: product-list.php");
exit;