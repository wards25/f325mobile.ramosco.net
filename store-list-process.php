<?php
session_start();
include_once("dbconnect.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['form_action'])) {
    header("Location: store-list.php");
    exit;
}

if ($_POST['form_action'] === 'save') {

    $store_id = (int) ($_POST['store_id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $branchname = trim($_POST['branchname'] ?? '');
    $shipping = trim($_POST['shipping'] ?? '');
    $billing = trim($_POST['billing'] ?? '');
    $franchise = trim($_POST['franchise'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $cluster = trim($_POST['cluster'] ?? '');
    $deducttype = trim($_POST['deducttype'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $retailer = trim($_POST['retailer'] ?? '');

    if ($code === '' || $branchname === '' || $shipping === '' || $billing === '' ||
        $franchise === '' || $region === '' || $cluster === '' || $deducttype === '' || $location === '' || $retailer === '') {
        $_SESSION['store_flash'] = ['type' => 'danger', 'msg' => 'Please fill in all required fields.'];
    } elseif ($store_id > 0) {
        // Update
        $stmt = $conn->prepare(
            "UPDATE tbl_census SET code=?, branchname=?, shipping=?, billing=?, franchise=?, region=?, cluster=?, deducttype=?, location=?, retailer=? WHERE id=?"
        );
        $stmt->bind_param("ssssssssssi", $code, $branchname, $shipping, $billing, $franchise, $region, $cluster, $deducttype, $location, $retailer, $store_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['store_flash'] = ['type' => 'success', 'msg' => 'Store updated successfully.'];
    } else {
        // Insert — status defaults to active (1)
        $stmt = $conn->prepare(
            "INSERT INTO tbl_census (code, branchname, shipping, billing, franchise, region, cluster, deducttype, location, retailer, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->bind_param("ssssssssss", $code, $branchname, $shipping, $billing, $franchise, $region, $cluster, $deducttype, $location, $retailer);
        $stmt->execute();
        $stmt->close();

        $_SESSION['store_flash'] = ['type' => 'success', 'msg' => 'Store added successfully.'];
    }

} elseif ($_POST['form_action'] === 'toggle_status') {
    $store_id = (int) ($_POST['store_id'] ?? 0);
    $new_status = (int) ($_POST['new_status'] ?? 0);

    if ($store_id > 0) {
        $stmt = $conn->prepare("UPDATE tbl_census SET status = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $store_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['store_flash'] = [
            'type' => 'success',
            'msg' => $new_status == 1 ? 'Store activated.' : 'Store deactivated.'
        ];
    }
}

$conn->close();
header("Location: store-list.php");
exit;