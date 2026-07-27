<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');
session_start();
include_once("dbconnect.php");
require_once('vendor/tcpdf/tcpdf.php');

// --- 1. PARAMS ---
$pre_no      = $_GET['pre_no']      ?? '';
$batchnumber = $_GET['batchnumber'] ?? '';

if (empty($pre_no) && empty($batchnumber)) {
    die("Error: PRE Number or Batch Number is required.");
}

// --- 2. FETCH DATA ---
$query = "SELECT * FROM tbl_preform WHERE ";
if (!empty($pre_no)) {
    $query .= "pre_no = '" . mysqli_real_escape_string($conn, $pre_no) . "'";
} else {
    $query .= "batchnumber = '" . mysqli_real_escape_string($conn, $batchnumber) . "'";
}
$query .= " ORDER BY id ASC";

$result = mysqli_query($conn, $query);
if (!$result) die("DB error: " . mysqli_error($conn));

$rows = [];
while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;
mysqli_free_result($result);
if (!$rows) die("Error: No records found.");

$h        = $rows[0];
$totalQty = array_sum(array_column($rows, 'qty'));

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// --- 3. PDF INIT ---
$pdf = new TCPDF('L', 'mm', 'LEGAL', true, 'UTF-8', false);
$pdf->SetCreator('SMIS');
$pdf->SetAuthor('SMIS');
$pdf->SetTitle('PRE ' . $h['pre_no']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 8);
$pdf->AddPage();

// --- 4. CONSTANTS ---
$X0 = 8;    // left margin
$PW = 340;  // usable width

// CRITICAL: Use freesans for Unicode support (Checkboxes and Checkmarks)
$FONT  = 'dejavusans';
$FONTB = 'dejavusansb';
$pdf->SetTextColor(0, 0, 0);

// --- 5. HEADER ---
$logoPath = __DIR__ . '/img/smis-logo-v2.jpg';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $X0, 8, 26, 14, 'JPEG');
}

$pdf->SetFont($FONTB, 'B', 14);
$pdf->SetXY($X0 + 28, 11);
$pdf->Cell(100, 8, 'PRODUCT RETURNS EVALUATION', 0, 0, 'L');

$iX = 210;
$i1 = 46; $i2 = 52; $i3 = 18; $i4 = 22;
$iH = 6;

// Row 1
$pdf->SetXY($iX, 8);
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->Cell($i1, $iH, 'Date Prepared:', 1, 0, 'L');
$pdf->SetFont($FONT, '', 7.5);
$pdf->Cell($i2, $iH, ' ' . e($h['date_processed']), 1, 0, 'L');
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->Cell($i3, $iH, 'RDO No:', 1, 0, 'L');
$pdf->SetFont($FONT, '', 7.5);
$pdf->Cell($i4, $iH, '', 1, 1, 'L');

// Row 2
$r2H = $iH * 2;
$pdf->SetXY($iX, 8 + $iH);
$pdf->SetFont($FONTB, 'B', 7);
$pdf->MultiCell($i1, $r2H, "Outlet's RTV or Returns\nDocument Ref No:", 1, 'L', false, 0, '', '', true, 0, false, true, $r2H, 'M');
$pdf->SetFont($FONT, '', 7.5);
$pdf->MultiCell($i2, $r2H, ' ' . e($h['pre_no'] . ' PUREFOODS ' . $h['hub']), 1, 'L', false, 0, '', '', true, 0, false, true, $r2H, 'M');
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->MultiCell($i3, $r2H, 'PRE No:', 1, 'L', false, 0, '', '', true, 0, false, true, $r2H, 'M');
$pdf->SetFont($FONT, '', 7.5);
$pdf->MultiCell($i4, $r2H, ' ' . e($h['pre_no']), 1, 'L', false, 0, '', '', true, 0, false, true, $r2H, 'M');

// --- 6. NOTE ---
$pdf->SetXY($X0, 30);
$pdf->SetFont($FONT, 'I', 7.5);
$pdf->MultiCell($PW, 4, 'NOTE: The PRE is a mandatory SMIS form for product returns processing. No trade returns maybe pulled-out without evaluation by authorized SMIS representatives.', 0, 'L');


// --- 7. CUSTOMER INFO TABLE ---
$c1 = 48; $c2 = 109; $c3 = 34; $c4 = 75; $c5 = 74;
$cIH = 5.5;
$yInfo = $pdf->GetY() + 2;

$pdf->SetXY($X0, $yInfo);
$pdf->SetFont($FONTB, 'BU', 7.5);
$pdf->Cell($c1 + $c2 + $c3, 5, 'Requesting Customer Details', 0, 0, 'L');
$pdf->Cell($c4 + $c5, 5, 'SMIS Authorized Representative Details:', 0, 1, 'L');

// Row A
$pdf->SetXY($X0, $pdf->GetY());
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->Cell($c1, $cIH, 'Business Name:', 1, 0, 'L');
$pdf->SetFont($FONT, '', 7.5);
$pdf->Cell($c2, $cIH, ' ' . e($h['business_name']), 1, 0, 'L');
$pdf->Cell($c3, $cIH, 'Customer: ' . e($h['customer_code']), 1, 0, 'L');
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->Cell($c4, $cIH, 'Assigned Salesman (SAS):', 1, 0, 'L');
$pdf->SetFont($FONT, '', 7.5);
$pdf->Cell($c5, $cIH, '', 1, 1, 'L');

// Row B
$pdf->SetXY($X0, $pdf->GetY());
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->Cell($c1, $cIH, 'Customer Outlet Addr:', 1, 0, 'L');
$pdf->SetFont($FONT, '', 7.5);
$pdf->Cell($c2 + $c3, $cIH, ' ' . e($h['address']), 1, 0, 'L');
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->Cell($c4, $cIH, 'Assigned Merchandiser:', 1, 0, 'L');
$pdf->SetFont($FONT, '', 7.5);
$pdf->Cell($c5, $cIH, ' ' . e($h['assigned_merchandiser']), 1, 1, 'L');

// --- 8. TYPE OF RETURN ---
$pdf->SetXY($X0, $pdf->GetY());
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->Cell(41, $cIH, 'Type of Return:', 1, 0, 'L');
$pdf->SetFont($FONT, '', 7.5);
// Using Unicode for boxes
$pdf->Cell(41, $cIH, "\xe2\x98\x91 Valid BO", 1, 0, 'C');
$pdf->Cell(54, $cIH, "\xe2\x96\xa1 Product Retrieval", 1, 0, 'C');
$pdf->Cell(54, $cIH, "\xe2\x96\xa1 For Investigation", 1, 0, 'C');
$pdf->Cell(54, $cIH, "\xe2\x96\xa1 Stock Transfer", 1, 0, 'C');
$pdf->Cell(96, $cIH, " \xe2\x96\xa1 Others (Specify) _____________________", 1, 1, 'L');

// --- 9. MAIN TABLE HEADER ---
// Adjusted widths to give Column M (Others) more space
$cw = [
    'A' => 24, 'B' => 68, 'C' => 13, 'D' => 26, 'E' => 24, 
    'F' => 14, 'G' => 14, 'H' => 15, 'I' => 18, 'J' => 15, 
    'K' => 15, 'L' => 15, 'M' => 59, 'N' => 20 
];

$hH = 5.5;
$spanH = $hH * 3;
$yTH = $pdf->GetY();

$pdf->SetFillColor(232, 232, 232);
$pdf->SetFont($FONTB, 'B', 6.5);
$pdf->SetXY($X0, $yTH);

foreach (['A' => "Product\nBar Code", 'B' => "Product Description (SKU)", 'C' => "Size", 'D' => "Production\nCode or Date", 'E' => "Expiry\nDate"] as $k => $lbl) {
    $pdf->MultiCell($cw[$k], $spanH, $lbl, 1, 'C', true, 0, '', '', true, 0, false, true, $spanH, 'M');
}

$detW = $cw['F'] + $cw['G'];
$pdf->MultiCell($detW, $hH, 'Details of Return', 1, 'C', true, 0, '', '', true, 0, false, true, $hH, 'M');

$rcW = $cw['H']+$cw['I']+$cw['J']+$cw['K']+$cw['L']+$cw['M'];
$pdf->MultiCell($rcW, $hH, 'Reason Code (Check applicable box)', 1, 'C', true, 0, '', '', true, 0, false, true, $hH, 'M');

$pdf->MultiCell($cw['N'], $spanH, "Disposition /\nRemarks", 1, 'C', true, 1, '', '', true, 0, false, true, $spanH, 'M');

$xFG = $X0 + $cw['A']+$cw['B']+$cw['C']+$cw['D']+$cw['E'];
$pdf->SetXY($xFG, $yTH + $hH);
$pdf->MultiCell($detW, $hH, 'Quantity', 1, 'C', true, 0, '', '', true, 0, false, true, $hH, 'M');

foreach (['H' => "G01\n(Expired\nNEX)", 'I' => "D04\n(Torn/\nDamaged\nLabels)", 'J' => "D02\n(Dented/\nDeformed)", 'K' => "M07\n(No\nVacuum)", 'L' => "M02\n(Weak /\nBurnt\nSeal)", 'M' => "Others\n(Specify\nCode)"] as $k => $lbl) {
    $pdf->MultiCell($cw[$k], $hH * 2, $lbl, 1, 'C', true, 0, '', '', true, 0, false, true, $hH * 2, 'M');
}

$pdf->SetXY($xFG, $yTH + $hH * 2);
$pdf->Cell($cw['F'], $hH, 'pcs', 1, 0, 'C', true);
$pdf->Cell($cw['G'], $hH, 'kgs', 1, 0, 'C', true);
$pdf->Ln();

// --- 10. DATA ROWS ---
$pdf->SetFont($FONT, '', 7); 
$rowH    = 7; // Increased row height for better text wrapping
$altFill = false;
$chk     = "\xe2\x9c\x93"; // Unicode Checkmark


foreach ($rows as $row) {
    if ($pdf->GetY() + $rowH > ($pdf->getPageHeight() - 65)) {
        $pdf->AddPage();
    }

    $pdf->SetFillColor(...($altFill ? [249, 249, 249] : [255, 255, 255]));
    $altFill = !$altFill;

    $pdf->SetX($X0);
    $pdf->Cell($cw['A'], $rowH, e($row['mdccode']), 1, 0, 'C', true);
    
    // MultiCell for SKU description
    $startX = $pdf->GetX();
    $startY = $pdf->GetY();
    $pdf->MultiCell($cw['B'], $rowH, e($row['description']), 1, 'L', true, 0, '', '', true, 0, false, true, $rowH, 'M');
    
    $pdf->Cell($cw['C'], $rowH, e($row['size']), 1, 0, 'C', true);
    $pdf->Cell($cw['D'], $rowH, '', 1, 0, 'C', true);
    $pdf->Cell($cw['E'], $rowH, e($row['expiration']), 1, 0, 'C', true);
    $pdf->Cell($cw['F'], $rowH, e($row['qty']), 1, 0, 'C', true);
    $pdf->Cell($cw['G'], $rowH, '', 1, 0, 'C', true);
    $pdf->Cell($cw['H'], $rowH, ($row['g01'] == 1) ? $chk : '', 1, 0, 'C', true);
    $pdf->Cell($cw['I'], $rowH, ($row['d04'] == 1) ? $chk : '', 1, 0, 'C', true);
    $pdf->Cell($cw['J'], $rowH, ($row['d02'] == 1) ? $chk : '', 1, 0, 'C', true);
    $pdf->Cell($cw['K'], $rowH, ($row['m07'] == 1) ? $chk : '', 1, 0, 'C', true);
    $pdf->Cell($cw['L'], $rowH, ($row['m02'] == 1) ? $chk : '', 1, 0, 'C', true);
    
    // MultiCell for "Others" to prevent text cut-off
    $pdf->MultiCell($cw['M'], $rowH, e($row['others']), 1, 'L', true, 0, '', '', true, 0, false, true, $rowH, 'M');
    
    $pdf->Cell($cw['N'], $rowH, '', 1, 1, 'L', true);
}

// Total row
$pdf->SetFillColor(255, 255, 255);
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->SetX($X0);
$blankL = $cw['A']+$cw['B']+$cw['C']+$cw['D']+$cw['E'];
$blankR = $cw['G']+$cw['H']+$cw['I']+$cw['J']+$cw['K']+$cw['L']+$cw['M']+$cw['N'];
$pdf->Cell($blankL,  $rowH, '', 1, 0, 'R', false);
$pdf->Cell($cw['F'], $rowH, $totalQty, 1, 0, 'C', false);
$pdf->Cell($blankR,  $rowH, '', 1, 1, 'C', false);

// --- 11. SIGNATURE SECTION ---
$drawSigBlock = function($title, $l1, $r1, $l2, $r2, $l3, $r3, $topGap = 5.0) use ($pdf, $FONT, $FONTB, $X0, $PW) {
    $pdf->Ln($topGap);
    if ($title !== '') {
        $pdf->SetFont($FONTB, 'B', 7.5);
        $pdf->SetX($X0);
        $pdf->Cell(0, 5, $title, 0, 1, 'L');
    }
    $colW  = $PW / 3;
    $lineY = $pdf->GetY() + 8;
    $pdf->SetDrawColor(0, 0, 0);
    foreach ([0, 1, 2] as $i) {
        $lx = $X0 + $i * $colW + 15;
        $pdf->Line($lx, $lineY, $lx + $colW - 30, $lineY);
    }
    $pdf->SetY($lineY + 1.5);
    $pdf->SetFont($FONT, '', 7.5);
    $pdf->SetX($X0);
    $pdf->Cell($colW, 4.5, $l1, 0, 0, 'C');
    $pdf->Cell($colW, 4.5, $l2, 0, 0, 'C');
    $pdf->Cell($colW, 4.5, $l3, 0, 1, 'C');
    $pdf->SetFont($FONT, 'I', 7);
    $pdf->SetX($X0);
    $pdf->Cell($colW, 4, $r1, 0, 0, 'C');
    $pdf->Cell($colW, 4, $r2, 0, 0, 'C');
    $pdf->Cell($colW, 4, $r3, 0, 1, 'C');
};

$drawSigBlock('', 'Prepared:', 'Merchandiser', 'Confirmed:', 'Customer Representative', 'Checked:', 'Coordinator', 5.0);
$drawSigBlock('TRADE RETURNS CASES FOR INVESTIGATION ONLY:', 'Endorsed:', 'TQA/ASM/RSM', 'Approved:', 'ANSM/SM', 'Approved:', 'NSM', 3.5);
$drawSigBlock('FOR STOCK TRANSFER ONLY:', 'Endorsed:', 'ASM/RSM', 'Approved:', 'ANSM/SM', 'Approved:', 'NSM', 3.5);

// --- 12. NOTES ---
$pdf->Ln(3.5);
$pdf->SetFont($FONTB, 'B', 7.5);
$pdf->SetX($X0);
$pdf->Cell(0, 4.5, 'NOTE TO BUSINESS CENTER:', 0, 1, 'L');
$pdf->SetFont($FONT, '', 7);
$pdf->SetX($X0);
$pdf->MultiCell($PW, 3.8, "1. For manual trade returns booking of regular BO cases, no PRE shall be processed if form is incomplete, unless with express consent (electronic or in writing) of the assigned salesman's direct manager.\n2. For automated/B2B trade returns booking, no request shall be processed for booking if without manual PRE reference, unless with express consent (electronic or in writing) of the assigned salesman's direct manager.", 0, 'L');

$pdf->SetFont($FONT, 'I', 7);
$pdf->SetX($X0);
$pdf->Cell(0, 4, 'MF INTERNAL USE', 0, 1, 'L');

// ===== INSERT HISTORY LOG =====
    $username = $_SESSION['fname'] ?? 'Unknown';
    $dateprocessed = date('Y-m-d');
    $timeprocessed = date('H:i:s');
    $processed = 'EXPORT PREFORM PDF for batch ' . $batchnumber;

    mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) 
    VALUES ('$batchnumber','$username','$processed','$dateprocessed','$timeprocessed')");
// --- 13. OUTPUT ---
$filename = 'PRE_' . ($pre_no ?: $batchnumber) . '_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');