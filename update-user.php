<?php
session_start();
include_once('dbconnect.php');

// Only logged-in admins can update
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Get POST data
$id = intval($_POST['id']);
$fname = mysqli_real_escape_string($conn, $_POST['fname']);
$username = mysqli_real_escape_string($conn, $_POST['username']);
$active = intval($_POST['active']);
$access = intval($_POST['access']);
$password = $_POST['password']; // plain text now

// Determine admin/semiadmin
$admin = 0;
$semiadmin = 0;
if ($access === 1) $admin = 1;
elseif ($access === 2) $semiadmin = 1;

// Location checkboxes (loc1-loc10)
$locs = [];
for ($i = 1; $i <= 10; $i++) {
    $locs[$i] = isset($_POST['location'][$i]) ? 1 : 0;
}

// Password update
$password_sql = '';
if (!empty($password)) {
    $password_sql = ", password='" . mysqli_real_escape_string($conn, $password) . "'";
}

$sql = "UPDATE dbuser SET
    fname='$fname',
    username='$username',
    active=$active,
    admin=$admin,
    semiadmin=$semiadmin,
    loc1={$locs[1]},
    loc2={$locs[2]},
    loc3={$locs[3]},
    loc4={$locs[4]},
    loc5={$locs[5]},
    loc6={$locs[6]},
    loc7={$locs[7]},
    loc8={$locs[8]},
    loc9={$locs[9]},
    loc10={$locs[10]}
    $password_sql
    WHERE id=$id";

if (mysqli_query($conn, $sql)) {
    header("Location: account.php?success=1");
    exit;
} else {
    echo "Error: " . mysqli_error($conn);
}

$conn->close();
