<?php
session_start();
include_once("dbconnect.php");

if (!isset($_GET['batchnumber'])) {
    die("Batch number not specified.");
}

$batchnumber = mysqli_real_escape_string($conn, $_GET['batchnumber']);
$type = $_GET['type'] ?? 'pullout'; 
$dateProcessed = $_GET['date_processed'] ?? date('Y-m-d');

if ($type === 'total') {
    $qtyField = 'r.rcvdqty';
    $qtyLabel   = 'Received Qty';
} else {
    $qtyField = 'r.forcharging';
    $qtyLabel   = 'Pull-Out Qty';
}

$preparedBy = $_SESSION['fname'] ?? '';

$headerQuery = "
    SELECT 
        r.category AS principal,
        c.name
    FROM dbraw r
    LEFT JOIN dbcompany c ON r.vendorcode = c.vendorcode
    WHERE r.batchnumber_forcharging = '$batchnumber'
    LIMIT 1
";
$headerResult = mysqli_query($conn, $headerQuery);
$header = mysqli_fetch_assoc($headerResult);

$principal = $header['principal'] ?? '';
$company   = $header['name'] ?? '';
?>

<!DOCTYPE html>
<html>

<head>
    <title>PULL-OUT SUMMARY </title>

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
        }

        .signature {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
        }
        .date {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
        }
    </style>
</head>

<body onload="window.print()">

    <div class="title">FOR CHARGING SUMMARY</div>

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td>
                <p><strong>Principal Name:</strong> <?= htmlspecialchars($principal) ?></p>
                <p><strong>Company:</strong> <?= htmlspecialchars($company) ?></p>
                <p><strong>Prepared By:</strong> <?= htmlspecialchars($preparedBy) ?></p>
            </td>
            <td class="text-right">
                <p><strong>Reference #:</strong><?= $batchnumber ?></p>
                <p><strong>Date Processed:</strong> <?= $dateProcessed ?></p>
                <p><strong>Hub:</strong></p>
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
            $cost_extended = 0;

            $query = "
    SELECT 
        c.branchname,
        c.franchise,
        c.code,
        r.f325number,
        p.description,
        $qtyField AS qty,
        p.uom,
        r.expiration,
        r.reasoncode,
        r.unitcost,
        r.forcharging,
        r.mdccode
    FROM dbraw r
    LEFT JOIN (
        SELECT f325number, brcode
        FROM dbf325number
        GROUP BY f325number
    ) f ON r.f325number = f.f325number
    LEFT JOIN (
        SELECT code, franchise, branchname
        FROM dbcensus
        GROUP BY code
    ) c ON f.brcode = c.code
    LEFT JOIN (
        SELECT mdccode, description, uom
        FROM dbproduct
        GROUP BY mdccode
    ) p ON r.mdccode = p.mdccode
    WHERE r.batchnumber_forcharging = '$batchnumber'
";

            $result = mysqli_query($conn, $query);

            while ($row = mysqli_fetch_assoc($result)) {
                $totalQty += (float)$row['qty']; 
                $cost_extended = (float) $row['unitcost'] * $row['forcharging'];
                $subtotal += (float) $cost_extended;

            ?>
                <tr>
                    <td><?= "{$row['franchise']} {$row['code']} - {$row['branchname']}" ?></td>
                    <td class="text-center"><?= $row['f325number'] ?></td>
                    <td><?= "{$row['mdccode']} - {$row['description']} "?></td>
                    <td class="text-right"><?= $row['qty'] ?></td>
                    <td class="text-center"><?= $row['reasoncode'] ?></td>
                    <td class="text-right"><?= $row['expiration'] ?></td>
                    <td class="text-center"><?= $row['uom'] ?></td>
                    <td class="text-right"><?= number_format($row['unitcost'], 2) ?></td>
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
    <div style="margin-top: 40px;">
        <div class="signature">BO Custodian Name</div>
        <p class="date">Date</p>
    </div>

</body>

</html>