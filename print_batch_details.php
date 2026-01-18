<?php
session_start();
include_once("dbconnect.php");

if (!isset($_GET['batchnumber'])) {
    die("Batch number not specified.");
}

$batchnumber = mysqli_real_escape_string($conn, $_GET['batchnumber']);
$dateProcessed = $_GET['date_processed'] ?? date('Y-m-d');

// Format for printing (same as image)
$dateProcessedFormatted = date("m-d-Y", strtotime($dateProcessed));

// Prepared by
$preparedBy = $_SESSION['fname'] ?? '';

// Reference #: YEAR + random 4 digits
$referenceNo = date('Y') . '-' . rand(1000, 9999);

/* Get Principal & Company */
$headerQuery = "
    SELECT 
        r.category AS principal,
        c.name
    FROM dbraw r
    LEFT JOIN dbcompany c ON r.vendorcode = c.vendorcode
    WHERE r.batchnumber = '$batchnumber'
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
<title>Pull-Out Summary</title>

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

.header-table {
    width: 100%;
    margin-bottom: 5px;
}

.header-table td {
    vertical-align: top;
}

.title {
    text-align: center;
    font-weight: bold;
    font-size: 14px;
    margin: 10px 0;
}

.info-left p,
.info-right p {
    margin: 2px 0;
}

.info-right {
    text-align: right;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.data-table th,
.data-table td {
    border: 1px solid #000;
    padding: 4px;
}

.data-table th {
    text-align: center;
    font-weight: bold;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.footer-table {
    width: 100%;
    margin-top: 15px;
}

.signature {
    margin-top: 40px;
    border-top: 1px solid #000;
    width: 200px;
    text-align: center;
}
.plateno{
     margin-top: 40px;
    border-top: 1px solid #000;
    width: 200px;
    text-align: center;
}
.totals {
    width: 250px;
    float: right;
    margin-top: 10px;
}

.totals td {
    padding: 3px;
}
</style>
</head>

<body onload="window.print()">
<div class="title">PULL-OUT SUMMARY</div>
<!-- HEADER -->
<table class="header-table">
<tr>
    <td class="info-left">
        <p><strong>Principal Name:</strong> <?= htmlspecialchars($principal) ?></p>
        <p><strong>Company:</strong> <?= htmlspecialchars($company) ?></p>
        <p><strong>Prepared By:</strong> <?= htmlspecialchars($preparedBy) ?></p>
    </td>
    <td class="info-right">
        <p><strong>Reference #:</strong> <?= $referenceNo ?></p>
        <p><strong>Date Processed:</strong> <?= $dateProcessed ?></p>
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
    <th>Quantity</th>
    <th>UoM</th>
    <th>Cost Extended</th>
</tr>
</thead>
<tbody>

<?php
$totalQty = 0;
$subtotal = 0;

$query = "
                            SELECT 
                                c.branchname,
                                c.franchise,
                                c.code,
                                r.f325number,
                                p.description,
                                r.quantity,
                                p.uom,
                                r.costextended
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

                            WHERE r.batchnumber = '$batchnumber';
                            ";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $totalQty += (float)$row['quantity'];
    $subtotal += (float)$row['costextended'];
?>
<tr>
    <td><?= "{$row['franchise']} {$row['code']} - {$row['branchname']}" ?></td>
    <td class="text-center"><?= $row['f325number'] ?></td>
    <td><?= $row['description'] ?></td>
    <td class="text-right"><?= $row['quantity'] ?></td>
    <td class="text-center"><?= $row['uom'] ?></td>
    <td class="text-right"><?= number_format($row['costextended'], 2) ?></td>
</tr>
<?php } ?>

</tbody>
</table>
<br>
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
    <div class="signature">
        Name of Driver
    </div>
    <p class="plateno">Plate #:</p>
</div>

</body>
</html>
