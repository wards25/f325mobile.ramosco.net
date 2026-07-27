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

$assigned_merchant = 'KEVIN DUMANGLAS';

// ══════════════════════════════════════════════════════════════
//  SMIS COLUMN SET
// ══════════════════════════════════════════════════════════════
$smis_form_columns = ['G01', 'D04', 'D02', 'M07', 'M02'];


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
                CASE 
                    WHEN r.reasoncode = 'D' THEN RAND()
                    ELSE id
                END
            LIMIT 1
        )
    WHERE r.batchnumber_forpullout = '" . mysqli_real_escape_string($conn, $batchnumber) . "'
";

$result = mysqli_query($conn, $query);
if (!$result) {
  die("Database query failed: " . mysqli_error($conn));
}

// Load ALL rows into memory NOW — query never runs again after this
$rows = [];
while ($r = mysqli_fetch_assoc($result)) {
  $rows[] = $r;
}
mysqli_free_result($result);

function resolveFormCode(array $row, array $smis_form_columns): array
{
  $smis_code = strtoupper(trim((string) ($row['smis_code'] ?? '')));
  $formCode = in_array($smis_code, $smis_form_columns, true) ? $smis_code : 'OTHERS';
  return [$smis_code, $formCode];
}

// ══════════════════════════════════════════════════════════════
//  GENERATE PRE NO  –  YY-MM-{prefix}{sequence}
// ══════════════════════════════════════════════════════════════
$yy = date('y');
$mm = date('m');
$prefix = $hubInfo['pre_prefix'];

$seq_pattern = mysqli_real_escape_string($conn, "$yy-$mm-{$prefix}%");
$seq_result = mysqli_query($conn, "SELECT pre_no FROM tbl_preform WHERE pre_no LIKE '$seq_pattern' AND category = 'PUREFOODS' ORDER BY id DESC LIMIT 1");

$next_seq = 1;
if ($seq_result && mysqli_num_rows($seq_result) > 0) {
  $last_row = mysqli_fetch_assoc($seq_result);
  $last_pre = $last_row['pre_no'];
  $last_suffix = (int) substr($last_pre, strrpos($last_pre, $prefix) + strlen($prefix));
  $next_seq = $last_suffix + 1;
}

$pre_no = sprintf('%s-%s-%s%d', $yy, $mm, $prefix, $next_seq);

// ══════════════════════════════════════════════════════════════
//  insert to tbl_preform
// ══════════════════════════════════════════════════════════════
$already_saved_q = "SELECT id FROM tbl_preform 
                    WHERE batchnumber = '" . mysqli_real_escape_string($conn, $batchnumber) . "'
                    LIMIT 1";
$already_saved_r = mysqli_query($conn, $already_saved_q);

if ($already_saved_r && mysqli_num_rows($already_saved_r) === 0 && count($rows) > 0) {

  $stmt = $conn->prepare(
    "INSERT INTO tbl_preform 
            (batchnumber, pre_no, hub, category, business_name, address, customer_code,
             assigned_merchandiser, mdccode, description, size, expiration, qty,
             g01, d04, d02, m07, m02, others, date_processed)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
  );
  if (!$stmt) {
    die("Prepare failed: " . $conn->error);

  }

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
    if (preg_match('/(\d+\s*(?:g|kg|ml|l|oz|pcs))/i', $row['description'] ?? '', $m)) {
      $size = $m[1];
    } else {
      $size = $row['uom'] ?? '';
    }

    $batchnum = $batchnumber;
    $category = 'PUREFOODS';
    $location = $hub;
    $business_name = $hubInfo['name'];
    $address = $hubInfo['address'];
    $cust_code = $hubInfo['code'];
    $merch = $assigned_merchant;
    $mdccode = $row['mdccode'];
    $description = $row['description'] ?? '';
    $expiration = $row['expiration'] ?? '';
    $quantity = $row['forpullout'] ?? 0;
    $date_proc = $date_processed;

    // 11 strings + 5 ints + 2 strings = 18 params
    $stmt->bind_param(
      'ssssssssssssiiiiiiss',
      $batchnum,
      $pre_no,
      $location,
      $category,
      $business_name,
      $address,
      $cust_code,
      $merch,
      $mdccode,
      $description,
      $size,
      $expiration,
      $quantity,
      $g01,
      $d04,
      $d02,
      $m07,
      $m02,
      $others_text,
      $date_proc
    );

    if (!$stmt->execute()) {
      error_log("PRE SAVE ERROR | mdccode={$mdccode} | " . $stmt->error);
    }
  }
  // ===== INSERT HISTORY LOG =====
    $username = $_SESSION['fname'] ?? 'Unknown';
    $dateprocessed = date('Y-m-d');
    $timeprocessed = date('H:i:s');
    $processed = 'PRINT PREFORM for batch ' . $batchnumber;

    mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) 
    VALUES ('$batchnumber','$username','$processed','$dateprocessed','$timeprocessed')");

  $stmt->close();
}
?>
<!DOCTYPE html>
<html>

<head>
  <title>SMIS Format - Product Returns Evaluation</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      padding: 8mm;
      background: #fff;
      color: #000;
    }

    .header-wrap {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 3px;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .header-left img {
      width: 100px;
    }

    .header-title {
      font-weight: bold;
      font-size: 15px;
      letter-spacing: 0.5px;
    }

    .header-right table {
      border-collapse: collapse;
    }

    .header-right td {
      border: 1px solid #000;
      padding: 2px 5px;
      font-size: 10px;
    }

    .note-text {
      font-size: 8.5px;
      font-style: italic;
      margin-bottom: 4px;
    }

    .section {
      margin-top: 4px;
    }

    table.info-table {
      width: 100%;
      border-collapse: collapse;
    }

    table.info-table td {
      border: 1px solid #000;
      padding: 2px 4px;
      font-size: 10px;
    }

    .return-type-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4px;
    }

    .return-type-table td {
      border: 1px solid #000;
      padding: 2px 6px;
      font-size: 10px;
    }

    table.main-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 4px;
    }

    table.main-table th,
    table.main-table td {
      border: 1px solid #000;
      padding: 2px 3px;
      font-size: 9px;
      text-align: center;
      vertical-align: middle;
    }

    table.main-table td.left {
      text-align: left;
    }

    table.main-table thead tr th {
      background-color: #e8e8e8;
      font-weight: bold;
    }

    table.main-table tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .sig-section {
      margin-top: 10px;
    }

    .sig-section .sig-label {
      font-size: 9px;
      font-weight: bold;
      margin-bottom: 2px;
    }

    table.sig-table {
      width: 100%;
      border-collapse: collapse;
    }

    table.sig-table td {
      border: none;
      padding: 2px 6px;
      text-align: center;
      font-size: 10px;
      width: 33.33%;
      vertical-align: bottom;
    }

    table.sig-table .sig-line {
      border-bottom: 1px solid #000;
      height: 22px;
      display: block;
    }

    table.sig-table .sig-role {
      font-size: 9px;
      text-align: center;
    }

    .notes-section {
      margin-top: 10px;
      font-size: 8.5px;
    }

    .notes-section b {
      font-size: 9px;
    }

    @media print {
      @page {
        size: landscape;
        margin: 8mm;
      }

      body {
        padding: 0;
      }
    }
  </style>
</head>

<body onload="window.print()">

  <!-- HEADER -->
  <div class="header-wrap">
    <div class="header-left">
      <img src="img/smis-logo.png" alt="SMIS Logo">
      <div class="header-title">PRODUCT RETURNS EVALUATION</div>
    </div>
    <div class="header-right">
      <table>
        <tr>
          <td><b>Date Prepared:</b></td>
          <td style="min-width:120px;"><?= htmlspecialchars($date_processed) ?></td>
          <td><b>RDO No:</b></td>
          <td style="min-width:80px;"></td>
        </tr>
        <tr>
          <td><b>Outlet's RTV or Returns Document Ref No:</b></td>
          <td><?= htmlspecialchars($pre_no . ' PUREFOODS ' . $hub) ?></td>
          <td><b>PRE No:</b></td>
          <td><?= htmlspecialchars($pre_no) ?></td>
        </tr>
      </table>
    </div>
  </div>

  <div class="note-text">
    <i>NOTE: The PRE is a mandatory SMIS form for product returns processing. No trade returns maybe pulled-out without
      evaluation by authorized SMIS representatives.</i>
  </div>

  <!-- CUSTOMER DETAILS -->
  <table class="info-table section">
    <tr>
      <td colspan="3" style="border:none; padding:2px 0;"><u><b>Requesting Customer Details</b></u></td>
      <td colspan="2" style="border:none; padding:2px 0;"><u><b>SMIS Authorized Representative Details:</b></u></td>
    </tr>
    <tr>
      <td style="width:14%"><b>Business Name:</b></td>
      <td style="width:32%"><?= htmlspecialchars($hubInfo['name']) ?></td>
      <td style="width:10%"><b>Customer: </b><?= htmlspecialchars($hubInfo['code']) ?></td>
      <td style="width:22%">Assigned Salesman (SAS):</td>
      <td style="width:22%"></td>
    </tr>
    <tr>
      <td><b>Customer Outlet Addr:</b></td>
      <td colspan="2"><?= htmlspecialchars($hubInfo['address']) ?></td>
      <td>Assigned Merchandiser:</td>
      <td><?= htmlspecialchars($assigned_merchant) ?></td>
    </tr>
  </table>

  <!-- TYPE OF RETURN -->
  <table class="return-type-table">
    <tr>
      <td style="width:12%"><b>Type of Return:</b></td>
      <td style="width:12%; text-align:center">☑ Valid BO</td>
      <td style="width:16%; text-align:center">□ Product Retrieval</td>
      <td style="width:16%; text-align:center">□ For Investigation</td>
      <td style="width:16%; text-align:center">□ Stock Transfer</td>
      <td style="text-align:left">□ Others (Specify) _____________________</td>
    </tr>
  </table>

  <!-- MAIN TABLE -->
  <table class="main-table section">
    <thead>
      <tr>
        <th rowspan="3">Product Bar Code</th>
        <th rowspan="3">Product Description (SKU)</th>
        <th rowspan="3">Size</th>
        <th rowspan="3">Production Code or Date</th>
        <th rowspan="3">Expiry Date</th>
        <th colspan="2">Details of Return</th>
        <th colspan="6">Reason Code (Check applicable box)</th>
        <th rowspan="3">Disposition / Remarks</th>
      </tr>
      <tr>
        <th colspan="2">Quantity</th>
        <th rowspan="2">G01<br>(Expired<br>NEX)</th>
        <th rowspan="2">D04<br>(Torn/<br>Damaged<br>Labels)</th>
        <th rowspan="2">D02<br>(Dented/<br>Deformed)</th>
        <th rowspan="2">M07<br>(No Vacuum)</th>
        <th rowspan="2">M02<br>(Weak /<br>Burnt<br>Seal)</th>
        <th rowspan="2">Others (Specify Code)</th>
      </tr>
      <tr>
        <th>pcs</th>
        <th>kgs</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $totalPcs = 0;

      if (count($rows) > 0):
        // ★ Loop over $rows — same in-memory array used by the save block above
        foreach ($rows as $row):
          $totalPcs += (int) ($row['forpullout'] ?? 0);

          [$smis_code, $formCode] = resolveFormCode($row, $smis_form_columns);

          $g01 = ($formCode === 'G01') ? '✔' : '';
          $d04 = ($formCode === 'D04') ? '✔' : '';
          $d02 = ($formCode === 'D02') ? '✔' : '';
          $m07 = ($formCode === 'M07') ? '✔' : '';
          $m02 = ($formCode === 'M02') ? '✔' : '';

          $others = '';
          if ($formCode === 'OTHERS') {
            $othersText = $smis_code
              ? $smis_code . ' - ' . ($row['reason_description'] ?? '')
              : ($row['reason_description'] ?? '');
            $others = htmlspecialchars($othersText);
          }

          $size = '';
          if (preg_match('/(\d+\s*(?:g|kg|ml|l|oz|pcs))/i', $row['description'] ?? '', $m)) {
            $size = htmlspecialchars($m[1]);
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
            <td><?= $g01 ?></td>
            <td><?= $d04 ?></td>
            <td><?= $d02 ?></td>
            <td><?= $m07 ?></td>
            <td><?= $m02 ?></td>
            <td><?= $others ?></td>
            <td></td>
          </tr>
        <?php endforeach; else: ?>
        <tr>
          <td colspan="14" style="text-align:center; padding:6px;">No records found.</td>
        </tr>
      <?php endif; ?>

      <!-- TOTAL ROW -->
      <tr>
        <td colspan="5" style="text-align:right; font-weight:bold;"></td>
        <td style="font-weight:bold;"><?= $totalPcs ?></td>
        <td colspan="8"></td>
      </tr>
    </tbody>
  </table>

  <!-- FIRST SIGNATURE BLOCK -->
  <div class="sig-section" style="margin-top:14px;">
    <table class="sig-table">
      <tr>
        <td><span class="sig-line"></span></td>
        <td><span class="sig-line"></span></td>
        <td><span class="sig-line"></span></td>
      </tr>
      <tr>
        <td>Prepared:<br><span class="sig-role">Merchandiser</span></td>
        <td>Confirmed:<br><span class="sig-role">Customer Representative</span></td>
        <td>Checked:<br><span class="sig-role">Coordinator</span></td>
      </tr>
    </table>
  </div>

  <!-- TRADE RETURNS / INVESTIGATION -->
  <div class="sig-section" style="margin-top:10px;">
    <div class="sig-label">TRADE RETURNS CASES FOR INVESTIGATION ONLY:</div>
    <table class="sig-table">
      <tr>
        <td><span class="sig-line"></span></td>
        <td><span class="sig-line"></span></td>
        <td><span class="sig-line"></span></td>
      </tr>
      <tr>
        <td>Endorsed:<br><span class="sig-role">TQA/ASM/RSM</span></td>
        <td>Approved:<br><span class="sig-role">ANSM/SM</span></td>
        <td>Approved:<br><span class="sig-role">NSM</span></td>
      </tr>
    </table>
  </div>

  <!-- STOCK TRANSFER -->
  <div class="sig-section" style="margin-top:10px;">
    <div class="sig-label">FOR STOCK TRANSFER ONLY:</div>
    <table class="sig-table">
      <tr>
        <td><span class="sig-line"></span></td>
        <td><span class="sig-line"></span></td>
        <td><span class="sig-line"></span></td>
      </tr>
      <tr>
        <td>Endorsed:<br><span class="sig-role">ASM/RSM</span></td>
        <td>Approved:<br><span class="sig-role">ANSM/SM</span></td>
        <td>Approved:<br><span class="sig-role">NSM</span></td>
      </tr>
    </table>
  </div>

  <!-- NOTE TO BUSINESS CENTER -->
  <div class="notes-section">
    <b>NOTE TO BUSINESS CENTER:</b><br>
    1. For manual trade returns booking of regular BO cases, no PRE shall be processed if form is incomplete, unless
    with express consent (electronic or in writing) of the assigned salesman's direct manager.<br>
    2. For automated/B2B trade returns booking, no request shall be processed for booking if without manual PRE
    reference, unless with express consent (electronic or in writing) of the assigned salesman's direct manager.<br>
    <i>MF INTERNAL USE</i>
  </div>

</body>

</html>