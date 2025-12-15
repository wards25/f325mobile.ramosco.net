<?php
session_start();
include('dbconnect.php');
include_once('header.php');
include_once('nav.php');
?>
<?php
$editData = null;

if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $editQuery = $conn->query("SELECT * FROM dbcensus WHERE id = '$id' LIMIT 1");
    $editData = $editQuery->fetch_assoc();
}

?>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
        <h4 class="mb-0">Store List</h4>
        <button class="btn btn-primary rounded" data-bs-toggle="modal" data-bs-target="#addStoreModal">
            <i class="bi bi-shop-window"></i> Add Store
        </button>
    </div>

    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4">

            <!-- FILTERS -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="fw-bold">Filter by Region</label>
                    <select id="filterRegion" class="form-select">
                        <option value="">All Regions</option>
                        <?php
                        $regionQuery = "SELECT DISTINCT region FROM dbcensus ORDER BY region";
                        $regionResult = $conn->query($regionQuery);
                        while ($reg = $regionResult->fetch_assoc()):
                        ?>
                            <option value="<?= $reg['region'] ?>"><?= $reg['region'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <?php
                $locationQuery = "SELECT * FROM dblocation WHERE active = '1'";
                $locationResult = mysqli_query($conn, $locationQuery);
                ?>

                <div class="col-md-4">
                    <label class="fw-bold">Filter by Active Location</label>
                    <select id="filterLocation" class="form-select">
                        <option value="">All Locations</option>

                        <?php while ($loc = mysqli_fetch_assoc($locationResult)): ?>
                            <option value="<?= htmlspecialchars($loc['location']); ?>">
                                <?= htmlspecialchars($loc['location']); ?>
                            </option>
                        <?php endwhile; ?>

                    </select>
                </div>
            </div>

            <div class="table-responsive">

                <?php
                $query = "SELECT id, code, branchname, region, location FROM dbcensus WHERE status = '1' ";
                $result = $conn->query($query);
                ?>

                <table id="storeTable" class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th hidden>#</th>
                            <th>Branch Code</th>
                            <th>Branch Name</th>
                            <th>Region</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td hidden><?= $row['id']; ?></td>
                                <td><?= htmlspecialchars($row['code']); ?></td>
                                <td><?= htmlspecialchars($row['branchname']); ?></td>
                                <td><?= htmlspecialchars($row['region']); ?></td>
                                <td>
                                    <a href="store-list.php?edit_id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>
<!-- ADD STORE MODAL -->
<div class="modal fade" id="addStoreModal" tabindex="-1" aria-labelledby="addStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-shop-window"></i> Add New Store
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="storeForm" method="POST" action="store-list-process.php" class="p-4">

                    <!-- CUSTOMER INFO -->
                    <h5 class="fw-bold">Customer Info</h5>
                    <hr>

                    <div class="row g-3">

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Code</label>
                            <input type="text"
                                class="form-control input-code"
                                maxlength="6" name="code"
                                required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Branch Name</label>
                            <textarea class="form-control input-branchname" name="branchname" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Shipping Address</label>
                            <textarea class="form-control input-shippingaddress" name="shipping" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Billing Address</label>
                            <textarea class="form-control input-billingaddress" name="billing" required></textarea>
                        </div>

                    </div>


                    <!-- CUSTOMER DETAIL -->
                    <h5 class="fw-bold mt-4">Customer Detail</h5>
                    <hr>

                    <div class="row g-3">

                        <!-- Franchise -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Franchise</label>
                            <select class="form-select input-franchise" name="franchise" required>
                                <option value="">Select Franchise</option>

                                <?php
                                $frQuery = mysqli_query($conn, "SELECT DISTINCT franchise FROM dbcensus WHERE franchise <> '' ORDER BY franchise ASC");
                                while ($f = mysqli_fetch_assoc($frQuery)) {
                                    echo "<option value='{$f['franchise']}'>{$f['franchise']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Region -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Region</label>
                            <select class="form-select input-region" name="region" required>
                                <option value="">Select Region</option>

                                <?php
                                $regQuery = mysqli_query($conn, "SELECT DISTINCT region FROM dbcensus WHERE region <> '' ORDER BY region ASC");
                                while ($r = mysqli_fetch_assoc($regQuery)) {
                                    echo "<option value='{$r['region']}'>{$r['region']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Cluster -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cluster</label>
                            <select class="form-select input-cluster" name="cluster" required>
                                <option value="">Select Cluster</option>

                                <?php
                                $clQuery = mysqli_query($conn, "SELECT DISTINCT cluster FROM dbcensus WHERE cluster <> '' ORDER BY cluster ASC");
                                while ($c = mysqli_fetch_assoc($clQuery)) {
                                    echo "<option value='{$c['cluster']}'>{$c['cluster']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Deduct Type -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Deduct Type</label>
                            <select class="form-select input-deducttype" name="deducttype" required>
                                <option value="">Select Deduct Type</option>

                                <?php
                                $dtQuery = mysqli_query($conn, "SELECT DISTINCT deducttype FROM dbcensus WHERE deducttype <> '' ORDER BY deducttype ASC");
                                while ($d = mysqli_fetch_assoc($dtQuery)) {
                                    echo "<option value='{$d['deducttype']}'>{$d['deducttype']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Location -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Location</label>
                            <select class="form-select input-location" name="location" required>
                                <option value="">Select Location</option>

                                <?php
                                $locQuery = mysqli_query($conn, "SELECT DISTINCT location FROM dbcensus WHERE location <> '' ORDER BY location ASC");
                                while ($l = mysqli_fetch_assoc($locQuery)) {
                                    echo "<option value='{$l['location']}'>{$l['location']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
            </form>
        </div>
    </div>
</div>
<!-- EDIT STORE MODAL -->
<?php if ($editData): ?>

    <body class="modal-open">
        <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <div class="modal fade <?php if ($editData) echo 'show'; ?>"
        id="editStoreModal"
        tabindex="-1"
        style="<?php if ($editData) echo 'display:block;'; ?>">

        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-shop-window"></i> Edit Store
                    </h5>
                    <a href="store-list.php" class="btn-close"></a>
                </div>

                <div class="modal-body">
                    <form method="POST" action="update-store-process.php" class="p-4">

                        <input type="hidden" name="id" value="<?= $editData['id']; ?>">

                        <h5 class="fw-bold">Customer Info</h5>
                        <hr>

                        <div class="row g-3">

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Code</label>
                                <input type="text" name="code" maxlength="6" class="form-control"
                                    value="<?= $editData['code']; ?>" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Branch Name</label>
                                <textarea name="branchname" class="form-control" required><?= $editData['branchname']; ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Shipping Address</label>
                                <textarea name="shipping" class="form-control" required><?= $editData['shipping']; ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Billing Address</label>
                                <textarea name="billing" class="form-control" required><?= $editData['billing']; ?></textarea>
                            </div>

                        </div>

                        <h5 class="fw-bold mt-4">Customer Detail</h5>
                        <hr>

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Franchise</label>
                                <select name="franchise" class="form-select" required>
                                    <option value="">Select Franchise</option>
                                    <?php
                                    $frQ = $conn->query("SELECT DISTINCT franchise FROM dbcensus WHERE franchise <> '' ORDER BY franchise");
                                    while ($f = $frQ->fetch_assoc()):
                                    ?>
                                        <option value="<?= $f['franchise']; ?>"
                                            <?= ($editData['franchise'] == $f['franchise']) ? 'selected' : '' ?>>
                                            <?= $f['franchise']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Region</label>
                                <select name="region" class="form-select" required>
                                    <option value="">Select Region</option>
                                    <?php
                                    $regionQ = $conn->query("SELECT DISTINCT region FROM dbcensus WHERE region <> '' ORDER BY region");
                                    while ($r = $regionQ->fetch_assoc()):
                                    ?>
                                        <option value="<?= $r['region']; ?>"
                                            <?= ($editData['region'] == $r['region']) ? 'selected' : '' ?>>
                                            <?= $r['region']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Cluster</label>
                                <select name="cluster" class="form-select" required>
                                    <option value="">Select Cluster</option>
                                    <?php
                                    $clQ = $conn->query("SELECT DISTINCT cluster FROM dbcensus WHERE cluster <> '' ORDER BY cluster");
                                    while ($c = $clQ->fetch_assoc()):
                                    ?>
                                        <option value="<?= $c['cluster']; ?>"
                                            <?= ($editData['cluster'] == $c['cluster']) ? 'selected' : '' ?>>
                                            <?= $c['cluster']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Deduct Type</label>
                                <select name="deducttype" class="form-select" required>
                                    <option value="">Select Deduct Type</option>
                                    <?php
                                    $dtQ = $conn->query("SELECT DISTINCT deducttype FROM dbcensus WHERE deducttype <> '' ORDER BY deducttype");
                                    while ($d = $dtQ->fetch_assoc()):
                                    ?>
                                        <option value="<?= $d['deducttype']; ?>"
                                            <?= ($editData['deducttype'] == $d['deducttype']) ? 'selected' : '' ?>>
                                            <?= $d['deducttype']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold">Location</label>
                                <select name="location" class="form-select" required>
                                    <option value="">Select Location</option>
                                    <?php
                                    $locQ = $conn->query("SELECT DISTINCT location FROM dbcensus WHERE location <> '' ORDER BY location");
                                    while ($l = $locQ->fetch_assoc()):
                                    ?>
                                        <option value="<?= $l['location']; ?>"
                                            <?= ($editData['location'] == $l['location']) ? 'selected' : '' ?>>
                                            <?= $l['location']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                        </div>

                        <div class="modal-footer mt-4">
                            <button type="submit" name="action" value="update" class="btn btn-primary">Save Changes</button>

                            <button type="submit" name="action" value="deactivate"
                                class="btn btn-outline-danger"
                                onclick="return confirm('Are you sure you want to deactivate this store?');">
                                Deactivate Store
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>



    <?php include_once('footer.php'); ?>
    <?php $conn->close(); ?>

    <script>
        $(document).ready(function() {
            var table = $('#storeTable').DataTable();

            // Filter by Region
            $('#filterLocation').on('change', function() {
                table.column(2).search(this.value).draw();
            });
             $('#filterRegion').on('change', function() {
                table.column(3).search(this.value).draw();
            });

        });
    </script>