<?php
/* =========================================================
   SALES REPORT MODULE

   File:
   admin/sales_report.php

   Purpose:
   Detailed sales report for the veterinary clinic.

   Database:
   billing
   billing_items
   customers
   pets

   Cash-only setup:
   No payment method is included.
   ========================================================= */

session_start();

/* =========================================================
   1. ADMIN SESSION
   ========================================================= */

if (!isset($_SESSION['admin_username'])) {
    header('Location: ../auth/login.php');
    exit();
}


/* =========================================================
   2. DATABASE
   ========================================================= */

require_once __DIR__ . '/../config/database.php';


/* =========================================================
   3. HELPER FUNCTIONS
   ========================================================= */

function q1($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die(
            'Sales Report query failed: '
            . mysqli_error($conn)
        );
    }

    return mysqli_fetch_assoc($result) ?: [];
}


function qall($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die(
            'Sales Report query failed: '
            . mysqli_error($conn)
        );
    }

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}


function money($amount)
{
    return '₱' . number_format(
        (float) $amount,
        2
    );
}


function invoiceNumber($billingId)
{
    return 'INV-' . str_pad(
        (int) $billingId,
        6,
        '0',
        STR_PAD_LEFT
    );
}


/* =========================================================
   4. FILTER VALUES
   ========================================================= */

$dateFrom = $_GET['date_from']
    ?? date('Y-m-01');

$dateTo = $_GET['date_to']
    ?? date('Y-m-d');

$statusFilter = $_GET['status']
    ?? 'All';

$itemTypeFilter = $_GET['item_type']
    ?? 'All';


/* =========================================================
   5. VALIDATE DATE FILTER
   ========================================================= */

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $dateFrom = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $dateTo = date('Y-m-d');
}


/* =========================================================
   6. BUILD FILTER CONDITIONS
   ========================================================= */

$whereConditions = [];

$whereConditions[] = "
    DATE(b.created_at)
    BETWEEN
    '" . mysqli_real_escape_string($conn, $dateFrom) . "'
    AND
    '" . mysqli_real_escape_string($conn, $dateTo) . "'
";


/*
 * Payment status filter
 */

if (
    $statusFilter === 'Paid'
    || $statusFilter === 'Pending'
) {

    $safeStatus =
        mysqli_real_escape_string(
            $conn,
            $statusFilter
        );

    $whereConditions[] = "
        b.payment_status = '$safeStatus'
    ";
}


/*
 * Item type filter
 *
 * Uses EXISTS so that one billing transaction
 * will not be duplicated.
 */

if (
    $itemTypeFilter !== 'All'
    && $itemTypeFilter !== ''
) {

    $safeItemType =
        mysqli_real_escape_string(
            $conn,
            $itemTypeFilter
        );

    $whereConditions[] = "
        EXISTS (
            SELECT 1
            FROM billing_items bi_filter
            WHERE bi_filter.billing_id = b.billing_id
              AND bi_filter.item_type = '$safeItemType'
        )
    ";
}


$whereSQL = implode(
    ' AND ',
    $whereConditions
);


/* =========================================================
   7. SALES SUMMARY
   ========================================================= */

$salesSummary = q1(
    $conn,
    "
    SELECT

        COALESCE(
            SUM(
                CASE
                    WHEN b.payment_status = 'Paid'
                    THEN b.total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_sales,

        COUNT(*) AS transactions,

        SUM(
            CASE
                WHEN b.payment_status = 'Paid'
                THEN 1
                ELSE 0
            END
        ) AS paid_transactions,

        SUM(
            CASE
                WHEN b.payment_status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_transactions

    FROM billing b

    WHERE $whereSQL
    "
);


$totalSales =
    (float) (
        $salesSummary['total_sales']
        ?? 0
    );

$salesTransactions =
    (int) (
        $salesSummary['transactions']
        ?? 0
    );

$paidTransactions =
    (int) (
        $salesSummary['paid_transactions']
        ?? 0
    );

$pendingTransactions =
    (int) (
        $salesSummary['pending_transactions']
        ?? 0
    );


/* =========================================================
   8. AVERAGE SALE
   ========================================================= */

$averageSale = 0;

if ($paidTransactions > 0) {

    $averageSale =
        $totalSales / $paidTransactions;
}


/* =========================================================
   9. SALES TREND
   ========================================================= */

$salesTrend = qall(
    $conn,
    "
    SELECT

        DATE(b.created_at) AS sale_date,

        SUM(
            CASE
                WHEN b.payment_status = 'Paid'
                THEN b.total_amount
                ELSE 0
            END
        ) AS daily_sales

    FROM billing b

    WHERE $whereSQL

    GROUP BY DATE(b.created_at)

    ORDER BY sale_date ASC
    "
);


/* =========================================================
   10. SALES BY ITEM TYPE
   ========================================================= */

$salesByType = qall(
    $conn,
    "
    SELECT

        bi.item_type,

        SUM(bi.amount) AS total_amount

    FROM billing_items bi

    INNER JOIN billing b
        ON b.billing_id = bi.billing_id

    WHERE b.payment_status = 'Paid'

      AND DATE(b.created_at)
          BETWEEN
          '" . mysqli_real_escape_string(
              $conn,
              $dateFrom
          ) . "'
          AND
          '" . mysqli_real_escape_string(
              $conn,
              $dateTo
          ) . "'

      " .
      (
          $itemTypeFilter !== 'All'
          && $itemTypeFilter !== ''
          ?
          "AND bi.item_type = '" .
          mysqli_real_escape_string(
              $conn,
              $itemTypeFilter
          ) .
          "'"
          :
          ""
      ) .

      "

    GROUP BY bi.item_type

    ORDER BY total_amount DESC
    "
);


/* =========================================================
   11. TOP 10 ITEMS
   ========================================================= */

$topItems = qall(
    $conn,
    "
    SELECT

        bi.item_name,

        SUM(bi.quantity) AS total_quantity,

        SUM(bi.amount) AS total_amount

    FROM billing_items bi

    INNER JOIN billing b
        ON b.billing_id = bi.billing_id

    WHERE b.payment_status = 'Paid'

      AND DATE(b.created_at)
          BETWEEN
          '" . mysqli_real_escape_string(
              $conn,
              $dateFrom
          ) . "'
          AND
          '" . mysqli_real_escape_string(
              $conn,
              $dateTo
          ) . "'

      " .
      (
          $itemTypeFilter !== 'All'
          && $itemTypeFilter !== ''
          ?
          "AND bi.item_type = '" .
          mysqli_real_escape_string(
              $conn,
              $itemTypeFilter
          ) .
          "'"
          :
          ""
      ) .

      "

    GROUP BY bi.item_name

    ORDER BY total_amount DESC

    LIMIT 10
    "
);


/* =========================================================
   12. PAYMENT STATUS DISTRIBUTION
   ========================================================= */

$statusDistribution = qall(
    $conn,
    "
    SELECT

        b.payment_status,

        COUNT(*) AS total_transactions

    FROM billing b

    WHERE $whereSQL

    GROUP BY b.payment_status

    ORDER BY b.payment_status
    "
);


/* =========================================================
   13. ITEM TYPE OPTIONS
   ========================================================= */

$itemTypes = qall(
    $conn,
    "
    SELECT DISTINCT
        item_type

    FROM billing_items

    WHERE item_type IS NOT NULL
      AND TRIM(item_type) <> ''

    ORDER BY item_type ASC
    "
);


/* =========================================================
   14. RECENT TRANSACTIONS
   ========================================================= */

$recentTransactions = qall(
    $conn,
    "
    SELECT

        b.billing_id,

        b.created_at,

        p.pet_name,

        c.owner_name,

        b.total_amount,

        b.payment_status,

        COUNT(bi.billing_item_id)
            AS item_count

    FROM billing b

    INNER JOIN customers c
        ON c.customer_id = b.customer_id

    INNER JOIN pets p
        ON p.pet_id = b.pet_id

    LEFT JOIN billing_items bi
        ON bi.billing_id = b.billing_id

    WHERE $whereSQL

    GROUP BY

        b.billing_id,
        b.created_at,
        p.pet_name,
        c.owner_name,
        b.total_amount,
        b.payment_status

    ORDER BY b.created_at DESC

    LIMIT 10
    "
);


/* =========================================================
   15. CHART DATA
   ========================================================= */

$salesChartLabels = [];

$salesChartValues = [];

foreach ($salesTrend as $row) {

    $salesChartLabels[] =
        date(
            'M d',
            strtotime($row['sale_date'])
        );

    $salesChartValues[] =
        (float) $row['daily_sales'];
}


$typeChartLabels = [];

$typeChartValues = [];

foreach ($salesByType as $row) {

    $typeChartLabels[] =
        $row['item_type'];

    $typeChartValues[] =
        (float) $row['total_amount'];
}


$topItemLabels = [];

$topItemValues = [];

foreach ($topItems as $row) {

    $topItemLabels[] =
        $row['item_name'];

    $topItemValues[] =
        (float) $row['total_amount'];
}


$statusChartLabels = [];

$statusChartValues = [];

foreach ($statusDistribution as $row) {

    $statusChartLabels[] =
        $row['payment_status'];

    $statusChartValues[] =
        (int) $row['total_transactions'];
}


/* =========================================================
   16. CHART DATA JSON
   ========================================================= */

$salesReportChartData = [

    'salesTrend' => [

        'labels' =>
            $salesChartLabels,

        'values' =>
            $salesChartValues

    ],

    'salesByType' => [

        'labels' =>
            $typeChartLabels,

        'values' =>
            $typeChartValues

    ],

    'topItems' => [

        'labels' =>
            $topItemLabels,

        'values' =>
            $topItemValues

    ],

    'statusDistribution' => [

        'labels' =>
            $statusChartLabels,

        'values' =>
            $statusChartValues

    ]

];

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
        Sales Report | Veterinary MIS
    </title>


    <!-- =====================================================
         SHARED LAYOUT CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >


    <!-- =====================================================
         SALES REPORT CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/sales_report.css"
    >


    <!-- =====================================================
         FONT AWESOME
         ===================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <!-- =====================================================
         CHART.JS
         ===================================================== -->

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js"
    ></script>

</head>


<body>


<div class="container">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <?php include 'partials/sidebar.php'; ?>


    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main
        class="content"
        id="mainContent"
    >


        <!-- =================================================
             TOPBAR
             ================================================= -->

        <?php

        $pageTitle = 'Sales Report';

        $showAdminInfo = true;

        include 'partials/topbar.php';

        ?>


        <!-- =================================================
             SALES REPORT PAGE
             ================================================= -->

        <div class="sales-report-page">


            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <div class="sales-report-header">


                <div class="sales-report-title">


                    <div>

                        <div class="sales-breadcrumb">

                            <a href="reports.php">
                                Reports
                            </a>

                            <i class="fa-solid fa-chevron-right"></i>

                            <span>
                                Sales Report
                            </span>

                        </div>


                        <h1>
                            Sales Report
                        </h1>


                        <p>
                            View and analyze clinic sales and billing transactions.
                        </p>

                    </div>


                </div>


                <div class="sales-report-actions">


                    <button
                        type="button"
                        class="report-button print-button"
                        onclick="window.print()"
                    >

                        <i class="fa-solid fa-print"></i>

                        Print Report

                    </button>


                    <button
                        type="button"
                        class="report-button back-button"
                        onclick="window.location.href='reports.php'"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to Reports

                    </button>


                </div>


            </div>


            <!-- =================================================
                 SUMMARY CARDS
                 ================================================= -->

            <section class="sales-summary-grid">


                <!-- TOTAL SALES -->

                <article class="sales-summary-card">

                    <div class="sales-card-icon green">

                        <i class="fa-solid fa-peso-sign"></i>

                    </div>


                    <div>

                        <span>
                            TOTAL SALES
                        </span>

                        <strong>
                            <?= money($totalSales) ?>
                        </strong>

                        <small>
                            Paid transactions only
                        </small>

                    </div>

                </article>


                <!-- TRANSACTIONS -->

                <article class="sales-summary-card">

                    <div class="sales-card-icon blue">

                        <i class="fa-solid fa-receipt"></i>

                    </div>


                    <div>

                        <span>
                            TRANSACTIONS
                        </span>

                        <strong>
                            <?= number_format($salesTransactions) ?>
                        </strong>

                        <small>
                            Total billing records
                        </small>

                    </div>

                </article>


                <!-- PAID -->

                <article class="sales-summary-card">

                    <div class="sales-card-icon green">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>


                    <div>

                        <span>
                            PAID
                        </span>

                        <strong>
                            <?= number_format($paidTransactions) ?>
                        </strong>

                        <small>
                            Completed payments
                        </small>

                    </div>

                </article>


                <!-- PENDING -->

                <article class="sales-summary-card">

                    <div class="sales-card-icon orange">

                        <i class="fa-solid fa-clock"></i>

                    </div>


                    <div>

                        <span>
                            PENDING
                        </span>

                        <strong>
                            <?= number_format($pendingTransactions) ?>
                        </strong>

                        <small>
                            Unpaid transactions
                        </small>

                    </div>

                </article>


            </section>


            <!-- =================================================
                 FILTERS
                 ================================================= -->

            <section class="sales-filter-card">


                <div class="filter-heading">

                    <div>

                        <h2>
                            Filters
                        </h2>

                        <p>
                            Filter sales data by date and transaction status.
                        </p>

                    </div>

                </div>


                <form
                    method="GET"
                    class="sales-filter-form"
                >


                    <div class="sales-filter-field">

                        <label for="date_from">
                            Date From
                        </label>

                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="<?= htmlspecialchars($dateFrom) ?>"
                        >

                    </div>


                    <div class="sales-filter-field">

                        <label for="date_to">
                            Date To
                        </label>

                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="<?= htmlspecialchars($dateTo) ?>"
                        >

                    </div>


                    <div class="sales-filter-field">

                        <label for="status">
                            Payment Status
                        </label>

                        <select
                            id="status"
                            name="status"
                        >

                            <option
                                value="All"
                                <?= $statusFilter === 'All'
                                    ? 'selected'
                                    : '' ?>
                            >
                                All Status
                            </option>

                            <option
                                value="Paid"
                                <?= $statusFilter === 'Paid'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Paid
                            </option>

                            <option
                                value="Pending"
                                <?= $statusFilter === 'Pending'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Pending
                            </option>

                        </select>

                    </div>


                    <div class="sales-filter-field">

                        <label for="item_type">
                            Item Type
                        </label>

                        <select
                            id="item_type"
                            name="item_type"
                        >

                            <option
                                value="All"
                                <?= $itemTypeFilter === 'All'
                                    ? 'selected'
                                    : '' ?>
                            >
                                All Item Types
                            </option>


                            <?php foreach ($itemTypes as $type): ?>

                                <option
                                    value="<?= htmlspecialchars($type['item_type']) ?>"
                                    <?= $itemTypeFilter === $type['item_type']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars($type['item_type']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="sales-filter-actions">

                        <a
                            href="sales_report.php"
                            class="reset-filter-button"
                        >
                            Reset
                        </a>


                        <button
                            type="submit"
                            class="apply-filter-button"
                        >

                            <i class="fa-solid fa-filter"></i>

                            Apply Filter

                        </button>

                    </div>


                </form>

            </section>


            <!-- =================================================
                 CHART GRID
                 ================================================= -->

            <section class="sales-chart-grid">


                <!-- SALES TREND -->

                <article class="sales-chart-card sales-trend-card">


                    <div class="sales-chart-header">

                        <div>

                            <h2>
                                Sales Trend
                            </h2>

                            <p>
                                Paid sales over the selected period.
                            </p>

                        </div>

                    </div>


                    <div class="sales-chart-container">

                        <canvas
                            id="salesTrendChart"
                        ></canvas>

                    </div>


                </article>


                <!-- SALES BY TYPE -->

                <article class="sales-chart-card">


                    <div class="sales-chart-header">

                        <div>

                            <h2>
                                Sales by Item Type
                            </h2>

                            <p>
                                Revenue distribution by item type.
                            </p>

                        </div>

                    </div>


                    <div class="sales-chart-container donut-container">

                        <canvas
                            id="salesTypeChart"
                        ></canvas>

                    </div>


                </article>


                <!-- TOP ITEMS -->

                <article class="sales-chart-card">


                    <div class="sales-chart-header">

                        <div>

                            <h2>
                                Top 10 Services & Items
                            </h2>

                            <p>
                                Highest revenue-generating items.
                            </p>

                        </div>

                    </div>


                    <div class="sales-chart-container">

                        <canvas
                            id="topItemsChart"
                        ></canvas>

                    </div>


                </article>


                <!-- PAYMENT STATUS -->

                <article class="sales-chart-card">


                    <div class="sales-chart-header">

                        <div>

                            <h2>
                                Transaction Status
                            </h2>

                            <p>
                                Paid and pending transactions.
                            </p>

                        </div>

                    </div>


                    <div class="sales-chart-container donut-container">

                        <canvas
                            id="statusChart"
                        ></canvas>

                    </div>


                </article>


            </section>


            <!-- =================================================
                 SALES INSIGHT
                 ================================================= -->

            <section class="sales-insight-card">


                <div class="insight-icon">

                    <i class="fa-solid fa-lightbulb"></i>

                </div>


                <div>

                    <h3>
                        Sales Insight
                    </h3>

                    <p>

                        Average paid transaction:
                        <strong>
                            <?= money($averageSale) ?>
                        </strong>

                    </p>

                </div>

            </section>


            <!-- =================================================
                 RECENT TRANSACTIONS
                 ================================================= -->

            <section class="recent-sales-card">


                <div class="recent-sales-header">

                    <div>

                        <h2>
                            Recent Transactions
                        </h2>

                        <p>
                            Latest billing transactions within the selected period.
                        </p>

                    </div>

                </div>


                <div class="sales-table-wrapper">


                    <table class="sales-report-table">


                        <thead>

                            <tr>

                                <th>
                                    DATE
                                </th>

                                <th>
                                    INVOICE NO.
                                </th>

                                <th>
                                    PET
                                </th>

                                <th>
                                    OWNER
                                </th>

                                <th>
                                    ITEMS
                                </th>

                                <th>
                                    AMOUNT
                                </th>

                                <th>
                                    STATUS
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (!$recentTransactions): ?>


                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-sales"
                                >

                                    No transactions found
                                    for the selected filters.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $recentTransactions
                                as $transaction
                            ): ?>


                                <tr>


                                    <td>

                                        <?= date(
                                            'm/d/Y',
                                            strtotime(
                                                $transaction['created_at']
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong class="invoice-number">

                                            <?= invoiceNumber(
                                                $transaction['billing_id']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $transaction['pet_name']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $transaction['owner_name']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            (int) $transaction['item_count']
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= money(
                                                $transaction['total_amount']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?php

                                        $statusClass =
                                            $transaction['payment_status']
                                            === 'Paid'
                                            ? 'paid'
                                            : 'pending';

                                        ?>


                                        <span
                                            class="sales-status-pill <?= $statusClass ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $transaction['payment_status']
                                            ) ?>

                                        </span>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </section>


            <!-- =================================================
                 ABOUT REPORT
                 ================================================= -->

            <section class="sales-about-card">


                <div class="about-icon">

                    <i class="fa-solid fa-circle-info"></i>

                </div>


                <div>

                    <h3>
                        About this report
                    </h3>

                    <p>

                        This report provides an overview of
                        clinic sales and billing transactions.
                        Paid transactions are included in total
                        sales, while pending transactions are
                        shown separately.

                    </p>

                </div>


            </section>


            <!-- =================================================
                 FOOTER
                 ================================================= -->

            <footer class="sales-report-footer">

                © 2026 3K Pet Solution Animal Clinic.
                All rights reserved.

            </footer>


        </div>


    </main>


</div>


<!-- =========================================================
     REPORT DATA FOR JAVASCRIPT
     ========================================================= -->

<script id="salesReportData">

    window.salesReportData =
        <?= json_encode(
            $salesReportChartData,
            JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_AMP
            | JSON_HEX_QUOT
        ) ?>;

</script>


<!-- =========================================================
     SHARED LAYOUT JS
     ========================================================= -->

<script
    src="../assets/js/layout.js"
></script>


<!-- =========================================================
     SALES REPORT JS
     ========================================================= -->

<script
    src="../assets/js/sales_report.js"
></script>


</body>

</html>