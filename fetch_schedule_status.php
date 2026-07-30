<?php
session_start();
include('dbconnect.php');
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

if (empty($_SESSION['schedule']) || $_SESSION['schedule'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to change scheduling status.']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$new_status = strtoupper(trim($_POST['new_status'] ?? ''));
$clear_transport = !empty($_POST['clear_transport']);

if ($id <= 0 || !in_array($new_status, ['PRINTED', 'SCHEDULED'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Look up the record — needed for the permission check and for the
// history log's f325number.
$stmt = $conn->prepare("SELECT f325number, retailer, location, vendor, status FROM tbl_f325number WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    echo json_encode(['success' => false, 'message' => 'Record not found.']);
    exit;
}

// Same independent-scope permission check as load-schedule-detail.php —
// retailer, location, and company are checked separately since a user's
// access to each can come from different tbl_permission rows.
$user_id = (string) $_SESSION['id'];

$stmt_r = $conn->prepare("SELECT 1 FROM tbl_permission WHERE user_id = ? AND retailer = ? LIMIT 1");
$stmt_r->bind_param("ss", $user_id, $record['retailer']);
$stmt_r->execute();
$has_retailer = (bool) $stmt_r->get_result()->fetch_assoc();
$stmt_r->close();

$stmt_l = $conn->prepare(
    "SELECT 1 FROM tbl_permission p JOIN tbl_location l ON l.id = p.location_id
     WHERE p.user_id = ? AND l.location = ? LIMIT 1"
);
$stmt_l->bind_param("ss", $user_id, $record['location']);
$stmt_l->execute();
$has_location = (bool) $stmt_l->get_result()->fetch_assoc();
$stmt_l->close();

$stmt_v = $conn->prepare(
    "SELECT 1 FROM tbl_permission p JOIN tbl_company c ON c.id = p.company_id
     WHERE p.user_id = ? AND c.vendorcode = ? LIMIT 1"
);
$stmt_v->bind_param("ss", $user_id, $record['vendor']);
$stmt_v->execute();
$has_vendor = (bool) $stmt_v->get_result()->fetch_assoc();
$stmt_v->close();

if (!($has_retailer && $has_location && $has_vendor)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have access to this record.']);
    exit;
}

$username = $_SESSION['fname'] ?? 'Unknown';
$dateprocessed = date('Y-m-d');
$timeprocessed = date('H:i:s');

if ($new_status === 'SCHEDULED') {
    $tmnumber    = trim($_POST['tmnumber'] ?? '');
    $drivername  = trim($_POST['drivername'] ?? '');
    $platenumber = trim($_POST['platenumber'] ?? '');
    $datesched   = trim($_POST['datesched'] ?? '');

    if ($tmnumber === '' || $drivername === '' || $platenumber === '' || $datesched === '') {
        echo json_encode(['success' => false, 'message' => 'TM Number, Driver, Plate Number, and Date Scheduled are all required.']);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE tbl_f325number
         SET status = 'SCHEDULED', tmnumber = ?, drivername = ?, platenumber = ?, datesched = ?
         WHERE id = ?"
    );
    $stmt->bind_param("ssssi", $tmnumber, $drivername, $platenumber, $datesched, $id);
    $ok = $stmt->execute();
    $stmt->close();

    $processed_label = 'Scheduled';

} else {
    // PRINTED — either a plain "Re-Open" (transport fields left as-is for
    // reference) or a "Re-schedule" (clear_transport=1, wipe them so the
    // next scheduling attempt starts from a blank form).
    if ($clear_transport) {
        $stmt = $conn->prepare(
            "UPDATE tbl_f325number
             SET status = 'PRINTED', tmnumber = NULL, drivername = NULL, platenumber = NULL, datesched = NULL
             WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();

        $processed_label = 'Re-scheduled';
    } else {
        $stmt = $conn->prepare("UPDATE tbl_f325number SET status = 'PRINTED' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();

        $processed_label = 'Re-opened';
    }
}

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'Could not update this record: ' . $conn->error]);
    exit;
}

// Log to the same history table load-schedule-detail.php reads from.
$hist_stmt = $conn->prepare(
    "INSERT INTO tbl_history (processnumber, name, processed, dateprocessed, timeprocessed)
     VALUES (?, ?, ?, ?, ?)"
);
$hist_stmt->bind_param("sssss", $record['f325number'], $username, $processed_label, $dateprocessed, $timeprocessed);
$hist_stmt->execute();
$hist_stmt->close();

echo json_encode(['success' => true, 'message' => $processed_label . ' successfully.', 'status' => $new_status]);
$conn->close();