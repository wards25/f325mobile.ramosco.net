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

// Don't allow a user to delete their own account out from under themselves.
if ($id === intval($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
    exit;
}

$user_id_str = (string)$id;
$stmt = $conn->prepare("DELETE FROM tbl_permission WHERE user_id = ?");
$stmt->bind_param("s", $user_id_str);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("DELETE FROM tbl_users WHERE id = ?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Could not delete user: ' . $conn->error]);
    exit;
}

echo json_encode(['success' => true]);
$conn->close();