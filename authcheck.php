<?php 
$user_check = $conn->prepare("SELECT user_type FROM tbl_users WHERE id = ?");
$user_check->bind_param("i", $_SESSION['id']);
$user_check->execute();
$current_user = $user_check->get_result()->fetch_assoc();
$user_check->close();

if (!$current_user || $current_user['user_type'] !== 'Admin') {
    http_response_code(404);
    header("Location: 404.php");
    exit;
}