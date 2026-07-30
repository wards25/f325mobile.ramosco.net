<?php
session_start();
include('dbconnect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated.']);
    exit;
}

$retailer = trim($_POST['retailer'] ?? '');
$company_id = (int) ($_POST['company_id'] ?? 0);
const DEFAULT_TEMPLATE = 'print-notepad-details.php';

if ($retailer === '' || $company_id <= 0) {
    echo json_encode(['template' => DEFAULT_TEMPLATE]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT template_file FROM tbl_print_template WHERE retailer = ? AND company_id = ? LIMIT 1"
);
$stmt->bind_param("si", $retailer, $company_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode([
    'template' => $row ? $row['template_file'] : DEFAULT_TEMPLATE
]);