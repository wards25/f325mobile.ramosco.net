<?php
/**
 * maintenance_check.php
 * 
 * Include this at the top of any module page AFTER session_start() and dbconnect.php
 * 
 * Usage:
 *   $maintenanceModule = 'exportprincipal';
 *   include('maintenance_check.php');
 * 
 * Set $maintenanceModule to the module_key in tbl_maintenance table.
 */

if (!isset($maintenanceModule)) {
    $maintenanceModule = '';
}

$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] == 1;

if (!$isAdmin) {

    // Check full website maintenance first
    $sqlFull = "SELECT is_maintenance, message, module_name FROM tbl_maintenance WHERE module_key = 'full_website' LIMIT 1";
    $resFull = mysqli_query($conn, $sqlFull);
    $rowFull = $resFull ? mysqli_fetch_assoc($resFull) : null;

    if ($rowFull && $rowFull['is_maintenance'] == 1) {
        $moduleName          = $rowFull['module_name'];
        $maintenanceMessage  = $rowFull['message'];
        include('maintenance.php');
        exit();
    }

    // Check specific module maintenance
    if (!empty($maintenanceModule)) {
        $safeModule = mysqli_real_escape_string($conn, $maintenanceModule);
        $sqlMod = "SELECT is_maintenance, message, module_name FROM tbl_maintenance WHERE module_key = '$safeModule' LIMIT 1";
        $resMod = mysqli_query($conn, $sqlMod);
        $rowMod = $resMod ? mysqli_fetch_assoc($resMod) : null;

        if ($rowMod && $rowMod['is_maintenance'] == 1) {
            $moduleName         = $rowMod['module_name'];
            $maintenanceMessage = $rowMod['message'];
            include('maintenance.php');
            exit();
        }
    }
}
?>