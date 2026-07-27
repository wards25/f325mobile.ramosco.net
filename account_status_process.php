<?php
session_start();
include_once("dbconnect.php");
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    exit;
}

$stmt = $conn->prepare("UPDATE tbl_users SET status = 1 - status WHERE id = ?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Could not update status: ' . $conn->error]);
    exit;
}

$stmt = $conn->prepare("SELECT status FROM tbl_users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode(['success' => true, 'status' => $row['status'] ?? null]);
$conn->close();