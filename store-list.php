<?php
session_start();
include('dbconnect.php');
include_once('header.php');
include_once('nav.php');
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
                $query = "SELECT code, branchname, region, status, location FROM dbcensus";
                $result = $conn->query($query);
                ?>

                <table id="storeTable" class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Branch Name</th>
                            <th>Region</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['code']; ?></td>
                                <td><?= htmlspecialchars($row['branchname']); ?></td>
                                <td><?= htmlspecialchars($row['region']); ?></td>
                                <td>
                                    <a href="edit_store.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">
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
                <form id="storeForm" class="p-4">

                    <!-- CUSTOMER INFO -->
                    <h5 class="fw-bold">Customer Info</h5>
                    <hr>

                    <div class="row g-3">

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Code</label>
                            <input type="text"
                                class="form-control input-code"
                                maxlength="6"
                                required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Branch Name</label>
                            <textarea class="form-control input-branchname" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Shipping Address</label>
                            <textarea class="form-control input-shippingaddress" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Billing Address</label>
                            <textarea class="form-control input-billingaddress" required></textarea>
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


<?php include_once('footer.php'); ?>
<?php $conn->close(); ?>

<script>
    $(document).ready(function() {
        var table = $('#storeTable').DataTable();

        // Filter by Region
        $('#filterRegion').on('change', function() {
            table.column(2).search(this.value).draw();
        });

        $('#filterLocation').on('change', function() {
            table.column(2).search(this.value).draw();
        });
    });
</script>