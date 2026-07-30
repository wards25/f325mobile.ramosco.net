<?php
session_start();
include('dbconnect.php');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    exit;
}

$user_id = (string) $_SESSION['id'];
$f325number = trim($_POST['f325number'] ?? '');
$vcode = trim($_POST['vcode'] ?? '');

if ($f325number === '' || $vcode === '') {
    exit;
}

// Same scope check as load-notepad-detail.php: confirm this F325 number
// actually belongs to a location/company this user is permitted to see,
// rather than trusting the f325number/vcode posted from the client.
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

if (empty($allowed_locations) || empty($allowed_companies) || !in_array($vcode, $allowed_companies, true)) {
    exit;
}

$loc_placeholders = implode(',', array_fill(0, count($allowed_locations), '?'));
$check_sql = "SELECT id FROM tbl_f325number WHERE f325number = ? AND vendor = ? AND location IN ($loc_placeholders) LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_types = "ss" . str_repeat('s', count($allowed_locations));
$check_params = array_merge([$f325number, $vcode], $allowed_locations);
$check_stmt->bind_param($check_types, ...$check_params);
$check_stmt->execute();
$owns_record = $check_stmt->get_result()->fetch_assoc();
$check_stmt->close();

if (!$owns_record) {
    exit; // not this user's record — return nothing
}

$order_stmt = $conn->prepare("SELECT * FROM tbl_raw WHERE f325number = ?");
$order_stmt->bind_param("s", $f325number);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

$product_stmt = $conn->prepare("SELECT itemcode, description, uom FROM tbl_product WHERE mdccode = ? AND vendor = ? AND active = '1' LIMIT 1");

while ($fetch_order = $order_result->fetch_assoc()) {
    $mdccode = $fetch_order['mdccode'];

    $product_stmt->bind_param("ss", $mdccode, $vcode);
    $product_stmt->execute();
    $fetch_product = $product_stmt->get_result()->fetch_assoc();

    if ($fetch_product) {
        $itemcode = $fetch_product['itemcode'];
        $description = $fetch_product['description'];
        $uom = $fetch_product['uom'];
    } else {
        $itemcode = "";
        $description = "Fill up";
        $uom = "";
    }

    $quantity = (float) $fetch_order['quantity'];
    $unitcost = (float) $fetch_order['unitcost'];
    $subtotal = $quantity * $unitcost;
    $uom_display = $uom !== '' ? ($quantity >= 2 ? $uom . 'S' : $uom) : '';
    ?>
    <tr class="tbl-order-detail-tr">
        <td class="tbl-order-detail-td1"><?php echo htmlspecialchars($mdccode); ?></td>
        <td class="tbl-order-detail-td2"><?php echo htmlspecialchars($itemcode); ?></td>
        <td class="tbl-order-detail-td3"><?php echo htmlspecialchars($description); ?></td>
        <td class="tbl-order-detail-td9"><?php echo htmlspecialchars($fetch_order['expiration']); ?></td>
        <td class="tbl-order-detail-td4"><?php echo htmlspecialchars($fetch_order['reasoncode']); ?></td>
        <td class="tbl-order-detail-td5"><?php echo htmlspecialchars((string) $fetch_order['quantity']); ?></td>
        <td class="tbl-order-detail-td6"><?php echo htmlspecialchars($uom_display); ?></td>
        <td class="tbl-order-detail-td7"><?php echo number_format($unitcost, 2); ?></td>
        <td class="tbl-order-detail-td8 subtotal-lines" subtotal="<?php echo $subtotal; ?>">
            <?php echo number_format($subtotal, 2); ?>
        </td>
    </tr>
    <?php
}

$product_stmt->close();
$order_stmt->close();
$conn->close();
?>