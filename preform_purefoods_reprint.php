<?php
include_once("dbconnect.php");

// Get the unique identifier (PRE No) or Batch Number to fetch the record
$pre_no = $_GET['pre_no'] ?? '';
$batchnumber = $_GET['batchnumber'] ?? '';
$hub = $_GET['hub'] ?? '';

if (empty($pre_no) && empty($batchnumber)) {
    die("Error: PRE Number or Batch Number is required for reprinting.");
}

// ══════════════════════════════════════════════════════════════
//  FETCH DATA FROM tbl_preform
// ══════════════════════════════════════════════════════════════
$query = "SELECT * FROM tbl_preform WHERE ";
if (!empty($pre_no)) {
    $query .= "pre_no = '" . mysqli_real_escape_string($conn, $pre_no) . "'";
} else {
    $query .= "batchnumber = '" . mysqli_real_escape_string($conn, $batchnumber) . "'";
}
$query .= " ORDER BY id ASC";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}

$rows = [];
while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
}
mysqli_free_result($result);

if (count($rows) === 0) {
    die("Error: No records found for the provided reference.");
}

// Use the first row to populate header information
$header = $rows[0];
?>
<!DOCTYPE html>
<html>
<head>
  <title>REPRINT: SMIS Format - Product Returns Evaluation</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

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

    .header-left { display: flex; align-items: center; gap: 8px; }
    .header-left img { width: 100px; }
    .header-title { font-weight: bold; font-size: 15px; letter-spacing: 0.5px; }

    .header-right table { border-collapse: collapse; }
    .header-right td { border: 1px solid #000; padding: 2px 5px; font-size: 10px; }

    .note-text { font-size: 8.5px; font-style: italic; margin-bottom: 4px; }
    .section { margin-top: 4px; }

    table.info-table { width: 100%; border-collapse: collapse; }
    table.info-table td { border: 1px solid #000; padding: 2px 4px; font-size: 10px; }

    .return-type-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .return-type-table td { border: 1px solid #000; padding: 2px 6px; font-size: 10px; }

    table.main-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.main-table th,
    table.main-table td {
      border: 1px solid #000;
      padding: 2px 3px;
      font-size: 9px;
      text-align: center;
      vertical-align: middle;
    }
    table.main-table td.left { text-align: left; }
    table.main-table thead tr th { background-color: #e8e8e8; font-weight: bold; }
    table.main-table tbody tr:nth-child(even) { background-color: #f9f9f9; }

    .sig-section { margin-top: 10px; }
    .sig-section .sig-label { font-size: 9px; font-weight: bold; margin-bottom: 2px; }

    table.sig-table { width: 100%; border-collapse: collapse; }
    table.sig-table td {
      border: none;
      padding: 2px 6px;
      text-align: center;
      font-size: 10px;
      width: 33.33%;
      vertical-align: bottom;
    }
    table.sig-table .sig-line { border-bottom: 1px solid #000; height: 22px; display: block; }
    table.sig-table .sig-role { font-size: 9px; text-align: center; }

    .notes-section { margin-top: 10px; font-size: 8.5px; }
    .notes-section b { font-size: 9px; }

    @media print {
      @page { size: landscape; margin: 8mm; }
      body { padding: 0; }
    }
  </style>
</head>
<body onload="window.print()">

  <div class="header-wrap">
    <div class="header-left">
      <img src="img/smis-logo.png" alt="SMIS Logo">
      <div class="header-title">PRODUCT RETURNS EVALUATION</div>
    </div>
    <div class="header-right">
      <table>
        <tr>
          <td><b>Date Prepared:</b></td>
          <td style="min-width:120px;"><?= htmlspecialchars($header['date_processed']) ?></td>
          <td><b>RDO No:</b></td>
          <td style="min-width:80px;"></td>
        </tr>
        <tr>
          <td><b>Outlet's RTV or Returns Document Ref No:</b></td>
          <td><?= htmlspecialchars($header['pre_no'] . ' PUREFOODS ' . $header['hub']) ?></td>
          <td><b>PRE No:</b></td>
          <td><?= htmlspecialchars($header['pre_no']) ?></td>
        </tr>
      </table>
    </div>
  </div>

  <div class="note-text">
    <i>NOTE: The PRE is a mandatory SMIS form for product returns processing. No trade returns maybe pulled-out without
      evaluation by authorized SMIS representatives.</i>
  </div>

  <table class="info-table">
    <tr>
      <td colspan="3" style="border:none; padding:2px 0;"><u><b>Requesting Customer Details</b></u></td>
      <td colspan="2" style="border:none; padding:2px 0;"><u><b>SMIS Authorized Representative Details:</b></u></td>
    </tr>
    <tr>
      <td style="width:14%"><b>Business Name:</b></td>
      <td style="width:32%"><?= htmlspecialchars($header['business_name']) ?></td>
      <td style="width:10%"><b>Customer: </b><?= htmlspecialchars($header['customer_code']) ?></td>
      <td style="width:22%">Assigned Salesman (SAS):</td>
      <td style="width:22%"></td>
    </tr>
    <tr>
      <td><b>Customer Outlet Addr:</b></td>
      <td colspan="2"><?= htmlspecialchars($header['address']) ?></td>
      <td>Assigned Merchandiser:</td>
      <td><?= htmlspecialchars($header['assigned_merchandiser']) ?></td>
    </tr>
  </table>

  <table class="return-type-table">
    <tr>
      <td style="width:12%"><b>Type of Return:</b></td>
      <td style="width:12%; text-align:center">☑ Valid BO</td>
      <td style="width:16%; text-align:center">□ Product Retrieval</td>
      <td style="width:16%; text-align:center">□ For Investigation</td>
      <td style="width:16%; text-align:center">□ Stock Transfer</td>
      <td>□ Others (Specify) _____________________</td>
    </tr>
  </table>

  <table class="main-table">
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
      $totalQty = 0;
      foreach ($rows as $row):
          $totalQty += (int)$row['qty'];
      ?>
          <tr>
            <td><?= htmlspecialchars($row['mdccode']) ?></td>
            <td class="left"><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['size']) ?></td>
            <td></td>
            <td><?= htmlspecialchars($row['expiration']) ?></td>
            <td><?= htmlspecialchars($row['qty']) ?></td>
            <td></td>
            <td><?= ($row['g01'] == 1) ? '✔' : '' ?></td>
            <td><?= ($row['d04'] == 1) ? '✔' : '' ?></td>
            <td><?= ($row['d02'] == 1) ? '✔' : '' ?></td>
            <td><?= ($row['m07'] == 1) ? '✔' : '' ?></td>
            <td><?= ($row['m02'] == 1) ? '✔' : '' ?></td>
            <td><?= htmlspecialchars($row['others']) ?></td>
            <td></td>
          </tr>
      <?php endforeach; ?>
      <tr>
        <td colspan="5" style="text-align:right; font-weight:bold;"></td>
        <td style="font-weight:bold;"><?= $totalQty ?></td>
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