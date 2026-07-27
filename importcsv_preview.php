<?php
include('dbconnect.php');

error_reporting(E_ALL);
ini_set('display_errors',1);

if($_FILES['file']['name'] != '')
{
    $file_data = fopen($_FILES['file']['tmp_name'], 'r');

    // skip header
    fgetcsv($file_data);

    while($row = fgetcsv($file_data))
    {
        $f325number = str_replace("'", "", $row[0]);
        $code = $row[1];
        $tmnumber = $row[2];
        $drivername = $row[3];
        $platenumber = $row[4];
        $datesched = $row[5];
        $remarks = $row[6];

        $check_query = mysqli_query($conn, "SELECT f325number, status FROM dbf325number WHERE f325number='$f325number'");
        $count = mysqli_num_rows($check_query);

        if($count >= 1)
        {
            $fetch = mysqli_fetch_array($check_query);
            $status = $fetch['status'];

            if($status == 'PRINTED')
            {
                $bg = '';
                $text_color = '';

                if(empty($tmnumber) || empty($platenumber) || empty($drivername) || empty($datesched))
                {
                    $bg = "background:#f8d7da";  // red-ish
                    $text_color = "color:#721c24;";
                }
                ?>
                <tr class="table-success" style="<?php echo $bg . ';' . $text_color; ?>">
                    <td>
                        <?php echo htmlspecialchars($f325number); ?>
                        <input type="hidden" name="f325number[]" value="<?php echo htmlspecialchars($f325number); ?>">
                    </td>
                    <td>
                        <?php echo htmlspecialchars($code); ?>
                        <input type="hidden" name="code[]" value="<?php echo htmlspecialchars($code); ?>">
                    </td>
                    <td>
                        <?php echo htmlspecialchars($tmnumber); ?>
                        <input type="hidden" name="tmnumber[]" value="<?php echo htmlspecialchars($tmnumber); ?>">
                    </td>
                    <td>
                        <?php echo htmlspecialchars($drivername); ?>
                        <input type="hidden" name="drivername[]" value="<?php echo htmlspecialchars($drivername); ?>">
                    </td>
                    <td>
                        <?php echo htmlspecialchars($platenumber); ?>
                        <input type="hidden" name="platenumber[]" value="<?php echo htmlspecialchars($platenumber); ?>">
                    </td>
                    <td>
                        <?php echo htmlspecialchars($datesched); ?>
                        <input type="hidden" name="datesched[]" value="<?php echo htmlspecialchars($datesched); ?>">
                    </td>
                    <td>
                        <?php echo htmlspecialchars($remarks); ?>
                        <input type="hidden" name="remarks[]" value="<?php echo htmlspecialchars($remarks); ?>">
                    </td>
                    <td><?php echo htmlspecialchars($status); ?></td>
                    <td>
                        <?php if($bg != '') { ?>
                            <button type="button" class="btn btn-danger btn-sm button-raw-delete">X</button>
                        <?php } else { ?>
                            <button type="button" class="btn btn-danger btn-sm button-raw-delete">X</button>
                        <?php } ?>
                    </td>
                </tr>
                <?php
            }
            else if($status == 'CLEARED'){
                ?>
                <tr class="table-info">
                    <td><?php echo htmlspecialchars($f325number); ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align:center; font-weight:bold;">This F325 is already Cleared.</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm button-raw-delete">X</button>
                    </td>
                </tr>
                <?php
            }
            else
            {
                // Scheduled — warning yellow background, full 9 columns
                ?>
                <tr class="table-warning">
                    <td><?php echo htmlspecialchars($f325number); ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align:center; font-weight:bold;">This F325 is already scheduled.</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm button-raw-delete">X</button>
                    </td>
                </tr>
                <?php
            }
        }
        else
        {
            // Not found — error red background, full 9 columns
            ?>
            <tr class="table-danger">
                <td><?php echo htmlspecialchars($f325number); ?></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td style="text-align:center; font-weight:bold;">F325 number not found in database.</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm button-raw-delete">X</button>
                </td>
            </tr>
            <?php
        }
    }

    fclose($file_data);

}
else
{
    ?>
    <tr>
        <td colspan="9">Please select CSV file</td>
    </tr>
    <?php
}

$conn->close();
?>