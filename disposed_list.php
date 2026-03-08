<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

$username = $_SESSION['fname'];

// clear list
mysqli_query($conn, "DELETE FROM cleared_list WHERE user = '$username'");

if (!isset($_SESSION['id']) || $_SESSION['schedule'] == '0') {
    header("Location: index.php");
    exit();
}

$res = mysqli_query($conn, "SELECT * FROM dbuser WHERE id=" . $_SESSION['id']);
$userRow = mysqli_fetch_assoc($res);
?>

<?php include_once("nav.php"); ?>

<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Disposed F325</h1>
    </div>

    <script>
        window.setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function() {
                $(this).remove();
            });
        }, 2000);
    </script>

    <?php
    if (!empty($_GET['status'])) {
        switch ($_GET['status']) {
            case 'succ':
                $statusType = 'alert-success';
                $statusMsg = '<i class="fa fa-check-circle"></i> <b>Success!</b> F325 disposed successfully.';
                break;

            case 'err':
                $statusType = 'alert-danger';
                $statusMsg = '<i class="fa fa-exclamation-triangle"></i> <b>Error!</b> Image error.';
                break;

            default:
                $statusType = '';
                $statusMsg = '';
        }
    }
    ?>

    <?php if (!empty($statusMsg)) { ?>
        <div class="alert <?php echo $statusType; ?> alert-dismissable fade show" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo $statusMsg; ?>
        </div>
    <?php } ?>


    <!-- TOTAL DISPOSED -->
    <div class="row">
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">

                    <div class="row no-gutters align-items-center">

                        <div class="col ml-4 mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Disposed F325
                            </div>

                            <div class="h5 mb-0 font-weight-bold text-gray-800">

                                <?php
                                $count_query = mysqli_query($conn, "
                                    SELECT COUNT(*) as total
                                    FROM dbf325number
                                    WHERE status='disposed'
                                    AND emaildate BETWEEN '2023-01-01' AND NOW()
                                    ");

                                $count_row = mysqli_fetch_assoc($count_query);
                                echo number_format($count_row['total']);
                                ?>

                            </div>

                            <small>
                                as of <?php echo date("h:i A"); ?> |
                                <a href="disposed.php">Dispose F325</a>
                            </small>

                        </div>

                        <div class="col-auto mr-4">
                            <i class="fas fa-trash fa-2x text-gray-300"></i>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- TABLE -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-striped table-bordered" id="dataTable" width="100%">
                    <thead class="table-info text-dark text-center">
                        <tr>
                            <th>Disposed Date</th>
                            <th>Document No.</th>
                            <th>View</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">

                        <?php

                        $query = mysqli_query($conn, "

                            SELECT 
                            f.f325number,
                            MAX(h.dateprocessed) as dateprocessed

                            FROM dbf325number f

                            LEFT JOIN dbhistory h 
                            ON h.processnumber = f.f325number

                            WHERE 
                            f.status='disposed'
                            AND f.emaildate BETWEEN '2023-01-01' AND NOW()

                            GROUP BY f.f325number

                            ORDER BY dateprocessed DESC

                            ");

                        while ($row = mysqli_fetch_assoc($query)) {
                        ?>

                            <tr>

                                <td><?php echo $row['dateprocessed']; ?></td>

                                <td><?php echo $row['f325number']; ?></td>

                                <td>
                                    <a class="btn btn-info btn-sm"
                                        onclick="window.open('view_disposed.php?f325number=<?php echo $row['f325number']; ?>')">
                                        View
                                    </a>
                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>

<?php include_once("footer.php"); ?>