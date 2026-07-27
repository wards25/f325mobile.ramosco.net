<?php
include('dbconnect.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_FILES['csv_file']['name'] != '') {
    $file_data = fopen($_FILES['csv_file']['tmp_name'], 'r');
    fgetcsv($file_data); // Skip header row

    while ($row = fgetcsv($file_data)) {
        $f325number = str_replace("'", "", trim($row[0] ?? ''));
        $mdccode = trim($row[1] ?? '');
        $description = trim($row[2] ?? '');
        $qty = (int) ($row[3] ?? 0);
        $cost_extended = number_format((float) ($row[4] ?? 0), 2);
        $company = trim($row[7] ?? '');
        $location = trim($row[8] ?? '');
        $principal = trim($row[9] ?? '');
        $forpullout = (int) ($row[10] ?? 0);
        $forcharging = (int) ($row[11] ?? 0);

        $rowErrors = [];
        $rowWarnings = [];

        // ── Check 1: F325 exists in DB ──────────────────────────────
        $check_query = mysqli_query($conn, "SELECT f325number, status FROM dbf325number WHERE f325number='$f325number'");
        $count = mysqli_num_rows($check_query);

        if ($count < 1) {
            echo "<tr class='table-danger'>";
            echo "<td>" . htmlspecialchars($f325number) . "</td>";
            echo "<td>" . htmlspecialchars($mdccode) . "</td>";
            echo "<td>" . htmlspecialchars($description) . "</td>";
            echo "<td>" . htmlspecialchars($qty) . "</td>";
            echo "<td>" . htmlspecialchars($cost_extended) . "</td>";
            echo "<td>" . htmlspecialchars($company) . "</td>";
            echo "<td>" . htmlspecialchars($location) . "</td>";
            echo "<td>" . htmlspecialchars($principal) . "</td>";
            echo "<td>" . htmlspecialchars($forpullout) . "</td>";
            echo "<td>" . htmlspecialchars($forcharging) . "</td>";
            echo "<td>ERROR<br><small>F325 number not found in the database.</small></td>";
            echo "</tr>";
            continue;
        }

        // ── Check 2: MDC Code exists in dbproduct ───────────────────
        $mdccode = trim($mdccode);
        $checkProduct = mysqli_query($conn, "SELECT mdccode FROM dbproduct WHERE mdccode='$mdccode' LIMIT 1");
        if (mysqli_num_rows($checkProduct) == 0) {
            $rowErrors[] = "SKU Code not found in products";
        }

        // ── Check 3: Invalid SKU or QTY ─────────────────────────────
        if (empty($mdccode) || $qty <= 0) {
            $rowErrors[] = "Invalid SKU or QTY";
        }

        // ── Check 4: Pullout + Charging must equal QTY ──────────────
        if (($forpullout + $forcharging) !== $qty) {
            $rowErrors[] = "Pullout + Charging must equal QTY";
        }

        // ── Check 5: Pullout already uploaded ───────────────────────

        //remove this AND (batchnumber_forpullout IS NULL OR batchnumber_forpullout = '') because sometimes it fail 
        if ($forpullout > 0) {
            $chkPullout = mysqli_query($conn, "
                SELECT id FROM dbraw 
                WHERE mdccode = '$mdccode' 
                  AND f325number = '$f325number' 
                  AND forpullout > 0
                LIMIT 1
            ");
            if (mysqli_num_rows($chkPullout) > 0) {
                $rowWarnings[] = "Pullout already uploaded";
            }
        }

        // ── Check 6: Charging already uploaded ──────────────────────

        //AND (batchnumber_forcharging IS NULL OR batchnumber_forcharging = '')
        if ($forcharging > 0) {
            $chkCharge = mysqli_query($conn, "
                SELECT id FROM dbraw 
                WHERE mdccode = '$mdccode' 
                  AND f325number = '$f325number'
                  AND forcharging > 0 
                LIMIT 1
            ");
            if (mysqli_num_rows($chkCharge) > 0) {
                $rowWarnings[] = "Charging already uploaded";
            }
        }

        // ── Determine row class & status ─────────────────────────────
        if (!empty($rowErrors)) {
            $rowClass = 'table-danger';
            $status = 'ERROR';
            $statusText = implode(', ', $rowErrors);
        } elseif (!empty($rowWarnings)) {
            $rowClass = 'table-warning';
            $status = 'WARNING';
            $statusText = implode(', ', $rowWarnings);
        } else {
            $rowClass = 'table-success';
            $status = 'FOR UPLOAD';
            $statusText = 'valid';
        }

        // ── Output row ───────────────────────────────────────────────
        echo "<tr class='{$rowClass}'>";
        echo "<td>" . htmlspecialchars($f325number) . "</td>";
        echo "<td>" . htmlspecialchars($mdccode) . "</td>";
        echo "<td>" . htmlspecialchars($description) . "</td>";
        echo "<td>" . htmlspecialchars($qty) . "</td>";
        echo "<td>" . htmlspecialchars($cost_extended) . "</td>";
        echo "<td>" . htmlspecialchars($company) . "</td>";
        echo "<td>" . htmlspecialchars($location) . "</td>";
        echo "<td>" . htmlspecialchars($principal) . "</td>";
        echo "<td>" . htmlspecialchars($forpullout) . "</td>";
        echo "<td>" . htmlspecialchars($forcharging) . "</td>";
        echo "<td>" . htmlspecialchars($status) . "<br><small>{$statusText}</small></td>";
        echo "</tr>";
    }

    fclose($file_data);
} else {
    echo "<tr><td colspan='9'>Please select a CSV file.</td></tr>";
}

$conn->close();
?>