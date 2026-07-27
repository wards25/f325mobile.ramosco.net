<?php
session_start();
include_once 'dbconnect.php';

date_default_timezone_set("Asia/Manila");
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");

if (isset($_POST['btn-login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['pass'] ?? '');

    if ($username === '' || $password === '') {
        header("Location: index.php?status=err");
        exit;
    }

    // Look up by username only — never build password comparison into the SQL itself.
    $stmt = $conn->prepare("SELECT * FROM tbl_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $fetch_login = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($fetch_login && password_verify($password, $fetch_login['password'])) {

        if ((int) $fetch_login['status'] === 1) {

            if (!empty($_POST['remember'])) {
                setcookie('username', $_POST['username'], time() + 3600);
            } else {
                setcookie('username', "", time() - 3600);
            }

            $_SESSION['id']        = $fetch_login['id'];
            $_SESSION['username']  = $fetch_login['username'];
            $_SESSION['fname']     = $fetch_login['fullname'];
            $_SESSION['user_type'] = $fetch_login['user_type']; 
            
            $module_fields = [
                'census', 'productlist', 'import_notepad', 'import_pu_charge', 'import_nestle_sku',
                'print', 'schedule', 'clearing', 'shortlanded', 'manual', 'pullout', 'deduct',
                'report', 'borf', 'settings'
            ];
            foreach ($module_fields as $field) {
                $_SESSION[$field] = !empty($fetch_login[$field]) ? 1 : 0;
            }

            // Login history
            $hist_stmt = $conn->prepare("INSERT INTO dbloginhistory (username, dateprocessed, timeprocessed) VALUES (?, ?, ?)");
            $hist_stmt->bind_param("sss", $_SESSION['fname'], $dateprocessed, $timeprocessed);
            $hist_stmt->execute();
            $hist_stmt->close();

            header("Location: loading.php");
            exit;

        } else {
            header("Location: index.php?status=activate");
            exit;
        }

    } else {
        header("Location: index.php?status=err");
        exit;
    }
}
?>