<?php
session_start();
include('dbconnect.php');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    exit;
}

$user_id = (string) $_SESSION['id'];

$allowed_retailers = [];
$allowed_locations = [];
$allowed_companies = [];

$perm_stmt = $conn->prepare(
    "SELECT DISTINCT p.retailer, l.location, c.vendorcode
     FROM tbl_permission p
     LEFT JOIN tbl_location l ON l.id = p.location_id
     LEFT JOIN tbl_company c ON c.id = p.company_id
     WHERE p.user_id = ?"
);
$perm_stmt->bind_param("s", $user_id);
$perm_stmt->execute();
$perm_result = $perm_stmt->get_result();
while ($row = $perm_result->fetch_assoc()) {
    if (!empty($row['retailer']) && !in_array($row['retailer'], $allowed_retailers, true)) {
        $allowed_retailers[] = $row['retailer'];
    }
    if (!empty($row['location']) && !in_array($row['location'], $allowed_locations, true)) {
        $allowed_locations[] = $row['location'];
    }
    if (!empty($row['vendorcode']) && !in_array($row['vendorcode'], $allowed_companies, true)) {
        $allowed_companies[] = $row['vendorcode'];
    }
}
$perm_stmt->close();

function render_no_data() {
    echo '<tr><td class="td-no-data" colspan="7">No data result found.</td></tr>';
}

if (empty($allowed_retailers) || empty($allowed_locations) || empty($allowed_companies)) {
    render_no_data();
    $conn->close();
    exit;
}

$status = $_POST['status'] ?? 'PRINTED';
$company_choice = trim($_POST['company'] ?? '');
$retailer_choice = trim($_POST['retailer'] ?? '');

$f325numbers = array_filter(array_map('trim', explode(',', $_POST['f325number'] ?? '')));
$branches = array_filter(array_map('trim', explode(',', $_POST['branch'] ?? '')));

$sql = "SELECT f.id, f.f325number, f.brcode, f.emaildate, f.f325date, f.vendor, f.retailer, f.status,
               c.franchise, c.branchname,
               v.name AS vendor_name
        FROM tbl_f325number f
        LEFT JOIN tbl_census c ON c.code = f.brcode AND c.retailer = f.retailer
        LEFT JOIN tbl_company v ON v.vendorcode = f.vendor
        WHERE f.status = ?
          AND f.emaildate BETWEEN '2025-01-01' AND NOW()";

$types = "s";
$params = [$status];

if ($retailer_choice !== '' && in_array($retailer_choice, $allowed_retailers, true)) {
    $sql .= " AND f.retailer = ?";
    $types .= "s";
    $params[] = $retailer_choice;
} else {
    $ret_placeholders = implode(',', array_fill(0, count($allowed_retailers), '?'));
    $sql .= " AND f.retailer IN ($ret_placeholders)";
    foreach ($allowed_retailers as $r) {
        $types .= "s";
        $params[] = $r;
    }
}

$loc_placeholders = implode(',', array_fill(0, count($allowed_locations), '?'));
$sql .= " AND f.location IN ($loc_placeholders)";
foreach ($allowed_locations as $loc) {
    $types .= "s";
    $params[] = $loc;
}

if ($company_choice !== '') {
    $sql .= " AND f.vendor = ?";
    $types .= "s";
    $params[] = $company_choice;
} else {
    $comp_placeholders = implode(',', array_fill(0, count($allowed_companies), '?'));
    $sql .= " AND f.vendor IN ($comp_placeholders)";
    foreach ($allowed_companies as $comp) {
        $types .= "s";
        $params[] = $comp;
    }
}

if (!empty($f325numbers)) {
    $sql .= " AND (" . implode(' OR ', array_fill(0, count($f325numbers), 'f.f325number LIKE ?')) . ")";
    foreach ($f325numbers as $val) {
        $types .= "s";
        $params[] = "%$val%";
    }
}

if (!empty($branches)) {
    $branch_clauses = [];
    foreach ($branches as $val) {
        $branch_clauses[] = "(c.branchname LIKE ? OR f.brcode LIKE ?)";
        $types .= "ss";
        $params[] = "%$val%";
        $params[] = "%$val%";
    }
    $sql .= " AND (" . implode(' OR ', $branch_clauses) . ")";
}

$sql .= " ORDER BY f.vendor, f.brcode ASC LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $branch_label = $row['branchname']
            ? htmlspecialchars(trim($row['franchise'] . ' ' . $row['brcode'] . ' - ' . $row['branchname']))
            : htmlspecialchars($row['brcode']) . ' - ';
        $vendor_label = $row['vendor_name'] ? htmlspecialchars($row['vendor_name']) : 'For Fill-Up';
        $status_upper = strtoupper($row['status']);
        $status_class = $status_upper === 'SCHEDULED' ? 'badge-active' : 'badge-printed';
        ?>
        <tr class="tbl-list-order-tr" role="button" f325id="<?php echo (int) $row['id']; ?>">
            <td class="tbl-list-order-td1"><?php echo htmlspecialchars($row['f325number']); ?></td>
            <td class="tbl-list-order-td2"><?php echo $branch_label; ?></td>
            <td class="tbl-list-order-td3"><?php echo date("m-d-Y", strtotime($row['emaildate'])); ?></td>
            <td class="tbl-list-order-td4"><?php echo date("m-d-Y", strtotime($row['f325date'])); ?></td>
            <td class="tbl-list-order-td5"><?php echo $vendor_label; ?></td>
            <td class="tbl-list-order-td6"><?php echo htmlspecialchars($row['retailer']); ?></td>
            <td class="tbl-list-order-td7">
                <span class="badge-status <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
            </td>
        </tr>
        <?php
    }
} else {
    render_no_data();
}

$stmt->close();
$conn->close();
?>