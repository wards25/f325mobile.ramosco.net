<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");
include_once("dbf325.php");

if(!isset($_SESSION['id']))
{
  header("Location: index.php");
}
$res=mysqli_query($conn,"SELECT * FROM dbuser WHERE id=".$_SESSION['id']);
$userRow=mysqli_fetch_array($res);
?>

<?php
include_once("nav.php");
?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                <form action="bypass_process.php" method="post" enctype="multipart/form-data">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Bypass Product</h1>
                        <a href="exportbypass.php" type="button" class="d-sm-inline-block btn btn-sm btn-success shadow-sm" ><i class="fa fa-download"></i> Export Product List</a>
                    </div>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="d-sm-flex card-header justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Select CSV File</h6>
                            <!--<a class="d-sm-inline-block btn btn-sm btn-success"><i class="fa fa-info"></i> Edit Census</a>-->
                        </div>
                        <div class="card-body">

<script>
window.setTimeout(function() {
    $(".alert").fadeTo(500, 0).slideUp(500, function(){
        $(this).remove(); 
    });
}, 2000);
</script>

<?php
// Get status message
if(!empty($_GET['status'])){
    switch($_GET['status']){
        case 'succ':
            $statusType = 'alert-success';
            $statusMsg = '<i class="fa fa-check-circle"></i>&nbsp;<b>Success!</b> Data has been imported successfully.';
            break;
        case 'err':
            $statusType = 'alert-danger';
            $statusMsg = '<i class="fa fa-exclamation-triangle"></i>&nbsp;<b>Error!</b> Some problem occurred, please try again.';
            break;
        case 'invalid_file':
            $statusType = 'alert-danger';
            $statusMsg = '<i class="fa fa-exclamation-circle"></i>&nbsp;<b>Error!</b> Please upload a valid CSV file.';
            break;
        default:
            $statusType = '';
            $statusMsg = '';
    }
}
?>

<!-- Display status message -->
<?php if(!empty($statusMsg)){ ?>
<div class="alert <?php echo $statusType; ?> alert-dismissable fade show" role="alert">
     <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <?php echo $statusMsg; ?>
</div>
<?php } ?>

                                
                                <div class="input-group mb-3">
                                      <input class="form-control" type="file" id="formFile" name="file">
                                        <div class="input-group-prepend">
                                        <span class="btn btn-primary" data-toggle="modal" data-target="#import"><i class="fa fa-upload"></i> Upload</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

 <!-- Upload Modal-->
    <div class="modal fade" id="import" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Upload File</h5>
            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div class="modal-body">Are you sure you want to upload this csv file?</div>
          <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
            <input type="submit" class="btn btn-primary" name="submit" value="Upload">
          </div>
        </div>
      </div>
    </div>
  </body>
</form>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Existing Census List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>SKU Code</th>
                                            <th>Item Code</th>
                                            <th>Description</th>
                                            <th>Category</th>
                                            <th>Vendor</th>
                                            <th>DMPI Code</th>
                                            <th>DMPI Pack</th>
                                            <th>DMPI Class</th>
                                            <th>UOM</th>
                                            <th>Active</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = mysqli_query($f325conn,"SELECT * FROM dbproduct");
                                        while($row = mysqli_fetch_array($result))
                                            {
                                        ?>
                                        <tr>
                                            <?php 
                                                echo '<td>'.$row['mdccode'].'</td>';
                                                echo '<td>'.$row['itemcode'].'</td>';
                                                echo '<td>'.$row['description'].'</td>';
                                                echo '<td>'.$row['category'].'</td>';
                                                echo '<td>'.$row['vendor'].'</td>';
                                                echo '<td>'.$row['dmpicode'].'</td>';
                                                echo '<td>'.$row['dmpipack'].'</td>'; 
                                                echo '<td>'.$row['dmpiclassification'].'</td>';
                                                echo '<td>'.$row['uom'].'</td>';
                                                echo '<td>'.$row['active'].'</td>';
                                            ?>
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- End Table -->

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

        <?php
        include_once("footer.php");
        ?>

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>

</body>

</html>