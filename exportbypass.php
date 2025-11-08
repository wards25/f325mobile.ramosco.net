<?php 
session_start();
include_once('dbconnect.php');
include_once('dbf325.php');

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="Product List as of '.date('m-d-Y').'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

fputcsv($output, array('MDC CODE', 'ITEM CODE', 'DESCRIPTION', 'CATEGORY', 'VENDOR', 'DMPI CODE', 'DMPI PACK', 'DMPI CLASS', 'UOM', 'ACTIVE'));

$result = mysqli_query($f325conn,"SELECT * FROM dbproduct ORDER BY id ASC ");

while($row = mysqli_fetch_array($result))
{

    fputcsv($output, array($row['mdccode'],$row['itemcode'],$row['description'],$row['category'],$row['vendor'],$row['dmpicode'],$row['dmpipack'],$row['dmpiclassification'],$row['uom'],$row['active']));
}

fclose($output);

$f325conn->close();
?>