<?php
$uri = $_SERVER['REQUEST_URI'];
?>
<?php
include_once("dbconnect.php");
// Settings visibility now comes straight from the session (set at login),
// no second DB query needed.
$system_setting = $_SESSION['settings'] ?? 0;

// --- Avatar helpers: initials + deterministic color, like the reference design ---
function nav_get_initials($name)
{
    $name = trim((string) $name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/', $name);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    } elseif (strlen($parts[0]) > 1) {
        $initials .= strtoupper(substr($parts[0], 1, 1));
    }
    return $initials;
}

function nav_get_avatar_color($seed)
{
    $palette = ['#6c5ce7', '#00b894', '#0984e3', '#e17055', '#d63031', '#00cec9', '#e84393', '#fdcb6e'];
    $hash = crc32((string) $seed);
    return $palette[$hash % count($palette)];
}

$avatar_initials = nav_get_initials($_SESSION['fname'] ?? '');
$avatar_color = nav_get_avatar_color($_SESSION['username'] ?? ($_SESSION['fname'] ?? 'user'));
?>

<body id="page-top">
    <!-- <div class="env-banner">
        DATABASE IS NEUTRALIZED — STAGING ENVIRONMENT
    </div> -->
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #915c83;">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon">
                </div>
                <div class="sidebar-brand-text mx-3"><img src="img/logo.png" class="img-fluid" style="height:30px;">
                    <small>F325/DESKTOP</small>
                </div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Search
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSearch"
                    aria-expanded="true" aria-controls="collapseSearch">
                    <i class="fas fa-fw fa-search"></i>
                    <span>Search F325</span>
                </a>
                <div id="collapseSearch" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Search:</h6>
                        <a class="collapse-item" href="search.php">Search F325</a>
                        <?php
                        if (!empty($_SESSION['storelist']) && $_SESSION['storelist'] == '1') {
                            echo '<a class="collapse-item" href="store-list.php">Store List</a>';
                        }
                        if (!empty($_SESSION['productlist']) && $_SESSION['productlist'] == '1') {
                            echo ' <a class="collapse-item" href="product-list.php">Product List</a>';
                        }
                        ?>
                        <!-- <a class="collapse-item" href="search_sl.php">Shortlanded F325</a> -->
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Modules
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <?php
            $has_import_access = (
                (!empty($_SESSION['import_rtv']) && $_SESSION['import_rtv'] == '1') ||
                (!empty($_SESSION['import_notepad']) && $_SESSION['import_notepad'] == '1') ||
                (!empty($_SESSION['import_pu_charge']) && $_SESSION['import_pu_charge'] == '1') ||
                (!empty($_SESSION['import_nestle_sku']) && $_SESSION['import_nestle_sku'] == '1')
            );
            ?>

            <?php if ($has_import_access): ?>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" ...>
                        <i class="fas fa-file-import"></i>
                        <span>Importing</span>
                    </a>
                    <div id="collapseTwo" class="collapse" ...>
                        <div class="bg-white py-2 collapse-inner rounded">
                            <?php
                            if (!empty($_SESSION['import_rtv']) && $_SESSION['import_rtv'] == '1') {
                                ?>
                                    <a class="collapse-item" href="import_rtv.php">Import RTV</a>
                                    <?php
                            }
                            ?>
                            <?php
                            if (!empty($_SESSION['import_notepad']) && $_SESSION['import_notepad'] == '1') {
                                ?>
                                    <a class="collapse-item" href="import-notepad.php">Import F325</a>
                                    <?php
                            }
                            ?>
                            <?php
                            if (!empty($_SESSION['import_pu_charge']) && $_SESSION['import_pu_charge'] == '1') {
                                ?>
                                    <a class="collapse-item" href="import_forpull_forcharge.php">Import Pullout -
                                        Charging</a>
                                    <?php
                            }
                            ?>
                            <?php
                            if (!empty($_SESSION['import_nestle_sku']) && $_SESSION['import_nestle_sku'] == '1') {
                                ?>
                                    <a class="collapse-item" href="import_sku_list.php">Nestle Sku List</a>
                                    <?php
                            }
                            ?>
                        </div>
                    </div>
                </li>
            <?php endif; ?>
            <?php
            // Precompute whether this user has access to anything inside "F325 Modules"
            $has_f325_module_access = (
                (!empty($_SESSION['print']) && $_SESSION['print'] == '1') ||
                (!empty($_SESSION['schedule']) && $_SESSION['schedule'] == '1') ||
                (!empty($_SESSION['clearing']) && $_SESSION['clearing'] == '1') ||
                (!empty($_SESSION['pulloutdoc']) && $_SESSION['pulloutdoc'] == '1') ||
                (!empty($_SESSION['payment']) && $_SESSION['payment'] == '1')
            );
            ?>

            <?php if ($has_f325_module_access): ?>
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseThree"
                        aria-expanded="true" aria-controls="collapseThree">
                        <i class="fas fa-fw fa-window-maximize"></i>
                        <span>F325 Modules</span>
                    </a>
                    <div id="collapseThree" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">F325 Modules:</h6>
                            <?php
                            if (!empty($_SESSION['print']) && $_SESSION['print'] == '1') {
                                echo '<a class="collapse-item" href="print-notepad.php">Print F325</a>';
                            }
                            if (!empty($_SESSION['schedule']) && $_SESSION['schedule'] == '1') {
                                echo '<a class="collapse-item" href="scheduled.php">Schedule F325</a>';
                            }
                            if (!empty($_SESSION['clearing']) && $_SESSION['clearing'] == '1') {
                                echo '<a class="collapse-item" href="clearing.php">Clearing</a>';
                                echo '<a class="collapse-item" href="verification.php">Verification</a>';
                                echo '<a class="collapse-item" href="disposed.php">Disposed F325</a>';
                            }
                            ?>
                            <?php if (!empty($_SESSION['pulloutdoc']) && $_SESSION['pulloutdoc'] == '1'): ?>
                                <h6 class="collapse-header">Pull-Out</h6>
                                <a class="collapse-item" href="for_pullout.php">For Pull Out</a>
                                <a class="collapse-item" href="batchlist.php">For Pull Out Batch List</a>
                            <?php endif; ?>

                            <?php if (!empty($_SESSION['payment']) && $_SESSION['payment'] == '1'): ?>
                                <h6 class="collapse-header">Charging</h6>
                                <a class="collapse-item" href="for_charging.php">For Charging</a>
                                <a class="collapse-item" href="charging_batchlist.php">For Charging Batch List</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endif; ?>

            <?php
            if (!empty($_SESSION['payment']) && $_SESSION['payment'] == '1') {
                ?>
                <!-- Divider -->
                <hr class="sidebar-divider">

                <!-- Heading -->
                <div class="sidebar-heading">
                    Receivables
                </div>

                <!-- Nav Item - Pages Collapse Menu -->
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSL"
                        aria-expanded="true" aria-controls="collapseSL">
                        <i class="fas fa-fw fa-money-bill"></i>
                        <span>Shortlanded</span>
                    </a>
                    <div id="collapseSL" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">Shortlanded:</h6>
                            <a class="collapse-item" href="shortlanded_complete.php">Shortlanded List</a>
                            <a class="collapse-item" href="shortlanded.php">Unpaid F325</a>
                        </div>
                    </div>
                </li>

                <?php
            }
            ?>

            <?php
            if (!empty($_SESSION['manual']) && $_SESSION['manual'] == '1') {
                ?>
                <!-- Divider -->
                <hr class="sidebar-divider">

                <!-- Heading -->
                <div class="sidebar-heading">
                    Manual
                </div>

                <!-- Nav Item - Dashboard -->
                <li class="nav-item">
                    <a class="nav-link" href="manual.php">
                        <i class="fas fa-fw fa-plus-circle"></i>
                        <span>Add Manual</span></a>
                </li>
                <?php
            }
            ?>

            <?php
            if (!empty($_SESSION['report']) && $_SESSION['report'] == '1') {
                ?>
                <!-- Divider -->
                <hr class="sidebar-divider">

                <!-- Heading -->
                <div class="sidebar-heading">
                    Reports
                </div>

                <!-- Nav Item - Pages Collapse Menu -->
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseReport"
                        aria-expanded="true" aria-controls="collapseSL">
                        <i class="fas fa-fw fa-download"></i>
                        <span>Exporting</span>
                    </a>
                    <div id="collapseReport" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">Export:</h6>
                            <!-- <a class="collapse-item" href="exportraw.php">Export Per Status</a> -->
                            <a class="collapse-item" href="exportprincipal.php">Generate Report</a>
                            <a class="collapse-item" href="#" data-toggle="modal" data-target="#F325Transmittal">Generate
                                F325 Transmittal</a>
                            <a class="collapse-item" href="export-pullout-report.php">Generate Pullout Report</a>
                            <a class="collapse-item" href="exportshortlanded.php">Generate SL Report</a>
                            <a class="collapse-item" href="#" data-toggle="modal" data-target="#LogTransmittal">Generate Log
                                Transmittal</a>
                            <?php
                            if (!empty($_SESSION['borfapps']) && $_SESSION['borfapps'] == '1') {
                                echo '<a class="collapse-item" href="borf.php" >Generate BORF</a>';
                            }
                            ?>
                            <a class="collapse-item" href="#" data-toggle="modal" data-target="#NestleSalesReport">Nestle
                                Sales report</a>
                        </div>
                    </div>
                </li>
                <?php
            }
            ?>

            <?php
            if ($system_setting == '1') {
                ?>
                <!-- Divider -->
                <hr class="sidebar-divider">

                <!-- Heading -->
                <div class="sidebar-heading">
                    Settings
                </div>

                <!-- Nav Item - Pages Collapse Menu -->
                <li class="nav-item">
                    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSetting"
                        aria-expanded="true" aria-controls="collapseSL">
                        <i class="fas fa-fw fa-cogs"></i>
                        <span>Configuration</span>
                    </a>
                    <div id="collapseSetting" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">System Setting:</h6>
                            <a class="collapse-item" href="company.php">Company</a>
                            <a class="collapse-item" href="account.php">User Accounts</a>
                            <a class="collapse-item" href="location.php">Location</a>
                            <a class="collapse-item" href="maintenance_settings.php">Maintenance</a>
                        </div>
                    </div>
                </li>
                <?php
            }
            ?>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - Messages -->
                        <li class="nav-item dropdown no-arrow mx-1">

                            <div class="topbar-divider d-none d-sm-block"></div>

                            <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span
                                    class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['fname'] ?? ''); ?></span>
                                <span
                                    class="img-profile rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:36px;height:36px;background-color:<?php echo $avatar_color; ?>;color:#fff;font-weight:600;font-size:0.8rem;">
                                    <?php echo htmlspecialchars($avatar_initials); ?>
                                </span>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <div class="dropdown-item d-flex align-items-center" style="cursor:default;">
                                    <span class="rounded-circle d-flex align-items-center justify-content-center mr-2"
                                        style="width:40px;height:40px;flex-shrink:0;background-color:<?php echo $avatar_color; ?>;color:#fff;font-weight:600;">
                                        <?php echo htmlspecialchars($avatar_initials); ?>
                                    </span>
                                    <div>
                                        <div class="font-weight-bold text-dark" style="line-height:1.2;">
                                            <?php echo htmlspecialchars(strtoupper($_SESSION['fname'] ?? '')); ?>
                                        </div>
                                        <div class="text-muted small">
                                            @<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="#" class="dropdown-item" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Logout Modal-->
                <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="exampleModalLabel">Ready to Leave?</h6>
                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">Select "Logout" below if you are ready to end your current session.
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary btn-sm" type="button"
                                    data-dismiss="modal">Cancel</button>
                                <a class="btn btn-primary btn-sm" href="logout.php?logout">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- F325 Transmittal Modal-->
                <div class="modal fade" id="F325Transmittal" tabindex="-1" role="dialog"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header text-light" style="background-color: #915c83;">
                                <h6 class="modal-title" id="exampleModalLabel">Generate F325 Transmittal</h6>
                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="exportf325transmittal.php">
                                    <div class="form-group">
                                        <div class="form-row">
                                            <div class="col-12">
                                                <label>Select Cleared Date:</label>
                                                <input type="date" class="form-control form-control-sm" name="f325date"
                                                    required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="form-row">
                                            <div class="col-6">
                                                <label>Time From:</label>
                                                <input type="time" class="form-control form-control-sm" name="timefrom"
                                                    required>
                                            </div>
                                            <div class="col-6">
                                                <label>Time To:</label>
                                                <input type="time" class="form-control form-control-sm" name="timeto"
                                                    required>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary btn-sm" type="button"
                                    data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success btn-sm">Generate</button>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Nestle Sales Report Modal-->
                <div class="modal fade" id="NestleSalesReport" tabindex="-1" role="dialog"
                    aria-labelledby="nestleSalesModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header text-light" style="background-color: #915c83;">
                                <h6 class="modal-title" id="nestleSalesModalLabel">Generate Nestle Sales Report</h6>
                                <button class="close text-light" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="export_sales_report.php">
                                    <div class="form-group">
                                        <div class="form-row">
                                            <div class="col-6">
                                                <label>SO Date From:</label>
                                                <input type="date" class="form-control form-control-sm"
                                                    name="so_date_from" required>
                                            </div>
                                            <div class="col-6">
                                                <label>SO Date To:</label>
                                                <input type="date" class="form-control form-control-sm"
                                                    name="so_date_to" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-secondary btn-sm" type="button"
                                            data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-sm">Generate</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Log Transmittal Modal-->
                <div class="modal fade" id="LogTransmittal" tabindex="-1" role="dialog"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header text-light" style="background-color: #915c83;">
                                <h6 class="modal-title" id="exampleModalLabel">Generate Log Transmittal</h6>
                                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" action="exportlogtransmittal.php" id="logTransmittalForm">
                                    <div class="form-group">
                                        <div class="form-row">
                                            <div class="col-12">

                                                <!-- Location -->
                                                <div class="form-group">
                                                    <div class="form-row">
                                                        <div class="col-12">
                                                            <label>Select Location:</label>
                                                            <select class="form-control form-control-sm" name="location"
                                                                required>
                                                                <option value="">-- Select Location --</option>
                                                                <?php
                                                                $query = "SELECT * FROM tbl_location WHERE active = 1 ORDER BY location ASC";
                                                                $result = mysqli_query($conn, $query);
                                                                while ($row = mysqli_fetch_assoc($result)) {
                                                                    $loc_id = $row['id'];
                                                                    if (!empty($_SESSION['loc' . $loc_id]) && $_SESSION['loc' . $loc_id] == 1) {
                                                                        echo '<option value="' . htmlspecialchars($row['location']) . '">' . htmlspecialchars($row['location']) . '</option>';
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Mode Toggle -->
                                                <div class="form-group">
                                                    <label>Generate By:</label>
                                                    <div class="btn-group btn-group-sm w-100" id="modeToggle">
                                                        <button type="button" class="btn btn-outline-secondary active"
                                                            onclick="switchTransmittalMode('daily', this)">Daily</button>
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            onclick="switchTransmittalMode('weekly', this)">Weekly</button>
                                                    </div>
                                                    <input type="hidden" name="mode" id="transmittalModeInput"
                                                        value="daily">
                                                </div>

                                                <!-- Daily Section -->
                                                <div id="section-daily">
                                                    <div class="form-group">
                                                        <label>Select Cleared Date:</label>
                                                        <input type="date" class="form-control form-control-sm"
                                                            name="logdate" id="dailyLogdate">
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="form-row">
                                                            <div class="col-6">
                                                                <label>Time From:</label>
                                                                <!-- NO name attribute — collected via JS -->
                                                                <input type="time" class="form-control form-control-sm"
                                                                    id="dailyTimefrom">
                                                            </div>
                                                            <div class="col-6">
                                                                <label>Time To:</label>
                                                                <!-- NO name attribute — collected via JS -->
                                                                <input type="time" class="form-control form-control-sm"
                                                                    id="dailyTimeto">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Weekly Section -->
                                                <div id="section-weekly" style="display:none;">
                                                    <div class="form-group">
                                                        <div class="form-row">
                                                            <div class="col-6">
                                                                <label>Week From:</label>
                                                                <input type="date" class="form-control form-control-sm"
                                                                    name="weekfrom" id="transmittalWeekFrom">
                                                            </div>
                                                            <div class="col-6">
                                                                <label>Week To:</label>
                                                                <input type="date" class="form-control form-control-sm"
                                                                    name="weekto" id="transmittalWeekTo">
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">Selecting "Week From" will auto-fill
                                                            "Week To" (+6 days).</small>
                                                    </div>
                                                    <div class="form-group">
                                                        <div class="form-row">
                                                            <div class="col-6">
                                                                <label>Time From:</label>
                                                                <!-- NO name attribute — collected via JS -->
                                                                <input type="time" class="form-control form-control-sm"
                                                                    id="weeklyTimefrom">
                                                            </div>
                                                            <div class="col-6">
                                                                <label>Time To:</label>
                                                                <!-- NO name attribute — collected via JS -->
                                                                <input type="time" class="form-control form-control-sm"
                                                                    id="weeklyTimeto">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Hidden unified fields — these are the ONLY timefrom/timeto sent to PHP -->
                                                <input type="hidden" name="timefrom" id="finalTimefrom">
                                                <input type="hidden" name="timeto" id="finalTimeto">

                                                <!-- Type -->
                                                <div class="form-group">
                                                    <div class="form-row">
                                                        <div class="col-12">
                                                            <select class="form-control form-control-sm" name="type">
                                                                <option value="1">With F325 Number</option>
                                                                <!-- <option value="2">Without F325 Number</option> -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button class="btn btn-secondary btn-sm" type="button"
                                            data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-sm">Generate</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal for Import Pull Out -->
                <div class="modal fade" id="pull-out" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content shadow">

                            <div class="modal-header text-white" style="background-color:#915c83;">
                                <h6 class="modal-title">
                                    <i class="fas fa-file-import mr-2"></i>
                                    Import Pullout – For Charge
                                </h6>
                                <button type="button" class="close text-white" data-dismiss="modal">
                                    <span>&times;</span>
                                </button>
                            </div>

                            <form action="import_pullout.php" method="POST" enctype="multipart/form-data">

                                <div class="modal-body">
                                    <?php if (!empty($_SESSION['pullout_errors'])): ?>

                                        <?php foreach ($_SESSION['pullout_errors'] as $error): ?>
                                            <div class="alert alert-danger alert-dismissible">
                                                <strong><i class="fas fa-exclamation-circle mr-1"></i>Error:</strong>
                                                <?= htmlspecialchars($error) ?>
                                                <button type="button" class="close" data-dismiss="alert">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php endif; ?>
                                    <div class="form-group">
                                        <label class="font-weight-bold text-muted">Select CSV File</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="csv_file" required>
                                            <label class="custom-file-label">Choose file</label>
                                        </div>
                                    </div>

                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">
                                        Cancel
                                    </button>
                                    <button type="submit" name="upload" class="btn btn-success btn-sm">
                                        <i class="fas fa-upload mr-1"></i> Upload
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <?php if (!empty($_SESSION['pullout_errors'])): ?>
                    <script>
                        $(document).ready(function () {
                            $('#pull-out').modal('show');
                        });
                    </script>
                <?php endif; ?>

                <?php
                unset($_SESSION['pullout_errors'], $_SESSION['pullout_success']);
                ?>
                <script>
                    document.querySelector('.custom-file-input').addEventListener('change', function (e) {
                        var fileName = e.target.files[0].name;
                        var nextSibling = e.target.nextElementSibling;
                        nextSibling.innerText = fileName;
                    });

                    function switchTransmittalMode(mode, btn) {
                        document.querySelectorAll('#modeToggle .btn').forEach(function (b) {
                            b.classList.remove('active');
                        });
                        btn.classList.add('active');
                        document.getElementById('transmittalModeInput').value = mode;
                        document.getElementById('section-daily').style.display = mode === 'daily' ? '' : 'none';
                        document.getElementById('section-weekly').style.display = mode === 'weekly' ? '' : 'none';
                    }

                    document.getElementById('transmittalWeekFrom').addEventListener('change', function () {
                        var from = new Date(this.value);
                        if (!isNaN(from)) {
                            var to = new Date(from);
                            to.setDate(to.getDate() + 6);
                            document.getElementById('transmittalWeekTo').value = to.toISOString().split('T')[0];
                        }
                    });
                    document.getElementById('logTransmittalForm').addEventListener('submit', function () {
                        var mode = document.getElementById('transmittalModeInput').value;
                        if (mode === 'daily') {
                            document.getElementById('finalTimefrom').value = document.getElementById('dailyTimefrom').value;
                            document.getElementById('finalTimeto').value = document.getElementById('dailyTimeto').value;
                        } else {
                            document.getElementById('finalTimefrom').value = document.getElementById('weeklyTimefrom').value;
                            document.getElementById('finalTimeto').value = document.getElementById('weeklyTimeto').value;
                        }
                    });
                </script>