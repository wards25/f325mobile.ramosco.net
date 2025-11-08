<?php
session_start();
// include mysql database configuration file
include_once("dbconnect.php");

header('Content-type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");

// delete prdlist in db 
mysqli_query($conn,"DELETE FROM dbproduct WHERE id");

// reset auto increment
mysqli_query($conn,"ALTER TABLE dbproduct AUTO_INCREMENT = 0 ");

if (isset($_POST['submit']))
{
 
    // Allowed mime types
    $fileMimes = array(
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'text/plain'
    );
 
    // Validate whether selected file is a CSV file
    if (!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $fileMimes))
    {
 
            // Open uploaded CSV file with read-only mode
            $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
 
            // Skip the first line
            fgetcsv($csvFile);

            // Parse data from CSV file line by line
            while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE)
            {
                // Get row data
                $code = $getData[0];
                $itemcode = utf8_encode(str_replace(array("'"), '', $getData[1]));
                $barcode = utf8_encode(str_replace(array("'"), '', $getData[2]));
                $casecode = utf8_encode(str_replace(array("'"), '', $getData[3]));
                $description = utf8_encode(str_replace(array("'"), '', $getData[4]));
                $description = strtolower($description);
                $description = ucwords($description);
                $category = strtoupper(utf8_encode(str_replace(array("'"), '', $getData[5])));
                $vendor = utf8_encode(str_replace(array("'"), '', $getData[6]));
                $dmpicode = utf8_encode(str_replace(array("'"), '', $getData[7]));
                $dmpipack = strtoupper(utf8_encode(str_replace(array("'"), '', $getData[8])));
                $dmpiclassification = strtoupper(utf8_encode(str_replace(array("'"), '', $getData[9])));
                $uom = strtoupper($getData[10]);
                $active = utf8_encode(str_replace(array("'"), '', $getData[11]));

                mysqli_query($conn, "INSERT INTO dbproduct (id,mdccode,itemcode,barcode,casecode,description,category,vendor,dmpicode,dmpipack,dmpiclassification,uom,active,batch) VALUES (NULL,'" . $code . "', '" . $itemcode . "', '" . $barcode . "' , '" . $casecode . "' , '" . $description . "', '" . $category . "', '" . $vendor . "', '" . $dmpicode . "', '" . $dmpipack . "', '" . $dmpiclassification . "', '" . $uom . "', '" . $active . "', '')");
            }
        }

            // Close opened CSV file
            // fclose($csvFile);

            $qstring = '?status=succ';
        }else{
            $qstring = '?status=err';
        }

// Redirect to the listing page
header("Location: import_product.php".$qstring);