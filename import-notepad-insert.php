<?php
session_start();
include('dbconnect.php');

date_default_timezone_set("Asia/Manila");
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");

$username = $_SESSION['fname'];

$emaildate = $_POST['emaildate'];

// Ensure files are uploaded
if (isset($_FILES['files']) && !empty($_FILES['files']['name'][0])) {
    // echo "<pre>";
    // print_r($_FILES['files']);
    // echo "</pre>";
    $files = $_FILES['files'];  // Array of uploaded files
    $total_files = count($files['name']);  // Total number of files uploaded

    // Loop through each uploaded file
    for ($i = 0; $i < $total_files; $i++) {
        $file_name = $files['name'][$i];
        $file_tmp_name = $files['tmp_name'][$i];
        $file_size = $files['size'][$i];
        $file_error = $files['error'][$i];

        if ($file_error === UPLOAD_ERR_OK) {
            // Process the uploaded file directly
            $open_file = fopen($file_tmp_name, 'r');
            fgets($open_file);  // Skip the first line
            $newname = trim(fgets($open_file));  // Read the second line
            $f325number = trim(str_replace('Doc.# - ', '', substr(preg_replace('/\s+/', ' ', $newname), strpos(preg_replace('/\s+/', ' ', $newname), 'Doc.# - '))));

            // Process the file content (extract details)
            $secondline = fgets($open_file);

            // Extract first number only
            if (preg_match('/Branch\s*-\s*(\d+)/i', $secondline, $match)) {
                $brcode = $match[1]; // e.g., "918"
            } else {
                $brcode = ''; // fallback if not found
            }

            // Optional: trim whitespace
            $brcode = trim($brcode);
            var_dump($secondline, $brcode);
            $thirdline = fgets($open_file);
            $preparedby = trim(str_replace('Prepared by - ', '', $thirdline));
            $preparedby = preg_replace('/\s+on\s+\d{2}\/\d{2}\/\d{4}$/', '', $preparedby); // remove "on 03/07/2026"

            // Detect encoding and convert to UTF-8
            $preparedby = mb_convert_encoding($preparedby, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

            // Replace accented/special characters with ASCII equivalents
            $preparedby = iconv('UTF-8', 'ASCII//TRANSLIT', $preparedby);

            // Remove any remaining non-printable characters
            $preparedby = preg_replace('/[^\x20-\x7E]/', '', $preparedby);
            var_dump($thirdline, $preparedby);
            preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $thirdline, $datef325);
            $f325date = date('Y-m-d', strtotime(trim(str_replace('"', '', preg_replace('/\\\\/', '', preg_replace('/(\[|\]){2}/', '', json_encode($datef325)))))));
            fgets($open_file);
            $issuedby = trim(fgets($open_file));
            $issuedby = trim($issuedby);

            // Convert encoding to UTF-8 safely
            $issuedby = mb_convert_encoding($issuedby, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

            // Replace accented/special characters with ASCII equivalents
            $issuedby = iconv('UTF-8', 'ASCII//TRANSLIT', $issuedby);

            // Remove any remaining non-printable characters
            $issuedby = preg_replace('/[^\x20-\x7E]/', '', $issuedby);
            fgets($open_file);
            fgets($open_file);
            $vendor = trim(preg_replace("/[^-0-9]+/", "", str_replace('Shipped To - ', ' ', fgets($open_file))));

            // Check if the F325 number exists in the database
            $f325_query = mysqli_query($conn, "SELECT * FROM dbf325number WHERE f325number='$f325number' ");
            $fetch_f325number = mysqli_fetch_array($f325_query);

            // If the F325 number doesn't exist, insert the data
            if (!is_array($fetch_f325number)) {
                // Get branch details
                $branch_query = mysqli_query($conn, "SELECT * FROM dbcensus WHERE code='$brcode' ");
                $fetch_branch = mysqli_fetch_array($branch_query);
                $cluster = $fetch_branch['cluster'];
                $location = $fetch_branch['location'];
                $deducttype = $fetch_branch['deducttype'];

                // Insert data into dbf325number table
                $sql = "INSERT INTO dbf325number(f325number,brcode,preparedby,issuedby,emaildate,f325date,vendor,tmnumber,drivername,platenumber,datesched,datecleared,arnumber,pageno,printremarks,logisticremarks,clearingremarks,cluster,location,deducttype,status,process,verificationdate,verificationreason,ilrno,stamped,cleared_time) 
                VALUES ('$f325number','$brcode','$preparedby','$issuedby','$emaildate','$f325date','$vendor','','','', NULL , NULL ,'','','','','','$cluster','$location','$deducttype','OPEN','UPLOADED', NULL ,'','','','')";
                if (!mysqli_query($conn, $sql)) {
                    echo "ERROR inserting dbf325number: " . mysqli_error($conn);
                }
                // echo "<pre>";
                // echo "SQL Query: $f325number, $brcode, $preparedby, $issuedby, $emaildate, $f325date, $vendor, $cluster, $location, $deducttype";
                // echo "</pre>";

                // Insert history
                $processed = 'Convert and Import.';
                $sql1 = "INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')";
                if (!mysqli_query($conn, $sql1)) {
                    echo "ERROR inserting DBRAW: " . mysqli_error($conn);
                }
                // echo "<pre>";
                // echo "SQL Query: $f325number, $username, $processed, $dateprocessed, $timeprocessed";
                // echo "</pre>";

                while (!feof($open_file)) {
                    $lines = fgets($open_file);
                    $mdccode = trim(preg_replace("/[^-0-9]+/", "", strtok(trim(preg_replace('/\s+/', ' ', $lines)), ' ')));
                    if (strlen($mdccode) >= 6 && strlen($mdccode) <= 7) {
                        $mdccode;
                        $check_qty = trim(strtok(str_replace($mdccode, '', preg_replace('/\s+/', ' ', $lines)), ' '));
                        if (ctype_digit($check_qty)) {
                            $mdccode;
                            $check_qty;

                            preg_match_all('/\d{2}\/\d{2}\/\d{2}/', $lines, $result);
                            $expire_date = trim(str_replace('"', '', preg_replace('/\\\\/', '', preg_replace('/(\[|\]){2}/', '', json_encode($result)))));
                            preg_match_all('/\d+\.\d+\b/', preg_replace('/\s+/', ' ', $lines), $cost);
                            $cost_each = trim(str_replace(array('[', ']', '"'), '', substr_replace(preg_replace('/(\[|\]){2}/', '', json_encode($cost)), '', strpos(preg_replace('/(\[|\]){2}/', '', json_encode($cost)), ','))));

                            $costextended = $cost_each * $check_qty;
                            if (is_numeric($cost_each)) {
                                $cost_position = strpos(preg_replace('/\s+/', ' ', $lines), $cost_each);
                                $reason_code = substr(preg_replace('/\s+/', ' ', $lines), $cost_position - 2, 1);

                                // get branch detail
                                $branch_query = mysqli_query($conn, "SELECT * FROM dbcensus WHERE code='$brcode' ");
                                $fetch_branch = mysqli_fetch_array($branch_query);
                                $location = $fetch_branch['location'];
                                $deducttype = $fetch_branch['deducttype'];

                                $check_query = mysqli_query($conn, "SELECT * FROM dbraw WHERE f325number='$f325number' AND mdccode='$mdccode' ");
                                $fetch_check = mysqli_fetch_array($check_query);

                                if (is_array($fetch_check)) {
                                    // not inserted
                                } else {
                                    // var_dump($f325number, $mdccode, $vendor, $deducttype, $check_qty, $expire_date, $cost_each, $costextended, $reason_code, $location);
                                    $sql2 = "SELECT category FROM dbproduct WHERE mdccode='$mdccode'";
                                    $category_result = mysqli_query($conn, $sql2);
                                    $category_row = mysqli_fetch_array($category_result);
                                    $category = $category_row['category'];
                                    $sql3 = "INSERT INTO dbraw(f325number,mdccode, category,vendorcode,deducttype,dmpiclass,quantity,expiration,unitcost,costextended,reasoncode,arnumber,arreason,dmpireason,rcvdqty,dmpiref,deductref,deductqty,deductcostextended,datecleared,pulloutref,location,status,statusout,paymentstatus,skustatus,slstatus,skutype,forpullout,forcharging) VALUES ('$f325number','$mdccode','$category','$vendor','$deducttype','','$check_qty','$expire_date','$cost_each','$costextended','$reason_code','','','0','0','','','0','0',NULL,'','$location','OPEN','','','0','','','0','0') ";
                                    if (!mysqli_query($conn, $sql3)) {
                                        echo "ERROR inserting dbraw: " . mysqli_error($conn);
                                    }
                                    // echo "<pre>";
                                    // echo "SQL Query: $f325number, $mdccode, $vendor, $deducttype, $check_qty, $expire_date, $cost_each, $costextended, $reason_code, $location";
                                    // echo "</pre>";
                                }
                            }
                        }
                    }
                }
                // header("Location: import-notepad.php?status=succ");
            }
            // Close the open file
            fclose($open_file);
        } else {
            // Handle the file upload error
            echo "Error uploading file: " . $file_name;
        }
    }
} else {
    echo "No files were uploaded.";
}

$conn->close();
