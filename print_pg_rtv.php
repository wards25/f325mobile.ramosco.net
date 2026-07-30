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
// NOTE: c.retailer = f.retailer is required here — branch codes are not
// unique across retailers, so without this the join can match the wrong
// branch (same bug fixed earlier in search_process.php / load-notepad-list.php).
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

// ---- Line items, joined to product for description ----
$stmt = $conn->prepare("
    SELECT r.mdccode, r.quantity, r.unitcost, r.costextended, r.reasoncode,
           p.description
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
    $username =  $_SESSION['fname'] ?? 'unknown'; // adjust to whatever your session actually stores
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
    $processed = 'PRINT';
    $u3->bind_param("sssss", $f325number, $username, $processed, $dateprocessed, $timeprocessed);
    $u3->execute();
    $u3->close();

$conn->close();

// =====================================================================
// PLACEHOLDER DATA — see notes below the code for what each needs.
// Every field here is either a real column read with a fallback, or a
// hardcoded stand-in clearly marked as such. None of these will crash —
// they'll just print blank/generic until the schema catches up.
// =====================================================================

// UPC — tbl_product has no upc column yet. Needs: ALTER TABLE tbl_product ADD upc VARCHAR(20);
$upc = ''; // TODO: pull from tbl_product.upc once it exists

// Puregold's own vendor code + GLN for this company — NOT tbl_company.vendorcode
// (that's your internal code). Needs a retailer-specific mapping table, e.g.:
//   CREATE TABLE tbl_retailer_vendor_code (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     retailer VARCHAR(75), company_id INT, external_vendorcode VARCHAR(30), gln VARCHAR(20)
//   );
$external_vendorcode = ''; // TODO: from tbl_retailer_vendor_code
$gln = ''; // TODO: from tbl_retailer_vendor_code

// Company full address — tbl_company has no address column yet.
// Needs: ALTER TABLE tbl_company ADD address VARCHAR(255);
$company_address = ''; // TODO: pull from tbl_company.address once it exists

// Branch address — using tbl_census.shipping per your own suggestion.
// FLAGGED: not yet confirmed this column actually holds a full mailing
// address in the "CITY PROVINCE REGION COUNTRY" format the sample shows.
$branch_address = trim($h['shipping'] ?? '');

// Reason for Return / Reference No. — no home in the schema yet.
// Needs either new tbl_f325number columns, or confirmation the RTV CSV
// carries these per-batch and import_rtv_process.php should start saving them.
$reason_for_return = ''; // TODO
$reference_no = ''; // TODO

// Reason code description — reasoncode itself is saved on tbl_raw, but
// import_rtv_process.php deliberately never captures it from the CSV today,
// so it will be blank for every row until that's wired up. If/when it is,
// this expands the short code (e.g. "D") into the full description via
// whatever the real lookup table turns out to be named.
function reason_description($conn, $code)
{
    if ($code === '' || $code === null) {
        return '';
    }
    // TODO: confirm the real table name — placeholder assumes tbl_reason_code(code, description)
    $stmt = $conn->prepare("SELECT description FROM tbl_reason_code WHERE code = ? LIMIT 1");
    if (!$stmt) {
        return $code; // table doesn't exist yet — just show the raw code
    }
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? ($code . ' ' . $row['description']) : $code;
}

$ship_date = !empty($h['f325date']) ? date('Y-m-d', strtotime($h['f325date'])) . 'T00:00:00' : '';
$branch_line = trim(($h['brcode'] ?? '') . '_PUREGOLD PRICE CLUB-' . strtoupper($h['branchname'] ?? ''));

$total_qty = 0;
$total_unitcost = 0;
$total_extended = 0;

header("Content-Type: text/html; charset=UTF-8");
?>
<html xmlns:fo="http://www.w3.org/1999/XSL/Format">

<head>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Puregold RTV (Returns Announcement)</title>
</head>

<body>
    <table style="line-height:85%">
        <table border="0" style="line-height:250%">
            <tr bgcolor="#F0E68C">
                <td width="950" align="center">
                    <font face="castellar" size="5" color="#2E8B57"><b>PUREGOLD PRICE CLUB
                            INC.</b></font>
                </td>
            </tr>
        </table>
        <table style="line-height:85%">
            <tr>
                <td width="100"></td>
                <td width="250"></td>
                <td width="200" align="center">
                    <font face="Tahoma" size="2"><b>
                            <?php echo htmlspecialchars($branch_line); ?><br>
                            <?php echo htmlspecialchars($branch_address); ?>
                        </b></font>
                </td>
                <td width="300"></td>
            </tr>
            <tr>
                <td width="100"></td>
                <td width="250"></td>
                <td width="200" align="center">
                    <font face="Tahoma" size="2"><b>*** RTV Shipping Manifest
                            ***</b></font>
                </td>
                <td width="300"></td>
            </tr>
            <tr>
                <td width="100"></td>
                <td width="250"></td>
                <td width="200" align="center">
                    <font face="Tahoma" size="2"><b>*** ***</b></font>
                </td>
                <td width="300"></td>
            </tr>
        </table>
        <hr width="950" align="left">
        <br>
        <table>
            <tr style="line-height:100%">
                <th rowspan="3" width="75" valign="top" align="left">
                    <font face="Tahoma" size="2">Vendor:</font>
                </th>
                <th rowspan="3" width="150" valign="top" align="center">
                    <font face="Tahoma" size="2">
                        <?php echo htmlspecialchars($external_vendorcode ?: $h['internal_vendorcode']); ?><br>
                        <span style="font-weight: normal;">(GLN: <?php echo htmlspecialchars($gln); ?>)</span>
                    </font>
                </th>
                <th rowspan="3" width="250" valign="top" align="left">
                    <font face="Tahoma" size="2">
                        CARBON DISTRIBUTION COMPANY INC.<br>
                        <span style="font-weight: normal;">NO. 79 QUEENSLAND ST. VISTA REAL CLASSICA BATASAN HILLS 1126
                            QUEZON CITY NCR SECOND DISTRICT QC 1126 PHILIPPINES</span>
                    </font>
                </th>
                <td width="150"></td>
                <td width="100">
                    <font face="Tahoma" size="2"><b>RTV Number:</b></font>
                </td>
                <td width="175" align="left">
                    <font face="Tahoma" size="2">
                        <?php echo htmlspecialchars($h['f325number']); ?>
                    </font>
                </td>
            </tr>
            <tr style="line-height:100%">
                <td width="150"></td>
                <td width="100">
                    <font face="Tahoma" size="2"><b>Ship Date:</b></font>
                </td>
                <td width="175" align="left">
                    <font face="Tahoma" size="2">
                        <?php echo htmlspecialchars($ship_date); ?>
                    </font>
                </td>
            </tr>
        </table>
        <hr width="950" align="left">
        <table>
            <tr>
                <td width="75" align="center">
                    <font face="Tahoma" size="2"><b>SKU</b></font>
                </td>
                <td width="125" align="center">
                    <font face="Tahoma" size="2"><b>UPC</b></font>
                </td>
                <td width="250" align="left">
                    <font face="Tahoma" size="2"><b>Description</b></font>
                </td>
                <td width="75" align="right">
                    <font face="Tahoma" size="2"><b>Quantity</b></font>
                </td>
                <td width="125" align="right">
                    <font face="Tahoma" size="2"><b>Unit Cost</b></font>
                </td>
                <td width="125" align="right">
                    <font face="Tahoma" size="2"><b>Extended Cost</b></font>
                </td>
                <td width="125" align="right">
                    <font face="Tahoma" size="2"><b>Reason Code/Des</b></font>
                </td>
            </tr>
        </table>
        <hr width="950" align="left">
        <table>
            <?php foreach ($items as $i):
                $qty = (float) $i['quantity'];
                $unitcost = (float) $i['unitcost'];
                $extended = (float) $i['costextended'];
                $total_qty += $qty;
                $total_unitcost += $unitcost;
                $total_extended += $extended;
                ?>
                <tr>
                    <td width="75" align="center">
                        <font face="Tahoma" size="2">
                            <?php echo htmlspecialchars($i['mdccode']); ?>
                        </font>
                    </td>
                    <td width="125" align="center">
                        <font face="Tahoma" size="2">
                            <?php echo htmlspecialchars($upc); ?>
                        </font>
                    </td>
                    <td width="250" align="justify">
                        <font face="Tahoma" size="2">
                            <?php echo htmlspecialchars($i['description'] ?? ''); ?>
                        </font>
                    </td>
                    <td width="75" align="right">
                        <font face="Tahoma" size="2">
                            <?php echo number_format($qty, 2); ?>
                        </font>
                    </td>
                    <td width="125" align="right">
                        <font face="Tahoma" size="2">
                            <?php echo number_format($unitcost, 4); ?>
                        </font>
                    </td>
                    <td width="125" align="right">
                        <font face="Tahoma" size="2">
                            <?php echo number_format($extended, 4); ?>
                        </font>
                    </td>
                    <td width="125" align="right">
                        <font face="Tahoma" size="2">
                            <?php echo htmlspecialchars(reason_description($conn, $i['reasoncode'] ?? '')); ?>
                        </font>
                    </td>
                </tr>
                <br>
            <?php endforeach; ?>
        </table>
        <hr width="950" align="left">
        <table>
            <tr>
                <td width="100" align="center">
                    <font face="Tahoma" size="2"><b></b></font>
                </td>
                <td width="150" align="center">
                    <font face="Tahoma" size="2"></font>
                </td>
                <td width="200" align="right">
                    <font face="Tahoma" size="2"><b>Total :</b></font>
                </td>
                <td width="70" align="right">
                    <font face="Tahoma" size="2"><b>
                            <?php echo number_format($total_qty, 2); ?>
                        </b></font>
                </td>
                <td width="125" align="right">
                    <font face="Tahoma" size="2"><b>
                            <?php echo number_format($total_unitcost, 4); ?>
                        </b></font>
                </td>
                <td width="125" align="right">
                    <font face="Tahoma" size="2"><b>
                            <?php echo number_format($total_extended, 4); ?>
                        </b></font>
                </td>
            </tr>
        </table>
        <hr width="950" align="left">
        <table>
            <tr>
                <td width="750">
                    <font face="Tahoma" size="2"><b>Reason for Return : </b>
                        <?php echo htmlspecialchars($reason_for_return); ?>
                    </font>
                </td>
                <td width="300">
                    <font face="Tahoma" size="2"><b>Reference No. : </b>
                        <?php echo htmlspecialchars($reference_no); ?>
                    </font>
                </td>
            </tr>
        </table>
        <table>
            <tr>
                <td>
                    <font face="Tahoma" size="2"><b>Comments:</b></font>
                </td>
            </tr>
            <tr>
                <th rowspan="3" width="25" valign="top" align="left"><span style="font-weight: normal;">
                        <font face="Tahoma" size="2"></font>
                    </span></th>
                <th rowspan="3" width="400" valign="top" align="left"><span style="font-weight: normal;">
                        <font face="Tahoma" size="2"><u></u></font>
                    </span></th>
            </tr>
        </table>
        <br>
        <table>
            <tr style="line-height:300%">
                <td width="260" align="left">
                    <font face="Tahoma" size="2"><b>Prepared by:</b></font>
                </td>
                <td width="260">
                    <font face="Tahoma" size="2"><b>Issued By:</b></font>
                </td>
                <td width="270">
                    <font face="Tahoma" size="2"><b>Approved for Release By:</b></font>
                </td>
                <td width="180">
                    <font face="Tahoma" size="2"><b>Received By:</b></font>
                </td>
            </tr>
            <tr style="line-height:100%">
                <td width="260" align="left">
                    <font face="Tahoma" size="2"><b>_________________</b></font>
                </td>
                <td width="260">
                    <font face="Tahoma" size="2"><b>___________________</b></font>
                </td>
                <td width="270">
                    <font face="Tahoma" size="2"><b>__________________________</b></font>
                </td>
                <td width="180">
                    <font face="Tahoma" size="2"><b>___________________</b></font>
                </td>
            </tr>
        </table>
    </table>
</body>

</html>
<script>
    window.print()
    window.onafterprint = function () {
        window.location.href = 'print-notepad.php';
    }
</script>