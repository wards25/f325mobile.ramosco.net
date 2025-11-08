<?php
    $uri = $_SERVER['REQUEST_URI'];
?>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #915c83;">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon">
                </div>
                <div class="sidebar-brand-text mx-3"><img src="img/logo.png" class="img-fluid" style="height:30px;">
                    <small>BO/F325 MOBILE APP</small>
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
                        <a class="collapse-item" href="search_sl.php">Shortlanded F325</a>
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
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-window-maximize"></i>
                    <span>F325 Modules</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">F325 Modules:</h6>
                        <a class="collapse-item" href="open.php">Open F325</a>
                    <?php
                        if($_SESSION['print']=='1')
                    {
                            echo '<a class="collapse-item" href="printed.php">Printed F325</a>';
                        }else{
                    }

                        if($_SESSION['schedule']=='1')
                    {
                            echo '<a class="collapse-item" href="scheduled.php">Scheduled F325</a>';
                        }else{
                    }

                        if($_SESSION['clearing']=='1')
                    {
                            echo '<a class="collapse-item" href="cleared.php">Cleared F325</a>';
                            echo '<a class="collapse-item" href="disposed.php">Disposed F325</a>';
                        }else{
                            
                    }

                        if($_SESSION['print']=='1')
                    {
                            echo '<a class="collapse-item" href="borf.php">BORF F325</a>';
                        }else{
                    }
                    ?>
                </div>
            </div>
            </li>

            <?php
            if($_SESSION['payment']=='1'){
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
            }else{

            }
            ?>

            <?php
            if($_SESSION['manual']=='1'){
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
            }else{

            }
            ?>

            <?php
            if($_SESSION['report']=='1'){
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
                        <a class="collapse-item" href="exportborf.php">Generate BORF Report</a>
                        <a class="collapse-item" href="exportshortlanded.php">Generate SL Report</a>
                        <a class="collapse-item" href="#" data-toggle="modal" data-target="#F325Transmittal">Generate F325 Transmittal</a>
                        <a class="collapse-item" href="#" data-toggle="modal" data-target="#LogTransmittal">Generate Log Transmittal</a>
                        <!-- <a class="collapse-item" href="bypass.php">Bypass Product List</a>
                        <a class="collapse-item" href="payslip.php">Payslip</a> -->
                    </div>
                </div>
            </li>
            <?php
            }else{

            }
            ?>

            <?php
            if($_SESSION['inventory']=='1'){
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
                        <h6 class="collapse-header">Settings:</h6>
                        <!-- <a class="collapse-item" href="exportraw.php">Export Per Status</a> -->
                        <a class="collapse-item" href="import_product.php">Import Product</a>
                        <!-- <a class="collapse-item" href="exportborf.php">Generate BORF Report</a>
                        <a class="collapse-item" href="exportshortlanded.php">Generate SL Report</a>
                        <a class="collapse-item" href="bypass.php">Bypass Product List</a> -->
                        <!-- <a class="collapse-item" href="payslip.php">Payslip</a> -->
                    </div>
                </div>
            </li>
            <?php
            }else{

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
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['fname'];?></span>
                                <img class="img-profile rounded-circle"
                                    src="img/undraw_profile.png">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <?php
                                    echo'<a class="dropdown-item text-dark">
                                        <i class="fas fa-user fa-sm fa-fw mr-2 text-primary"></i>
                                        Hi ! <b>'.$_SESSION['fname'].'</b>
                                    </a>';
                                ?>
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
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary btn-sm" href="logout.php?logout">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- F325 Transmittal Modal-->
    <div class="modal fade" id="F325Transmittal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
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
                                <input type="date" class="form-control form-control-sm" name="f325date" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-6">
                                <label>Time From:</label>
                                <input type="time" class="form-control form-control-sm" name="timefrom" required>
                            </div>
                            <div class="col-6">
                                <label>Time To:</label>
                                <input type="time" class="form-control form-control-sm" name="timeto" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Generate</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Log Transmittal Modal-->
    <div class="modal fade" id="LogTransmittal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header text-light" style="background-color: #915c83;">
                    <h6 class="modal-title" id="exampleModalLabel">Generate Log Transmittal</h6>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                <form method="POST" action="exportlogtransmittal.php">
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-12">
                                <label>Select Cleared Date:</label>
                                <input type="date" class="form-control form-control-sm" name="logdate" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-6">
                                <label>Time From:</label>
                                <input type="time" class="form-control form-control-sm" name="timefrom" required>
                            </div>
                            <div class="col-6">
                                <label>Time To:</label>
                                <input type="time" class="form-control form-control-sm" name="timeto" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-row">
                            <div class="col-12">
                                <select class="form-control form-control-sm" name="type">
                                    <option value="1">With F325 Number</option>
                                    <option value="2">Without F325 Number</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary btn-sm" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">Generate</button>
                </div>
                </form>
            </div>
        </div>
    </div>