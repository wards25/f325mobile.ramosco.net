<?php 
session_start();
include_once("dbconnect.php");

//mime type
header('Content-Type: text/csv');
//tell browser what's the file name
header('Content-Disposition: attachment; filename="F325 Product List as of '.date('m-d-Y').'.csv"');
//no cache
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

fputcsv($output, array('CODE', 'ITEM CODE', 'BARCODE', 'CASECODE', 'DESCRIPTION', 'CATEGORY', 'VENDOR', 'DMPICODE', 'DMPIPACK', 'DMPICLASS', 'UOM', 'STATUS'));

$result = mysqli_query($conn,"SELECT * FROM dbproduct");

while($row = mysqli_fetch_array($result))
{
    // if($row['vendor']=='79449'){
    //     $vendor = 'RSDI';
    // }else if($row['vendor']=='24134'){
    //     $vendor = 'CLUTCH';
    // }else if($row['vendor']=='18565'){
    //     $vendor = 'EDGE';
    // }else if($row['vendor']=='85001'){
    //     $vendor = 'CLOCK';
    // }else if($row['vendor']=='22821'){
    //     $vendor = 'CARBON';
    // }else if($row['vendor']=='84088'){
    //     $vendor = 'SCAN';
    // }else if($row['vendor']=='1606'){
    //     $vendor = 'NESTLE';
    // }else if($row['vendor']=='8851'){
    //     $vendor = 'WYETH';
    // }else{
    //     $vendor = '';
    // }

    fputcsv($output, array($row['mdccode'],$row['itemcode'],$row['barcode'],$row['casecode'],$row['description'],$row['category'],$row['vendor'],$row['dmpicode'],$row['dmpipack'],$row['dmpiclassification'],$row['uom'],$row['active']));
}

fclose($output);

$conn->close();
?>