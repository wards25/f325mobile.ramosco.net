<?php
session_start();
include_once("dbconnect.php");
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'Invalid id']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tbl_users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Never send the password hash back to the client.
unset($user['password']);

$scopes = [];
$stmt = $conn->prepare("
    SELECT retailer, company_id, company_name, location_id, location_name
    FROM tbl_permission
    WHERE user_id = ?
");
$user_id_str = (string)$id;
$stmt->bind_param("s", $user_id_str);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $scopes[] = $row;
}
$stmt->close();

echo json_encode(['user' => $user, 'scopes' => $scopes]);
$conn->close();