<?php
include("dbconnect.php");

if (isset($_FILES['files'])) {

    $data = []; // array to hold all rows

    foreach ($_FILES['files']['tmp_name'] as $index => $tmpFile) {

        $originalName = $_FILES['files']['name'][$index];
        $f325number = '';
        $brcode = '';
        $vendor = '';
        $status = '';

        $lines = file($tmpFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (preg_match('/Doc\.#\s*-\s*(\d{6,})/', $line, $m)) $f325number = trim($m[1]);
            if (preg_match('/Branch\s*-\s*(\d+)/', $line, $m)) $brcode = trim($m[1]);
            if (preg_match('/\((\d{3,})\s*\)/', $line, $m)) $vendor = trim($m[1]);
        }

        // Branch check
        $census_query = mysqli_query($conn, "SELECT code, status FROM dbcensus WHERE code='$brcode'");
        $fetch_census = mysqli_fetch_assoc($census_query);

        if (!$fetch_census) {
            $status = (preg_match('/\bRef\b/i', $f325number)) ? "Summary" : "Branch not exist";
        } else if ($fetch_census['status'] == '0') {
            $status = "Branch is deactivated";
        } else {
            $vendor_query = mysqli_query($conn, "SELECT * FROM dbcompany WHERE vendorcode='$vendor'");
            $fetch_vendor = mysqli_fetch_assoc($vendor_query);

            if (!$fetch_vendor) $status = "Vendor does not exist";
            else if ($fetch_vendor['active'] == '0') $status = "Vendor is not active";
            else {
                $process_query = mysqli_query($conn, "SELECT * FROM dbf325number WHERE f325number='$f325number'");
                $fetch_process = mysqli_fetch_assoc($process_query);
                $status = $fetch_process ? $fetch_process['process'] : "Ready";
            }
        }

        $data[] = [
            'filename' => $originalName,
            'f325' => $f325number,
            'status' => $status
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($data);
}
?>
