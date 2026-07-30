<?php
session_start();
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$f325number = trim($_GET['f325number'] ?? '');
if ($f325number === '') {
    echo "Missing F325 / RTV Number";
    exit;
}

// ---- Header: F325 + branch (census) + company ----
// c.retailer = f.retailer required — branch codes aren't unique across
// retailers (same fix applied in search_process.php / load-notepad-list.php).
$stmt = $conn->prepare("
    SELECT f.f325number, f.brcode, f.emaildate, f.f325date, f.vendor, f.retailer,
           co.name AS company_name, co.vendorcode AS internal_vendorcode,
           ce.branchname, ce.shipping
    FROM tbl_f325number f
    LEFT JOIN tbl_company co ON co.vendorcode = f.vendor
    LEFT JOIN tbl_census ce ON ce.code = f.brcode AND ce.retailer = f.retailer
    WHERE f.f325number = ?
    LIMIT 1
");
$stmt->bind_param("s", $f325number);
$stmt->execute();
$h = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$h) {
    echo "F325 / RTV Number not found.";
    exit;
}

// ---- Line items, joined to product for description + UOM ----
$stmt = $conn->prepare("
    SELECT r.mdccode, r.quantity, r.unitcost, r.costextended, r.reasoncode,
           p.description, p.uom
    FROM tbl_raw r
    LEFT JOIN tbl_product p ON p.mdccode = r.mdccode
    WHERE r.f325number = ?
");
$stmt->bind_param("s", $f325number);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
$status = 'PRINTED';
    $remarks = trim($_POST['remarks'] ?? '');
    $username = $_SESSION['fname'] ?? 'unknown'; // confirm actual session key

    $processed = 'Print';
    $dateprocessed = date('Y-m-d');
    $timeprocessed = date('H:i:s');

    $u1 = $conn->prepare("UPDATE tbl_f325number SET status = ?, printremarks = ? WHERE f325number = ?");
    $u1->bind_param("sss", $status, $remarks, $f325number);
    $u1->execute();
    $u1->close();

    $u2 = $conn->prepare("UPDATE tbl_raw SET status = ? WHERE f325number = ?");
    $u2->bind_param("ss", $status, $f325number);
    $u2->execute();
    $u2->close();

    $u3 = $conn->prepare("INSERT INTO tbl_history (processnumber, name, processed, dateprocessed, timeprocessed) VALUES (?, ?, ?, ?, ?)");
    $u3->bind_param("sssss", $f325number, $username, $processed, $dateprocessed, $timeprocessed);
    $u3->execute();
    $u3->close();
$conn->close();

$barcode = ''; 

// table proposed for Puregold.
$external_vendorcode = ''; // TODO: from tbl_retailer_vendor_code, falls back below

$ro_date = !empty($h['f325date']) ? date('m/d/Y', strtotime($h['f325date'])) : '';
$site_line = trim(($h['brcode'] ?? '') . ' ' . strtoupper($h['branchname'] ?? ''));
$site_address = trim($h['shipping'] ?? ''); // same unconfirmed-format flag as the Puregold version

$total_amount = 0;
foreach ($items as $i) {
    $total_amount += (float) $i['costextended'];
}
$article_count = count($items);

header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Prevents iOS/Safari from auto-detecting numbers/dates as phone/calendar
         links and re-coloring them blue/orange, which broke the visual match
         to the reference SM PDF format. -->
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
    <title>SM / SuperValue RTV Announcement</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            max-width: 800px;
            margin: 18px auto;
            padding: 0 22px;
        }

        * {
            box-sizing: border-box;
        }

        /* Hard override: force ANY auto-detected "link" (phone, date,
           address, etc.) to render as plain inherited text. The
           format-detection meta tag isn't being honored by this
           webview, so this catches it regardless of which scheme
           (tel:, x-apple-data-detectors:, etc.) the auto-linker used. */
        a,
        a:link,
        a:visited {
            color: inherit !important;
            text-decoration: none !important;
            cursor: text !important;
            pointer-events: none;
        }

        table {
            border-collapse: collapse;
        }

        /* ===========================
   HEADER
=========================== */

        .header-table {
            width: 100%;
            margin-bottom: 2px;
        }

        .header-top {
            display: flex;
            align-items: center;
        }

        .logo-cell {
            position: absolute;
            top: 0;
            left: 100;
            z-index: 0;
            margin-bottom: 20px;
        }

        .logo-box {
            width: 75px;
        }

        .logo-box img {
            width: 150px;
            height: auto;
            display: block;
        }

        .title-cell {
            flex: 1 1 auto;
            text-align: center;
        }

        .main-title {
            margin-top: 20px;
            font-size: 17px;
            font-weight: bold;
            line-height: 20px;
        }

        .sub-title {
            margin-bottom: 20px;
            font-size: 15px;
            font-weight: bold;
            line-height: 18px;
        }

        .datetime-cell {
            text-align: right;
            white-space: nowrap;
            font-size: 11px;
            margin-top: 2px;
            margin-right: 50px;
        }

        .meta-table {
            width: 100%;
            margin-top: 40px;
            margin-bottom: 8px;
        }

        .meta-table td {
            vertical-align: top;
            padding: 2px 8px;
            font-size: 11px;
        }

        .meta-label {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .articles-count {
            margin: 8px 0 4px;
            font-weight: bold;
            font-size: 11px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }

        .items-table thead th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 4px;
            text-align: center;
            font-weight: bold;
        }

        .items-table tbody td {
            padding: 6px 4px;
            vertical-align: top;
        }

        .items-table .num {
            text-align: right;
        }

        /* Column Widths */

        .items-table th:nth-child(1),
        .items-table td:nth-child(1) {
            width: 12%;
        }

        .items-table th:nth-child(2),
        .items-table td:nth-child(2) {
            width: 38%;
        }

        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            width: 15%;
            text-align: center;
        }

        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
            width: 8%;
            text-align: right;
        }

        .items-table th:nth-child(5),
        .items-table td:nth-child(5) {
            width: 8%;
            text-align: center;
        }

        .items-table th:nth-child(6),
        .items-table td:nth-child(6) {
            width: 10%;
            text-align: right;
        }

        .items-table th:nth-child(7),
        .items-table td:nth-child(7) {
            width: 12%;
            text-align: right;
        }

        /* ===========================
   REMARKS
=========================== */

        .remarks-box {
            border: 1px solid #860000;
            margin-top: 28px;
            padding: 6px 8px;
            font-size: 10px;
            line-height: 14px;
        }

        .remarks-title {
            font-weight: bold;
            margin-bottom: 2px;
        }

        /* ===========================
   FOOTER
=========================== */

        .page-footer {
            text-align: right;
            font-size: 10px;
            margin-top: 35px;
        }
    </style>
</head>

<body>

    <div class="header-table">

        <div class="header-top">
            <div class="logo-cell">
                <div class="logo-box">
                    <img src="./img/SVI.png">
                </div>
            </div>

            <div class="title-cell">
                <div class="main-title">SUPERVALUE INC.</div>
                <div class="sub-title">ANNOUNCEMENT FOR RETURN TO VENDOR</div>
            </div>
        </div>

        <div class="datetime-cell">
            <b>Date:</b> <?= date("m/d/Y") ?>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <b>Time:</b> <?= date("H:i:s") ?>
        </div>

    </div>
    <table class="meta-table">
        <tr>
            <td width="40%">
                <div class="meta-label">Vendor Code & Name :</div>
                <?php echo htmlspecialchars(($external_vendorcode ?: $h['internal_vendorcode']) . ' ' . strtoupper($h['company_name'] ?? '')); ?>
            </td>
            <td width="26%">
                <div class="meta-label">RO Date :</div>
                <?php echo htmlspecialchars($ro_date); ?>
            </td>
            <td width="26%">
                <div class="meta-label">RTV NO :</div>
                <?php echo htmlspecialchars($h['f325number']); ?>
            </td>
        </tr>
        <tr>
            <td>
                <div class="meta-label">Site Code & Name :</div>
                <?php echo htmlspecialchars($site_line); ?>
            </td>
            <td>
                <div class="meta-label">Amount :</div>
                <?php echo number_format($total_amount, 2); ?>
            </td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3">
                <div class="meta-label">Site Address :</div>
                <?php echo htmlspecialchars($site_address); ?>
            </td>
        </tr>
    </table>

    <div class="articles-count">Total articles in this RTV: <?php echo (int) $article_count; ?></div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Article Code</th>
                <th>Article Description</th>
                <th>Barcode</th>
                <th class="num">Qty</th>
                <th>UOM</th>
                <th class="num">Unit Cost</th>
                <th class="num">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i):
                $qty = (float) $i['quantity'];
                $unitcost = (float) $i['unitcost'];
                $extended = (float) $i['costextended'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($i['mdccode']); ?></td>
                    <td><?php echo htmlspecialchars($i['description'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($barcode); ?></td>
                    <td class="num"><?php echo number_format($qty, 3); ?></td>
                    <td><?php echo htmlspecialchars($i['uom'] ?? ''); ?></td>
                    <td class="num"><?php echo number_format($unitcost, 2); ?></td>
                    <td class="num"><?php echo number_format($extended, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="remarks-box">
        <div class="remarks-title">IMPORTANT REMARKS:</div>
        All prices/values are net of VAT. Bad Orders unclaimed beyond prescription period (7 days for Fresh, 15 days
        for Food and 30 days for General Merchandise) shall be automatically disposed without prior notice.
        SUPERVALUE INC. shall not entertain any questions nor be held liable for any overdue return order.
    </div>

</body>

</html>
<script>
    window.print()
    window.onafterprint = function () {
        window.location.href = 'print-notepad.php';
    }


</script>