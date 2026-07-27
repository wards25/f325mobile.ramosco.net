<?php 
session_start();
include('dbconnect.php');

$mode     = $_POST['mode'] ?? 'daily';
$timefrom = $_POST['timefrom'];
$timeto   = $_POST['timeto'];
$location = $_POST['location'];

if ($mode === 'weekly') {
    $datefrom = date("Y-m-d", strtotime($_POST['weekfrom']));
    $dateto   = date("Y-m-d", strtotime($_POST['weekto']));
    $filename = $location . ' - Weekly - Log Transmittal - ' . $datefrom . ' to ' . $dateto . '.csv';
} else {
    $datefrom = date("Y-m-d", strtotime($_POST['logdate']));
    $dateto   = $datefrom;
    $filename = $location . ' - Daily - Log Transmittal - ' . $datefrom . '.csv';
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

if($_POST['type'] == 2){

    fputcsv($output, array('SKU CODE', 'DESCRIPTION', 'TOTAL QTY', 'COST EXTENDED' ,'CLEARED DATE', 'CLEARED TIME', 'COMPANY', 'LOCATION', 'PRINCIPAL'));

    $query = "
        SELECT DISTINCT dbraw.mdccode, dbraw.datecleared, SUM(dbraw.rcvdqty) AS totalqty, dbraw.category,
               dbcompany.nickname AS company, dbraw.location, 
               dbproduct.description, dbf325number.cleared_time,
               dbraw.costextended
        FROM dbraw
        LEFT JOIN dbcompany ON dbraw.vendorcode = dbcompany.vendorcode
        LEFT JOIN dbproduct ON dbraw.mdccode = dbproduct.mdccode AND dbraw.vendorcode = dbproduct.vendor
        LEFT JOIN dbf325number ON dbraw.f325number = dbf325number.f325number
        WHERE dbraw.status = 'CLEARED' 
            AND dbraw.statusout = 'CLEARED'
            AND dbf325number.cleared_time BETWEEN '$timefrom' AND '$timeto'
            AND dbraw.datecleared BETWEEN '$datefrom' AND '$dateto'
            AND dbraw.location = '$location'
        ORDER BY dbf325number.cleared_time;
    ";

    $result = mysqli_query($conn, $query);
    if(!$result){
        die(mysqli_error($conn));
    }
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, array(
                $row['mdccode'], 
                mysqli_real_escape_string($conn, utf8_encode(str_replace("\n", '',$row['description']))), 
                $row['totalqty'], 
                $row['costextended'],
                $row['datecleared'], 
                $row['cleared_time'],
                $row['company'], 
                $row['location'],
                $row['category']
            ));
        }
    } else {
        die("Query failed: " . mysqli_error($conn));
    }

} else {

    fputcsv($output, array('F325 NUMBER', 'SKU CODE', 'DESCRIPTION', 'QTY', 'COST EXTENDED' ,'CLEARED DATE', 'CLEARED TIME', 'COMPANY', 'LOCATION', 'PRINCIPAL' ,'FOR PULL OUT', 'FOR CHARGING'));

    $query = "
        SELECT DISTINCT dbraw.f325number, dbraw.mdccode, dbraw.datecleared, dbraw.rcvdqty, dbraw.category,
               dbcompany.nickname AS company, dbraw.location, 
               dbproduct.description, dbf325number.cleared_time,
               dbraw.costextended
        FROM dbraw
        LEFT JOIN dbcompany ON dbraw.vendorcode = dbcompany.vendorcode
        LEFT JOIN dbproduct ON dbraw.mdccode = dbproduct.mdccode AND dbraw.vendorcode = dbproduct.vendor
        LEFT JOIN dbf325number ON dbraw.f325number = dbf325number.f325number
        WHERE dbraw.status = 'CLEARED' 
        AND dbraw.statusout = 'CLEARED'
            AND dbf325number.cleared_time BETWEEN '$timefrom' AND '$timeto'
            AND dbraw.datecleared BETWEEN '$datefrom' AND '$dateto'
            AND dbraw.location = '$location'
        ORDER BY dbf325number.cleared_time;
    ";

    $result = mysqli_query($conn, $query);
    if(!$result){
        die(mysqli_error($conn));
    }
    $for_pullout = '0';
    $for_charging = '0';

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, array(
                $row['f325number'],
                $row['mdccode'], 
                mysqli_real_escape_string($conn, utf8_encode(str_replace("\n", '',$row['description']))), 
                $row['rcvdqty'], 
                $row['costextended'],
                $row['datecleared'], 
                $row['cleared_time'],
                $row['company'], 
                $row['location'],
                $row['category'],
                $for_pullout,
                $for_charging
            ));
        }
    } else {
        die("Query failed: " . mysqli_error($conn));
    }

}

fclose($output);

$conn->close();
?>