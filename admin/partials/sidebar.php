<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">

    <div class="logo">

        <img src="../assets/images/logo.png" alt="Logo">

    </div>

    <ul class="menu">

        <li class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php">
                <i class="fa-solid fa-table-columns"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="<?= $currentPage == 'appointments.php' ? 'active' : '' ?>">
            <a href="appointments.php">
                <i class="fa-regular fa-calendar"></i>
                <span>Appointments</span>
            </a>
        </li>

        <li class="<?= $currentPage == 'customer_records.php' ? 'active' : '' ?>">
            <a href="customer_records.php">
                <i class="fa-regular fa-clipboard"></i>
                <span>Customer Records</span>
            </a>
        </li>

        <li class="<?= $currentPage == 'billing.php' ? 'active' : '' ?>">
            <a href="billing.php">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Billing</span>
            </a>
        </li>

        <li class="<?= $currentPage == 'inventory.php' ? 'active' : '' ?>">
            <a href="inventory.php">
                <i class="fa-solid fa-box"></i>
                <span>Inventory</span>
            </a>
        </li>

        <li class="<?= $currentPage == 'vaccination_certificates.php' ? 'active' : '' ?>">
            <a href="vaccination_certificates.php">
                <i class="fa-solid fa-shield-dog"></i>
                <span>Vaccination Certificates</span>
            </a>
        </li>

        <li class="<?= $currentPage == 'reports.php' ? 'active' : '' ?>">
            <a href="reports.php">
                <i class="fa-regular fa-file-lines"></i>
                <span>Reports</span>
            </a>
        </li>

        <li class="<?= $currentPage == 'archived.php' ? 'active' : '' ?>">
            <a href="archived.php">
                <i class="fa-solid fa-box-archive"></i>
                <span>Archived</span>
            </a>
        </li>

        <li class="<?= $currentPage == 'settings.php' ? 'active' : '' ?>">
            <a href="settings.php">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
        </li>

    </ul>

    <div class="sidebar-footer">

        <div class="user">

            <img src="../assets/images/default-user.png" alt="User">

            <div>

                <strong><?php echo $_SESSION["admin_username"]; ?></strong>

                <small>Administrator</small>

            </div>

        </div>

        <a href="../process/logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

        </a>

    </div>

</aside>