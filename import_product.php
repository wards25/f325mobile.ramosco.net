<?php
//error_reporting(0);
session_start();
include_once("header.php");
include_once("dbconnect.php");
$username = $_SESSION['fname'];

// delete prdlist in db 
mysqli_query($conn,"DELETE FROM cleared_list WHERE user = '$username'");

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
                <form action="import_product_process.php" method="post" enctype="multipart/form-data">
                    <!-- Page Heading -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Import Census</h1>
                        <a href="export_product.php" type="button" class="d-sm-inline-block btn btn-sm btn-success shadow-sm" ><i class="fa fa-download"></i> Export Product List</a>
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
                                      <input class="form-control form-control-sm" type="file" id="formFile" name="file">
                                        <div class="input-group-prepend">
                                        <span class="btn btn-primary btn-sm" data-toggle="modal" data-target="#import"><i class="fa fa-upload"></i> Upload</span>
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
                                <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                                <input type="submit" class="btn btn-primary btn-sm" name="submit" value="Upload">
                              </div>
                            </div>
                          </div>
                        </div>
                      </body>
                    </form>

                    <form method="POST" action="import_product.php">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div class="input-group shadow-sm">
                            <?php
                            $query ="SELECT * FROM dbcompany WHERE active = '1' ORDER BY name";
                                $result = $conn->query($query);
                                if($result->num_rows> 0){
                                  $options= mysqli_fetch_all($result, MYSQLI_ASSOC);?>

                                <select class="form-control form-control-sm" name="vendor" required>
                                   <option value="">Select Vendor</option>
                                  <?php 
                                  foreach ($options as $option) {
                                  ?>
                                    <option value="<?php echo $option['vendorcode'];?>"><?php echo $option['name']; ?> </option>
                                    <?php 
                                    }
                                  }
                                ?>
                            </select>
                            <button class="input-group-addon btn btn-sm text-light" type="submit" name="submit" style="background-color: #915c83;"><i class="fa fa-sm fa-search"></i> Search Vendor</button>
                        </div>
                    </div>
                    </form>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-2" style="background-color: #915c83;">
                            <h6 class="m-0 font-weight-bold text-light">Existing Product List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Itemcode</th>
                                            <th>Barcode</th>
                                            <th>Casecode</th>
                                            <th>Description</th>
                                            <th>Category</th>
                                            <th>Company</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if(isset($_POST['vendor'])){
                                            $vendorcode = $_POST['vendor'];
                                            $result = mysqli_query($conn,"SELECT * FROM dbproduct WHERE vendor = '$vendorcode'");
                                        }else{
                                            $result = mysqli_query($conn,"SELECT * FROM dbproduct");
                                        }
                                        
                                        while($row = mysqli_fetch_array($result))
                                            {
                                        ?>
                                        <tr>
                                            <?php echo '<td>'.$row['mdccode'].'</td>'; ?>
                                            <?php echo '<td>'.$row['itemcode'].'</td>'; ?>
                                            <?php echo '<td>'.$row['barcode'].'</td>'; ?>
                                            <?php echo '<td>'.$row['casecode'].'</td>'; ?>
                                            <?php echo '<td>'.$row['description'].'</td>'; ?>
                                            <?php echo '<td>'.$row['category'].'</td>'; ?>
                                            <?php
                                            if($row['vendor']=='79449'){
                                                echo '<td>RSDI</td>';
                                            }else if($row['vendor']=='24134'){
                                                echo '<td>CLUTCH</td>';
                                            }else if($row['vendor']=='18565'){
                                                echo '<td>EDGE</td>';
                                            }else if($row['vendor']=='85001'){
                                                echo '<td>CLOCK</td>';
                                            }else if($row['vendor']=='22821'){
                                                echo '<td>CARBON</td>';
                                            }else if($row['vendor']=='84088'){
                                                echo '<td>SCAN</td>';
                                            }else if($row['vendor']=='1606'){
                                                echo '<td>NESTLE</td>';
                                            }else if($row['vendor']=='8851'){
                                                echo '<td>WYETH</td>';
                                            }else{
                                                echo '<td></td>';
                                            }
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