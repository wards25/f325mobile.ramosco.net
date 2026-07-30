<?php
session_start();
include('dbconnect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated.']);
    exit;
}

$user_id = (string) $_SESSION['id'];
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid F325 id.']);
    exit;
}

// Same permission scope used by load-notepad-list.php — a user should only
// be able to fetch details for a document within their allowed
// locations/companies, not any id they happen to guess.
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
    echo json_encode(['error' => 'No access.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tbl_f325number WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$fetch_detail = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fetch_detail) {
    echo json_encode(['error' => 'Record not found.']);
    exit;
}

if (
    !in_array($fetch_detail['location'], $allowed_locations, true) ||
    !in_array($fetch_detail['vendor'], $allowed_companies, true)
) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access to this record.']);
    exit;
}

// Branch name lookup — also match retailer, since branch code alone isn't
// unique across franchises (same issue fixed in load-notepad-list.php).
$brcode = $fetch_detail['brcode'];
$retailer = $fetch_detail['retailer'];
$branchname = $brcode;
$br_stmt = $conn->prepare("SELECT franchise, branchname FROM tbl_census WHERE code = ? AND retailer = ? LIMIT 1");
$br_stmt->bind_param("ss", $brcode, $retailer);
$br_stmt->execute();
$fetch_brname = $br_stmt->get_result()->fetch_assoc();
$br_stmt->close();
if ($fetch_brname) {
    $branchname = trim($fetch_brname['franchise'] . ' ' . $brcode . ' - ' . $fetch_brname['branchname']);
}

// Vendor name + id lookup — company_id is needed by get-print-template.php
// to look up which print layout this retailer+company combination uses.
$vcode = $fetch_detail['vendor'];
$vendorname = '';
$company_id = null;
$vn_stmt = $conn->prepare("SELECT id, name FROM tbl_company WHERE vendorcode = ? LIMIT 1");
$vn_stmt->bind_param("s", $vcode);
$vn_stmt->execute();
$fetch_vname = $vn_stmt->get_result()->fetch_assoc();
$vn_stmt->close();
if ($fetch_vname) {
    $vendorname = $fetch_vname['name'];
    $company_id = (int) $fetch_vname['id'];
}

echo json_encode([
    'f325number'      => $fetch_detail['f325number'],
    'f325date'        => $fetch_detail['f325date'],
    'tmnumber'        => $fetch_detail['tmnumber'],
    'datesched'       => $fetch_detail['datesched'],
    'driver'          => $fetch_detail['drivername'],
    'platenumber'     => $fetch_detail['platenumber'],
    'logisticremarks' => $fetch_detail['logisticremarks'],
    'emaildate'       => $fetch_detail['emaildate'],
    'branchname'      => $branchname,
    'vendorname'      => $vendorname,
    'vcode'           => $vcode,
    'company_id'      => $company_id,
    'retailer'        => $retailer,
    'preparedby'      => $fetch_detail['preparedby'],
    'issuedby'        => $fetch_detail['issuedby'],
    'remarks'         => $fetch_detail['printremarks'],
    'status'          => $fetch_detail['status'],
]);

$conn->close();