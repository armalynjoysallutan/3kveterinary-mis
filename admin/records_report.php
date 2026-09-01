<?php
/* =========================================================
   RECORDS REPORT
   File: admin/records_report.php
   Purpose: Detailed customer and pet records analysis
   ========================================================= */

session_start();

if (!isset($_SESSION['admin_username'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';

/* =========================================================
   1. HELPERS
   ========================================================= */
function q1($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die('Records report query failed: ' . mysqli_error($conn));
    }

    return mysqli_fetch_assoc($result) ?: [];
}

function qall($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die('Records report query failed: ' . mysqli_error($conn));
    }

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

/* =========================================================
   2. SUMMARY METRICS
   ========================================================= */
$summary = q1(
    $conn,
    "
    SELECT
        (SELECT COUNT(*) FROM customers WHERE record_status = 'Active') AS total_customers,
        (SELECT COUNT(*) FROM pets) AS total_pets,
        (SELECT COUNT(*) FROM customers WHERE record_status = 'Active') AS active_records,
        (SELECT COUNT(*) FROM customers WHERE record_status <> 'Active') AS archived_records
    "
);

$totalCustomers = (int) ($summary['total_customers'] ?? 0);
$totalPets = (int) ($summary['total_pets'] ?? 0);
$activeRecords = (int) ($summary['active_records'] ?? 0);
$archivedRecords = (int) ($summary['archived_records'] ?? 0);

/* =========================================================
   3. PET REGISTRATION TREND - LAST 12 MONTHS
   ========================================================= */
$petTrendRows = qall(
    $conn,
    "
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        DATE_FORMAT(created_at, '%b') AS month_label,
        COUNT(*) AS total
    FROM pets
    WHERE created_at >= DATE_SUB(
        DATE_FORMAT(CURDATE(), '%Y-%m-01'),
        INTERVAL 11 MONTH
    )
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b')
    ORDER BY month_key ASC
    "
);

/* =========================================================
   4. CUSTOMER REGISTRATION TREND - LAST 12 MONTHS
   ========================================================= */
$customerTrendRows = qall(
    $conn,
    "
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        DATE_FORMAT(created_at, '%b') AS month_label,
        COUNT(*) AS total
    FROM customers
    WHERE created_at >= DATE_SUB(
        DATE_FORMAT(CURDATE(), '%Y-%m-01'),
        INTERVAL 11 MONTH
    )
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b')
    ORDER BY month_key ASC
    "
);

/* =========================================================
   5. BREED - TOP 10
   ========================================================= */
$breedRows = qall(
    $conn,
    "
    SELECT
        CASE
            WHEN TRIM(COALESCE(breed, '')) = '' THEN 'Others'
            ELSE TRIM(breed)
        END AS breed_name,
        COUNT(*) AS total
    FROM pets
    GROUP BY
        CASE
            WHEN TRIM(COALESCE(breed, '')) = '' THEN 'Others'
            ELSE TRIM(breed)
        END
    ORDER BY total DESC, breed_name ASC
    LIMIT 10
    "
);

/* =========================================================
   6. RECORD STATUS DISTRIBUTION
   ========================================================= */
$statusRows = qall(
    $conn,
    "
    SELECT
        CASE
            WHEN record_status = 'Active' THEN 'Active'
            ELSE 'Archived'
        END AS status_name,
        COUNT(*) AS total
    FROM customers
    GROUP BY
        CASE
            WHEN record_status = 'Active' THEN 'Active'
            ELSE 'Archived'
        END
    ORDER BY status_name ASC
    "
);

/* =========================================================
   7. INSIGHT DATA
   ========================================================= */
$currentMonthPets = (int) (q1(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM pets
    WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND created_at < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
    "
)['total'] ?? 0);

$previousMonthPets = (int) (q1(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM pets
    WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
      AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')
    "
)['total'] ?? 0);

if ($previousMonthPets > 0) {
    $change = (($currentMonthPets - $previousMonthPets) / $previousMonthPets) * 100;
    $changeText = number_format(abs($change), 0) . '% ' . ($change >= 0 ? 'increase' : 'decrease');
} elseif ($currentMonthPets > 0) {
    $changeText = 'new registrations recorded this month';
} else {
    $changeText = 'no new registrations recorded this month';
}

$insightText = "There were {$currentMonthPets} pets registered this month with a {$changeText} compared to last month.";

/* =========================================================
   7A. PRINT REPORT INTERPRETATIONS
   ========================================================= */

/* PET REGISTRATION TREND */

$petTrendInterpretation =
    "The chart presents the number of pets registered "
    . "over the last 12 months.";

if (!empty($petTrendRows)) {

    $highestPetMonth = $petTrendRows[0];
    $lowestPetMonth = $petTrendRows[0];

    foreach ($petTrendRows as $row) {

        if ((int)$row['total'] > (int)$highestPetMonth['total']) {
            $highestPetMonth = $row;
        }

        if ((int)$row['total'] < (int)$lowestPetMonth['total']) {
            $lowestPetMonth = $row;
        }
    }

    $petTrendInterpretation .=
        " The highest number of pet registrations was "
        . "recorded in {$highestPetMonth['month_label']} "
        . "with " . number_format((int)$highestPetMonth['total'])
        . " registration(s), while the lowest was recorded "
        . "in {$lowestPetMonth['month_label']} with "
        . number_format((int)$lowestPetMonth['total'])
        . " registration(s).";
}


/* PET BREED */

$breedInterpretation =
    "The chart shows the top pet breeds recorded in the system.";

if (!empty($breedRows)) {

    $topBreed = $breedRows[0];

    $breedInterpretation .=
        " {$topBreed['breed_name']} has the highest number "
        . "of recorded pets with "
        . number_format((int)$topBreed['total'])
        . " pet(s), making it the most frequently recorded "
        . "breed among the displayed results.";
}


/* CUSTOMER REGISTRATION TREND */

$customerTrendInterpretation =
    "The chart presents customer registrations over the "
    . "last 12 months.";

if (!empty($customerTrendRows)) {

    $highestCustomerMonth = $customerTrendRows[0];
    $lowestCustomerMonth = $customerTrendRows[0];

    foreach ($customerTrendRows as $row) {

        if ((int)$row['total'] > (int)$highestCustomerMonth['total']) {
            $highestCustomerMonth = $row;
        }

        if ((int)$row['total'] < (int)$lowestCustomerMonth['total']) {
            $lowestCustomerMonth = $row;
        }
    }

    $customerTrendInterpretation .=
        " The highest customer registration activity "
        . "was recorded in {$highestCustomerMonth['month_label']} "
        . "with "
        . number_format((int)$highestCustomerMonth['total'])
        . " registration(s), while the lowest activity "
        . "was recorded in {$lowestCustomerMonth['month_label']} "
        . "with "
        . number_format((int)$lowestCustomerMonth['total'])
        . " registration(s).";
}


/* RECORD STATUS */

$statusInterpretation =
    "The chart presents the current distribution of customer "
    . "records according to their status.";

if (!empty($statusRows)) {

    $totalStatusRecords = 0;
    $largestStatus = $statusRows[0];

    foreach ($statusRows as $row) {

        $totalStatusRecords +=
            (int)$row['total'];

        if ((int)$row['total'] > (int)$largestStatus['total']) {
            $largestStatus = $row;
        }
    }

    $statusPercentage = 0;

    if ($totalStatusRecords > 0) {

        $statusPercentage =
            ((int)$largestStatus['total']
            / $totalStatusRecords) * 100;
    }

    $statusInterpretation .=
        " {$largestStatus['status_name']} records represent "
        . number_format($statusPercentage, 1)
        . "% of the total customer records, with "
        . number_format((int)$largestStatus['total'])
        . " record(s).";
}

/* =========================================================
   8. CHART ARRAYS
   ========================================================= */
$petTrendLabels = [];
$petTrendValues = [];

foreach ($petTrendRows as $row) {
    $petTrendLabels[] = $row['month_label'];
    $petTrendValues[] = (int) $row['total'];
}

$customerTrendLabels = [];
$customerTrendValues = [];

foreach ($customerTrendRows as $row) {
    $customerTrendLabels[] = $row['month_label'];
    $customerTrendValues[] = (int) $row['total'];
}

$breedLabels = [];
$breedValues = [];

foreach ($breedRows as $row) {
    $breedLabels[] = $row['breed_name'];
    $breedValues[] = (int) $row['total'];
}

$statusLabels = [];
$statusValues = [];

foreach ($statusRows as $row) {
    $statusLabels[] = $row['status_name'];
    $statusValues[] = (int) $row['total'];
}

/* =========================================================
   9. PAGE
   ========================================================= */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records Report | Veterinary MIS</title>

    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/records_report.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="layout">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="content">

        <?php
    $pageTitle = 'Records Report';
    $showAdminInfo = true;
    include __DIR__ . '/partials/topbar.php';
?>

<div class="page-content records-report-page">

    <!-- =====================================================
         REPORT HEADER
         ===================================================== -->
    <div class="report-page-header">

        <div class="report-header-left">

            <!-- Breadcrumb -->
            <div class="report-breadcrumb">
                <a href="reports.php">Reports</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>Records Report</span>
            </div>

            <!-- Title -->
            <h1>Records Report</h1>

            <!-- Description -->
            <p>
                Overview and analysis of customer and pet records.
            </p>

        </div>

        <!-- Header Actions -->
        <div class="report-header-actions">

            <button
                type="button"
                class="print-report-btn"
                id="printRecordsReport"
            >
                <i class="fa-solid fa-print"></i>
                Print Report
            </button>

            <a
                href="reports.php"
                class="back-report-btn"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Back to Reports
            </a>

        </div>

    </div>

            <!-- =====================================================
                 SUMMARY CARDS
                 ===================================================== -->
            <section class="record-metrics">

                <article class="record-metric-card">
                    <div class="metric-icon blue">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <span>Total Customers</span>
                        <strong><?= number_format($totalCustomers) ?></strong>
                        <small>Total Customers</small>
                    </div>
                </article>

                <article class="record-metric-card">
                    <div class="metric-icon green">
                        <i class="fa-solid fa-paw"></i>
                    </div>
                    <div>
                        <span>Total Pets</span>
                        <strong><?= number_format($totalPets) ?></strong>
                        <small>Total Pets</small>
                    </div>
                </article>

                <article class="record-metric-card">
                    <div class="metric-icon blue-soft">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>
                    <div>
                        <span>Active Records</span>
                        <strong class="green-text"><?= number_format($activeRecords) ?></strong>
                        <small>Active Records</small>
                    </div>
                </article>

                <article class="record-metric-card">
                    <div class="metric-icon orange">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                    <div>
                        <span>Archived Records</span>
                        <strong class="red-text"><?= number_format($archivedRecords) ?></strong>
                        <small>Archived Records</small>
                    </div>
                </article>

            </section>

            <!-- =====================================================
                 FILTERS
                 ===================================================== -->
            <section class="report-card filters-card">
                <div class="report-card-header compact-header">
                    <div>
                        <h2>Filters</h2>
                        <p>Refine the report view using the available record filters.</p>
                    </div>
                </div>

                <div class="records-filters">

                    <div class="filter-field">
                        <label for="dateFilter">Date Registered</label>
                        <select id="dateFilter">
                            <option value="all">All Dates</option>
                            <option value="today">Today</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>

                    <div class="filter-field">
                        <label for="breedFilter">Breed</label>
                        <select id="breedFilter">
                            <option value="all">All Breeds</option>
                            <?php foreach ($breedRows as $row): ?>
                                <option value="<?= htmlspecialchars(strtolower($row['breed_name'])) ?>">
                                    <?= htmlspecialchars($row['breed_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-field">
                        <label for="statusFilter">Status</label>
                        <select id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="button" class="reset-filter-btn" id="resetRecordFilters">
                            Reset
                        </button>
                        <button type="button" class="apply-filter-btn" id="applyRecordFilters">
                            Apply Filter
                        </button>
                    </div>

                </div>
            </section>

            <!-- =====================================================
                 MAIN CHARTS
                 ===================================================== -->
            <section class="chart-grid">

                <article class="report-card chart-card wide-chart">
                    <div class="report-card-header">
                        <div>
                            <h2>Pets Registered Over Time</h2>
                            <p>Monthly pet registrations during the last 12 months.</p>
                        </div>
                        <span class="report-badge">
                            <i class="fa-solid fa-chart-line"></i>
                            Pet Trend
                        </span>
                    </div>

                    <div class="chart-wrap">
                        <canvas id="petsTrendChart"></canvas>
                    </div>

                    <div class="print-interpretation">
                        <h3>Interpretation</h3>
                        <p>
                            <?= htmlspecialchars($petTrendInterpretation) ?>
                        </p>
                    </div>
                </article>

                <article class="report-card chart-card">
                    <div class="report-card-header">
                        <div>
                            <h2>Pets by Breed</h2>
                            <p>Top 10 breeds in the system.</p>
                        </div>
                        <span class="report-badge purple-badge">
                            <i class="fa-solid fa-chart-column"></i>
                            Top 10
                        </span>
                    </div>
                    <div class="chart-wrap breed-chart-wrap">
                        <canvas id="breedChart"></canvas>
                    </div>

                    <div class="print-interpretation">
                        <h3>Interpretation</h3>
                        <p>
                            <?= htmlspecialchars($breedInterpretation) ?>
                        </p>
                    </div>
                </article>

            </section>

            <section class="chart-grid second-chart-row">

                <article class="report-card chart-card wide-chart">
                    <div class="report-card-header">
                        <div>
                            <h2>Customers Registered Over Time</h2>
                            <p>Monthly customer registrations during the last 12 months.</p>
                        </div>
                        <span class="report-badge green-badge">
                            <i class="fa-solid fa-users"></i>
                            Customer Trend
                        </span>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="customersTrendChart"></canvas>
                    </div>

                    <div class="print-interpretation">
                        <h3>Interpretation</h3>
                        <p>
                            <?= htmlspecialchars($customerTrendInterpretation) ?>
                        </p>

                    </div>
                </article>

                <article class="report-card chart-card">
                    <div class="report-card-header">
                        <div>
                            <h2>Record Status Distribution</h2>
                            <p>Current customer record status.</p>
                        </div>
                        <span class="report-badge orange-badge">
                            <i class="fa-solid fa-chart-pie"></i>
                            Status
                        </span>
                    </div>
                    <div class="status-chart-wrap">
                        <canvas id="statusChart"></canvas>
                    </div>

                    <div class="print-interpretation">
                        <h3>Interpretation</h3>
                        <p>
                            <?= htmlspecialchars($statusInterpretation) ?>
                        </p>
                    </div>
                </article>

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
                        <h3>Summary Insight</h3>
                        <p><?= htmlspecialchars($insightText) ?></p>
                    </div>
                </article>

                <article class="insight-card blue-insight">
                    <div class="insight-icon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div>
                        <h3>About this report</h3>
                        <p>
                            This report provides an overview of customer and pet records in the system.
                            Use the filters above to review records based on date, breed, and status.
                        </p>
                    </div>
                </article>

            </section>

            <div class="report-footer">
                © 2026 3K Pet Solution Animal Clinic. All rights reserved.
            </div>

        </div>

    </main>
</div>

<script>
window.recordsReportData = <?= json_encode([
    'petTrend' => [
        'labels' => $petTrendLabels,
        'values' => $petTrendValues
    ],
    'customerTrend' => [
        'labels' => $customerTrendLabels,
        'values' => $customerTrendValues
    ],
    'breed' => [
        'labels' => $breedLabels,
        'values' => $breedValues
    ],
    'status' => [
        'labels' => $statusLabels,
        'values' => $statusValues
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="../assets/js/records_report.js"></script>

</body>
</html>
