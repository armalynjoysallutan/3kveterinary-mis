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

    <title>
        Audit Trail | Veterinary MIS
    </title>


    <!-- SHARED SYSTEM CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/appointments.css"
    >


    <!-- AUDIT TRAIL CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/audit_trail.css"
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

    <?php include "partials/sidebar.php"; ?>


    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- MAIN CONTENT -->

    <main
        class="content"
        id="mainContent"
    >


        <!-- TOPBAR -->

        <?php

        $pageTitle = "Audit Trail";

        $showAdminInfo = false;

        include "partials/topbar.php";

        ?>


        <div class="audit-trail-page">


            <!-- ==========================================
                 BREADCRUMB
            =========================================== -->

            <div class="audit-breadcrumb">

                <a href="settings.php">
                    Settings
                </a>

                <i class="fa-solid fa-chevron-right"></i>

                <span>
                    Audit Trail
                </span>

            </div>


            <!-- ==========================================
                 PAGE HEADER
            =========================================== -->

            <div class="audit-page-header">


                <div class="audit-page-header-info">

                    <div class="audit-page-icon">

                        <i class="fa-solid fa-clock-rotate-left"></i>

                    </div>


                    <div>

                        <h1>
                            Audit Trail
                        </h1>

                        <p>
                            Review automatically recorded user activities
                            and system changes.
                        </p>

                    </div>

                </div>


                <div class="audit-page-actions">

                    <button
                        type="button"
                        class="audit-export-btn"
                        id="auditExportBtn"
                    >

                        <i class="fa-solid fa-download"></i>

                        Export

                    </button>

                </div>

            </div>


            <!-- ==========================================
                 FILTERS
            =========================================== -->

            <div class="audit-filter-card">


                <div class="audit-filter-header">

                    <div>

                        <h2>
                            Filters
                        </h2>

                        <p>
                            Filter system activities and user actions.
                        </p>

                    </div>

                </div>


                <div class="audit-filter-grid">


                    <!-- SEARCH -->

                    <div class="audit-filter-group">

                        <label for="auditSearch">
                            Search
                        </label>

                        <div class="audit-input-wrapper">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="auditSearch"
                                placeholder="Search by description or reference no..."
                            >

                        </div>

                    </div>


                    <!-- DATE FROM -->

                    <div class="audit-filter-group">

                        <label for="auditDateFrom">
                            Date From
                        </label>

                        <input
                            type="date"
                            id="auditDateFrom"
                        >

                    </div>


                    <!-- DATE TO -->

                    <div class="audit-filter-group">

                        <label for="auditDateTo">
                            Date To
                        </label>

                        <input
                            type="date"
                            id="auditDateTo"
                        >

                    </div>


                    <!-- USER -->

                    <div class="audit-filter-group">

                        <label for="auditUserFilter">
                            User
                        </label>

                        <select id="auditUserFilter">

                            <option value="">
                                All Users
                            </option>

                        </select>

                    </div>


                    <!-- MODULE -->

                    <div class="audit-filter-group">

                        <label for="auditModuleFilter">
                            Module
                        </label>

                        <select id="auditModuleFilter">

                            <option value="">
                                All Modules
                            </option>

                            <option value="Appointments">
                                Appointments
                            </option>

                            <option value="Customer Records">
                                Customer Records
                            </option>

                            <option value="Pet Records">
                                Pet Records
                            </option>

                            <option value="Billing">
                                Billing
                            </option>

                            <option value="Inventory">
                                Inventory
                            </option>

                            <option value="Services">
                                Services
                            </option>

                            <option value="Vaccination">
                                Vaccination
                            </option>

                            <option value="System Variables">
                                System Variables
                            </option>

                            <option value="Account Management">
                                Account Management
                            </option>

                        </select>

                    </div>


                    <!-- ACTION -->

                    <div class="audit-filter-group">

                        <label for="auditActionFilter">
                            Action
                        </label>

                        <select id="auditActionFilter">

                            <option value="">
                                All Actions
                            </option>

                            <option value="Created">
                                Created
                            </option>

                            <option value="Updated">
                                Updated
                            </option>

                            <option value="Archived">
                                Archived
                            </option>

                            <option value="Restored">
                                Restored
                            </option>

                            <option value="Deleted">
                                Deleted
                            </option>

                            <option value="Login">
                                Login
                            </option>

                            <option value="Logout">
                                Logout
                            </option>

                        </select>

                    </div>


                </div>


                <div class="audit-filter-actions">

                    <button
                        type="button"
                        class="audit-reset-btn"
                        id="auditResetBtn"
                    >

                        <i class="fa-solid fa-rotate-left"></i>

                        Reset

                    </button>


                    <button
                        type="button"
                        class="audit-apply-btn"
                        id="auditApplyBtn"
                    >

                        <i class="fa-solid fa-filter"></i>

                        Apply Filters

                    </button>

                </div>

            </div>


            <!-- ==========================================
                 AUDIT LOG TABLE
            =========================================== -->

            <div class="audit-table-card">


                <div class="audit-table-header">

                    <div>

                        <h2>
                            Activity Logs
                        </h2>

                        <p>
                            Recorded system activities and changes.
                        </p>

                    </div>


                    <div
                        class="audit-log-count"
                        id="auditLogCount"
                    >
                        0 Logs
                    </div>

                </div>


                <div class="audit-table-wrapper">


                    <table class="audit-table">


                        <thead>

                            <tr>

                                <th>
                                    DATE &amp; TIME
                                </th>

                                <th>
                                    USER
                                </th>

                                <th>
                                    ROLE
                                </th>

                                <th>
                                    MODULE
                                </th>

                                <th>
                                    ACTION
                                </th>

                                <th>
                                    DESCRIPTION
                                </th>

                                <th>
                                    REFERENCE NO.
                                </th>

                                <th>
                                    VIEW
                                </th>

                            </tr>

                        </thead>


                        <tbody id="auditTableBody">


                            <!-- BACKEND DATA WILL BE LOADED HERE -->

                            <tr class="audit-empty-row">

                                <td colspan="8">

                                    <div class="audit-empty-state">

                                        <div class="audit-empty-icon">

                                            <i class="fa-solid fa-clock-rotate-left"></i>

                                        </div>

                                        <h3>
                                            No audit logs yet
                                        </h3>

                                        <p>
                                            System activities will appear
                                            here once they are recorded.
                                        </p>

                                    </div>

                                </td>

                            </tr>


                        </tbody>


                    </table>


                </div>


                <!-- PAGINATION -->

                <div class="audit-pagination">

                    <span id="auditPaginationInfo">
                        Showing 0 to 0 of 0 logs
                    </span>


                    <div
                        class="audit-pagination-buttons"
                        id="auditPagination"
                    >

                        <button
                            type="button"
                            disabled
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <button
                            type="button"
                            class="active"
                        >
                            1
                        </button>

                        <button
                            type="button"
                            disabled
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                    </div>

                </div>


            </div>


        </div>


    </main>

</div>


<!-- MAIN LAYOUT JS -->

<script src="../assets/js/layout.js"></script>


<!-- AUDIT TRAIL JS -->

<script src="../assets/js/audit_trail.js"></script>


</body>

</html>