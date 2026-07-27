<?php
session_start();
include('dbconnect.php');

date_default_timezone_set("Asia/Manila");
$username = $_SESSION['fname'] ?? '';
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");
$f325 = $_GET['f325number'] ?? '';
$status = 'PRINTED';
$action = $_GET['action'] ?? '';

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

// Fetch items
$items = [];
$qi = mysqli_query($conn, "SELECT * FROM dbraw WHERE f325number = '$f325'");
while ($row = mysqli_fetch_assoc($qi)) {
    $items[] = $row;
}

$f325date = date('m/d/Y', strtotime($h['f325date']));
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            size: auto;
            margin: 0.5in;
        }

        body {
            font-family: "Courier New", Courier, monospace;
            white-space: pre;
            font-size: 13px;
            /* Adjust size if columns wrap */
            line-height: 1.2;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
<?php
$total = 0;

// Printer Control Codes with New Lines
echo "\x1B@\r\n"; // Initialize
echo "\x1BM\r\n"; // Select 12 CPI

// Header Section (60 spaces indentation)
$indent = "                                                            ";
echo $indent . "Doc.#  - {$h['f325number']}\r\n";
echo $indent . "Branch - {$h['brcode']} {$h['branchname']}\r\n";
echo $indent . "Prepared by - {$h['preparedby']} on {$f325date}\r\n";
echo $indent . "Issued by - ________________________________\r\n";
echo $indent . "             {$h['issuedby']}\r\n";
echo $indent . "             (Branch Manager)\r\n\r\n";

echo " Shipped To - " . str_pad($h['vendorname'] . " (" . $h['vendorcode'] . ")", 45) . "\r\n";
echo " Shipped Via Forwarder (Name and Waybill No.) - ________________________  Date - _________\r\n\r\n";

// Table Header
echo " ITEM                                                     COST    COST     \r\n";
echo " CODE     QNTY  DESCRIPTION                EXP-DATE Reason EACH    EXTENDED \r\n";
echo " ----     ----  -----------                --Lot #- ----- ----    -------- \r\n";

// Document Items
foreach ($items as $i) {
    $code   = $i['mdccode'] ?? '';
    $qty    = $i['quantity'] ?? 0;
    
    $product_q = mysqli_query($conn, "SELECT description FROM dbproduct WHERE mdccode= '$code'");
    $p_row = mysqli_fetch_assoc($product_q);
    $desc = substr($p_row['description'] ?? '', 0, 26); 
    
    $exp_raw = $i['expiration'];
    $exp = (!is_null($exp_raw) && !empty($exp_raw) && $exp_raw !== '0000-00-00' && $exp_raw !== '0000-00-00 00:00:00') 
       ? date('m/d/y', strtotime($exp_raw)) 
       : '        ';
    $reason = $i['reasoncode'] ?? '';
    $unit   = number_format($i['unitcost'] ?? 0, 2);
    $ext    = number_format($i['costextended'] ?? 0, 2);
    $lot    = $i['lotno'] ?? ''; 

    $total += ($i['costextended'] ?? 0);

    // Row Logic
    $row = " " . str_pad($code, 9, " ");
    $row .= str_pad($qty, 6, " ");
    $row .= str_pad($desc, 27, " ");
    $row .= str_pad($exp, 9, " ");
    $row .= str_pad($reason, 7, " ");
    $row .= str_pad($unit, 8, " ", STR_PAD_LEFT);
    $row .= str_pad($ext, 10, " ", STR_PAD_LEFT);
    
    echo $row . "\r\n";

    if (!empty($lot)) {
        echo "                " . $lot . "\r\n";
    }
}

// Footer Section
echo "\r\n";
echo " NO. OF CARTONS OF THIS F-325                TOTAL==>     " . str_pad(number_format($total, 2), 10, " ", STR_PAD_LEFT) . "\r\n";
echo "                                             Rundate " . date("m/d/Y  H:i:s") . "\r\n\r\n";

// Reason Detail
$reason_query = mysqli_query($conn, "SELECT * FROM dbmercuryreason WHERE nameinitial= '$reason'");
$reason_row = mysqli_fetch_array($reason_query);
$full_reason = $reason_row['reason'] ?? 'EXPIRING';

echo " Reasons for returning the item/s to Central Warehouse c/o Returns Section or to Supplier\r\n";
echo "  " . $reason . "-" . $full_reason . "\r\n";
if ($action == 'RE-PRINT') {
        ?>
        <script>window.print()</script>
        <?php
}else{
    // Database Updates
$remarks = $_POST['remarks'] ?? '';
mysqli_query($conn, "UPDATE dbf325number SET status='$status', printremarks='$remarks' WHERE f325number='$f325'");
mysqli_query($conn, "UPDATE dbraw SET status='$status' WHERE f325number='$f325'");
mysqli_query($conn, "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325','$username','Printed','$dateprocessed','$timeprocessed')");
}

$conn->close();
?>
</body>
</html>
<script>window.print()</script>