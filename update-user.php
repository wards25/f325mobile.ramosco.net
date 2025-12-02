<?php
include('dbconnect.php');

$id = intval($_POST['id']);
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$fname = $_POST['fname'] ?? '';
$active = isset($_POST['active']) ? 1 : 0;

// Admin / Semi Admin
$admin = ($_POST['access'] ?? '') == 1 ? 1 : 0;
$semiadmin = ($_POST['access'] ?? '') == 2 ? 1 : 0;

// Companies
$companies = [];
$result = mysqli_query($conn, "SELECT id FROM dbcompany");
while($row = mysqli_fetch_assoc($result)) {
    $compId = $row['id'];
    $companies['comp'.$compId] = in_array($compId, $_POST['company'] ?? []) ? 1 : 0;
}

// Locations
$locations = [];
$result = mysqli_query($conn, "SELECT id FROM dblocation");
while($row = mysqli_fetch_assoc($result)) {
    $locId = $row['id'];
    $locations['loc'.$locId] = in_array($locId, $_POST['location'] ?? []) ? 1 : 0;
}

// Modules
$modules = [
    'store','inventory','import','importdop','print','schedule','clearing','manual','fordeduct','borfapps',
    'dmpiraw','deduction','deductdoc','paiddeduction','payment','returntosupplier','pulloutdoc','report','syssetting'
];
$moduleUpdates = [];
foreach($modules as $mod) {
    $moduleUpdates[$mod] = isset($_POST[$mod]) ? 1 : 0;
}

// Build update query
$updateFields = [
    "username='$username'",
    "password='$password'",
    "fname='$fname'",
    "admin='$admin'",
    "semiadmin='$semiadmin'",
    "active='$active'"
];
$updateFields = array_merge($updateFields, $companies, $locations, $moduleUpdates);

$updateSQL = "UPDATE dbuser SET " . implode(',', $updateFields) . " WHERE id='$id'";

if(mysqli_query($conn, $updateSQL)){
    echo "User updated successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}

$conn->close();
?>
