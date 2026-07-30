<?php
session_start();
include_once("dbconnect.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['form_action'])) {
    header("Location: product-list.php");
    exit;
}

if ($_POST['form_action'] === 'save') {

    $product_id = (int) ($_POST['product_id'] ?? 0);
    $mdccode = trim($_POST['mdccode'] ?? '');
    $itemcode = trim($_POST['itemcode'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $uom = trim($_POST['uom'] ?? '');
    $vendor = trim($_POST['company'] ?? '');
    $retailer = trim($_POST['retailer'] ?? '');
    $active = !empty($_POST['active']) ? 1 : 0;

    if ($mdccode === '' || $itemcode === '' || $description === '' || $category === '' || $vendor === '' || $retailer === '') {
        $_SESSION['product_flash'] = ['type' => 'danger', 'msg' => 'Please fill in all required fields.'];
    } elseif ($product_id > 0) {
        // Update
        $stmt = $conn->prepare(
            "UPDATE tbl_product SET mdccode=?, itemcode=?, description=?, category=?, uom=?, vendor=?, retailer=?, active=? WHERE id=?"
        );
        $stmt->bind_param("sssssssii", $mdccode, $itemcode, $description, $category, $uom, $vendor, $retailer, $active, $product_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['product_flash'] = ['type' => 'success', 'msg' => 'Product updated successfully.'];
    } else {
        // Insert
        $stmt = $conn->prepare(
            "INSERT INTO tbl_product (mdccode, itemcode, description, category, uom, vendor, retailer, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssssi", $mdccode, $itemcode, $description, $category, $uom, $vendor, $retailer, $active);
        $stmt->execute();
        $stmt->close();

        $_SESSION['product_flash'] = ['type' => 'success', 'msg' => 'Product added successfully.'];
    }

} elseif ($_POST['form_action'] === 'delete') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    if ($product_id > 0) {
        $stmt = $conn->prepare("DELETE FROM tbl_product WHERE id=?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['product_flash'] = ['type' => 'success', 'msg' => 'Product deleted.'];
    }
}

$conn->close();
header("Location: product-list.php");
exit;