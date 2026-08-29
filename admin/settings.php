<?php

session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Settings | Veterinary MIS</title>

    <!-- MAIN LAYOUT -->
    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <!-- SETTINGS CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/settings.css"
    >

    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

</head>

<body>

<div class="container">

    <!-- SIDEBAR -->
    <?php include 'partials/sidebar.php'; ?>

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- MAIN CONTENT -->
    <main
        class="content"
        id="mainContent"
    >

        <?php

        $pageTitle = "Settings";
        $showAdminInfo = false;

        include "partials/topbar.php";

        ?>


        <div class="settings-content">

            <!-- PAGE INTRO -->
            <div class="settings-header-card">

                <h2>Settings</h2>

                <p>
                    Manage system configurations, accounts,
                    and system operations.
                </p>

            </div>


            <!-- SETTINGS GRID -->
            <div class="settings-grid">


                <!-- SYSTEM VARIABLES -->
                <div class="settings-card">

                    <div class="settings-card-icon blue">

                        <i class="fa-solid fa-gear"></i>

                    </div>


                    <div class="settings-card-content">

                        <h3>
                            System Variables
                        </h3>

                        <p>
                            Manage system configurations
                            and reference data used
                            throughout the system.
                        </p>

                    </div>


                    <div class="settings-card-footer">

                        <a
                            href="system_variables.php"
                            class="settings-open-btn blue-btn"
                        >
                            Open
                        </a>

                    </div>

                </div>



                <!-- AUDIT & TRAIL -->
                <div class="settings-card">

                    <div class="settings-card-icon green">

                        <i class="fa-solid fa-clock-rotate-left"></i>

                    </div>


                    <div class="settings-card-content">

                        <h3>
                            Audit &amp; Trail
                        </h3>

                        <p>
                            Review automatically recorded
                            user activities and system
                            changes for monitoring,
                            accountability, and security.
                        </p>

                    </div>


                    <div class="settings-card-footer">

                        <a
                            href="audit_trail.php"
                            class="settings-open-btn green-btn"
                        >
                            Open
                        </a>

                    </div>

                </div>



                <!-- BACKUP & RESTORE -->
                <div class="settings-card">

                    <div class="settings-card-icon purple">

                        <i class="fa-solid fa-cloud-arrow-up"></i>

                    </div>


                    <div class="settings-card-content">

                        <h3>
                            Backup &amp; Restore
                        </h3>

                        <p>
                            Create backups and restore
                            previously saved backup files
                            to protect clinic data.
                        </p>

                    </div>


                    <div class="settings-card-footer">

                        <a
                            href="backup_restore.php"
                            class="settings-open-btn purple-btn"
                        >
                            Open
                        </a>

                    </div>

                </div>



                <!-- ACCOUNT MANAGEMENT -->
                <div class="settings-card">

                    <div class="settings-card-icon orange">

                        <i class="fa-solid fa-user-gear"></i>

                    </div>


                    <div class="settings-card-content">

                        <h3>
                            Account Management
                        </h3>

                        <p>
                            Manage administrator, staff,
                            and customer accounts.
                        </p>

                    </div>


                    <div class="settings-card-footer">

                        <a
                            href="account_management.php"
                            class="settings-open-btn orange-btn"
                        >
                            Open
                        </a>

                    </div>

                </div>


            </div>

        </div>

    </main>

</div>


<!-- MAIN LAYOUT JS -->
<script src="../assets/js/layout.js"></script>

<!-- SETTINGS JS -->
<script src="../assets/js/settings.js"></script>

</body>

</html>