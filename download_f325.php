<?php
// Define the path to the image folder (replace with your actual path)
$module = $_GET['folder'];

$image_folder = "filepicture/".$module."/";

// Get the filename from the URL parameter (if available)
$filename = $_GET['f325number'].'.jpg';

// Check if the file exists and is within the designated folder
if (file_exists($image_folder . $filename) && strpos($image_folder . $filename, $image_folder) === 0) {
    // Set headers to force a download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Read the file from disk and output it
    readfile($image_folder . $filename);

    // Exit to prevent any further processing
    exit();

} else {
    
    // Handle the error case (file not found or invalid)
    header('HTTP/1.1 404 Not Found');
    echo "File not found or invalid.";
}
?>