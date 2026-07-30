<?php
session_start();
include('dbconnect.php');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in.']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT f.f325number, f.f325date, f.emaildate, f.tmnumber, f.datesched, f.drivername,
            f.platenumber, f.brcode, f.vendor, f.retailer, f.preparedby, f.issuedby,
            f.logisticremarks, f.status, f.location,
            c.franchise, c.branchname,
            v.name AS vendorname
     FROM tbl_f325number f
     LEFT JOIN tbl_census c ON c.code = f.brcode AND c.retailer = f.retailer
     LEFT JOIN tbl_company v ON v.vendorcode = f.vendor
     WHERE f.id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$detail = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$detail) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found.']);
    exit;
}

// Permission check — retailer, location, and company are independent scopes
// (a user can be granted them via separate tbl_permission rows), so each is
// checked on its own rather than requiring one row to match all three.
// e.g. a user with rows (MERCURY DRUG, Warehouse A, Acme Corp) and
// (CARBON-MT, Warehouse B, Beta Inc) should also have access to a
// CARBON-MT record at Warehouse A — that combination doesn't exist as a
// single row, but the user is scoped into each piece individually.
$user_id = (string) $_SESSION['id'];

$stmt_r = $conn->prepare("SELECT 1 FROM tbl_permission WHERE user_id = ? AND retailer = ? LIMIT 1");
$stmt_r->bind_param("ss", $user_id, $detail['retailer']);
$stmt_r->execute();
$has_retailer = (bool) $stmt_r->get_result()->fetch_assoc();
$stmt_r->close();

$stmt_l = $conn->prepare(
    "SELECT 1 FROM tbl_permission p
     JOIN tbl_location l ON l.id = p.location_id
     WHERE p.user_id = ? AND l.location = ? LIMIT 1"
);
$stmt_l->bind_param("ss", $user_id, $detail['location']);
$stmt_l->execute();
$has_location = (bool) $stmt_l->get_result()->fetch_assoc();
$stmt_l->close();

$stmt_v = $conn->prepare(
    "SELECT 1 FROM tbl_permission p
     JOIN tbl_company c ON c.id = p.company_id
     WHERE p.user_id = ? AND c.vendorcode = ? LIMIT 1"
);
$stmt_v->bind_param("ss", $user_id, $detail['vendor']);
$stmt_v->execute();
$has_vendor = (bool) $stmt_v->get_result()->fetch_assoc();
$stmt_v->close();

$has_access = $has_retailer && $has_location && $has_vendor;

if (!$has_access) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this record.']);
    exit;
}

$branchname = $detail['branchname']
    ? trim($detail['franchise'] . ' ' . $detail['brcode'] . ' - ' . $detail['branchname'])
    : (string) $detail['brcode'];

$history = [];
$hist_stmt = $conn->prepare(
    "SELECT name, processed, dateprocessed, timeprocessed
     FROM tbl_history
     WHERE processnumber = ?
     ORDER BY dateprocessed DESC, timeprocessed DESC"
);
$hist_stmt->bind_param("s", $detail['f325number']);
$hist_stmt->execute();
$hist_result = $hist_stmt->get_result();
while ($h = $hist_result->fetch_assoc()) {
    $history[] = [
        'name' => $h['name'],
        'processed' => $h['processed'],
        'datetime' => date('m-d-Y', strtotime($h['dateprocessed'])) . ' ' . date('h:i A', strtotime($h['timeprocessed'])),
    ];
}
$hist_stmt->close();

echo json_encode([
    'id' => $id,
    'f325number' => $detail['f325number'],
    'f325date' => $detail['f325date'],
    'emaildate' => $detail['emaildate'],
    'tmnumber' => $detail['tmnumber'],
    'datesched' => $detail['datesched'],
    'driver' => $detail['drivername'],
    'platenumber' => $detail['platenumber'],
    'logisticremarks' => $detail['logisticremarks'],
    'branchname' => $branchname,
    'vendorname' => $detail['vendorname'],
    'vcode' => $detail['vendor'],
    'retailer' => $detail['retailer'],
    'preparedby' => $detail['preparedby'],
    'issuedby' => $detail['issuedby'],
    'status' => $detail['status'],
    'history' => $history,
]);

$conn->close();