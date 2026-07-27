<?php
session_start();
include_once("dbconnect.php");

$batchnumber = $_GET['batchnumber'] ?? '';

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

// ══════════════════════════════════════════════════════════════
//  QUERY FROM tbl_preform  (all rows for this pre_no)
// ══════════════════════════════════════════════════════════════
$bn_esc = mysqli_real_escape_string($conn, $batchnumber);

// Accept either pre_no or batchnumber as the lookup key

$result = mysqli_query($conn, "SELECT * FROM tbl_preform WHERE batchnumber = '$batchnumber' ORDER BY id ASC");
if (!$result)
  die("Database query failed: " . mysqli_error($conn));

$rows = [];
while ($r = mysqli_fetch_assoc($result))
  $rows[] = $r;
mysqli_free_result($result);

if (empty($rows))
  die("No records found for this PRE No / Batch.");

// ── Derive header values from the first row ──────────────────
$first = $rows[0];
$pre_no = $first['pre_no'];            // use the saved one
$batchnumber = $first['batchnumber'];
$date_processed = $first['date_processed'];
$hub = strtolower(trim($first['hub']));
$hubInfo = $hubMapping[$hub] ?? ['code' => $first['customer_code'], 'name' => $first['business_name'], 'address' => $first['address'], 'pre_prefix' => '000'];
$assigned_merchant = $first['assigned_merchandiser'];
$type_short = strtoupper(substr($_GET['type'] ?? '', 0, 3));
$hub_upper  = strtoupper($hub);
$smis_form_columns = ['G01', 'D04', 'D02', 'M07', 'M02'];
?>
<!DOCTYPE html>
<html>

<head>
  <title>SMIS Format – Product Returns Evaluation (MAGNOLIA) — REPRINT</title>
  <style>
    /* ── paste your exact same <style> block here ── */
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

    .page-wrap {
      width: 100%;
      padding: 5mm;
    }

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
      text-align: left;
      font-weight: bold;
      font-size: 13px;
      line-height: 1.4;
      vertical-align: middle;
      padding-left: 15px;
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

    .lbl {
      background: #f0f0f0;
      font-weight: bold;
      font-size: 8px;
      white-space: nowrap;
    }

    .section-banner td {
      background: #d0d0d0;
      text-align: center;
      font-weight: bold;
      font-size: 10px;
      padding: 2px;
    }

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
  </style>
</head>

<body onload="window.print()">
  <div class="page-wrap">

    <!-- ══ HEADER TABLE ══ -->
    <table class="header-table">
      <tr>
        <td class="logo-cell" rowspan="2">
          <img src="img/smis-logo.png" alt="SMIS Logo">
        </td>
        <td class="title-cell" rowspan="2">
          PRODUCT RETURNS<br>EVALUATION
        </td>
        <td class="date-prepared-label">DATE PREPARED:</td>
        <td class="date-prepared-value"><?= htmlspecialchars($date_processed) ?></td>
        <td class="porf-label">Required Pull Out</td>
        <td class="porf-value">&nbsp;</td>
      </tr>
      <tr>
        <td class="pre-no-label">Outlet's RTV / PRE No:</td>
        <td><strong><?= htmlspecialchars($pre_no . ' ' . $type_short . ' ' . $hub_upper) ?></strong></td>
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
              <td class="lbl" style="text-align:center; border:none; border-bottom:1px solid #000;">Distributor /
                Customer Code:</td>
            </tr>
            <tr>
              <td style="text-align:center; border:none;"><?= htmlspecialchars($hubInfo['code']) ?></td>
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
        <tr>
          <th style="width:5%;">pcs</th>
          <th style="width:5%;">kgs</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $totalPcs = 0;
        $totalUnitCost = 0;

        foreach ($rows as $row):
          $totalPcs += (int) ($row['qty'] ?? 0);
          $totalUnitCost += (float) ($row['unitcost'] ?? 0);

          // Rebuild reason display from saved columns
          $reasonParts = [];
          foreach ($smis_form_columns as $col) {
            $colKey = strtolower($col); // g01, d04 …
            if (!empty($row[$colKey]))
              $reasonParts[] = $col;
          }
          if (empty($reasonParts) && !empty($row['others'])) {
            $parts = explode(' - ', $row['others'], 2);
            $reasonDisplay = trim($parts[0]);
          } else {
            $reasonDisplay = implode(', ', $reasonParts);
          }
          ?>
          <tr>
            <td><?= htmlspecialchars($row['mdccode']) ?></td>
            <td class="left"><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['size']) ?></td>
            <td></td>
            <td><?= htmlspecialchars($row['expiration']) ?></td>
            <td><?= htmlspecialchars($row['qty']) ?></td>
            <td></td>
            <td style="text-align:right;"><?= htmlspecialchars($row['unitcost'] ?? '') ?></td>
            <td><?= htmlspecialchars($reasonDisplay) ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          </tr>
        <?php endforeach; ?>

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

    <!-- ══ SIGNATURE SECTION ══ -->
    <table style="width:100%; border-collapse:collapse;">
      <tr>
        <td style="width:42%; border:1px solid #000; vertical-align:top;">
          <table style="width:100%; border-collapse:collapse;">
            <tr>
              <td colspan="1" style="font-size:10px; padding:3px;">Prepared By Sender:</td>
              <td colspan="3"><b></b></td>
            </tr>
            <tr>
              <td style="width:25%; font-size:8px;">Position Title:</td>
              <td style="border-bottom:1px solid #000;"></td>
              <td style="width:15%; font-size:8px;">Date:</td>
            </tr>
            <tr>
              <td colspan="4" style="padding-top:6px; font-size:8px;">Received By Call Center / Business Center</td>
            </tr>
            <tr>
              <td style="font-size:8px;">Position Title:</td>
              <td style="border-bottom:1px solid #000;"></td>
              <td style="font-size:8px;">Date:</td>
            </tr>
          </table>
        </td>
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

  </div>
</body>

</html>