<?php
session_start();
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['form_action'])) {
    header("Location: company.php");
    exit;
}

// Logs one row to tbl_history. processnumber is the vendor code per company.
function log_history($conn, $processnumber, $name, $processed) {
    $stmt = $conn->prepare(
        "INSERT INTO tbl_history (processnumber, name, processed, dateprocessed, timeprocessed)
         VALUES (?, ?, ?, CURDATE(), CURTIME())"
    );
    $stmt->bind_param("sss", $processnumber, $name, $processed);
    $stmt->execute();
    $stmt->close();
}

$name = trim($_POST['name'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$vendorcode = trim($_POST['vendorcode'] ?? '');
$refcode = (int) ($_POST['refcode'] ?? 0);
$address = trim($_POST['address'] ?? '');
$retailer = trim($_POST['retailer'] ?? '');
$active = !empty($_POST['active']) ? 1 : 0;

if ($_POST['form_action'] === 'save') {
    $company_id = (int) ($_POST['company_id'] ?? 0);

    if ($name === '' || $nickname === '' || $vendorcode === '' || $retailer === '') {
        $_SESSION['company_flash'] = ['type' => 'danger', 'msg' => 'Please fill in all required fields.'];
    } elseif ($company_id > 0) {
        // Update
        $stmt = $conn->prepare(
            "UPDATE tbl_company SET name=?, nickname=?, vendorcode=?, refcode=?, address=?, retailer=?, active=? WHERE id=?"
        );
        $stmt->bind_param("sssisssi", $name, $nickname, $vendorcode, $refcode, $address, $retailer, $active, $company_id);
        $stmt->execute();
        $stmt->close();

        log_history($conn, $vendorcode, $name, 'Company updated');

        $_SESSION['company_flash'] = ['type' => 'success', 'msg' => 'Company updated successfully.'];
    } else {
        // Insert — date_created is stamped at the moment the company is added
        $stmt = $conn->prepare(
            "INSERT INTO tbl_company (name, nickname, vendorcode, refcode, address, retailer, active, date_created)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("sssisss", $name, $nickname, $vendorcode, $refcode, $address, $retailer, $active);
        $stmt->execute();
        $stmt->close();

        log_history($conn, $vendorcode, $name, 'Company added');

        $_SESSION['company_flash'] = ['type' => 'success', 'msg' => 'Company added successfully.'];
    }

} elseif ($_POST['form_action'] === 'delete') {
    $company_id = (int) ($_POST['company_id'] ?? 0);
    if ($company_id > 0) {
        // Grab name/vendorcode before the row is gone, so we still have something to log
        $lookup = $conn->prepare("SELECT name, vendorcode FROM tbl_company WHERE id = ?");
        $lookup->bind_param("i", $company_id);
        $lookup->execute();
        $existing = $lookup->get_result()->fetch_assoc();
        $lookup->close();

        $stmt = $conn->prepare("DELETE FROM tbl_company WHERE id=?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $stmt->close();

        if ($existing) {
            log_history($conn, $existing['vendorcode'], $existing['name'], 'Company deleted');
        }

        $_SESSION['company_flash'] = ['type' => 'success', 'msg' => 'Company deleted.'];
    }
}

$conn->close();
header("Location: company.php");
exit;