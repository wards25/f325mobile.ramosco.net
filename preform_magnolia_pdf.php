<?php
/**
 * preform_magnolia_pdf.php
 * Generates a PDF matching exactly the SMIS Product Returns Evaluation (MAGNOLIA) format.
 * Layout reference: SMIS_Format___Product_Returns_Evaluation__MAGNOLIA____REPRINT.pdf
 *
 * Usage: preform_magnolia_pdf.php?batchnumber=XXXX
 */

session_start();
include_once("dbconnect.php");
require_once('vendor/tcpdf/tcpdf.php');

$batchnumber = $_GET['batchnumber'] ?? '';

// ══════════════════════════════════════════════════════════════
//  HUB MAPPING
// ══════════════════════════════════════════════════════════════
$hubMapping = [
    'cainta'     => ['code' => '165237', 'name' => 'RAMOSCO SALES AND DIST INC - CAINTA',     'address' => '167 Felix Ave, Cainta, 1900 Rizal'],
    'cdo'        => ['code' => '165239', 'name' => 'RAMOSCO SALES AND DIST INC - CDO',        'address' => 'NEXT STEP REALTY, ZONE 5 BARRA OPOL, MIS.OR'],
    'cebu'       => ['code' => '165238', 'name' => 'RAMOSCO SALES AND DIST INC - CEBU',       'address' => 'Door 6&7 THE GREEN STRIP WAREHOUSE - Jayme St. Paknaan, Mandaue City'],
    'davao'      => ['code' => '165241', 'name' => 'RAMOSCO SALES AND DIST INC - DAVAO',      'address' => 'YLG REALTY INC. WAREHOUSE D5 KM10 MCARTHUR HIGHWAY BAGO APLAYA'],
    'iloilo'     => ['code' => '165240', 'name' => 'RAMOSCO SALES AND DIST INC - ILOILO',     'address' => 'Door A4 GOLDEN LUCK WAREHOUSE - Brgy. Ticud Lapaz, Iloilo City'],
    'leyte'      => ['code' => '171482', 'name' => 'RAMOSCO SALES AND DIST INC - LEYTE',      'address' => 'Zone Jupiter, Asia Ice Plant compound Brgy Pawing Palo Leyte'],
    'pangasinan' => ['code' => '165242', 'name' => 'RAMOSCO SALES AND DIST INC - PANGASINAN', 'address' => '#47 McArthur Highway San Nicolas Villasis Pangasinan'],
];

// ══════════════════════════════════════════════════════════════
//  DATABASE QUERY
// ══════════════════════════════════════════════════════════════
$bn_esc = mysqli_real_escape_string($conn, $batchnumber);
$result = mysqli_query($conn, "SELECT * FROM tbl_preform WHERE batchnumber = '$bn_esc' ORDER BY id ASC");
if (!$result) die("Database query failed: " . mysqli_error($conn));

$rows = [];
while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;
mysqli_free_result($result);
if (empty($rows)) die("No records found for this PRE No / Batch.");

$first             = $rows[0];
$pre_no            = $first['pre_no'];
$date_processed    = $first['date_processed'];
$hub               = strtolower(trim($first['hub']));
$hubInfo           = $hubMapping[$hub] ?? [
    'code'    => $first['customer_code'],
    'name'    => $first['business_name'],
    'address' => $first['address'],
];
$type_short = strtoupper(substr($first['category'], 0, 3));
$hub_upper  = strtoupper($hub);
$assigned_merchant = $first['assigned_merchandiser'];
$smis_form_columns = ['G01', 'D04', 'D02', 'M07', 'M02'];

// ══════════════════════════════════════════════════════════════
//  PDF SETUP  (A4 Landscape)
// ══════════════════════════════════════════════════════════════
class MagnoliaPDF extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new MagnoliaPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SMIS');
$pdf->SetAuthor('RAMOSCO');
$pdf->SetTitle('PRE MAGNOLIA - ' . $pre_no);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);

// A4 Landscape usable: 297-10=287mm wide, 210-10=200mm tall
$X0 = 5;
$PW = 287;
$y  = 5;

// Colour helpers
$W = [255, 255, 255]; // white
$G = [220, 220, 220]; // grey (labels / banners)

function rgb(MagnoliaPDF $pdf, array $c): void {
    $pdf->SetFillColor($c[0], $c[1], $c[2]);
}

// ══════════════════════════════════════════════════════════════
//  REUSABLE TABLE HEADER FUNCTION
// ══════════════════════════════════════════════════════════════
function addTableHeader(MagnoliaPDF $pdf, $X0, $y, $c, $PW, $G, $W) {
    $leftNote  = $c['sku']+$c['desc']+$c['size']+$c['prod']+$c['exp']
               + $c['pcs']+$c['kgs']+$c['price']+$c['reason']+$c['disp'];
    $rightRecv = $c['as1']+$c['as2']+$c['as3'];

    // Note bar
    $pdf->SetFillColor($W[0], $W[1], $W[2]);
    $pdf->SetFont('helvetica', '', 6);
    $pdf->SetXY($X0, $y);
    $pdf->Cell($leftNote, 4, 'To be filled up by Distributor / Account Specialist / TPA :', 'LTR', 0, 'L', false);
    $pdf->SetFillColor($G[0], $G[1], $G[2]);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell($rightRecv, 4, 'Receiving', 1, 1, 'C', true);
    $y += 4;

    // Header row
    $hdrH = 8;
    $pdf->SetFillColor($G[0], $G[1], $G[2]);
    $pdf->SetFont('helvetica', 'B', 6.5);
    $pdf->SetXY($X0, $y);

    $pdf->MultiCell($c['sku'],  $hdrH, 'SKU CODE',                  1, 'C', true, 0);
    $pdf->MultiCell($c['desc'], $hdrH, 'Product Description (SKU)', 1, 'C', true, 0);
    $pdf->MultiCell($c['size'], $hdrH, 'Size',                       1, 'C', true, 0);
    $pdf->MultiCell($c['prod'], $hdrH, "Production Code or Date",    1, 'C', true, 0, '', '', true, 0, false, true, $hdrH, 'M');
    $pdf->MultiCell($c['exp'],  $hdrH, 'Expiry Date',                1, 'C', true, 0);

    // Quantity super-header
    $qX = $X0 + $c['sku'] + $c['desc'] + $c['size'] + $c['prod'] + $c['exp'];
    $pdf->SetXY($qX, $y);
    $pdf->Cell($c['pcs'] + $c['kgs'], $hdrH / 2, 'Quantity', 1, 0, 'C', true);
    $pdf->SetXY($qX, $y + $hdrH / 2);
    $pdf->Cell($c['pcs'], $hdrH / 2, 'pcs', 1, 0, 'C', true);
    $pdf->Cell($c['kgs'], $hdrH / 2, 'kgs', 1, 1, 'C', true);

    // Remaining columns
    $pdf->SetXY($qX + $c['pcs'] + $c['kgs'], $y);
    $pdf->MultiCell($c['price'],  $hdrH, "Unit\nPrice",            1, 'C', true, 0);
    $pdf->MultiCell($c['reason'], $hdrH, "Reason\nCode",           1, 'C', true, 0);
    $pdf->MultiCell($c['disp'],   $hdrH, "Disposition /\nRemarks", 1, 'C', true, 0);
    $asWidth = $c['as1'] + $c['as2'] + $c['as3'];
    $pdf->MultiCell($asWidth, $hdrH, "AS/DS/TQA\nVerification\nRemarks", 1, 'C', true, 0, '', '', true, 0, false, true, $hdrH, 'M');

    return $y + $hdrH;
}

// ══════════════════════════════════════════════════════════════
//  SECTION 1 — HEADER
// ══════════════════════════════════════════════════════════════
$hA = 28; $hB = 55; $hC = 38; $hD = 55; $hE = 38;
$hF = $PW - $hA - $hB - $hC - $hD - $hE; // 73
$rH = 10;  // increased from 7 to accommodate long category text

// Logo (spans 2 rows)
rgb($pdf, $W);
$pdf->Rect($X0, $y, $hA, $rH * 2, 'D');
$logoPath = __DIR__ . '/img/smis-logo-v2.jpg';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, $X0 + 1, $y + 1, $hA - 2, ($rH * 2) - 2, 'JPG');
} else {
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY($X0, $y + 3);
    $pdf->Cell($hA, 8, 'SMIS', 0, 0, 'C');
}

// Title (spans 2 rows)
rgb($pdf, $W);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetXY($X0 + $hA, $y);
$pdf->MultiCell($hB, $rH * 2, "PRODUCT RETURNS\nEVALUATION", 1, 'L', true, 0);

// Right side – sub-row 1
$xR = $X0 + $hA + $hB;
$pdf->SetXY($xR, $y);
$pdf->SetFont('helvetica', 'B', 7);
rgb($pdf, $W);
$pdf->Cell($hC, $rH, 'DATE PREPARED:',   'LTB', 0, 'L', false);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell($hD, $rH, $date_processed,    'LTB', 0, 'L', false);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($hE, $rH, 'Required Pull Out','LTB', 0, 'L', false);
$pdf->Cell($hF, $rH, '',                 'LTBR',0, 'L', false);

// Right side – sub-row 2
$pdf->SetXY($xR, $y + $rH);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($hC, $rH, "Outlet's RTV / PRE No:", 'LTB', 0, 'L', false);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell($hD, $rH, $pre_no . ' ' . $type_short . ' ' . $hub_upper, 'LBR', 0, 'L', false);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($hE, $rH, 'PORF No:',               'LBR', 0, 'L', false);
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell($hF, $rH, $pre_no,                  'LBR', 0, 'L', false);

$y += $rH * 2;

// ══════════════════════════════════════════════════════════════
//  SECTION 2 — SENDER ROW
// ══════════════════════════════════════════════════════════════
$s1=30; $s2=55; $s3=28; $s4=80; $s5=40;
$s6 = $PW - $s1 - $s2 - $s3 - $s4 - $s5; // 54
$sH = 9;

$pdf->SetXY($X0, $y);
rgb($pdf, $G);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell($s1, $sH, "Sender's Name:\n(Distributor/Customer)", 1, 'L', true, 0);
rgb($pdf, $W);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell($s2, $sH, $hubInfo['name'], 1, 'L', false, 0);
rgb($pdf, $G);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell($s3, $sH, "Sender's Address:", 1, 'L', true, 0);
rgb($pdf, $W);
$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell($s4, $sH, $hubInfo['address'], 1, 'L', false, 0);

$codeX = $X0 + $s1 + $s2 + $s3 + $s4;
$half  = $sH / 2;
rgb($pdf, $G);
$pdf->SetFont('helvetica', 'B', 6.5);
$pdf->SetXY($codeX, $y);
$pdf->Cell($s5, $half, 'Distributor / Customer Code:', 'LTR', 1, 'C', true);
rgb($pdf, $W);
$pdf->SetFont('helvetica', '', 7);
$pdf->SetXY($codeX, $y + $half);
$pdf->Cell($s5, $half, $hubInfo['code'], 'LBR', 0, 'C', false);

$dsX = $codeX + $s5;
rgb($pdf, $G);
$pdf->SetFont('helvetica', 'B', 6.5);
$pdf->SetXY($dsX, $y);
$pdf->Cell($s6, $half, 'Name of DS/AS assigned/ Code:', 'LTR', 1, 'C', true);
rgb($pdf, $W);
$pdf->SetFont('helvetica', '', 7);
$pdf->SetXY($dsX, $y + $half);
$pdf->Cell($s6, $half, $assigned_merchant, 'LBR', 0, 'C', false);

$y += $sH;

// ══════════════════════════════════════════════════════════════
//  SECTION 3 — TYPE OF RETURN
// ══════════════════════════════════════════════════════════════
$tLbl = 25;
$tItems = [
    'TR Ordinary Pull Out', 'TR Application for CM', 'Product Recall',
    'On-site Condemn', 'Good Stocks', 'Others (Specify) _______________',
];
$tCW = ($PW - $tLbl) / count($tItems);

rgb($pdf, $G);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetXY($X0, $y);
$pdf->Cell($tLbl, 5, 'Type of Return:', 1, 0, 'L', true);
rgb($pdf, $W);
$pdf->SetFont('dejavusans', '', 7);
foreach ($tItems as $item) {
    $pdf->Cell($tCW, 5, "\xE2\x98\x90 " . $item, 1, 0, 'L', false);
}
$y += 5;

// ══════════════════════════════════════════════════════════════
//  SECTION 4 — CONCERNED BUSINESS UNIT
// ══════════════════════════════════════════════════════════════
$cLbl = 35;
$cItems = [
    'Mag-BMC','Mag-IC','Mag-Others','PHC-GP',
    'PHC-RM','SMSCCI','SMMI','Others (Specify) _____',
];
$cCW = ($PW - $cLbl) / count($cItems);

rgb($pdf, $G);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetXY($X0, $y);
$pdf->Cell($cLbl, 5, 'Concerned Business Unit:', 1, 0, 'L', true);
rgb($pdf, $W);
$pdf->SetFont('dejavusans', '', 7);
foreach ($cItems as $item) {
    $pdf->Cell($cCW, 5, "\xE2\x98\x90 " . $item, 1, 0, 'L', false);
}
$y += 5;

// ══════════════════════════════════════════════════════════════
//  BANNER — DETAILS OF RETURN
// ══════════════════════════════════════════════════════════════
rgb($pdf, $G);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY($X0, $y);
$pdf->Cell($PW, 5, 'DETAILS OF RETURN', 1, 1, 'C', true);
$y += 5;

// ══════════════════════════════════════════════════════════════
//  MAIN TABLE — COLUMNS
// ══════════════════════════════════════════════════════════════
$c = [
    'sku'    => 20,
    'desc'   => 72,
    'size'   => 12,
    'prod'   => 22,
    'exp'    => 26,
    'pcs'    => 15,
    'kgs'    => 15,
    'price'  => 20,
    'reason' => 18,
    'disp'   => 28,
    'as1'    => 13,
    'as2'    => 13,
    'as3'    => 0,
];
$c['as3'] = $PW - array_sum($c); // = 13

// Draw initial table header
$y = addTableHeader($pdf, $X0, $y, $c, $PW, $G, $W);

// ══════════════════════════════════════════════════════════════
//  DATA ROWS
// ══════════════════════════════════════════════════════════════
$rowH        = 5;
$totalPcs    = 0;
$totalAmt    = 0.0;
$pageHeight  = 210; // A4 landscape height in mm
$bottomMargin = 35; // space reserved at bottom for signature section on last page
$maxY        = $pageHeight - $bottomMargin;

$pdf->SetFont('helvetica', '', 7);

foreach ($rows as $i => $row) {
    // ── Page break check ──────────────────────────────────────
    if ($y + $rowH > $maxY) {
        $pdf->AddPage();
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);
        $y = 5;

        // Repeat table header
        $y = addTableHeader($pdf, $X0, $y, $c, $PW, $G, $W);
        $pdf->SetFont('helvetica', '', 7);
    }

    $totalPcs += (int)   ($row['qty']      ?? 0);
    $totalAmt += (float) ($row['unitcost'] ?? 0);

    $reasonParts = [];
    foreach ($smis_form_columns as $col) {
        if (!empty($row[strtolower($col)])) $reasonParts[] = $col;
    }
    if (empty($reasonParts) && !empty($row['others'])) {
        $parts = explode(' - ', $row['others'], 2);
        $reasonDisplay = trim($parts[0]);
    } else {
        $reasonDisplay = implode(', ', $reasonParts);
    }

    $stripe = ($i % 2 === 1);
    $pdf->SetFillColor($stripe ? 248 : 255, $stripe ? 248 : 255, $stripe ? 248 : 255);

    $pdf->SetXY($X0, $y);
    $pdf->Cell($c['sku'],    $rowH, $row['mdccode'],         1, 0, 'C', $stripe);
    $pdf->Cell($c['desc'],   $rowH, $row['description'],     1, 0, 'L', $stripe);
    $pdf->Cell($c['size'],   $rowH, $row['size'],            1, 0, 'C', $stripe);
    $pdf->Cell($c['prod'],   $rowH, '',                      1, 0, 'C', $stripe);
    $pdf->Cell($c['exp'],    $rowH, $row['expiration'],      1, 0, 'C', $stripe);
    $pdf->Cell($c['pcs'],    $rowH, $row['qty'],             1, 0, 'C', $stripe);
    $pdf->Cell($c['kgs'],    $rowH, '',                      1, 0, 'C', $stripe);
    $pdf->Cell($c['price'],  $rowH, $row['unitcost'] ?? '',  1, 0, 'R', $stripe);
    $pdf->Cell($c['reason'], $rowH, $reasonDisplay,          1, 0, 'C', $stripe);
    $pdf->Cell($c['disp'],   $rowH, '',                      1, 0, 'C', $stripe);
    $pdf->Cell($c['as1'],    $rowH, '',                      1, 0, 'C', $stripe);
    $pdf->Cell($c['as2'],    $rowH, '',                      1, 0, 'C', $stripe);
    $pdf->Cell($c['as3'],    $rowH, '',                      1, 1, 'C', $stripe);
    $y += $rowH;
}

// ── Totals row ────────────────────────────────────────────────
$spanL = $c['sku']+$c['desc']+$c['size']+$c['prod']+$c['exp'];
$spanR = $c['reason']+$c['disp']+$c['as1']+$c['as2']+$c['as3'];
rgb($pdf, $W);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetXY($X0, $y);
$pdf->Cell($spanL,     $rowH, '',                           1, 0, 'L', false);
$pdf->Cell($c['pcs'],  $rowH, $totalPcs,                    1, 0, 'C', false);
$pdf->Cell($c['kgs'],  $rowH, '',                           1, 0, 'C', false);
$pdf->Cell($c['price'],$rowH, number_format($totalAmt, 2),  1, 0, 'R', false);
$pdf->Cell($spanR,     $rowH, '',                           1, 1, 'C', false);
$y += $rowH;

// ── Empty buffer row ──────────────────────────────────────────
rgb($pdf, $W);
$pdf->SetXY($X0, $y);
$pdf->Cell($PW, 2, '', 'LR', 1, 'C', false);

// ── "(Please sign over printed name)" ────────────────────────
$pdf->SetFont('helvetica', 'I', 6.5);
$pdf->SetXY($X0, $y);
$pdf->Cell($PW, 2, '(Please sign over printed name)', 'LBR', 1, 'C', false);
$y += 4;

// ══════════════════════════════════════════════════════════════
//  SIGNATURE SECTION
// ══════════════════════════════════════════════════════════════
$sigH = 24;

$col1W = round($PW * 0.43);
$col2W = round($PW * 0.38);
$col3W = $PW - $col1W - $col2W;

$x1 = $X0;
$x2 = $x1 + $col1W;
$x3 = $x2 + $col2W;

$rowH = 5;

// ── COLUMN 1 (LEFT) ───────────────────────────────────────────
$pdf->SetXY($x1, $y);
$pdf->Rect($x1, $y, $col1W, $sigH);

$pdf->SetFillColor(220, 220, 220);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($col1W, $rowH, 'Prepared By Sender:', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 7);
$pdf->SetX($x1);
$pdf->Cell($col1W * 0.7, $rowH, 'Position Title:', 1, 0);
$pdf->Cell($col1W * 0.3, $rowH, 'Date:', 1, 1);

$pdf->SetX($x1);
$pdf->Cell($col1W, $rowH, 'Received By Call Center / Business Center', 1, 1);

$pdf->SetX($x1);
$pdf->Cell($col1W * 0.7, $rowH, 'Position Title:', 1, 0);
$pdf->Cell($col1W * 0.3, $rowH, 'Date:', 1, 1);

// ── COLUMN 2 (MIDDLE) ─────────────────────────────────────────
$pdf->SetXY($x2, $y);
$pdf->Rect($x2, $y, $col2W, $sigH);

$pdf->SetFillColor(220, 220, 220);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell($col2W, $rowH, 'Checked By Distributor / Account Specialist', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 7);
$pdf->SetX($x2);
$pdf->Cell($col2W * 0.7, $rowH, 'Signature:', 1, 0);
$pdf->Cell($col2W * 0.3, $rowH, 'Date:', 1, 1);

$pdf->SetFont('helvetica', 'I', 6);
$pdf->SetX($x2);
$pdf->Cell($col2W, $rowH, 'Recommended by: CM (Bad Orders)/ASM (Good Stocks)', 1, 1);

$pdf->SetFont('helvetica', '', 7);
$pdf->SetX($x2);
$pdf->Cell($col2W * 0.7, $rowH, 'Signature:', 1, 0);
$pdf->Cell($col2W * 0.3, $rowH, 'Date:', 1, 1);

// ── COLUMN 3 (RIGHT) ──────────────────────────────────────────
$pdf->SetXY($x3, $y);
$pdf->Rect($x3, $y, $col3W, $sigH);

$pdf->SetFillColor(220, 220, 220);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->MultiCell($col3W, $rowH * 2, "Approval of GOOD STOCKS\nApproved by: NSM", 1, 'C', true);

$pdf->SetFont('helvetica', '', 7);
$pdf->SetX($x3);
$pdf->Cell($col3W, $rowH, 'Signature:', 1, 1);

$pdf->SetX($x3);
$pdf->Cell($col3W, $rowH, 'Date:', 1, 1);

$y += $sigH;

// ══════════════════════════════════════════════════════════════
//  HISTORY LOG
// ══════════════════════════════════════════════════════════════
$username      = $_SESSION['fname'] ?? 'Unknown';
$dateprocessed = date('Y-m-d');
$timeprocessed = date('H:i:s');
$processed     = 'EXPORT PDF for batch ' . $batchnumber;

mysqli_query($conn, "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) 
    VALUES ('$batchnumber','$username','$processed','$dateprocessed','$timeprocessed')");

// ══════════════════════════════════════════════════════════════
//  OUTPUT
// ══════════════════════════════════════════════════════════════
$filename = 'PRE_MAGNOLIA_SANMIGUEL_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $pre_no) . '.pdf';
$pdf->Output($filename, 'D');