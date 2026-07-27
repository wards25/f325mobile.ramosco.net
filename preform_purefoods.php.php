<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_array($res);

// Handle form submission
$toast_msg = '';
$toast_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pre_submit'])) {

    // Sanitize helper
    function clean($v) {
        return htmlspecialchars(trim($v ?? ''), ENT_QUOTES, 'UTF-8');
    }

    $data = [
        'date_prepared'         => clean($_POST['date_prepared'] ?? ''),
        'outlet_rtv'            => clean($_POST['outlet_rtv'] ?? ''),
        'rdo_no'                => clean($_POST['rdo_no'] ?? ''),
        'pre_no'                => clean($_POST['pre_no'] ?? ''),
        'business_name'         => clean($_POST['business_name'] ?? ''),
        'customer_code'         => clean($_POST['customer_code'] ?? ''),
        'customer_outlet'       => clean($_POST['customer_outlet'] ?? ''),
        'assigned_salesman'     => clean($_POST['assigned_salesman'] ?? ''),
        'assigned_merchandiser' => clean($_POST['assigned_merchandiser'] ?? ''),
        'return_type'           => clean($_POST['return_type'] ?? ''),
        'return_type_other'     => clean($_POST['return_type_other'] ?? ''),
        'sig_prepared_name'     => clean($_POST['sig_prepared_name'] ?? ''),
        'sig_prepared_pos'      => clean($_POST['sig_prepared_pos'] ?? ''),
        'sig_evaluated_name'    => clean($_POST['sig_evaluated_name'] ?? ''),
        'sig_evaluated_pos'     => clean($_POST['sig_evaluated_pos'] ?? ''),
        'sig_approved_name'     => clean($_POST['sig_approved_name'] ?? ''),
        'sig_approved_pos'      => clean($_POST['sig_approved_pos'] ?? ''),
        'submitted_by'          => (int)$_SESSION['id'],
    ];

    // Required field check
    $required = ['date_prepared', 'business_name', 'customer_code', 'assigned_salesman', 'return_type'];
    $missing = false;
    foreach ($required as $field) {
        if ($data[$field] === '') { $missing = true; break; }
    }

    // Parse product rows
    $rowsJson = $_POST['rows_json'] ?? '[]';
    $rows = json_decode($rowsJson, true);
    $cleanRows = [];
    if (is_array($rows)) {
        foreach ($rows as $row) {
            if (empty(trim($row['barcode'] ?? '')) && empty(trim($row['sku'] ?? ''))) continue;
            $reasons = isset($row['reasons']) && is_array($row['reasons'])
                ? array_map('htmlspecialchars', $row['reasons']) : [];
            $cleanRows[] = [
                'barcode'     => clean($row['barcode'] ?? ''),
                'sku'         => clean($row['sku'] ?? ''),
                'size'        => clean($row['size'] ?? ''),
                'prod_date'   => clean($row['prod_date'] ?? ''),
                'expiry_date' => clean($row['expiry_date'] ?? ''),
                'pcs'         => (int)($row['pcs'] ?? 0),
                'kgs'         => (float)($row['kgs'] ?? 0),
                'reasons'     => implode(', ', $reasons),
                'others_code' => clean($row['others_code'] ?? ''),
                'remarks'     => clean($row['remarks'] ?? ''),
            ];
        }
    }

    if ($missing) {
        $toast_msg  = 'Please fill in all required fields.';
        $toast_type = 'danger';
    } elseif (empty($cleanRows)) {
        $toast_msg  = 'Please add at least one product row.';
        $toast_type = 'danger';
    } else {
        // Generate reference number
        $ref = 'PRE-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
        $submitted_at = date('Y-m-d H:i:s');

        // --- Save to DB (adjust table/column names to match yours) ---
        $stmt = $conn->prepare("INSERT INTO pre_forms
            (reference, date_prepared, outlet_rtv, rdo_no, pre_no,
             business_name, customer_code, customer_outlet,
             assigned_salesman, assigned_merchandiser,
             return_type, return_type_other,
             sig_prepared_name, sig_prepared_pos,
             sig_evaluated_name, sig_evaluated_pos,
             sig_approved_name, sig_approved_pos,
             rows_json, submitted_by, submitted_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        if ($stmt) {
            $rowsJsonClean = json_encode($cleanRows);
            $stmt->bind_param(
                'ssssssssssssssssssssi',
                $ref,
                $data['date_prepared'], $data['outlet_rtv'],
                $data['rdo_no'],        $data['pre_no'],
                $data['business_name'], $data['customer_code'], $data['customer_outlet'],
                $data['assigned_salesman'], $data['assigned_merchandiser'],
                $data['return_type'],   $data['return_type_other'],
                $data['sig_prepared_name'], $data['sig_prepared_pos'],
                $data['sig_evaluated_name'], $data['sig_evaluated_pos'],
                $data['sig_approved_name'],  $data['sig_approved_pos'],
                $rowsJsonClean,
                $data['submitted_by'],
                $submitted_at
            );
            $stmt->execute();
            $stmt->close();
            $toast_msg  = "Form submitted successfully! Reference: <strong>{$ref}</strong>";
            $toast_type = 'success';
        } else {
            // Fallback: save as JSON file if DB insert fails
            $saveDir = __DIR__ . '/submissions/';
            if (!is_dir($saveDir)) mkdir($saveDir, 0755, true);
            file_put_contents($saveDir . $ref . '.json', json_encode(array_merge($data, [
                'reference'    => $ref,
                'submitted_at' => $submitted_at,
                'rows'         => $cleanRows,
            ]), JSON_PRETTY_PRINT));
            $toast_msg  = "Saved locally. Reference: <strong>{$ref}</strong>";
            $toast_type = 'warning';
        }
    }
}

include_once("nav.php");
?>

<!-- ═══════════════════════════════════════════════════
     PRE FORM PAGE STYLES
════════════════════════════════════════════════════ -->
<style>
.pre-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #c0392b;
    border-bottom: 2px solid #c0392b;
    padding-bottom: 5px;
    margin-bottom: 14px;
}
.pre-note {
    font-size: 11.5px;
    background: #fdf3f2;
    border-left: 4px solid #c0392b;
    padding: 8px 14px;
    border-radius: 0 4px 4px 0;
    color: #555;
    margin-bottom: 1rem;
}
/* Return type chips */
.return-chip { display: none; }
.return-chip + label {
    display: inline-block;
    padding: 5px 14px;
    border: 1px solid #ccc;
    border-radius: 20px;
    cursor: pointer;
    font-size: 12px;
    margin: 3px 4px 3px 0;
    transition: all .15s;
    user-select: none;
}
.return-chip:checked + label {
    background: #c0392b;
    border-color: #c0392b;
    color: #fff;
    font-weight: 600;
}
.return-chip + label:hover { border-color: #c0392b; color: #c0392b; }

/* Table */
#pre-table { font-size: 11.5px; min-width: 1100px; }
#pre-table thead tr:first-child th {
    background: #2c3e50;
    color: #fff;
    text-align: center;
    vertical-align: middle;
    padding: 7px 5px;
    border: 1px solid #3d5166;
    white-space: nowrap;
}
#pre-table thead tr:last-child th {
    background: #3d5166;
    color: #dde;
    text-align: center;
    vertical-align: top;
    padding: 5px 4px;
    border: 1px solid #4a6278;
    font-size: 10.5px;
}
#pre-table tbody td {
    padding: 3px 4px;
    border: 1px solid #dee2e6;
    vertical-align: middle;
}
#pre-table tbody td input[type="text"],
#pre-table tbody td input[type="date"],
#pre-table tbody td input[type="number"] {
    border: none;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0;
    background: transparent;
    padding: 3px 4px;
    font-size: 11.5px;
    width: 100%;
    outline: none;
    text-align: center;
}
#pre-table tbody td input:focus {
    border-bottom-color: #c0392b;
    background: #fff9f8;
}
#pre-table tbody td input.remarks-input { text-align: left; }
#pre-table tfoot td {
    background: #f8f9fc;
    font-weight: 700;
    border: 1px solid #dee2e6;
    padding: 6px 5px;
}
.total-label { text-align: right; font-size: 11px; text-transform: uppercase; color: #555; padding-right: 10px !important; }
.total-cell  { text-align: center; color: #c0392b; font-family: monospace; }

.reason-check { width: 16px; height: 16px; accent-color: #c0392b; cursor: pointer; }
.others-code  { width: 100% !important; font-size: 11px; }
.btn-del-row  { padding: 1px 6px; font-size: 13px; line-height: 1; }

/* Signature blocks */
.sig-block label  { font-size: 11px; font-weight: 600; color: #555; }
.sig-line { height: 50px; border-bottom: 1.5px solid #aaa; margin-bottom: 5px; }

/* Toast */
#pre-toast {
    position: fixed; bottom: 24px; right: 24px;
    min-width: 280px; z-index: 9999;
    display: none; animation: slideUp .25s ease;
}
@keyframes slideUp {
    from { transform: translateY(10px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}

@media print {
    .sidebar, .topbar, .btn, #pre-toast { display: none !important; }
    .card { box-shadow: none !important; }
}
</style>

<!-- ═══════════════════════════════════════════════════
     BEGIN PAGE CONTENT
════════════════════════════════════════════════════ -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list text-danger mr-2"></i>
            PUREFOODS – Product Returns Evaluation (PRE)
        </h1>
        <div>
            <button class="btn btn-sm btn-outline-secondary mr-2" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-sm btn-danger" id="btn-submit-pre">
                <i class="fas fa-paper-plane"></i> Submit Form
            </button>
        </div>
    </div>

    <form id="preForm" method="POST" action="" novalidate>
    <input type="hidden" name="pre_submit" value="1" />
    <input type="hidden" name="rows_json"  id="rows_json" />

    <!-- ── NOTE ───────────────────────────────────── -->
    <div class="pre-note mb-3">
        <strong>NOTE:</strong> The PRE is a mandatory SMIS form for product returns processing.
        No trade returns may be pulled-out without evaluation by authorized SMIS representatives.
    </div>

    <!-- ── HEADER META ────────────────────────────── -->
    <div class="card shadow mb-3">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Date Prepared <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" name="date_prepared"
                               id="date_prepared" required />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Outlet's RTV or Returns</label>
                        <input type="text" class="form-control form-control-sm" name="outlet_rtv"
                               placeholder="Outlet reference" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">RDO No.</label>
                        <input type="text" class="form-control form-control-sm" name="rdo_no"
                               placeholder="RDO-XXXXXXX" />
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">PRE No.</label>
                        <input type="text" class="form-control form-control-sm" name="pre_no"
                               placeholder="PRE-XXXXXXX" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── CUSTOMER + REP ─────────────────────────── -->
    <div class="row mb-3">
        <!-- Customer -->
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-body py-3">
                    <div class="pre-section-title">Requesting Customer Details</div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Business Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="business_name"
                               placeholder="Enter business name" required />
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Customer Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="customer_code"
                                       placeholder="Code" required />
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Customer Outlet</label>
                                <input type="text" class="form-control form-control-sm" name="customer_outlet"
                                       placeholder="Outlet" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- SMIS Rep -->
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-body py-3">
                    <div class="pre-section-title">SMIS Authorized Representative Details</div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Assigned Salesman (SAS) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="assigned_salesman"
                               placeholder="Full name" required />
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">Assigned Merchandiser</label>
                        <input type="text" class="form-control form-control-sm" name="assigned_merchandiser"
                               placeholder="Full name" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TYPE OF RETURN ─────────────────────────── -->
    <div class="card shadow mb-3">
        <div class="card-body py-3">
            <div class="pre-section-title">Type of Return <span class="text-danger">*</span></div>
            <div class="d-flex flex-wrap align-items-center">
                <?php
                $returnTypes = ['Valid BO', 'Product Retrieval', 'For Investigation', 'Stock Transfer', 'Others'];
                foreach ($returnTypes as $rt):
                    $id = 'rt_' . strtolower(str_replace(' ', '_', $rt));
                ?>
                <input type="radio" class="return-chip" name="return_type" id="<?= $id ?>"
                       value="<?= htmlspecialchars($rt) ?>" <?= $rt === 'Valid BO' ? 'required' : '' ?> />
                <label for="<?= $id ?>"><?= htmlspecialchars($rt) ?><?= $rt === 'Others' ? ' (Specify)' : '' ?></label>
                <?php endforeach; ?>
                <input type="text" class="form-control form-control-sm ml-2 hidden" id="rt_other_text"
                       name="return_type_other" placeholder="Specify..." style="width:160px; display:none;" />
            </div>
        </div>
    </div>

    <!-- ── DETAILS TABLE ──────────────────────────── -->
    <div class="card shadow mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="pre-section-title mb-0">Details of Return</div>
                <button type="button" class="btn btn-sm btn-danger" id="btn-add-row">
                    <i class="fas fa-plus"></i> Add Row
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="pre-table">
                    <thead>
                        <tr>
                            <th rowspan="2" style="width:100px">Product Bar Code</th>
                            <th rowspan="2" style="width:170px">Product Description (SKU)</th>
                            <th rowspan="2" style="width:55px">Size</th>
                            <th rowspan="2" style="width:95px">Production Code or Date</th>
                            <th rowspan="2" style="width:95px">Expiry Date</th>
                            <th colspan="2">Quantity</th>
                            <th colspan="6">Reason Code (Check applicable box)</th>
                            <th rowspan="2" style="width:130px">Disposition / Remarks</th>
                            <th rowspan="2" style="width:34px"></th>
                        </tr>
                        <tr>
                            <th style="width:52px">pcs</th>
                            <th style="width:52px">kgs</th>
                            <th style="width:60px">G01<br/><span style="font-size:9px;opacity:.75">Expired NEX</span></th>
                            <th style="width:60px">D04<br/><span style="font-size:9px;opacity:.75">Torn/Damaged</span></th>
                            <th style="width:60px">D02<br/><span style="font-size:9px;opacity:.75">Dented/Deformed</span></th>
                            <th style="width:60px">M07<br/><span style="font-size:9px;opacity:.75">No Vacuum</span></th>
                            <th style="width:60px">M02<br/><span style="font-size:9px;opacity:.75">Weak/Burnt Seal</span></th>
                            <th style="width:68px">Others<br/><span style="font-size:9px;opacity:.75">Specify Code</span></th>
                        </tr>
                    </thead>
                    <tbody id="pre-tbody">
                        <!-- Rows injected by JS -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="total-label">TOTAL</td>
                            <td class="total-cell" id="total-pcs">0</td>
                            <td class="total-cell" id="total-kgs">0.00</td>
                            <td colspan="7"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- ── SIGNATURES ─────────────────────────────── -->
    <div class="card shadow mb-4">
        <div class="card-body py-3">
            <div class="pre-section-title">Acknowledgement &amp; Signatures</div>
            <div class="row">
                <div class="col-md-4">
                    <div class="sig-block">
                        <label>Prepared by (Customer Representative)</label>
                        <div class="sig-line"></div>
                        <input type="text" class="form-control form-control-sm mb-1"
                               name="sig_prepared_name" placeholder="Print name" />
                        <input type="text" class="form-control form-control-sm"
                               name="sig_prepared_pos" placeholder="Position / designation" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="sig-block">
                        <label>Evaluated by (SMIS Representative)</label>
                        <div class="sig-line"></div>
                        <input type="text" class="form-control form-control-sm mb-1"
                               name="sig_evaluated_name" placeholder="Print name" />
                        <input type="text" class="form-control form-control-sm"
                               name="sig_evaluated_pos" placeholder="Position / designation" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="sig-block">
                        <label>Approved by (SMIS Supervisor)</label>
                        <div class="sig-line"></div>
                        <input type="text" class="form-control form-control-sm mb-1"
                               name="sig_approved_name" placeholder="Print name" />
                        <input type="text" class="form-control form-control-sm"
                               name="sig_approved_pos" placeholder="Position / designation" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    </form><!-- /preForm -->

</div><!-- /.container-fluid -->

<!-- ── TOAST ──────────────────────────────────────── -->
<div id="pre-toast" class="alert shadow" role="alert"></div>

<!-- ── PHP TOAST TRIGGER ───────────────────────────── -->
<?php if ($toast_msg): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    showPreToast(<?= json_encode($toast_msg) ?>, <?= json_encode($toast_type) ?>);
  });
</script>
<?php endif; ?>

<!-- ── JAVASCRIPT ─────────────────────────────────── -->
<script>
(function () {
  'use strict';

  const tbody    = document.getElementById('pre-tbody');
  const tPcs     = document.getElementById('total-pcs');
  const tKgs     = document.getElementById('total-kgs');
  const REASONS  = ['G01','D04','D02','M07','M02'];
  let rowCount   = 0;

  /* ── Add initial 10 rows ── */
  for (let i = 0; i < 10; i++) addRow();

  /* ── Add row button ── */
  document.getElementById('btn-add-row').addEventListener('click', addRow);

  /* ── Submit button ── */
  document.getElementById('btn-submit-pre').addEventListener('click', function () {
    if (!validateForm()) return;
    serializeRows();
    document.getElementById('preForm').submit();
  });

  /* ── Others radio toggle ── */
  document.querySelectorAll('.return-chip').forEach(function (r) {
    r.addEventListener('change', function () {
      var otherTxt = document.getElementById('rt_other_text');
      otherTxt.style.display = (r.value === 'Others' && r.checked) ? 'inline-block' : 'none';
    });
  });

  /* ── Build row ── */
  function addRow() {
    rowCount++;
    var idx = rowCount;
    var tr  = document.createElement('tr');

    // Text cells
    [
      { n: 'barcode',   ph: 'Scan / enter' },
      { n: 'sku',       ph: 'Description'  },
      { n: 'size',      ph: 'Size'         },
      { n: 'prod_date', ph: 'Code / Date'  },
    ].forEach(function (c) {
      var td = document.createElement('td');
      var inp = document.createElement('input');
      inp.type = 'text'; inp.name = 'rows['+idx+']['+c.n+']';
      inp.placeholder = c.ph; inp.style.width = '100%';
      td.appendChild(inp); tr.appendChild(td);
    });

    // Expiry date
    var tdExp = document.createElement('td');
    var inExp = document.createElement('input');
    inExp.type = 'date'; inExp.name = 'rows['+idx+'][expiry_date]';
    inExp.style.width = '100%'; tdExp.appendChild(inExp); tr.appendChild(tdExp);

    // pcs
    var tdPcs = document.createElement('td');
    var inPcs = document.createElement('input');
    inPcs.type = 'number'; inPcs.min = '0';
    inPcs.name = 'rows['+idx+'][pcs]'; inPcs.placeholder = '0';
    inPcs.style.width = '100%';
    inPcs.addEventListener('input', recalc);
    tdPcs.appendChild(inPcs); tr.appendChild(tdPcs);

    // kgs
    var tdKgs = document.createElement('td');
    var inKgs = document.createElement('input');
    inKgs.type = 'number'; inKgs.min = '0'; inKgs.step = '0.01';
    inKgs.name = 'rows['+idx+'][kgs]'; inKgs.placeholder = '0.00';
    inKgs.style.width = '100%';
    inKgs.addEventListener('input', recalc);
    tdKgs.appendChild(inKgs); tr.appendChild(tdKgs);

    // Reason checkboxes
    REASONS.forEach(function (code) {
      var td  = document.createElement('td');
      td.style.textAlign = 'center';
      var cb  = document.createElement('input');
      cb.type = 'checkbox'; cb.className = 'reason-check';
      cb.name = 'rows['+idx+'][reasons][]'; cb.value = code;
      td.appendChild(cb); tr.appendChild(td);
    });

    // Others code
    var tdOth = document.createElement('td');
    var inOth = document.createElement('input');
    inOth.type = 'text'; inOth.className = 'form-control form-control-sm others-code';
    inOth.name = 'rows['+idx+'][others_code]'; inOth.placeholder = 'Code';
    tdOth.appendChild(inOth); tr.appendChild(tdOth);

    // Remarks
    var tdRem = document.createElement('td');
    var inRem = document.createElement('input');
    inRem.type = 'text'; inRem.className = 'remarks-input';
    inRem.name = 'rows['+idx+'][remarks]'; inRem.placeholder = 'Notes...';
    inRem.style.width = '100%';
    tdRem.appendChild(inRem); tr.appendChild(tdRem);

    // Delete
    var tdDel = document.createElement('td');
    tdDel.style.textAlign = 'center';
    var btnDel = document.createElement('button');
    btnDel.type = 'button'; btnDel.className = 'btn btn-sm btn-outline-danger btn-del-row';
    btnDel.title = 'Remove row'; btnDel.innerHTML = '&times;';
    btnDel.addEventListener('click', function () { tr.remove(); recalc(); });
    tdDel.appendChild(btnDel); tr.appendChild(tdDel);

    tbody.appendChild(tr);
  }

  /* ── Recalc totals ── */
  function recalc() {
    var sp = 0, sk = 0;
    tbody.querySelectorAll('input[type="number"]').forEach(function (i) {
      var v = parseFloat(i.value) || 0;
      if (i.name && i.name.indexOf('[pcs]') !== -1) sp += v;
      if (i.name && i.name.indexOf('[kgs]') !== -1) sk += v;
    });
    tPcs.textContent = sp;
    tKgs.textContent = sk.toFixed(2);
  }

  /* ── Validate ── */
  function validateForm() {
    var ok = true;
    document.querySelectorAll('#preForm [required]').forEach(function (el) {
      el.classList.remove('is-invalid');
      if (!el.value.trim()) { el.classList.add('is-invalid'); ok = false; }
    });
    if (!ok) { showPreToast('Please fill in all required fields.', 'danger'); return false; }

    var firstBarcode = tbody.querySelector('input[name*="[barcode]"]');
    var firstSku     = tbody.querySelector('input[name*="[sku]"]');
    if ((!firstBarcode || !firstBarcode.value.trim()) &&
        (!firstSku     || !firstSku.value.trim())) {
      showPreToast('Add at least one product in the returns table.', 'danger');
      return false;
    }
    return true;
  }

  /* ── Serialize rows ── */
  function serializeRows() {
    var rows = [];
    tbody.querySelectorAll('tr').forEach(function (tr, i) {
      var idx  = i + 1;
      var get  = function (n) {
        var el = tr.querySelector('[name="rows['+idx+']['+n+']"]');
        return el ? el.value : '';
      };
      var checked = Array.from(tr.querySelectorAll('.reason-check:checked')).map(function (c) { return c.value; });
      rows.push({
        barcode: get('barcode'), sku: get('sku'), size: get('size'),
        prod_date: get('prod_date'), expiry_date: get('expiry_date'),
        pcs: get('pcs'), kgs: get('kgs'),
        reasons: checked, others_code: get('others_code'), remarks: get('remarks'),
      });
    });
    document.getElementById('rows_json').value = JSON.stringify(rows);
  }

  /* ── Toast ── */
  window.showPreToast = function (msg, type) {
    var t = document.getElementById('pre-toast');
    t.className = 'alert alert-' + type + ' shadow';
    t.innerHTML = msg;
    t.style.display = 'block';
    setTimeout(function () { t.style.display = 'none'; }, 4500);
  };

  /* ── Set today's date ── */
  var dp = document.getElementById('date_prepared');
  if (dp && !dp.value) dp.value = new Date().toISOString().split('T')[0];

})();
</script>

<?php include_once("footer.php"); ?>