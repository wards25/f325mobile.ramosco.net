<?php
session_start();
include('dbconnect.php');

$editData = null;

// if editing
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $get = $conn->query("SELECT * FROM dbproduct WHERE id='$id' LIMIT 1");
    $editData = $get->fetch_assoc();
}
?>

<?php include('header.php'); ?>
<?php include('nav.php'); ?>

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom">
        <h4 class="mb-0">Product List</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-box-seam"></i> Add New Product
        </button>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">

            <div class="table-responsive">
                <?php
                $result = $conn->query("SELECT * FROM dbproduct WHERE active='1' ORDER BY description ASC");
                ?>

                <table id="productTable" class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>MDC Code</th>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Vendor</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['mdccode']; ?></td>
                                <td><?= $row['itemcode']; ?></td>
                                <td><?= $row['description']; ?></td>
                                <td><?= $row['category']; ?></td>
                                <td><?= $row['vendor']; ?></td>
                                <td>
                                    <a href="edit-product-list.php?edit_id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">
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

<!-- ADD PRODUCT MODAL -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title">Add Product</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="product-list-process.php">
                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label>MDC Code</label>
                            <input type="text" name="mdccode" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label>Item Code</label>
                            <input type="text" name="itemcode" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label>Description</label>
                            <textarea name="description" class="form-control" required></textarea>
                        </div>

                        <div class="col-md-4">
                            <label>Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php
                                // Fetch distinct categories from dbproduct
                                $category_query = $conn->query("SELECT DISTINCT category FROM dbproduct WHERE category <> '' ORDER BY category ASC");
                                while ($cat = $category_query->fetch_assoc()) {
                                    // Pre-select the category if editing
                                    $selected = ($editData['category'] == $cat['category']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($cat['category']) . "' $selected>" . htmlspecialchars($cat['category']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label>UOM</label>
                            <select name="uom" class="form-select">
                                <option value="">Select UOM</option>
                                <?php
                                $uom_query = $conn->query("SELECT DISTINCT uom FROM dbproduct WHERE uom <> '' ORDER BY uom ASC");
                                while ($u = $uom_query->fetch_assoc()) {
                                    $selected = ($editData['uom'] == $u['uom']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($u['uom']) . "' $selected>" . htmlspecialchars($u['uom']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label>Company</label>
                            <select name="company" class="form-select" required>
                                <option value="">Select Company</option>
                                <?php
                                // Fetch all active companies from dbcompany
                                $company_query = $conn->query("SELECT vendorcode, name FROM dbcompany WHERE active='1' ORDER BY name ASC");
                                while ($comp = $company_query->fetch_assoc()) {
                                    // Keep the current vendor selected if editing
                                    $selected = ($editData['vendor'] == $comp['vendorcode']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($comp['vendorcode']) . "' $selected>" . htmlspecialchars($comp['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>


                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">Add Product</button>
                </div>
            </form>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>
<?php $conn->close(); ?>
<script>
    $(document).ready(function() {
        $('#productTable').DataTable({
            "pageLength": 10,
            "ordering": true
        });
    });
</script>