<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');

session_start();
include_once("dbconnect.php");
require_once('vendor/tcpdf/tcpdf.php');

if (!isset($_SESSION['id'])) {
    die("Unauthorized access");
}

// ================= INPUT =================
$batchnumber    = $_GET['batchnumber']    ?? '';
$vendor         = $_GET['vendor']         ?? '';
$type           = $_GET['type']           ?? 'pullout';
$date_processed = $_GET['date_processed'] ?? date('Y-m-d');

if (empty($batchnumber)) {
    die("Batch number is required");
}

// ================= FETCH =================
$query = "
    SELECT r.*, p.description, c.branchname, c.code AS brcode
    FROM dbraw r
    LEFT JOIN dbproduct    p ON r.mdccode    = p.mdccode
    LEFT JOIN dbf325number f ON r.f325number = f.f325number
    LEFT JOIN dbcensus     c ON f.brcode     = c.code
    WHERE r.batchnumber_forpullout = '" . mysqli_real_escape_string($conn, $batchnumber) . "'
    ORDER BY r.f325number, r.mdccode
";
$result = mysqli_query($conn, $query);

// ================= GROUP =================
$documents = [];
while ($row = mysqli_fetch_assoc($result)) {
    $documents[$row['f325number']][] = $row;
}

// ================= VENDOR =================
$vq          = mysqli_query($conn, "SELECT name FROM dbcompany WHERE vendorcode='" . mysqli_real_escape_string($conn, $vendor) . "'");
$vendor_name = mysqli_fetch_assoc($vq)['name'] ?? $vendor;

// ================= PDF SETUP =================
// Courier 9pt on A4 (210mm) with 8mm L+R margins = ~194mm usable
// At 9pt Courier: 1 char ~1.814mm  =>  194 / 1.814 ~106 chars per line
// Indent = 60 chars, right-side content must stay <= 46 chars to avoid wrap
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetFont('courier', '', 9);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// ================= LOOP =================
foreach ($documents as $f325 => $items) {

    $pdf->AddPage();

    $total = 0;
    $text  = "";
    $indent = "                                                    ";

    // ---- per-document header fields ----
    $pq      = mysqli_query($conn, "SELECT preparedby, issuedby FROM dbf325number WHERE f325number='" . mysqli_real_escape_string($conn, $f325) . "'");
    $f325row = mysqli_fetch_assoc($pq);

    $prepared_by = $f325row['preparedby'] ?? 'unknown';
    $issued_by   = $f325row['issuedby']   ?? '';
    $doc_val    = substr($f325, 0, 37);
    $branch_val = substr($items[0]['brcode'] . ' ' . $items[0]['branchname'], 0, 37);
    $date_fmt   = date('m/d/Y', strtotime($date_processed));
    $prep_val   = substr($prepared_by, 0, 18) . ' ' . $date_fmt;
    $issued_val = substr($issued_by, 0, 33);

    // ================= HEADER =================
    $text .= $indent . "Doc.#  - {$doc_val}\n";
    $text .= $indent . "Branch - {$branch_val}\n";
    $text .= $indent . "Prepared by - {$prep_val}\n";
    $text .= $indent . "Issued by - ______________________________\n";
    $text .= $indent . "             {$issued_val}\n";
    $text .= $indent . "             (Branch Manager)\n";
    $text .= "\n";

    // ================= SHIPPED TO =================
    $text .= " Shipped To - {$vendor_name} ({$vendor})\n";
    $text .= " Shipped Via Forwarder (Name and Waybill No.) - ________________________  Date - _________\n";
    $text .= "\n";

    // ================= TABLE HEADER =================
    $text .= " ITEM                                                     COST    COST\n";
    $text .= " CODE     QNTY  DESCRIPTION                EXP-DATE Reason EACH    EXTENDED\n";
    $text .= " ----     ----  -----------                --Lot #- ----- ----    --------\n";

    // ================= ITEMS =================
    $last_reason = '';
    foreach ($items as $i) {

        $code    = $i['mdccode'] ?? '';
        $qty     = $i['quantity'] ?? 0;
        $desc    = substr($i['description'] ?? '', 0, 26);

        $exp_raw = $i['expiration'] ?? '';
        $exp     = (!empty($exp_raw)
                    && $exp_raw !== '0000-00-00'
                    && $exp_raw !== '0000-00-00 00:00:00')
                    ? date('m/d/y', strtotime($exp_raw))
                    : '        ';

        $reason  = $i['reasoncode']   ?? '';
        $unit    = number_format($i['unitcost']     ?? 0, 2);
        $ext     = number_format($i['costextended'] ?? 0, 2);
        $lot     = $i['lotno'] ?? '';

        $total      += ($i['costextended'] ?? 0);
        $last_reason = $reason;

        $row  = " " . str_pad($code,   9);
        $row .= str_pad($qty,          6);
        $row .= str_pad($desc,        27);
        $row .= str_pad($exp,          9);
        $row .= str_pad($reason,       7);
        $row .= str_pad($unit,         6, " ", STR_PAD_LEFT);
        $row .= str_pad($ext,         8, " ", STR_PAD_LEFT);

        $text .= $row . "\n";

        if (!empty($lot)) {
            $text .= "                " . $lot . "\n";
        }
    }

    // ================= FOOTER =================
    $text .= "\n";
    $text .= " NO. OF CARTONS OF THIS F-325                TOTAL==>     "
           . str_pad(number_format($total, 2), 10, " ", STR_PAD_LEFT) . "\n";
    $text .= "                                             Rundate "
           . date("m/d/Y  H:i:s") . "\n";
    $text .= "\n";

    // ================= REASON =================
    $rq          = mysqli_query($conn, "SELECT reason FROM dbmercuryreason WHERE nameinitial='" . mysqli_real_escape_string($conn, $last_reason) . "'");
    $reason_name = mysqli_fetch_assoc($rq)['reason'] ?? 'EXPIRING';

    $text .= " Reasons for returning the item/s to Central Warehouse c/o Returns Section or to Supplier\n";
    $text .= "  {$last_reason}-{$reason_name}\n";

    // ================= RENDER =================
    $pdf->writeHTML("<pre>" . htmlspecialchars($text) . "</pre>", true, false, true, false, '');
}
 // ===== INSERT HISTORY LOG =====
    $username = $_SESSION['fname'] ?? 'Unknown';
    $dateprocessed = date('Y-m-d');
    $timeprocessed = date('H:i:s');
    $processed = 'EXPORT NOTEPAD for batch ' . $batchnumber;

    mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) 
    VALUES ('$batchnumber','$username','$processed','$dateprocessed','$timeprocessed')");
// ================= OUTPUT =================
$pdf->Output("Preform_SMIS_Notepad_{$batchnumber}.pdf", "D");

$conn->close();
exit;
?>