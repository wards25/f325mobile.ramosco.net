<?php
session_start();
include_once("dbconnect.php");

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// ---- Permission scope (same pattern as dashboard.php) ----
// Restricts which retailers/companies/locations this user is allowed to see,
// regardless of what filter values the client sends.
$user_id = (string) intval($_SESSION['id']);

function build_in_placeholders($count)
{
    return implode(',', array_fill(0, $count, '?'));
}

$scope_cache_key = 'scope_cache_' . $user_id;

if (!isset($_SESSION[$scope_cache_key])) {
    $user_retailers = [];
    $stmt = $conn->prepare("SELECT DISTINCT retailer FROM tbl_permission WHERE user_id = ? AND retailer != '' ORDER BY retailer ASC");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $retailer_result = $stmt->get_result();
    while ($row = $retailer_result->fetch_assoc()) {
        $user_retailers[] = $row['retailer'];
    }
    $stmt->close();

    $_SESSION[$scope_cache_key] = $user_retailers;
} else {
    $user_retailers = $_SESSION[$scope_cache_key];
}

// Only trust a retailer value the user actually has a scope for.
$requested_retailer = trim($_GET['retailer'] ?? 'all');
$selected_retailer = ($requested_retailer !== 'all' && in_array($requested_retailer, $user_retailers, true))
    ? $requested_retailer
    : '';

$retailer_scope = $selected_retailer !== '' ? [$selected_retailer] : $user_retailers;

$permitted_companies = [];
$permitted_locations = [];
if (!empty($retailer_scope)) {
    $placeholders = build_in_placeholders(count($retailer_scope));
    $stmt = $conn->prepare("SELECT DISTINCT company_name, location_name FROM tbl_permission WHERE user_id = ? AND retailer IN ($placeholders)");
    $stmt->bind_param(str_repeat('s', count($retailer_scope) + 1), $user_id, ...$retailer_scope);
    $stmt->execute();
    $perm_result = $stmt->get_result();
    $seen_companies = [];
    $seen_locations = [];
    while ($row = $perm_result->fetch_assoc()) {
        if ($row['company_name'] !== '' && $row['company_name'] !== null && !isset($seen_companies[$row['company_name']])) {
            $seen_companies[$row['company_name']] = true;
            $permitted_companies[] = $row['company_name'];
        }
        if ($row['location_name'] !== '' && $row['location_name'] !== null && !isset($seen_locations[$row['location_name']])) {
            $seen_locations[$row['location_name']] = true;
            $permitted_locations[] = $row['location_name'];
        }
    }
    $stmt->close();
}

// Only trust a company/location value the user actually has a scope for.
$requested_company = trim($_GET['company'] ?? 'all');
$selected_company = ($requested_company !== 'all' && in_array($requested_company, $permitted_companies, true))
    ? $requested_company
    : '';

$requested_location_scope = trim($_GET['location'] ?? 'all');
$selected_location_scope = ($requested_location_scope !== 'all' && in_array($requested_location_scope, $permitted_locations, true))
    ? $requested_location_scope
    : '';

$company_scope = $selected_company !== '' ? [$selected_company] : $permitted_companies;
$location_scope = $selected_location_scope !== '' ? [$selected_location_scope] : $permitted_locations;

// If the user has no retailer access at all, there is nothing to show.
if (empty($retailer_scope)) {
    echo json_encode([
        'draw' => isset($_GET['draw']) ? (int) $_GET['draw'] : 0,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit;
}

// ---- DataTables server-side request params ----
$draw = isset($_GET['draw']) ? (int) $_GET['draw'] : 0;
$start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
$length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
if ($length <= 0 || $length > 100) {
    $length = 10; // guard against absurd/negative page sizes
}

// ---- Our custom filter params ----
$f325raw = trim($_GET['f325number'] ?? '');
$branchraw = trim($_GET['branch'] ?? '');
$status = trim($_GET['status'] ?? 'all');

/**
 * Split a comma-separated field into a clean array of non-empty, trimmed values.
 * e.g. "123, 245,  678" -> ["123", "245", "678"]
 */
function split_multi_value(string $raw): array
{
    if ($raw === '') {
        return [];
    }
    $parts = explode(',', $raw);
    $parts = array_map('trim', $parts);
    $parts = array_filter($parts, fn($v) => $v !== '');
    return array_values(array_unique($parts));
}

// F325 numbers: keep only digit-only entries (f.f325number is varchar but stores digits).
$f325list = array_filter(split_multi_value($f325raw), 'ctype_digit');
$f325list = array_values($f325list);

// Branch: split into two buckets —
//   - numeric entries match f.brcode directly (the branch code stored on the F325 row)
//   - non-numeric entries match the branch NAME, which lives in the census/branch
//     table (tbl_census.branchname), not on tbl_f325number itself.
$branchraw_list = split_multi_value($branchraw);
$branchCodeList = array_values(array_filter($branchraw_list, 'ctype_digit'));
$branchNameList = array_values(array_filter($branchraw_list, fn($v) => !ctype_digit($v)));

// Need at least one usable search value.
if (empty($f325list) && empty($branchCodeList) && empty($branchNameList)) {
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => []
    ]);
    exit;
}

/**
 * Build a "col IN (?, ?, ?)" fragment plus matching bind types/params for a list of values.
 */
function build_in_clause(string $column, array $values, string $bindType = 's'): array
{
    $placeholders = implode(',', array_fill(0, count($values), '?'));
    return [
        'sql' => "$column IN ($placeholders)",
        'types' => str_repeat($bindType, count($values)),
        'params' => $values,
    ];
}

// ---- NOTE ON SCHEMA ----
// tbl_f325number has a "brcode" (int) column but NOT a branch name column.
// The branch name lives in a separate census/branch table, joined here as
// "tbl_census" via tbl_census.code = tbl_f325number.brcode.
// >>> If your branch table is actually named something else, change
// >>> BRANCH_TABLE below (and its "code"/"branchname" column names if needed).
const BRANCH_TABLE = 'tbl_census';

$fromSql = "tbl_f325number f
    LEFT JOIN " . BRANCH_TABLE . " c ON c.code = f.brcode AND c.retailer = f.retailer
    LEFT JOIN tbl_company vc ON vc.vendorcode = f.vendor";

// ---- Build WHERE clause + bound params ----
$whereParts = [];
$types = "";
$params = [];

if (!empty($f325list)) {
    $clause = build_in_clause('f.f325number', $f325list, 's');
    $whereParts[] = $clause['sql'];
    $types .= $clause['types'];
    $params = array_merge($params, $clause['params']);
}

if (!empty($branchCodeList) || !empty($branchNameList)) {
    $branchParts = [];

    if (!empty($branchCodeList)) {
        $clause = build_in_clause('f.brcode', $branchCodeList, 'i');
        $branchParts[] = $clause['sql'];
        $types .= $clause['types'];
        $params = array_merge($params, $clause['params']);
    }

    if (!empty($branchNameList)) {
        // Contains-style match (not exact), so "Los Banos" also matches
        // "Los Banos College" — each value gets its own LIKE, OR'd together.
        $nameConditions = [];
        foreach ($branchNameList as $name) {
            $nameConditions[] = "UPPER(c.branchname) LIKE ?";
            $types .= "s";
            $params[] = '%' . strtoupper($name) . '%';
        }
        $branchParts[] = '(' . implode(' OR ', $nameConditions) . ')';
    }

    $whereParts[] = '(' . implode(' OR ', $branchParts) . ')';
}

if ($status !== 'all') {
    $whereParts[] = "f.status = ?";
    $types .= "s";
    $params[] = $status;
}

// ---- Permission scope enforcement (always applied — not optional) ----
// Retailer: user can only ever see rows within their retailer scope.
$retailerClause = build_in_clause('f.retailer', $retailer_scope, 's');
$whereParts[] = $retailerClause['sql'];
$types .= $retailerClause['types'];
$params = array_merge($params, $retailerClause['params']);

// Company: resolved via the joined tbl_company name (f.vendor is a vendor code).
if (!empty($company_scope)) {
    $upperCompanies = array_map('strtoupper', $company_scope);
    $companyClause = build_in_clause('UPPER(TRIM(vc.name))', $upperCompanies, 's');
    $whereParts[] = $companyClause['sql'];
    $types .= $companyClause['types'];
    $params = array_merge($params, $companyClause['params']);
} else {
    // User has retailer access but no company scope defined — show nothing,
    // same "fail closed" behavior as dashboard.php.
    $whereParts[] = "1=0";
}

// Location: same idea, scoped to what tbl_permission grants this user.
if (!empty($location_scope)) {
    $upperLocations = array_map('strtoupper', $location_scope);
    $locationClause = build_in_clause('UPPER(TRIM(f.location))', $upperLocations, 's');
    $whereParts[] = $locationClause['sql'];
    $types .= $locationClause['types'];
    $params = array_merge($params, $locationClause['params']);
} else {
    $whereParts[] = "1=0";
}

// ---- DataTables built-in quick-search box (global search across visible columns) ----
$globalSearch = trim($_GET['search']['value'] ?? '');
if ($globalSearch !== '') {
    $like = '%' . $globalSearch . '%';
    $whereParts[] = "(f.f325number LIKE ? OR f.vendor LIKE ? OR f.status LIKE ?
                      OR c.branchname LIKE ? OR CAST(f.brcode AS CHAR) LIKE ? OR CAST(f.emaildate AS CHAR) LIKE ?)";
    $types .= "ssssss";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
}

$where = implode(' AND ', $whereParts);

// ---- Ordering (only column 0 = emaildate is orderable client-side) ----
$order_col = 'f.emaildate';
$order_dir = 'DESC';
if (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') {
    $order_dir = 'ASC';
}

// ---- Count matching rows (for DataTables paging info) ----
$count_sql = "SELECT COUNT(*) AS c FROM $fromSql WHERE $where";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_filtered = (int) $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// ---- Fetch only the current page of rows ----
$data_sql = "SELECT f.emaildate, f.f325number, f.status, f.vendor, f.brcode, c.branchname
             FROM $fromSql
             WHERE $where ORDER BY $order_col $order_dir LIMIT ?, ?";
$stmt = $conn->prepare($data_sql);
$stmt->bind_param($types . "ii", ...array_merge($params, [$start, $length]));
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = [
        'emaildate' => $row['emaildate'],
        'f325number' => $row['f325number'],
        'status' => $row['status'],
        'vendor' => $row['vendor'],
        'brcode' => $row['brcode'],
        'branchname' => $row['branchname'],
    ];
}
$stmt->close();
$conn->close();

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total_filtered,
    'recordsFiltered' => $total_filtered,
    'data' => $rows
]);
exit;