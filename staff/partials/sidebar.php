<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">

    <div class="logo">
        <img src="../assets/images/logo.png" alt="Logo">
    </div>

    <ul class="menu">

        <!-- DASHBOARD -->
        <li class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <a href="dashboard.php">
                <i class="fa-solid fa-table-columns"></i>
                <span>Dashboard</span>
            </a>
        </li>


        <!-- APPOINTMENTS -->
        <li class="<?= $currentPage == 'appointments.php' ? 'active' : '' ?>">
            <a href="appointments.php">
                <i class="fa-regular fa-calendar"></i>
                <span>Appointments</span>
            </a>
        </li>


        <!-- CUSTOMER RECORDS -->
        <li class="<?= $currentPage == 'customer_records.php' ? 'active' : '' ?>">
            <a href="customer_records.php">
                <i class="fa-regular fa-clipboard"></i>
                <span>Customer Records</span>
            </a>
        </li>


        <!-- BILLING -->
        <li class="<?= $currentPage == 'billing.php' ? 'active' : '' ?>">
            <a href="billing.php">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Billing</span>
            </a>
        </li>


        <!-- INVENTORY -->
        <li class="<?= $currentPage == 'inventory.php' ? 'active' : '' ?>">
            <a href="inventory.php">
                <i class="fa-solid fa-box"></i>
                <span>Inventory</span>
            </a>
        </li>


        <!-- VACCINATION CERTIFICATES -->
        <li class="<?= $currentPage == 'vaccination_certificates.php' ? 'active' : '' ?>">
            <a href="vaccination_certificates.php">
                <i class="fa-solid fa-shield-dog"></i>
                <span>Vaccination Certificates</span>
            </a>
        </li>


        <!-- REPORTS -->
        <li class="<?= $currentPage == 'reports.php' ? 'active' : '' ?>">
            <a href="reports.php">
                <i class="fa-regular fa-file-lines"></i>
                <span>Reports</span>
            </a>
        </li>


        <!-- ARCHIVED -->
        <li class="<?= $currentPage == 'archived.php' ? 'active' : '' ?>">
            <a href="archived.php">
                <i class="fa-solid fa-box-archive"></i>
                <span>Archived</span>
            </a>
        </li>

    </ul>


    <!-- SIDEBAR FOOTER -->
    <div class="sidebar-footer">

        <div class="user">

            <img
                src="../assets/images/default-user.png"
                alt="User"
            >

            <div>

                <strong>
                    <?php echo htmlspecialchars(
                        $_SESSION["username"] ?? "Staff"
                    ); ?>
                </strong>

                <small>Staff</small>

            </div>

        </div>


        <!-- LOGOUT -->
        <a href="../process/logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

        </a>

    </div>

</aside>