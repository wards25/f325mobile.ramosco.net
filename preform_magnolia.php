<?php
session_start();
include_once("dbconnect.php");

$batchnumber = $_GET['batchnumber'] ?? '';
$hub = $_GET['hub'] ?? '';
$date_processed = $_GET['date_processed'] ?? date('Y-m-d');

// ══════════════════════════════════════════════════════════════
//  HUB MAPPING
// ══════════════════════════════════════════════════════════════
$hubMapping = [
  'cainta' => ['code' => '165237', 'name' => 'RAMOSCO SALES AND DIST INC - CAINTA', 'address' => '167 Felix Ave, Cainta, 1900 Rizal', 'pre_prefix' => '400'],
  'cdo' => ['code' => '165239', 'name' => 'RAMOSCO SALES AND DIST INC - CDO', 'address' => 'NEXT STEP REALTY, ZONE 5 BARRA OPOL, MIS.OR', 'pre_prefix' => '700'],
  'cebu' => ['code' => '165238', 'name' => 'RAMOSCO SALES AND DIST INC - CEBU', 'address' => 'Door 6&7 THE GREEN STRIP WAREHOUSE - Jayme St. Paknaan, Mandaue City', 'pre_prefix' => '800'],
  'davao' => ['code' => '165241', 'name' => 'RAMOSCO SALES AND DIST INC - DAVAO', 'address' => 'YLG REALTY INC. WAREHOUSE D5 KM10 MCARTHUR HIGHWAY BAGO APLAYA', 'pre_prefix' => '600'],
  'iloilo' => ['code' => '165240', 'name' => 'RAMOSCO SALES AND DIST INC - ILOILO', 'address' => 'Door A4 GOLDEN LUCK WAREHOUSE - Brgy. Ticud Lapaz, Iloilo City', 'pre_prefix' => '900'],
  'leyte' => ['code' => '171482', 'name' => 'RAMOSCO SALES AND DIST INC - LEYTE', 'address' => 'Zone Jupiter, Asia Ice Plant compound Brgy Pawing Palo Leyte', 'pre_prefix' => '300'],
  'pangasinan' => ['code' => '165242', 'name' => 'RAMOSCO SALES AND DIST INC - PANGASINAN', 'address' => '#47 McArthur Highway San Nicolas Villasis Pangasinan', 'pre_prefix' => '500'],
];

$hubKey = strtolower(trim($hub));
$hubInfo = $hubMapping[$hubKey] ?? ['code' => '', 'name' => '', 'address' => '', 'pre_prefix' => '000'];

$assigned_merchant = 'JONATHAN DAYAG';
$principal_category = $_GET['type'];
$type_short = strtoupper(substr($principal_category, 0, 3));
$hub_upper  = strtoupper($hubKey);

// ══════════════════════════════════════════════════════════════
//  SMIS COLUMN SET
// ══════════════════════════════════════════════════════════════
$smis_form_columns = ['G01', 'D04', 'D02', 'M07', 'M02'];

// ══════════════════════════════════════════════════════════════
//  QUERY
// ══════════════════════════════════════════════════════════════
$query = "
    SELECT 
        r.*, 
        p.description, 
        p.uom,
        pr.initial AS smis_code,
        pr.reason  AS reason_description
    FROM dbraw r
    LEFT JOIN dbproduct p 
        ON r.mdccode = p.mdccode
    LEFT JOIN tbl_principal_reason pr 
        ON pr.id = (
            SELECT id 
            FROM tbl_principal_reason 
            WHERE mdc_reason_initial = r.reasoncode
            ORDER BY 
                CASE WHEN r.reasoncode = 'D' THEN RAND() ELSE id END
            LIMIT 1
        )
    WHERE r.batchnumber_forpullout = '" . mysqli_real_escape_string($conn, $batchnumber) . "'
";

$result = mysqli_query($conn, $query);
if (!$result)
  die("Database query failed: " . mysqli_error($conn));

$rows = [];
while ($r = mysqli_fetch_assoc($result))
  $rows[] = $r;
mysqli_free_result($result);

function resolveFormCode(array $row, array $smis_form_columns): array
{
  $smis_code = strtoupper(trim((string) ($row['smis_code'] ?? '')));
  $formCode = in_array($smis_code, $smis_form_columns, true) ? $smis_code : 'OTHERS';
  return [$smis_code, $formCode];
}

// ══════════════════════════════════════════════════════════════
//  GENERATE PRE NO
// ══════════════════════════════════════════════════════════════
$yy = date('y');
$mm = date('m');
$prefix = $hubInfo['pre_prefix'];

$seq_pat = mysqli_real_escape_string($conn, "$yy-$mm-{$prefix}%");

$principal_upper = strtoupper(trim($principal_category));

// Magnolia + San Miguel share one sequence
$isSharedSeries =
    $principal_upper === 'MAGNOLIA INC.' ||
    $principal_upper === 'SAN MIGUEL SUPER COFFEEMIX CO., INC.';

if ($isSharedSeries) {

    $seq_r = mysqli_query($conn, "
        SELECT pre_no
        FROM tbl_preform
        WHERE pre_no LIKE '$seq_pat'
        AND (
            UPPER(category) = 'MAGNOLIA INC.'
            OR UPPER(category) = 'SAN MIGUEL SUPER COFFEEMIX CO., INC.'
        )
        ORDER BY id DESC
        LIMIT 1
    ");

} else {

    // PUREFOODS and other categories get separate numbering
    $cat_esc = mysqli_real_escape_string($conn, $principal_category);

    $seq_r = mysqli_query($conn, "
        SELECT pre_no
        FROM tbl_preform
        WHERE pre_no LIKE '$seq_pat'
        AND category = '$cat_esc'
        ORDER BY id DESC
        LIMIT 1
    ");
}

$next_seq = 1;

if ($seq_r && mysqli_num_rows($seq_r) > 0) {

    $last = mysqli_fetch_assoc($seq_r)['pre_no'];

    $next_seq = (int) substr(
        $last,
        strrpos($last, $prefix) + strlen($prefix)
    ) + 1;
}

$pre_no = sprintf('%s-%s-%s%d', $yy, $mm, $prefix, $next_seq);

// ══════════════════════════════════════════════════════════════
//  SAVE TO tbl_preform
// ══════════════════════════════════════════════════════════════
$bn_esc = mysqli_real_escape_string($conn, $batchnumber);
$chk_r = mysqli_query($conn, "SELECT id FROM tbl_preform WHERE batchnumber='$bn_esc' LIMIT 1");

if ($chk_r && mysqli_num_rows($chk_r) === 0 && count($rows) > 0) {
  
  $stmt = $conn->prepare(
    "INSERT INTO tbl_preform 
       (batchnumber, pre_no, hub, category, business_name, address, customer_code,
        assigned_merchandiser, mdccode, description, size, expiration, qty, unitcost, costextended,
        g01, d04, d02, m07, m02, others, date_processed)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
  );
  if (!$stmt) die("Prepare failed: " . $conn->error);

  foreach ($rows as $row) {
    [$smis_code, $formCode] = resolveFormCode($row, $smis_form_columns);

    $g01 = ($formCode === 'G01') ? 1 : 0;
    $d04 = ($formCode === 'D04') ? 1 : 0;
    $d02 = ($formCode === 'D02') ? 1 : 0;
    $m07 = ($formCode === 'M07') ? 1 : 0;
    $m02 = ($formCode === 'M02') ? 1 : 0;

    $others_text = '';
    if ($formCode === 'OTHERS') {
      $others_text = $smis_code
        ? $smis_code . ' - ' . ($row['reason_description'] ?? '')
        : ($row['reason_description'] ?? '');
    }

    $size = '';
    if (preg_match('/(\d+\s*(?:g|kg|ml|l|oz|pcs))/i', $row['description'] ?? '', $m2)) {
      $size = $m2[1];
    } else {
      $size = $row['uom'] ?? '';
    }

    $batchnum      = $batchnumber;
    $category      = $principal_category;
    $location      = $hub;
    $business_name = $hubInfo['name'];
    $address       = $hubInfo['address'];
    $cust_code     = $hubInfo['code'];
    $merch         = $assigned_merchant;
    $mdccode       = $row['mdccode'];
    $description   = $row['description'] ?? '';
    $expiration    = $row['expiration']   ?? '';
    $quantity      = $row['forpullout']     ?? 0;
    $date_proc     = $date_processed;
    $unitcost = $row['unitcost'] ?? 0;
    $costextended = $row['costextended'] ?? 0;

    $stmt->bind_param('ssssssssssssiddiiiiiss',
      $batchnum, $pre_no, $location, $category, $business_name, $address,
      $cust_code, $merch, $mdccode, $description, $size, $expiration, $quantity, $unitcost, $costextended,
      $g01, $d04, $d02, $m07, $m02, $others_text, $date_proc
    );
    if (!$stmt->execute()) error_log("PRE SAVE ERROR | mdccode={$mdccode} | " . $stmt->error);
  }

  // History log
  $username      = $_SESSION['fname'] ?? 'Unknown';
  $dateprocessed = date('Y-m-d');
  $timeprocessed = date('H:i:s');
  $processed     = 'PRINT PREFORM (MAGNOLIA) for batch ' . $batchnumber;
  mysqli_query($conn, "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) 
    VALUES ('$batchnumber','$username','$processed','$dateprocessed','$timeprocessed')");

  $stmt->close();
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>SMIS Format – Product Returns Evaluation</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 8.5px;
      background: #fff;
      color: #000;
    }

    /* ── OUTER WRAPPER ── */
    .page-wrap {
      width: 100%;
      padding: 5mm;
    }

    /* ── ALL TABLES base ── */
    table {
      border-collapse: collapse;
      width: 100%;
    }

    td,
    th {
      border: 1px solid #000;
      padding: 2px 4px;
      vertical-align: middle;
      font-size: 8.5px;
    }

    /* ══ HEADER TABLE ══ */
    .header-table td {
      padding: 2px 4px;
    }

    .logo-cell {
      width: 90px;
      text-align: center;
      vertical-align: middle;
      padding: 3px !important;
    }

    .logo-cell img {
      width: 80px;
    }

    .title-cell {
      text-align: center;
      font-weight: bold;
      font-size: 12px;
      line-height: 1.4;
      vertical-align: middle;
      width: 200px;
    }

    .date-prepared-label {
      font-weight: bold;
      font-size: 8px;
      white-space: nowrap;
      background: #fff;
      width: 85px;
    }

    .date-prepared-value {
      font-size: 8.5px;
      min-width: 80px;
    }

    .porf-label {
      font-weight: bold;
      font-size: 8px;
      background: #fff;
      white-space: nowrap;
      width: 85px;
    }

    .porf-value {
      font-size: 8.5px;
    }

    .pre-no-label {
      font-weight: bold;
      font-size: 7.5px;
      background: #f0f0f0;
      white-space: nowrap;
    }

    .pre-no-value {
      font-size: 8.5px;
      font-weight: bold;
    }

    /* ══ INFO ROWS ══ */
    .lbl {
      background: #f0f0f0;
      font-weight: bold;
      font-size: 8px;
      white-space: nowrap;
    }

    /* ══ SECTION BANNER ══ */
    .section-banner td {
      background: #d0d0d0;
      text-align: center;
      font-weight: bold;
      font-size: 10px;
      padding: 2px;
    }

    .fill-note {
      font-size: 7.5px;
      font-style: italic;
      padding: 2px 2px 1px 2px;
      border-left: 1px solid #000;
      border-right: 1px solid #000;
      background: #fff;
    }

    /* ══ MAIN DETAIL TABLE ══ */
    .main-table th {
      background-color: #e8e8e8;
      font-weight: bold;
      font-size: 8px;
      text-align: center;
      padding: 2px 2px;
      line-height: 1.2;
    }

    .main-table td {
      font-size: 8.5px;
      text-align: center;
    }

    .main-table td.left {
      text-align: left;
    }

    .main-table tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .main-table .total-row td {
      font-weight: bold;
      background: #f0f0f0;
    }

    /* ══ PLEASE SIGN NOTE ══ */
    .sign-note-row td {
      text-align: center;
      font-size: 8px;
      font-style: italic;
      border-left: 1px solid #000;
      border-right: 1px solid #000;
      border-top: none;
      border-bottom: none;
      padding: 2px;
      background: #fff;
    }

    /* ══ SIGNATURE SECTION ══ */
    .sig-outer {
      margin-top: 0;
    }

    .sig-outer>td {
      padding: 0;
      vertical-align: top;
      border: none;
    }

    .sig-block {
      border: 1px solid #000;
      padding: 3px 5px 4px 5px;
      font-size: 8px;
      height: 100%;
    }

    .sig-block .prepared-name {
      font-weight: bold;
      font-size: 8.5px;
      border-bottom: 1px solid #000;
      padding-bottom: 1px;
      margin-bottom: 1px;
      min-height: 13px;
    }

    .sig-block .role-label {
      font-size: 7.5px;
      margin-bottom: 4px;
    }

    .sig-block .field-row {
      font-size: 7.5px;
      display: flex;
      align-items: flex-end;
      gap: 3px;
      margin-top: 3px;
    }

    .sig-block .field-row .underline {
      border-bottom: 1px solid #000;
      flex: 1;
      min-width: 40px;
      display: inline-block;
      height: 10px;
    }

    .sig-block .section-divider {
      font-size: 7.5px;
      border-top: 1px solid #ccc;
      padding-top: 3px;
      margin-top: 6px;
    }

    .checked-block {
      border: 1px solid #000;
      padding: 3px 5px 4px 5px;
      font-size: 8px;
      height: 100%;
    }

    .checked-block .cb-header {
      font-weight: bold;
      font-size: 8px;
      text-align: center;
      margin-bottom: 4px;
    }

    .checked-block .field-row {
      font-size: 7.5px;
      display: flex;
      align-items: flex-end;
      gap: 3px;
      margin-top: 3px;
    }

    .checked-block .field-row .underline {
      border-bottom: 1px solid #000;
      flex: 1;
      min-width: 40px;
      display: inline-block;
      height: 10px;
    }

    .checked-block .italic-note {
      font-style: italic;
      font-size: 7.5px;
      margin-top: 8px;
      margin-bottom: 2px;
    }

    .approval-block {
      border: 1px solid #000;
      padding: 0;
      font-size: 8px;
      height: 100%;
    }

    .approval-block .appr-header {
      font-weight: bold;
      font-size: 8.5px;
      text-align: center;
      background: #e0e0e0;
      padding: 2px;
      border-bottom: 1px solid #000;
    }

    .approval-block .appr-body {
      padding: 4px 5px;
    }

    .approval-block .field-row {
      font-size: 7.5px;
      display: flex;
      align-items: flex-end;
      gap: 3px;
      margin-top: 4px;
    }

    .approval-block .field-row .underline {
      border-bottom: 1px solid #000;
      flex: 1;
      min-width: 40px;
      display: inline-block;
      height: 10px;
    }

    .approval-block .italic-note {
      font-style: italic;
      font-size: 7.5px;
      margin-top: 1px;
    }

    @media print {
      @page {
        size: landscape;
        margin: 5mm;
      }

      body {
        padding: 0;
      }

      .page-wrap {
        padding: 0;
      }
    }

    .title-cell {
      text-align: left;
      font-weight: bold;
      font-size: 13px;
      line-height: 1.4;
      vertical-align: middle;
      padding-left: 15px;
    }
  </style>
</head>

<body onload="window.print()">
  <div class="page-wrap">

    <!-- ══ HEADER TABLE ══ -->
    <table class="header-table">
      <tr>
        <!-- Logo -->
        <td class="logo-cell" rowspan="2">
          <img src="img/smis-logo.png" alt="SMIS Logo">
        </td>
        <!-- Title -->
        <td class="title-cell" rowspan="2" style="text-align:left; padding-left:15px;">
          PRODUCT RETURNS<br>EVALUATION
        </td>
        <!-- Date Prepared -->
        <td class="date-prepared-label">DATE PREPARED:</td>
        <td class="date-prepared-value"><?= htmlspecialchars($date_processed) ?></td>
        <!-- Required Pull Out -->
        <td class="porf-label">Required Pull Out</td>
        <td class="porf-value">&nbsp; </td>
      </tr>
      <tr>
        <!-- Outlet RTV / PRE No -->
        <td class="pre-no-label">Outlet's RTV / PRE No:</td>
        <td><strong><?= htmlspecialchars($pre_no . ' ' . $type_short . ' ' . $hub_upper) ?></strong></td>
        <!-- Empty filler for PORF col -->
        <td>&nbsp; PORF No: </td>
        <td><strong><?= htmlspecialchars($pre_no) ?></strong></td>
      </tr>
    </table>

    <!-- ══ SENDER / DISTRIBUTOR ROW ══ -->
    <table>
      <tr>
        <td class="lbl" style="width:11%">Sender's Name:<br>(Distributor/Customer)</td>
        <td style="width:22%; font-weight:bold;"><?= htmlspecialchars($hubInfo['name']) ?></td>
        <td class="lbl" style="width:10%">Sender's Address:</td>
        <td style="width:24%"><?= htmlspecialchars($hubInfo['address']) ?></td>
        <td style="width:13%; padding:0;">
          <table style="width:100%; height:100%; border-collapse:collapse;">
            <tr>
              <td class="lbl" style="text-align:center; border:none; border-bottom:1px solid #000;">
                Distributor / Customer Code:
              </td>
            </tr>
            <tr>
              <td style="text-align:center; border:none;">
                <?= htmlspecialchars($hubInfo['code']) ?>
              </td>
            </tr>
          </table>
        </td>
        <td class="lbl" style="width:7%; text-align:center;">
          <span style="display:block; text-align:left; font-weight:normal;">Name of DS/AS assigned/ Code:</span>
          <?= htmlspecialchars($assigned_merchant) ?>
        </td>
      </tr>
    </table>

    <!-- ══ TYPE OF RETURN ══ -->
    <table>
      <tr>
        <td class="lbl" style="width:8%">Type of Return:</td>
        <td style="width:17%">&#9744; TR Ordinary Pull Out</td>
        <td style="width:18%">&#9744; TR Application for CM</td>
        <td style="width:13%">&#9744; Product Recall</td>
        <td style="width:13%">&#9744; On-site Condemn</td>
        <td style="width:11%">&#9744; Good Stocks</td>
        <td>&#9744; Others (Specify) _______________</td>
      </tr>
    </table>

    <!-- ══ CONCERNED BUSINESS UNIT ══ -->
    <table>
      <tr>
        <td class="lbl" style="width:12%">Concerned Business Unit:</td>
        <td style="width:8%">&#9744; Mag-BMC</td>
        <td style="width:7%">&#9744; Mag-IC</td>
        <td style="width:9%">&#9744; Mag-Others</td>
        <td style="width:8%">&#9744; PHC-GP</td>
        <td style="width:8%">&#9744; PHC-RM</td>
        <td style="width:8%">&#9744; SMSCCI</td>
        <td style="width:6%">&#9744; SMMI</td>
        <td>&#9744; Others (Specify) _______________</td>
      </tr>
    </table>

    <!-- ══ DETAILS OF RETURN BANNER ══ -->
    <table class="section-banner">
      <tr>
        <td>DETAILS OF RETURN</td>
      </tr>
    </table>

    <!-- ══ MAIN DETAIL TABLE ══ -->
    <table class="main-table">
      <thead>
        <!-- ROW 1: fill note + Receiving -->
        <tr>
          <td colspan="10" style="border:none; border-left:1px solid #000; border-top:1px solid #000;
        font-size:7.5px; font-style:italic; padding:2px 3px; background:#fff; text-align:left;">
            To be filled up by Distributor / Account Specialist / TPA :
          </td>
          <td colspan="3"
            style="font-size:7.5px; font-weight:bold; text-align:center; padding:2px 3px; background:#fff;">
            Receiving
          </td>
        </tr>
        <!-- ROW 2: column headers -->
        <tr>
          <th rowspan="2" style="width:7%;">SKU CODE</th>
          <th rowspan="2" style="width:26%;">Product Description (SKU)</th>
          <th rowspan="2" style="width:4%;">Size</th>
          <th rowspan="2" style="width:7%;">Production<br>Code or<br>Date</th>
          <th rowspan="2" style="width:8%;">Expiry Date</th>
          <th colspan="2">Quantity</th>
          <th rowspan="2" style="width:6%;">Unit<br>Price</th>
          <th rowspan="2" style="width:6%;">Reason<br>Code</th>
          <th rowspan="2" style="width:9%;">Disposition /<br>Remarks</th>
          <th rowspan="2" colspan="3" style="width:11%;">AS/DS/TQA<br>Verification<br>Remarks</th>
        </tr>
        <!-- ROW 3: pcs / kgs only -->
        <tr>
          <th style="width:5%;">pcs</th>
          <th style="width:5%;">kgs</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $totalPcs = 0;
        $totalUnit = 0;

        if (count($rows) > 0):
          foreach ($rows as $row):
            $totalPcs += (int) ($row['forpullout'] ?? 0);
            $totalUnitCost += (float) ($row['unitcost'] ?? 0);

            [$smis_code, $formCode] = resolveFormCode($row, $smis_form_columns);
            $reasonDisplay = $smis_code ?: ($row['reason_description'] ?? '');

            $size = '';
            if (preg_match('/(\d+\s*(?:g|kg|ml|l|oz|pcs))/i', $row['description'] ?? '', $m2)) {
              $size = htmlspecialchars($m2[1]);
            } else {
              $size = htmlspecialchars($row['uom'] ?? '');
            }
            ?>
            <tr>
              <td><?= htmlspecialchars($row['mdccode']) ?></td>
              <td class="left"><?= htmlspecialchars($row['description']) ?></td>
              <td><?= $size ?></td>
              <td></td>
              <td><?= htmlspecialchars($row['expiration']) ?></td>
              <td><?= htmlspecialchars($row['forpullout']) ?></td>
              <td></td>
              <td style="text-align:right;"><?= htmlspecialchars($row['unitcost'] ?? '') ?></td>
              <td><?= htmlspecialchars($reasonDisplay) ?></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
          <?php endforeach; else: ?>
          <tr>
            <td colspan="11" style="text-align:center; padding:6px;">No records found.</td>
          </tr>
        <?php endif; ?>

        <!-- TOTAL ROW -->
        <tr class="total-row">
          <td colspan="5" style="text-align:right; font-size:8px;"></td>
          <td style="text-align:center;"><?= $totalPcs ?></td>
          <td></td>
          <td style="text-align:right;"><?= number_format($totalUnitCost, 2) ?></td>
          <td colspan="5"></td>
        </tr>
      </tbody>
    </table>

    <!-- ══ PLEASE SIGN NOTE ══ -->
    <table>
      <tr>
        <td style="text-align:center; font-size:8px; font-style:italic; border:1px solid #000; border-bottom:none;">
          (Please sign over printed name)
        </td>
      </tr>
    </table>

    <!-- ══ SIGNATURE SECTION (FIXED) ══ -->
    <table style="width:100%; border-collapse:collapse;">
      <tr>

        <!-- LEFT BLOCK -->
        <td style="width:42%; border:1px solid #000; vertical-align:top;">
          <table style="width:100%; border-collapse:collapse;">

            <tr>
              <td colspan="1" style="font-size:10px; padding:3px;">
                Prepared By Sender:
              </td>
              <td colspan="3"><b></b></td>
            </tr>

            <tr>
              <td style="width:25%; font-size:8px;">Position Title:</td>
              <td style="border-bottom:1px solid #000;"></td>
              <td style="width:15%; font-size:8px;">Date:</td>
            </tr>

            <tr>
              <td colspan="4" style="padding-top:6px; font-size:8px;">
                Received By Call Center / Business Center
              </td>
            </tr>

            <tr>
              <td style="font-size:8px;">Position Title:</td>
              <td style="border-bottom:1px solid #000;"></td>
              <td style="font-size:8px;">Date:</td>
            </tr>

          </table>
        </td>

        <!-- MIDDLE BLOCK -->
        <td style="width:37%; border:1px solid #000; vertical-align:top;">
          <table style="width:100%; border-collapse:collapse;">

            <tr>
              <td colspan="4" style="text-align:center; font-size:8px; font-weight:bold; padding:3px;">
                Checked By Distributor / Account Specialist
              </td>
            </tr>

            <tr>
              <td style="width:20%; font-size:8px;">Signature:</td>
              <td style="border-bottom:1px solid #000;"></td>
              <td style="width:15%; font-size:8px;">Date:</td>
            </tr>

            <tr>
              <td colspan="4" style="font-size:7.5px; padding-top:5px;">
                <i>Recommended by: CM (Bad Orders)/ASM (Good Stocks)</i>
              </td>
            </tr>

            <tr>
              <td style="font-size:8px;">Signature:</td>
              <td style="border-bottom:1px solid #000;"></td>
              <td style="font-size:8px;">Date:</td>
            </tr>

          </table>
        </td>

        <!-- RIGHT BLOCK -->
        <td style="width:21%; border:1px solid #000; vertical-align:top;">
          <table style="width:100%; border-collapse:collapse;">

            <tr>
              <td colspan="2" style="text-align:center; font-weight:bold; font-size:8px; background:#e0e0e0;">
                Approval of GOOD STOCKS <br>Approved by: NSM
              </td>
            </tr>
            <tr>
              <td style="font-size:8px;">Signature:</td>
            </tr>

            <tr>
              <td style="font-size:8px;">Date:</td>
            </tr>

          </table>
        </td>

      </tr>
    </table>

  </div><!-- end page-wrap -->
</body>

</html>