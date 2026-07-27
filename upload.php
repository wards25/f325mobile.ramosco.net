<?php

// Optional: simple security token
$token = $_POST['token'] ?? '';
$expected_token = 'my_secure_token_123';

if ($token !== $expected_token) {
    http_response_code(403);
    echo "INVALID TOKEN";
    exit;
}

// Check if file exists
if (!isset($_FILES['file'])) {
    echo "NO FILE";
    exit;
}

$target_dir = __DIR__ . "/uploads/stamped/";

// Create folder if not exists
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

$filename = basename($_FILES["file"]["name"]);
$target_file = $target_dir . $filename;

// Move file
if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
    echo "SUCCESS";
} else {
    echo "FAILED";
}