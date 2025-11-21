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
            $brcode = preg_replace("/[^\d+$]\b\w+/", "", trim(str_replace(array('Branch - ', '.', ']', '[', '(', ')'), '', $secondline)));
            $thirdline = fgets($open_file);
            $preparedby = trim(substr_replace(str_replace('Prepared by - ', '', $thirdline), '', strpos(str_replace('Prepared by - ', '', $thirdline), ' on ')));
            preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $thirdline, $datef325);
            $f325date = date('Y-m-d', strtotime(trim(str_replace('"', '', preg_replace('/\\\\/', '', preg_replace('/(\[|\]){2}/', '', json_encode($datef325)))))));
            fgets($open_file);
            $issuedby = trim(fgets($open_file));
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
                mysqli_query($conn,"INSERT INTO dbf325number(f325number,brcode,preparedby,issuedby,emaildate,f325date,vendor,tmnumber,drivername,platenumber,datesched,datecleared,arnumber,pageno,printremarks,logisticremarks,clearingremarks,cluster,location,deducttype,status,process,verificationdate,verificationreason,ilrno,stamped,cleared_time) 
                VALUES ('$f325number','$brcode','$preparedby','$issuedby','$emaildate','$f325date','$vendor','','','','0000-00-00','0000-00-00','','','','','','$cluster','$location','$deducttype','OPEN','UPLOADED','0000-00-00','','','','')");
                // echo "<pre>";
                // echo "SQL Query: $f325number, $brcode, $preparedby, $issuedby, $emaildate, $f325date, $vendor, $cluster, $location, $deducttype";
                // echo "</pre>";

                // Insert history
                $processed = 'Convert and Import.';
                mysqli_query($conn,"INSERT INTO dbhistory(processnumber,name,processed,dateprocessed,timeprocessed) VALUES ('$f325number','$username','$processed','$dateprocessed','$timeprocessed')");
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
                                    mysqli_query($conn, "INSERT INTO dbraw(f325number,mdccode,category,vendorcode,deducttype,dmpiclass,quantity,expiration,unitcost,costextended,reasoncode,arnumber,arreason,dmpireason,rcvdqty,dmpiref,deductref,deductqty,deductcostextended,datecleared,pulloutref,location,status,statusout,paymentstatus,skustatus,slstatus,skutype) VALUES ('$f325number','$mdccode','','$vendor','$deducttype','','$check_qty','$expire_date','$cost_each','$costextended','$reason_code','','','0','0','','','0','0','0000-00-00','','$location','OPEN','','','0','','') ");
                                    // echo "<pre>";
                                    // echo "SQL Query: $f325number, $mdccode, $vendor, $deducttype, $check_qty, $expire_date, $cost_each, $costextended, $reason_code, $location";
                                    // echo "</pre>";
                                }
                            }
                        }

                    }
                }
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