<?php
session_start();
include('dbconnect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

// Only users with the "Print F325" module permission may change a
// notepad's status — mirrors the nav.php check that gates access to this
// page in the first place.
if (empty($_SESSION['print']) || $_SESSION['print'] != '1') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to do this.']);
    exit;
}

$user_id = (string) $_SESSION['id'];
$id = (int) ($_POST['id'] ?? 0);
$new_status = strtoupper(trim($_POST['new_status'] ?? ''));

$allowed_statuses = ['OPEN', 'PRINTED'];
if ($id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Same permission scope as the list/detail endpoints.
$allowed_locations = [];
$allowed_companies = [];

$perm_stmt = $conn->prepare(
    "SELECT DISTINCT l.location, c.vendorcode
     FROM tbl_permission p
     LEFT JOIN tbl_location l ON l.id = p.location_id
     LEFT JOIN tbl_company c ON c.id = p.company_id
     WHERE p.user_id = ?"
);
$perm_stmt->bind_param("s", $user_id);
$perm_stmt->execute();
$perm_result = $perm_stmt->get_result();
while ($row = $perm_result->fetch_assoc()) {
    if (!empty($row['location']) && !in_array($row['location'], $allowed_locations, true)) {
        $allowed_locations[] = $row['location'];
    }
    if (!empty($row['vendorcode']) && !in_array($row['vendorcode'], $allowed_companies, true)) {
        $allowed_companies[] = $row['vendorcode'];
    }
}
$perm_stmt->close();

if (empty($allowed_locations) || empty($allowed_companies)) {
    echo json_encode(['success' => false, 'message' => 'No access.']);
    exit;
}

$fetch_stmt = $conn->prepare("SELECT f325number, location, vendor FROM tbl_f325number WHERE id = ? LIMIT 1");
$fetch_stmt->bind_param("i", $id);
$fetch_stmt->execute();
$record = $fetch_stmt->get_result()->fetch_assoc();
$fetch_stmt->close();

if (!$record) {
    echo json_encode(['success' => false, 'message' => 'Record not found.']);
    exit;
}

if (
    !in_array($record['location'], $allowed_locations, true) ||
    !in_array($record['vendor'], $allowed_companies, true)
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have access to this record.']);
    exit;
}

$update_stmt = $conn->prepare("UPDATE tbl_f325number SET status = ? WHERE id = ?");
$update_stmt->bind_param("si", $new_status, $id);
$update_stmt->execute();
$update_stmt->close();

// Look up the current user's name for the history log entry.
$user_stmt = $conn->prepare("SELECT fullname FROM tbl_users WHERE id = ? LIMIT 1");
$user_stmt->bind_param("i", $_SESSION['id']);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();
$actor_name = $current_user['fullname'] ?? 'Unknown user';

// Log this status change to tbl_history — processnumber ties it back to
// this F325 document, same convention used elsewhere in the app.
$processed_label = $new_status === 'PRINTED' ? 'Printed' : 'Re-Open';
$history_stmt = $conn->prepare(
    "INSERT INTO tbl_history (processnumber, name, processed, dateprocessed, timeprocessed)
     VALUES (?, ?, ?, CURDATE(), CURTIME())"
);
$history_stmt->bind_param("sss", $record['f325number'], $actor_name, $processed_label);
$history_stmt->execute();
$history_stmt->close();

$conn->close();

echo json_encode([
    'success' => true,
    'status' => $new_status,
    'message' => $new_status === 'PRINTED' ? 'Marked as printed.' : 'Re-opened successfully.'
]);