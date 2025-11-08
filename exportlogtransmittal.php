<?php 
session_start();
include('dbconnect.php');
$cleareddate = date("Y-m-d", strtotime($_POST['logdate']));
$timefrom = $_POST['timefrom'];
$timeto = $_POST['timeto'];

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="Log Transmittal '.$cleareddate.'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

if($_POST['type'] == 2){

    fputcsv($output, array('SKU CODE', 'DESCRIPTION', 'TOTAL QTY', 'CLEARED DATE', 'CLEARED TIME', 'COMPANY', 'LOCATION'));

    $query = "
        SELECT dbraw.mdccode, dbraw.datecleared, SUM(dbraw.rcvdqty) AS totalqty, 
               dbcompany.nickname AS company, dbraw.location, 
               dbproduct.description, dbf325number.cleared_time
        FROM dbraw
        LEFT JOIN dbcompany ON dbraw.vendorcode = dbcompany.vendorcode
        LEFT JOIN dbproduct ON dbraw.mdccode = dbproduct.mdccode
        LEFT JOIN dbf325number ON dbraw.f325number = dbf325number.f325number
        WHERE dbraw.status = 'cleared' 
            AND dbf325number.cleared_time BETWEEN '$timefrom' AND '$timeto'
            AND dbraw.datecleared = '$cleareddate'
            AND dbraw.location = 'cainta'
        GROUP BY dbraw.mdccode
        ORDER BY dbf325number.cleared_time;
    ";

    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, array(
                $row['mdccode'], 
                mysqli_real_escape_string($conn, utf8_encode(str_replace("\n", '',$row['description']))), 
                $row['totalqty'], 
                $row['datecleared'], 
                $row['cleared_time'],
                $row['company'], 
                $row['location']
            ));
        }
    } else {
        die("Query failed: " . mysqli_error($conn));
    }

}else{

    fputcsv($output, array('F325 NUMBER', 'SKU CODE', 'DESCRIPTION', 'QTY', 'CLEARED DATE', 'CLEARED TIME', 'COMPANY', 'LOCATION'));

    $query = "
        SELECT dbraw.f325number,dbraw.mdccode, dbraw.datecleared, dbraw.rcvdqty, 
               dbcompany.nickname AS company, dbraw.location, 
               dbproduct.description, dbf325number.cleared_time
        FROM dbraw
        LEFT JOIN dbcompany ON dbraw.vendorcode = dbcompany.vendorcode
        LEFT JOIN dbproduct ON dbraw.mdccode = dbproduct.mdccode
        LEFT JOIN dbf325number ON dbraw.f325number = dbf325number.f325number
        WHERE dbraw.status = 'cleared' 
            AND dbf325number.cleared_time BETWEEN '$timefrom' AND '$timeto'
            AND dbraw.datecleared = '$cleareddate'
            AND dbraw.location = 'cainta'
        GROUP BY dbraw.f325number,dbraw.mdccode
        ORDER BY dbf325number.cleared_time;
    ";

    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, array(
                $row['f325number'],
                $row['mdccode'], 
                mysqli_real_escape_string($conn, utf8_encode(str_replace("\n", '',$row['description']))), 
                $row['rcvdqty'], 
                $row['datecleared'], 
                $row['cleared_time'],
                $row['company'], 
                $row['location']
            ));
        }
    } else {
        die("Query failed: " . mysqli_error($conn));
    }

}

fclose($output);

$conn->close();
?>