<?php
session_start();
include_once('dbconnect.php');
include_once('header.php');
include_once('nav.php');
?>
<form class="form-report-export" method="POST" action="export-pullout-process.php">
    <table class="tbl-filter">
        <tr>
            <th class="tbl-filter-th">Location</th>
            <td class="tbl-filter-td">
                <select class="select-withBorder" name="select_location">
                    <option value="ALL">ALL</option>
                    <?php
                    $loc_query = "SELECT * FROM dblocation WHERE active='1' ";
                    // location
                    $location = "location='' ";

                    for ($loc = 1; $loc <= 10; $loc++) {
                        // get location
                        $location_query = mysqli_query($conn, "SELECT * FROM dblocation WHERE id='$loc' ");
                        $fetch_location = mysqli_fetch_array($location_query);

                        if ($_SESSION['loc' . $fetch_location['id']] == '1') {
                            $location .= "OR location='" . $fetch_location['location'] . "' ";
                        }
                    }

                    $loc_query .= "AND (" . $location . ") ";
                    $loc_query .= "ORDER BY location";

                    $loc_allow_query = mysqli_query($conn, $loc_query);

                    while ($fetch_allow_loc = mysqli_fetch_array($loc_allow_query)) {
                        ?>
                        <option value="<?php echo $fetch_allow_loc['location']; ?>">
                            <?php echo $fetch_allow_loc['location']; ?></option>
                        <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="tbl-filter-td tbl-filter-button" colspan="2">
                <button type="submit" class="button-export-style">Export</button>
            </td>
        </tr>
    </table>
</form>
<?php include_once('footer.php'); ?>