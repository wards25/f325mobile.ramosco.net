<?php
session_start();
include('dbconnect.php');

header("Content-Type: text/plain; charset=UTF-8");
header("Content-Disposition: attachment; filename=".basename($_GET['f325number']).".txt");
header("Pragma: no-cache");
header("Expires: 0");

$f325 = $_GET['f325number'] ?? '';

if ($f325 == '') {
    echo "Missing F325 Number";
    exit;
}

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

$items = [];
$qi = mysqli_query($conn, "
    SELECT * FROM dbraw 
    WHERE f325number = '$f325'
");

while ($row = mysqli_fetch_assoc($qi)) {
    $items[] = $row;
}
$f325date = date('m/d/Y', strtotime($h['f325date']));

echo "\x0C"; 
echo "\r\n";
echo "@\r\n";
echo"M\r\n";

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

$total = 0;

foreach ($items as $i) {

    $code     = $i['mdccode']        ?? '';
    $qty      = $i['quantity']       ?? '';
    $desc     = $i['description']    ?? ''; 
    $exp      = date('m/d/Y', strtotime($i['expiration']))     ?? '';
    $reason   = $i['reasoncode']     ?? '';
    $unit     = $i['unitcost']       ?? 0;
    $sub      = $i['costextended']   ?? 0;

    $each     = number_format($unit, 2);
    $ext      = number_format($sub, 2);

    $total += $sub;

    echo " {$code}    {$qty}  {$desc}       {$exp} {$reason}         {$each}   {$ext}\r\n";
}


echo "\r\n\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo"\r\n";
echo " NO. OF CARTONS OF THIS F-325                         TOTAL==>          " . number_format($total, 2) . "\r\n";
echo "                                                       Rundate " . date("m/d/Y  H:i:s") . "\r\n\r\n";

echo " Reasons for returning the item/s to Central Warehouse c/o Returns Section or to Supplier\r\n";
echo "  E-Expiring\r\n";

?>
