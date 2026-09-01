<?php
session_start();

if (
    !isset($_SESSION["admin_id"]) ||
    !isset($_SESSION["admin_username"]) ||
    !isset($_SESSION["admin_role"]) ||
    $_SESSION["admin_role"] !== "Admin"
) {
    header("Location: ../auth/login.php");
    exit();
}

$pageTitle = "Archive";
$showAdminInfo = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Archive | Veterinary MIS</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- Shared Layout CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <!-- Archive CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/archive.css"
    >
</head>

<body>

<div class="container">

    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <?php include __DIR__ . '/partials/sidebar.php'; ?>


    <!-- =====================================================
         SIDEBAR OVERLAY
         ===================================================== -->

    <div
        class="sidebar-overlay"
        id="sidebarOverlay">
    </div>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="content" id="mainContent">

        <!-- =================================================
             TOPBAR
             ================================================= -->

        <?php
        $pageTitle = "Archive";
        $showAdminInfo = false;

        include __DIR__ . '/partials/topbar.php';
        ?>


        <!-- =================================================
             ARCHIVE PAGE
             ================================================= -->

        <section class="archive-page">

            <!-- PAGE HEADER -->

            <div class="archive-page-header">

                <div>
                    <h1>Archive</h1>

                    <p>
                        Manage and restore archived records
                        from each module.
                    </p>
                </div>

            </div>


           <!-- =================================================
     ARCHIVE MODULE CARDS
     ================================================= -->

<div class="archive-module-grid">

    <!-- =================================================
         CUSTOMER RECORDS
         ================================================= -->

    <article class="archive-module-card">

        <div class="archive-card-top">

            <div class="archive-card-icon green">
                <i class="fa-solid fa-users"></i>
            </div>

            <span class="archive-count">
                0 Archived
            </span>

        </div>

        <div class="archive-card-body">

            <h2>Customer Records</h2>

            <p>
                View and manage archived customer
                and pet records.
            </p>

        </div>

        <a
            href="customer_archive.php"
            class="archive-card-link">

            View Archive
            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </article>


    <!-- =================================================
         BILLING
         ================================================= -->

    <article class="archive-module-card">

        <div class="archive-card-top">

            <div class="archive-card-icon red">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>

            <span class="archive-count">
                0 Archived
            </span>

        </div>

        <div class="archive-card-body">

            <h2>Billing</h2>

            <p>
                View and manage archived billing
                records and transactions.
            </p>

        </div>

        <a
            href="billing_archive.php"
            class="archive-card-link">

            View Archive
            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </article>


    <!-- =================================================
         INVENTORY
         ================================================= -->

    <article class="archive-module-card">

        <div class="archive-card-top">

            <div class="archive-card-icon teal">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>

            <span class="archive-count">
                0 Archived
            </span>

        </div>

        <div class="archive-card-body">

            <h2>Inventory</h2>

            <p>
                View and manage archived inventory
                items and stock records.
            </p>

        </div>

        <a
            href="inventory_archive.php"
            class="archive-card-link">

            View Archive
            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </article>


    <!-- =================================================
         APPOINTMENTS
         ================================================= -->

    <article class="archive-module-card">

        <div class="archive-card-top">

            <div class="archive-card-icon blue">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <span class="archive-count">
                0 Archived
            </span>

        </div>

        <div class="archive-card-body">

            <h2>Appointments</h2>

            <p>
                View and manage archived
                appointment records.
            </p>

        </div>

        <a
            href="appointments_archive.php"
            class="archive-card-link">

            View Archive
            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </article>

</div>


            </div>


            <!-- =================================================
                 FOOTER
                 ================================================= -->

            <div class="archive-footer">

                <p>
                    Veterinary Management Information System
                </p>

            </div>

        </section>

    </main>

</div>


<!-- =========================================================
     SHARED LAYOUT JS
     ========================================================= -->

<script src="../assets/js/layout.js"></script>

<!-- =========================================================
     ARCHIVE JS
     ========================================================= -->

<script src="../assets/js/archive.js"></script>

</body>
</html>