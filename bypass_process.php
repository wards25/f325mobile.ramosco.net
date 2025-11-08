<?php
session_start();
// include mysql database configuration file
include_once("dbconnect.php");
include_once("dbf325.php");

header('Content-type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");

    // delete prdlist in db
    mysqli_query($f325conn,"DELETE FROM dbproduct WHERE id");
    // reset auto increment
    mysqli_query($f325conn,"ALTER TABLE dbproduct AUTO_INCREMENT = 1 ");

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
                        $mdccode = $getData[0];
                        $itemcode = $getData[1];
                        $description = utf8_encode(str_replace(array("'"), '', $getData[2]));
                        $category = utf8_encode(str_replace(array("'"), '', $getData[3]));
                        $vendor = $getData[4];
                        $dmpicode = $getData[5];
                        $dmpipack = $getData[6];
                        $dmpiclass = $getData[7];
                        $uom = strtoupper($getData[8]);
                        $active = $getData[9];

                        $i=1;

                        if ($i==1)
                        {
                            mysqli_query($f325conn, "INSERT INTO dbproduct (id,mdccode,itemcode,description,category,vendor,dmpicode,dmpipack,dmpiclassification,uom,active,batch) VALUES (NULL, '" . $mdccode . "', '" . $itemcode . "', '" . $description . "', '" . $category . "', '" . $vendor . "', '" . $dmpicode . "', '" . $dmpipack . "', '" . $dmpiclass . "', '" . $uom . "', '" . $active . "', '')");     
                        }
                        else
                        {
                           echo "ERROR!";
                        }
                    }

                    $qstring = '?status=succ';
                }else{
                    $qstring = '?status=err';
                }

            }else{
                $qstring = '?status=invalid_file';
            }

// Redirect to the listing page
header("Location: bypass.php".$qstring);