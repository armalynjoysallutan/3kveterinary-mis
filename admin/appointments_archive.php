<?php
session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';


/* =========================================================
   RESTORE ARCHIVED APPOINTMENT
   ========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "restore_appointment"
) {

    $appointmentId = isset($_POST["appointment_id"])
        ? (int) $_POST["appointment_id"]
        : 0;

    if ($appointmentId > 0) {

        $restoreSql = "
            UPDATE appointments
            SET is_archived = 0
            WHERE appointment_id = ?
            AND is_archived = 1
        ";

        $stmt = mysqli_prepare($conn, $restoreSql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $appointmentId
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);
        }
    }

    header("Location: appointments_archive.php");
    exit();
}


/* =========================================================
   GET ARCHIVED APPOINTMENTS
   ========================================================= */

$sql = "
    SELECT
        a.appointment_id,
        a.customer_id,
        a.pet_id,
        a.service_category,
        a.service,
        a.appointment_date,
        a.appointment_time,
        a.appointment_type,
        a.status,
        a.is_archived,

        c.owner_name,

        p.pet_name,
        p.species,
        p.breed

    FROM appointments a

    LEFT JOIN customers c
        ON a.customer_id = c.customer_id

    LEFT JOIN pets p
        ON a.pet_id = p.pet_id

    WHERE a.is_archived = 1

    ORDER BY
        a.appointment_date DESC,
        a.appointment_time DESC
";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die(
        "Unable to load archived appointments: " .
        mysqli_error($conn)
    );
}


/* =========================================================
   STORE RECORDS
   ========================================================= */

$archivedAppointments = [];

while ($row = mysqli_fetch_assoc($result)) {
    $archivedAppointments[] = $row;
}

$archivedCount = count($archivedAppointments);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Archive | Veterinary MIS</title>

    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/appointments.css">
    <link rel="stylesheet" href="../assets/css/appointments_archive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* =====================================================
           APPOINTMENTS ARCHIVE PAGE LAYOUT
           Same scroll behavior as the working report/archive layout.
           ===================================================== */

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* MAIN SYSTEM CONTENT */
        .content#mainContent {
            height: auto !important;
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: visible !important;
            box-sizing: border-box !important;
        }

        /* TOPBAR — stays in normal flow */
        .content#mainContent > .topbar-wrapper {
            flex: 0 0 auto !important;
            position: relative !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            transform: none !important;
            z-index: 100 !important;
        }

        /* PAGE CONTENT — normal document flow; browser handles vertical scrolling */
        .content#mainContent > .page-content {
            flex: 0 0 auto !important;
            min-height: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 24px 30px 40px !important;
            box-sizing: border-box !important;
            overflow: visible !important;
            position: relative !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            transform: none !important;
        }

        /* ARCHIVE CONTENT — no hidden offset */
        .appointments-archive-content {
            position: relative !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            transform: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
        }

        .appointments-archive-content > * {
            position: relative !important;
            top: auto !important;
            transform: none !important;
        }

        @media (max-width: 700px) {
            .content#mainContent > .page-content {
                padding: 18px 15px 30px !important;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="content" id="mainContent">

        <?php
        $pageTitle = 'Appointments Archive';
        $showAdminInfo = true;
        include __DIR__ . '/partials/topbar.php';
        ?>

        <div class="page-content appointments-archive-page">

            <div class="appointments-archive-content">

                <!-- =====================================================
                     PAGE HEADER — SAME STRUCTURE AS RECORDS REPORT
                     ===================================================== -->
                <div class="archive-report-header">

                    <div class="archive-report-header-left">

                        <div class="archive-report-breadcrumb">
                            <a href="archived.php">Archive</a>
                            <i class="fa-solid fa-chevron-right"></i>
                            <span>Appointments Archive</span>
                        </div>

                        <h1>Appointments Archive</h1>

                        <p>
                            View completed and paid appointments that have been archived.
                        </p>

                    </div>

                    <div class="archive-report-actions">

                        <a href="appointments.php" class="archive-report-back-btn">
                            <i class="fa-solid fa-arrow-left"></i>
                            Back to Appointment
                        </a>

                    </div>

                </div>


                <!-- =====================================================
                     SUMMARY METRICS
                     ===================================================== -->
                <?php
                $uniqueCustomers = [];
                $uniquePets = [];
                $appointmentTypes = [];

                foreach ($archivedAppointments as $metricRow) {
                    if (!empty($metricRow['customer_id'])) {
                        $uniqueCustomers[$metricRow['customer_id']] = true;
                    }

                    if (!empty($metricRow['pet_id'])) {
                        $uniquePets[$metricRow['pet_id']] = true;
                    }

                    if (!empty($metricRow['appointment_type'])) {
                        $appointmentTypes[strtolower(trim($metricRow['appointment_type']))] = true;
                    }
                }

                $uniqueCustomerCount = count($uniqueCustomers);
                $uniquePetCount = count($uniquePets);
                $appointmentTypeCount = count($appointmentTypes);
                ?>

                <section class="archive-metrics">

                    <article class="archive-metric-card">
                        <div class="metric-icon blue">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <span>ARCHIVED APPOINTMENTS</span>
                            <strong><?= number_format($archivedCount) ?></strong>
                            <small>Total archived appointments</small>
                        </div>
                    </article>

                    <article class="archive-metric-card">
                        <div class="metric-icon green">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <span>UNIQUE CUSTOMERS</span>
                            <strong><?= number_format($uniqueCustomerCount) ?></strong>
                            <small>Customers with archived appointments</small>
                        </div>
                    </article>

                    <article class="archive-metric-card">
                        <div class="metric-icon blue-soft">
                            <i class="fa-solid fa-paw"></i>
                        </div>
                        <div>
                            <span>UNIQUE PETS</span>
                            <strong><?= number_format($uniquePetCount) ?></strong>
                            <small>Pets with archived appointments</small>
                        </div>
                    </article>

                    <article class="archive-metric-card">
                        <div class="metric-icon orange">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <div>
                            <span>APPOINTMENT TYPES</span>
                            <strong><?= number_format($appointmentTypeCount) ?></strong>
                            <small>Types represented in archive</small>
                        </div>
                    </article>

                </section>


                <!-- =====================================================
                     FILTERS — SAME SECTION PATTERN AS RECORDS REPORT
                     ===================================================== -->
                <section class="report-card filters-card">

                    <div class="report-card-header compact-header">
                        <div>
                            <h2>Filters</h2>
                            <p>Refine the archived appointment list using the available filters.</p>
                        </div>
                    </div>

                    <div class="archive-filters">

                        <div class="filter-field search-filter-field">
                            <label for="archiveSearch">Search</label>

                            <div class="archive-search-box">
                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="text"
                                    id="archiveSearch"
                                    placeholder="Search appointment, customer, pet..."
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="filter-field">
                            <label for="archiveDateFilter">Appointment Date</label>

                            <select id="archiveDateFilter">
                                <option value="all">All Dates</option>
                                <option value="today">Today</option>
                                <option value="month">This Month</option>
                                <option value="year">This Year</option>
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="archiveTypeFilter">Appointment Type</label>

                            <select id="archiveTypeFilter">
                                <option value="all">All Types</option>

                                <?php
                                $typeOptions = [];

                                foreach ($archivedAppointments as $filterRow) {
                                    $type = trim((string)($filterRow['appointment_type'] ?? ''));

                                    if ($type !== '') {
                                        $key = strtolower($type);
                                        $typeOptions[$key] = ucfirst($type);
                                    }
                                }

                                asort($typeOptions);
                                ?>

                                <?php foreach ($typeOptions as $typeValue => $typeLabel): ?>
                                    <option value="<?= htmlspecialchars($typeValue, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-field">
                            <label for="archiveCategoryFilter">Service Category</label>

                            <select id="archiveCategoryFilter">
                                <option value="all">All Categories</option>

                                <?php
                                $categoryOptions = [];

                                foreach ($archivedAppointments as $filterRow) {
                                    $category = trim((string)($filterRow['service_category'] ?? ''));

                                    if ($category !== '') {
                                        $key = strtolower($category);
                                        $categoryOptions[$key] = ucfirst($category);
                                    }
                                }

                                asort($categoryOptions);
                                ?>

                                <?php foreach ($categoryOptions as $categoryValue => $categoryLabel): ?>
                                    <option value="<?= htmlspecialchars($categoryValue, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-actions">
                            <button type="button" class="reset-filter-btn" id="resetArchiveFilters">
                                Reset
                            </button>

                            <button type="button" class="apply-filter-btn" id="applyArchiveFilters">
                                Apply Filter
                            </button>
                        </div>

                    </div>

                </section>


                <!-- =====================================================
                     ARCHIVED APPOINTMENT LIST
                     ===================================================== -->
                <section class="report-card archive-list-card">

                    <div class="report-card-header archive-list-header">

                        <div>
                            <h2>Archived Appointments</h2>
                            <p>
                                Completed and paid appointments automatically appear here.
                            </p>
                        </div>

                        <span class="report-badge">
                            <i class="fa-solid fa-box-archive"></i>
                            <?= number_format($archivedCount) ?> Archived
                        </span>

                    </div>

                    <div class="archive-table-area">

                        <table class="archive-appointment-table">

                            <thead>
                                <tr>
                                    <th>Appointment</th>
                                    <th>Date &amp; Time</th>
                                    <th>Pet</th>
                                    <th>Owner</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($archivedCount > 0): ?>


                                <?php foreach (
                                    $archivedAppointments
                                    as $row
                                ): ?>


                                    <?php


                                    /* =====================================
                                       APPOINTMENT REFERENCE
                                    ====================================== */

                                    $appointmentReference =
                                        "APT-" .
                                        str_pad(
                                            $row["appointment_id"],
                                            4,
                                            "0",
                                            STR_PAD_LEFT
                                        );


                                    /* =====================================
                                       FORMAT DATE
                                    ====================================== */

                                    $formattedDate = "—";


                                    if (
                                        !empty(
                                            $row["appointment_date"]
                                        )
                                    ) {

                                        $timestamp =
                                            strtotime(
                                                $row["appointment_date"]
                                            );


                                        if (
                                            $timestamp !== false
                                        ) {

                                            $formattedDate =
                                                date(
                                                    "M d, Y",
                                                    $timestamp
                                                );

                                        }

                                    }


                                    /* =====================================
                                       FORMAT TIME
                                    ====================================== */

                                    $formattedTime = "—";


                                    if (
                                        !empty(
                                            $row["appointment_time"]
                                        )
                                    ) {

                                        $timestamp =
                                            strtotime(
                                                $row["appointment_time"]
                                            );


                                        if (
                                            $timestamp !== false
                                        ) {

                                            $formattedTime =
                                                date(
                                                    "h:i A",
                                                    $timestamp
                                                );

                                        }

                                    }


                                    /* =====================================
                                       APPOINTMENT TYPE
                                    ====================================== */

                                    $appointmentType = "";


                                    if (
                                        !empty(
                                            $row["appointment_type"]
                                        )
                                    ) {

                                        $appointmentType =
                                            ucfirst(
                                                $row["appointment_type"]
                                            );

                                    }


                                    /* =====================================
                                       SEARCH DATA
                                    ====================================== */

                                    $searchText = strtolower(

                                        $appointmentReference .
                                        " " .

                                        ($row["owner_name"] ?? "") .
                                        " " .

                                        ($row["pet_name"] ?? "") .
                                        " " .

                                        ($row["species"] ?? "") .
                                        " " .

                                        ($row["breed"] ?? "") .
                                        " " .

                                        ($row["service"] ?? "") .
                                        " " .

                                        ($row["service_category"] ?? "") .
                                        " " .

                                        $appointmentType .
                                        " " .

                                        $formattedDate

                                    );

                                    ?>


                                    <tr
                                        class="archive-appointment-row"
                                        data-type="<?php echo htmlspecialchars(strtolower(trim($row["appointment_type"] ?? "")), ENT_QUOTES, "UTF-8"); ?>"
                                        data-category="<?php echo htmlspecialchars(strtolower(trim($row["service_category"] ?? "")), ENT_QUOTES, "UTF-8"); ?>"
                                        data-date="<?php echo htmlspecialchars($row["appointment_date"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                        data-search="<?php

                                            echo htmlspecialchars(
                                                $searchText,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );

                                        ?>"
                                    >


                                        <!-- REFERENCE -->

                                        <td>

                                            <strong
                                                class="appointment-reference"
                                            >

                                                <?php

                                                echo htmlspecialchars(
                                                    $appointmentReference
                                                );

                                                ?>

                                            </strong>

                                        </td>



                                        <!-- DATE & TIME -->

                                        <td>

                                            <div class="date-main">

                                                <?php

                                                echo htmlspecialchars(
                                                    $formattedDate
                                                );

                                                ?>

                                            </div>


                                            <small>

                                                <?php

                                                echo htmlspecialchars(
                                                    $formattedTime
                                                );

                                                ?>

                                            </small>

                                        </td>



                                        <!-- PET -->

                                        <td>

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $row["pet_name"]
                                                        ?? "—"
                                                );

                                                ?>

                                            </strong>


                                            <br>


                                            <small>

                                                <?php

                                                echo htmlspecialchars(
                                                    $row["species"]
                                                        ?? "—"
                                                );

                                                ?>

                                            </small>

                                        </td>



                                        <!-- OWNER -->

                                        <td>

                                            <?php

                                            echo htmlspecialchars(
                                                $row["owner_name"]
                                                    ?? "—"
                                            );

                                            ?>

                                        </td>



                                        <!-- SERVICE -->

                                        <td>

                                            <div
                                                class="archive-service"
                                            >


                                                <?php if (
                                                    !empty(
                                                        $row["service"]
                                                    )
                                                ): ?>

                                                    <strong>

                                                        <?php

                                                        echo htmlspecialchars(
                                                            $row["service"]
                                                        );

                                                        ?>

                                                    </strong>

                                                <?php else: ?>

                                                    <strong>
                                                        —
                                                    </strong>

                                                <?php endif; ?>


                                                <?php if (
                                                    !empty(
                                                        $appointmentType
                                                    )
                                                ): ?>

                                                    <small>

                                                        <?php

                                                        echo htmlspecialchars(
                                                            $appointmentType
                                                        );

                                                        ?>

                                                    </small>

                                                <?php endif; ?>


                                            </div>

                                        </td>



                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="status-badge archived"
                                            >

                                                <i
                                                    class="fa-solid fa-box-archive"
                                                ></i>

                                                Archived

                                            </span>

                                        </td>



                                        <!-- ACTION -->

                                        <td>

                                            <div
                                                class="action-group"
                                            >


                                                <form
                                                    method="POST"
                                                    action="appointments_archive.php"
                                                    class="archive-restore-form"
                                                    onsubmit="return confirm('Are you sure you want to restore this appointment?');"
                                                >


                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="restore_appointment"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="appointment_id"
                                                        value="<?php

                                                            echo (int)
                                                                $row[
                                                                    "appointment_id"
                                                                ];

                                                        ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="archive-restore-btn"
                                                    >

                                                        <i
                                                            class="fa-solid fa-rotate-left"
                                                        ></i>

                                                        Restore

                                                    </button>


                                                </form>


                                            </div>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                                <!-- =================================================
                                     NO SEARCH RESULT
                                ================================================== -->

                                <tr
                                    id="archiveNoSearchResult"
                                    style="display:none;"
                                >

                                    <td
                                        colspan="7"
                                        class="archive-empty-state"
                                    >

                                        <i
                                            class="fa-solid fa-magnifying-glass"
                                        ></i>


                                        <strong>
                                            No appointments found
                                        </strong>


                                        <span>
                                            Try searching using an
                                            appointment, customer,
                                            or pet name.
                                        </span>


                                    </td>

                                </tr>


                            <?php else: ?>


                                <!-- =================================================
                                     NO ARCHIVED APPOINTMENTS
                                ================================================== -->

                                <tr>

                                    <td
                                        colspan="7"
                                        class="archive-empty-state"
                                    >

                                        <i
                                            class="fa-solid fa-box-open"
                                        ></i>


                                        <strong>
                                            No archived appointments
                                        </strong>


                                        <span>
                                            Completed and paid appointments
                                            will automatically appear here.
                                        </span>


                                    </td>

                                </tr>


                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =====================================================
                     INSIGHTS
                     ===================================================== -->
                <section class="insight-grid">

                    <article class="insight-card purple-insight">
                        <div class="insight-icon">
                            <i class="fa-regular fa-lightbulb"></i>
                        </div>

                        <div>
                            <h3>Archive Summary</h3>

                            <p>
                                There are
                                <strong><?= number_format($archivedCount) ?></strong>
                                archived appointments currently stored in the system.
                                These records remain available for review and restoration.
                            </p>
                        </div>
                    </article>

                    <article class="insight-card blue-insight">
                        <div class="insight-icon">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>

                        <div>
                            <h3>About this archive</h3>

                            <p>
                                Archived appointments are kept separately from the active
                                appointment list. Use the filters above to locate a record,
                                then use the Restore action when the appointment needs to
                                return to the active records.
                            </p>
                        </div>
                    </article>

                </section>


                <div class="report-footer">
                    © 2026 3K Pet Solution Animal Clinic. All rights reserved.
                </div>

            </div>

        </div>

    </main>

</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

    const searchInput = document.getElementById("archiveSearch");
    const dateFilter = document.getElementById("archiveDateFilter");
    const typeFilter = document.getElementById("archiveTypeFilter");
    const categoryFilter = document.getElementById("archiveCategoryFilter");

    const applyButton = document.getElementById("applyArchiveFilters");
    const resetButton = document.getElementById("resetArchiveFilters");

    const rows = document.querySelectorAll(".archive-appointment-row");
    const noSearchResult = document.getElementById("archiveNoSearchResult");

    function normalize(value) {
        return String(value || "").trim().toLowerCase();
    }

    function rowMatches(row) {

        const searchValue = normalize(searchInput ? searchInput.value : "");
        const selectedDate = dateFilter ? dateFilter.value : "all";
        const selectedType = normalize(typeFilter ? typeFilter.value : "all");
        const selectedCategory = normalize(categoryFilter ? categoryFilter.value : "all");

        const searchText = normalize(row.dataset.search);
        const rowType = normalize(row.dataset.type);
        const rowCategory = normalize(row.dataset.category);
        const rowDate = row.dataset.date || "";

        if (searchValue && !searchText.includes(searchValue)) {
            return false;
        }

        if (selectedType !== "all" && rowType !== selectedType) {
            return false;
        }

        if (selectedCategory !== "all" && rowCategory !== selectedCategory) {
            return false;
        }

        if (selectedDate !== "all" && rowDate) {

            const today = new Date();
            const appointmentDate = new Date(rowDate + "T00:00:00");

            if (selectedDate === "today") {
                if (
                    appointmentDate.getFullYear() !== today.getFullYear() ||
                    appointmentDate.getMonth() !== today.getMonth() ||
                    appointmentDate.getDate() !== today.getDate()
                ) {
                    return false;
                }
            }

            if (selectedDate === "month") {
                if (
                    appointmentDate.getFullYear() !== today.getFullYear() ||
                    appointmentDate.getMonth() !== today.getMonth()
                ) {
                    return false;
                }
            }

            if (selectedDate === "year") {
                if (appointmentDate.getFullYear() !== today.getFullYear()) {
                    return false;
                }
            }
        }

        return true;
    }

    function applyFilters() {

        let visibleCount = 0;

        rows.forEach(function (row) {

            if (rowMatches(row)) {
                row.style.display = "";
                visibleCount++;
            } else {
                row.style.display = "none";
            }

        });

        if (noSearchResult) {
            noSearchResult.style.display =
                visibleCount === 0 ? "" : "none";
        }
    }

    if (applyButton) {
        applyButton.addEventListener("click", applyFilters);
    }

    if (resetButton) {

        resetButton.addEventListener("click", function () {

            if (searchInput) searchInput.value = "";
            if (dateFilter) dateFilter.value = "all";
            if (typeFilter) typeFilter.value = "all";
            if (categoryFilter) categoryFilter.value = "all";

            applyFilters();
        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", applyFilters);
    }

});
</script>

</body>
</html>