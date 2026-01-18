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

include_once("nav.php");
?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Batch List</h1>
    </div>

    <!-- DataTables Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-info text-dark text-center">
                        <tr>
                            <th>Batch Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">

                        <?php
                        $query = "
                                SELECT 
                                    batchnumber
                                FROM dbraw
                                WHERE batchnumber IS NOT NULL
                                AND batchnumber <> ''
                                GROUP BY batchnumber
                                ORDER BY batchnumber ASC
                            ";
                        $result = mysqli_query($conn, $query);

                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                <td>{$row['batchnumber']}</td>
                               <td>
                                <a href='batchlist_details.php?batchnumber={$row['batchnumber']}'
                                class='btn btn-primary btn-sm'>
                                    View
                                </a>
                            </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>
<!-- /.container-fluid -->

<?php
include_once("footer.php");
?>