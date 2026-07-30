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
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

// Whitelist of module columns — must match tbl_users schema and the
// $module_groups array in account.php. Column names are never taken
// from client input directly, only these fixed keys are used.
$module_fields = [
    'census', 'productlist', 'storelist', 'import_notepad', 'import_rtv' ,'import_pu_charge', 'import_nestle_sku',
    'print', 'schedule', 'clearing', 'shortlanded', 'manual', 'pullout', 'deduct',
    'report', 'borf', 'settings'
];

$id        = intval($data['id'] ?? 0);
$fullname  = trim($data['fullname'] ?? '');
$username  = trim($data['username'] ?? '');
$password  = (string)($data['password'] ?? '');
$user_type = trim($data['user_type'] ?? 'User');
$status    = intval($data['status'] ?? 0) ? 1 : 0;
$modules   = is_array($data['modules'] ?? null) ? $data['modules'] : [];
$scopes    = is_array($data['scopes'] ?? null) ? $data['scopes'] : [];

if ($fullname === '' || $username === '') {
    echo json_encode(['success' => false, 'message' => 'Full name and username are required.']);
    exit;
}
if (!$id && $password === '') {
    echo json_encode(['success' => false, 'message' => 'Password is required for new users.']);
    exit;
}

$allowed_roles = ['Admin', 'Semi Admin', 'User'];
if (!in_array($user_type, $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role.']);
    exit;
}

// Username must be unique (excluding the current user when editing).
$stmt = $conn->prepare("SELECT id FROM tbl_users WHERE username = ? AND id != ?");
$stmt->bind_param("si", $username, $id);
$stmt->execute();
$dupe = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($dupe) {
    echo json_encode(['success' => false, 'message' => 'That username is already taken.']);
    exit;
}

$module_values = [];
foreach ($module_fields as $field) {
    $module_values[] = !empty($modules[$field]) ? 1 : 0;
}

if ($id) {
    // ---- UPDATE existing user ----
    $set_parts = array_merge(
        ["fullname = ?", "username = ?", "user_type = ?", "status = ?"],
        array_map(fn($f) => "`$f` = ?", $module_fields)
    );
    $types  = "sssi" . str_repeat("i", count($module_fields));
    $params = array_merge([$fullname, $username, $user_type, $status], $module_values);

    if ($password !== '') {
        $set_parts[] = "password = ?";
        $types .= "s";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $types .= "i";
    $params[] = $id;

    $sql = "UPDATE tbl_users SET " . implode(', ', $set_parts) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Could not update user: ' . $conn->error]);
        exit;
    }
} else {
    // ---- INSERT new user ----
    $columns = array_merge(['fullname', 'username', 'password', 'user_type', 'status'], $module_fields);
    $col_list = implode(', ', array_map(fn($c) => "`$c`", $columns));
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    // fullname(s), username(s), hashed(s), user_type(s), status(i) = "ssssi"
    $types  = "ssssi" . str_repeat("i", count($module_fields));
    $params = array_merge([$fullname, $username, $hashed, $user_type, $status], $module_values);

    $sql = "INSERT INTO tbl_users ($col_list) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $ok = $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Could not create user: ' . $conn->error]);
        exit;
    }
}

// Replace this user's retailer/company/location scopes with the submitted set.
// tbl_permission.user_id is a varchar column, so it's bound as a string here.
$user_id_str = (string)$id;

$stmt = $conn->prepare("DELETE FROM tbl_permission WHERE user_id = ?");
$stmt->bind_param("s", $user_id_str);
$stmt->execute();
$stmt->close();

if (count($scopes)) {
    $stmt = $conn->prepare("
        INSERT INTO tbl_permission (user_id, retailer, company_id, company_name, location_id, location_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($scopes as $s) {
        $retailer      = trim($s['retailer'] ?? '');
        $company_id    = intval($s['company_id'] ?? 0);
        $company_name  = trim($s['company_name'] ?? '');
        $location_id   = intval($s['location_id'] ?? 0);
        $location_name = trim($s['location_name'] ?? '');
        if ($retailer === '') continue;

        $stmt->bind_param("ssisis", $user_id_str, $retailer, $company_id, $company_name, $location_id, $location_name);
        $stmt->execute();
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'id' => $id]);
$conn->close();