<?php
session_start();
include('dbconnect.php');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    exit;
}

$f325number = trim($_POST['f325number'] ?? '');
$vcode = trim($_POST['vcode'] ?? '');

if ($f325number === '') {
    echo '<tr><td colspan="9" class="text-center text-muted">No line items.</td></tr>';
    exit;
}

// Single query with a JOIN to tbl_product, instead of firing one extra
// product lookup per row (the N+1 the original version had).
$stmt = $conn->prepare(
    "SELECT r.mdccode, r.expiration, r.reasoncode, r.quantity, r.unitcost, r.costextended,
            p.itemcode, p.description, p.uom
     FROM tbl_raw r
     LEFT JOIN tbl_product p ON p.mdccode = r.mdccode AND p.vendor = ? AND p.active = 1
     WHERE r.f325number = ?"
);
$stmt->bind_param("ss", $vcode, $f325number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<tr><td colspan="9" class="text-center text-muted">No line items.</td></tr>';
    $stmt->close();
    $conn->close();
    exit;
}

while ($row = $result->fetch_assoc()) {
    $has_product = $row['itemcode'] !== null;
    $itemcode = $has_product ? $row['itemcode'] : '';
    $description = $has_product ? $row['description'] : 'Fill up';
    $uom = $has_product ? $row['uom'] : '';
    $quantity = (int) $row['quantity'];
    $unitcost = (float) $row['unitcost'];
    $subtotal = $quantity * $unitcost;

    $uom_label = $has_product ? ($quantity >= 2 ? $uom . 'S' : $uom) : '';
    ?>
    <tr class="tbl-order-detail-tr">
        <td class="tbl-order-detail-td1"><?php echo htmlspecialchars($row['mdccode']); ?></td>
        <td class="tbl-order-detail-td2"><?php echo htmlspecialchars($itemcode); ?></td>
        <td class="tbl-order-detail-td3"><?php echo htmlspecialchars($description); ?></td>
        <td class="tbl-order-detail-td9"><?php echo htmlspecialchars($row['expiration']); ?></td>
        <td class="tbl-order-detail-td4"><?php echo htmlspecialchars($row['reasoncode']); ?></td>
        <td class="tbl-order-detail-td5"><?php echo $quantity; ?></td>
        <td class="tbl-order-detail-td6"><?php echo htmlspecialchars($uom_label); ?></td>
        <td class="tbl-order-detail-td7"><?php echo number_format($unitcost, 2); ?></td>
        <td class="tbl-order-detail-td8 subtotal-lines" subtotal="<?php echo $subtotal; ?>">
            <?php echo number_format($subtotal, 2); ?>
        </td>
    </tr>
    <?php
}

$stmt->close();
$conn->close();
?>