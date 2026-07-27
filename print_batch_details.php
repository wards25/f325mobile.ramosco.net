<?php
session_start();
include_once("dbconnect.php");

if (!isset($_GET['batchnumber'])) {
    die("Batch number not specified.");
}

$batchnumber = mysqli_real_escape_string($conn, $_GET['batchnumber']);
$type = $_GET['type'] ?? 'pullout';
$dateProcessed = $_GET['date_processed'] ?? date('Y-m-d');
$location = $_GET['hub'] ?? '';

if ($type === 'total') {
    $qtyField = 'r.quantity';
    $qtyLabel = 'Total Qty';
} else {
    $qtyField = 'r.forpullout';
    $qtyLabel = 'Pull-Out Qty';
}

$preparedBy = $_SESSION['fname'] ?? '';

$vendorcode = mysqli_real_escape_string($conn, $_GET['vendor'] ?? '');

$headerQuery = "
    SELECT 
        p.*,
        c.name AS company_name
    FROM dbpullout p
    INNER JOIN dbcompany c ON $vendorcode = c.vendorcode
    WHERE p.reference = '$batchnumber'
    
    LIMIT 1
";
if (!$headerResult = mysqli_query($conn, $headerQuery)) {
    die("SQL Error: " . mysqli_error($conn));
}

$headerResult = mysqli_query($conn, $headerQuery);
$header = mysqli_fetch_assoc($headerResult);

$principal = $header['principal'] ?? '';
$company = $header['company_name'] ?? '';
$preparedBy = $header['preparedby'] ?? $_SESSION['fname'] ?? '';
$dateProcessed = $header['dateprocessed'] ?? $dateProcessed;
$location = $header['location'] ?? $location;
?>

<!DOCTYPE html>
<html>

<head>
    <title>PULL-OUT SUMMARY</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        @media print {
            body {
                margin: 0;
            }
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
        }

        .header-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .header-inner td {
            padding: 2px 4px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px;
        }

        .data-table th {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            width: 250px;
            float: right;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .totals td {
            padding: 4px;
        }

        .signature {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
        }
    </style>
</head>

<body onload="window.print()">

    <div class="title">PULL-OUT SUMMARY</div>

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <!-- LEFT SIDE -->
            <td width="60%">
                <table class="header-inner">
                    <tr>
                        <td><strong>Principal Name:</strong></td>
                        <td><?= htmlspecialchars($principal) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Company:</strong></td>
                        <td><?= htmlspecialchars($company) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Prepared By:</strong></td>
                        <td><?= htmlspecialchars($preparedBy) ?></td>
                    </tr>
                </table>
            </td>

            <!-- RIGHT SIDE -->
            <td width="40%" align="right">
                <table class="header-inner" style="float:right;">
                    <tr>
                        <td><strong>Reference #:</strong></td>
                        <td><?= htmlspecialchars($batchnumber) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date Processed:</strong></td>
                        <td><?= htmlspecialchars($dateProcessed) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Hub:</strong></td>
                        <td><?= htmlspecialchars($location) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr>

    <!-- DATA TABLE -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Branch Name</th>
                <th>F325 Number</th>
                <th>Material Code</th>
                <th>Description</th>
                <th><?= $qtyLabel ?></th>
                <th>Reason Code</th>
                <th>BBD</th>
                <th>UoM</th>
                <th>Cost Extended</th>
            </tr>
        </thead>
        <tbody>

            <?php
            $totalQty = 0;
            $subtotal = 0;

            $query = "
SELECT DISTINCT
    c.branchname,
    c.franchise,
    c.code,
    r.f325number,
    CONCAT(r.mdccode, ' - ', p.description) AS description,
    $qtyField AS qty,
    r.forpullout,
    r.reasoncode,
    r.expiration,
    r.costextended,
    p.uom,
    r.unitcost,
    s.prod_insp_memo
FROM dbraw r

LEFT JOIN (
    SELECT f325number, MAX(brcode) AS brcode
    FROM dbf325number
    GROUP BY f325number
) f ON r.f325number = f.f325number

LEFT JOIN (
    SELECT code, MAX(franchise) AS franchise, MAX(branchname) AS branchname
    FROM dbcensus
    GROUP BY code
) c ON f.brcode = c.code

LEFT JOIN (
    SELECT mdccode, MAX(description) AS description, MAX(uom) AS uom
    FROM dbproduct
    GROUP BY mdccode
) p ON r.mdccode = p.mdccode

LEFT JOIN tbl_sku_list s
ON r.mdccode = s.mdccode

WHERE r.batchnumber_forpullout = '$batchnumber';
";

            $result = mysqli_query($conn, $query);

            while ($row = mysqli_fetch_assoc($result)) {

                $cost_extended = (float) $row['unitcost'] * (float) $row['forpullout'];

                $totalQty += (float) $row['qty'];
                // $subtotal += $row['costextended'];
                $subtotal += $cost_extended;
                ?>
                <tr>
                    <td><?= "{$row['franchise']} {$row['code']} - {$row['branchname']}" ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['f325number']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['prod_insp_memo']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td class="text-right"><?= number_format($row['qty'], 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['reasoncode']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['expiration']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['uom']) ?></td>
                    <td class="text-right"><?= number_format($cost_extended, 2) ?></td>
                </tr>
            <?php } ?>

        </tbody>
    </table>

    <hr>

    <!-- TOTALS -->
    <table class="totals">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td class="text-right"><?= number_format($subtotal, 2) ?></td>
        </tr>
        <tr>
            <td><strong>Total Qty:</strong></td>
            <td class="text-right"><?= number_format($totalQty, 2) ?></td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <!-- SIGNATURE -->
    <div style="margin-top: 60px;">
        <div class="signature">Name of Driver</div>
        <p>Plate #:</p>
    </div>

</body>

</html>