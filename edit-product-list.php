<?php
session_start();
include('dbconnect.php');

// Get product to edit
if (!isset($_GET['edit_id'])) {
    echo "Product ID not provided.";
    exit;
}

$id = $_GET['edit_id'];
$get = $conn->query("SELECT * FROM dbproduct WHERE id='$id' LIMIT 1");
$editData = $get->fetch_assoc();

if (!$editData) {
    echo "Product not found.";
    exit;
}
?>

<?php include('header.php'); ?>
<?php include('nav.php'); ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom">
        <h4 class="mb-0">Edit Product</h4>
        <a href="product-list.php" class="btn btn-secondary">Back to Product List</a>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">

            <form method="POST" action="edit-product-process.php">

                <input type="hidden" name="id" value="<?= $editData['id']; ?>">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label>MDC Code</label>
                        <input type="text" name="mdccode" class="form-control" value="<?= $editData['mdccode']; ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label>Item Code</label>
                        <input type="text" name="itemcode" class="form-control" value="<?= $editData['itemcode']; ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label>Description</label>
                        <textarea name="description" class="form-control" required><?= $editData['description']; ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" value="<?= $editData['category']; ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label>UOM</label>
                        <input type="text" name="uom" class="form-control" value="<?= $editData['uom']; ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label>Company</label>
                        <input type="text" name="company" class="form-control" value="<?= $editData['dmpiclassification']; ?>">
                    </div>
                </div>

                <h5 class="fw-bold mb-3">Stock Availability per Location</h5>
                <div class="row g-3 mb-4">
                    <?php
                    $mdccode = $editData['mdccode'];
                    $location_query = mysqli_query($conn, "SELECT * FROM dblocation WHERE active='1'");
                    while ($loc = mysqli_fetch_assoc($location_query)) {
                        $location = $loc['location'];
                        $status_cleared = "CLEARED";
                        $status_scheduled = "SCHEDULED";

                        $stock_query = mysqli_query($conn,"SELECT SUM(quantity) AS totalquantity FROM dbraw WHERE mdccode='$mdccode' AND location='$location' AND statusout='$status_cleared'");
                        $stock = mysqli_fetch_assoc($stock_query);

                        $pick_query = mysqli_query($conn,"SELECT SUM(quantity) AS totalpickquantity FROM dbraw WHERE mdccode='$mdccode' AND location='$location' AND status='$status_scheduled'");
                        $pick = mysqli_fetch_assoc($pick_query);
                    ?>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars($location); ?> Available</label>
                            <input type="text" class="form-control" value="<?= number_format($stock['totalquantity'],0); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= htmlspecialchars($location); ?> Scheduled</label>
                            <input type="text" class="form-control" value="<?= number_format($pick['totalpickquantity'],0); ?>" readonly>
                        </div>
                    <?php } ?>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" name="action" value="update" class="btn btn-primary">Save Changes</button>
                    <button type="submit" name="action" value="deactivate"
                        onclick="return confirm('Deactivate this product?');" class="btn btn-danger">Deactivate</button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>
<?php $conn->close(); ?>
