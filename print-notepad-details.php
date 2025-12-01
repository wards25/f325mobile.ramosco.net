<?php
session_start();
include('dbconnect.php');

date_default_timezone_set("Asia/Manila");
$username = $_SESSION['fname'] ?? '';
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");
$f325 = $_GET['f325number'] ?? '';
$status = 'PRINTED';

if ($f325 == '') {
    echo "Missing F325 Number";
    exit;
}

// Fetch F-325 header info
$q = mysqli_query($conn, "
    SELECT a.*, 
           b.name AS vendorname, 
           b.vendorcode,
           c.branchname
    FROM dbf325number a
    LEFT JOIN dbcompany b ON a.vendor = b.vendorcode
    LEFT JOIN dbcensus c ON a.brcode = c.code
    WHERE a.f325number = '$f325'
") or die("SQL ERROR: " . mysqli_error($conn));

$h = mysqli_fetch_assoc($q);
if (!$h) {
    echo "F325 Number not found.";
    exit;
}

// Fetch items
$items = [];
$qi = mysqli_query($conn, "SELECT * FROM dbraw WHERE f325number = '$f325'");
while ($row = mysqli_fetch_assoc($qi)) {
    $items[] = $row;
}

$f325date = date('m/d/Y', strtotime($h['f325date']));

// Change content type to HTML for browser printing
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: monospace; /* preserves spacing */
            white-space: pre;       /* preserves formatting */
        }
        .print-button {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<?php
$total = 0;

// Document Header
echo "\x0C\r\n"; // Form feed for thermal printers
echo "\r\n";
echo "@\r\n";
echo "M\r\n";
echo "                                                            Doc.#  - {$h['f325number']}\r\n";
echo "                                                            Branch - {$h['brcode']} {$h['branchname']}\r\n";
echo "                                                            Prepared by - {$h['preparedby']} on {$f325date}\r\n";
echo "                                                            Issued by - ________________________________\r\n";
echo "                                                                         MS.MA. FE NERI RASCO\r\n";
echo "                                                                            (Branch Manager)\r\n\r\n";
echo " Shipped To - {$h['vendorname']} ({$h['vendorcode']}  )\r\n";
echo " Shipped Via Forwarder (Name and Waybill No.) - _________________________________    Date - _________________\r\n\r\n";
echo "  ITEM                                                          COST    COST\r\n";
echo "  CODE    QNTY   DESCRIPTION                  EXP-DATE Reason   EACH  EXTENDED\r\n";
echo "  ----    ----   -----------                  --Lot #- -----    ----  --------\r\n\r\n";

// Document Items
foreach ($items as $i) {
    $code     = $i['mdccode'] ?? '';
    $qty      = $i['quantity'] ?? '';
    $product = mysqli_query($conn, "SELECT * FROM dbproduct WHERE mdccode= '$code'");
    $product_query = mysqli_fetch_array($product);
    $desc     = $product_query['description'] ?? '';
    $exp      = date('m/d/Y', strtotime($i['expiration'])) ?? '';
    $reason   = $i['reasoncode'] ?? '';
    $unit     = $i['unitcost'] ?? 0;
    $sub      = $i['costextended'] ?? 0;

    $each     = number_format($unit, 2);
    $ext      = number_format($sub, 2);

    $total += $sub;

    echo " {$code}    {$qty}  {$desc}       {$exp} {$reason}      {$each}   {$ext}\r\n";
}
echo "\r\n\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo "\r\n";
echo " NO. OF CARTONS OF THIS F-325                         TOTAL==>          " . number_format($total, 2) . "\r\n";
echo "                                                       Rundate " . date("m/d/Y  H:i:s") . "\r\n\r\n";

// Reason for return
$reason_query = mysqli_query($conn, "SELECT * FROM dbmercuryreason WHERE nameinitial= '$reason'");
$reason_row = mysqli_fetch_array($reason_query);
echo " Reasons for returning the item/s to Central Warehouse c/o Returns Section or to Supplier\r\n";
echo "  " .  $reason . "-" . $reason_row['reason'] . "\r\n";

// Optional remarks
$remarks = $_POST['remarks'] ?? '';

// Update database
mysqli_query($conn, "UPDATE dbf325number SET status='$status', printremarks='$remarks' WHERE f325number='$f325'");
mysqli_query($conn, "UPDATE dbraw SET status='$status' WHERE f325number='$f325'");
$processed = 'Printed';
mysqli_query($conn, "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325','$username','$processed','$dateprocessed','$timeprocessed')");

$conn->close();
?>

</body>
</html>
<script>window.print()</script>
