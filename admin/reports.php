<?php
/* =========================================================
   REPORTS MODULE
   File: admin/reports.php
   Purpose: Reports landing page and database data preparation
   ========================================================= */

/* =========================================================
   1. SESSION & DATABASE
   ========================================================= */
session_start();

if (!isset($_SESSION['admin_username'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';

/* =========================================================
   2. HELPER FUNCTIONS
   ========================================================= */
function q1($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die('Reports query failed: ' . mysqli_error($conn));
    }

    return mysqli_fetch_assoc($result) ?: [];
}

function qall($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die('Reports query failed: ' . mysqli_error($conn));
    }

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function money($number)
{
    return '₱' . number_format((float) $number, 2);
}

function petId($id)
{
    return 'PET-' . str_pad((int) $id, 3, '0', STR_PAD_LEFT);
}

function stockStatus($stock, $reorder)
{
    $stock = (float) $stock;
    $reorder = (float) $reorder;

    if ($stock <= 0) {
        return ['Out of Stock', 'out'];
    }

    if ($reorder > 0 && $stock <= $reorder) {
        return ['Low Stock', 'low'];
    }

    return ['In Stock', 'in'];
}

/* =========================================================
   3. RECORDS REPORT DATA
   ========================================================= */

$recordsSummary = q1(
    $conn,
    "
    SELECT
        (SELECT COUNT(*)
         FROM customers
         WHERE record_status = 'Active') AS total_customers,

        (SELECT COUNT(*)
         FROM pets) AS total_pets,

        (SELECT COUNT(*)
         FROM customers
         WHERE record_status = 'Active') AS active_records,

        (SELECT COUNT(*)
         FROM customers
         WHERE record_status <> 'Active') AS archived_records
    "
);

$totalCustomers = (int) ($recordsSummary['total_customers'] ?? 0);
$totalPets = (int) ($recordsSummary['total_pets'] ?? 0);
$activeRecords = (int) ($recordsSummary['active_records'] ?? 0);
$archivedRecords = (int) ($recordsSummary['archived_records'] ?? 0);
$totalRecords = $totalCustomers + $totalPets;

$recentRecords = qall(
    $conn,
    "
    SELECT
        p.pet_id,
        p.pet_name,
        c.owner_name,
        p.species,
        p.created_at,
        c.record_status
    FROM pets p
    INNER JOIN customers c
        ON c.customer_id = p.customer_id
    ORDER BY p.created_at DESC
    LIMIT 5
    "
);

$recordsTrend = qall(
    $conn,
    "
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        DATE_FORMAT(created_at, '%b') AS month_label,
        COUNT(*) AS pet_count
    FROM pets
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
    GROUP BY
        DATE_FORMAT(created_at, '%Y-%m'),
        DATE_FORMAT(created_at, '%b')
    ORDER BY month_key
    "
);

/* =========================================================
   4. SALES REPORT DATA
   Cash-only setup: no payment-method field is required.
   ========================================================= */

$salesSummary = q1(
    $conn,
    "
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'Paid'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_sales,

        COUNT(*) AS transactions,

        SUM(
            CASE
                WHEN payment_status = 'Paid'
                THEN 1
                ELSE 0
            END
        ) AS paid_transactions,

        SUM(
            CASE
                WHEN payment_status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_transactions
    FROM billing
    WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND created_at < DATE_ADD(
          DATE_FORMAT(CURDATE(), '%Y-%m-01'),
          INTERVAL 1 MONTH
      )
    "
);

$totalSales = (float) ($salesSummary['total_sales'] ?? 0);
$salesTransactions = (int) ($salesSummary['transactions'] ?? 0);
$paidTransactions = (int) ($salesSummary['paid_transactions'] ?? 0);
$pendingTransactions = (int) ($salesSummary['pending_transactions'] ?? 0);

$salesTrend = qall(
    $conn,
    "
    SELECT
        DATE(created_at) AS sale_date,
        SUM(
            CASE
                WHEN payment_status = 'Paid'
                THEN total_amount
                ELSE 0
            END
        ) AS daily_sales
    FROM billing
    WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND created_at < DATE_ADD(
          DATE_FORMAT(CURDATE(), '%Y-%m-01'),
          INTERVAL 1 MONTH
      )
    GROUP BY DATE(created_at)
    ORDER BY sale_date
    "
);

$recentSales = qall(
    $conn,
    "
    SELECT
        b.created_at,
        b.billing_id,
        p.pet_name,
        c.owner_name,
        b.total_amount,
        b.payment_status
    FROM billing b
    INNER JOIN customers c
        ON c.customer_id = b.customer_id
    INNER JOIN pets p
        ON p.pet_id = b.pet_id
    ORDER BY b.created_at DESC
    LIMIT 5
    "
);

/* =========================================================
   5. INVENTORY REPORT DATA
   ========================================================= */

$inventorySummary = q1(
    $conn,
    "
    SELECT
        COUNT(*) AS total_items,

        SUM(
            CASE
                WHEN COALESCE(st.total_stock, 0) > 0
                THEN 1
                ELSE 0
            END
        ) AS in_stock,

        SUM(
            CASE
                WHEN COALESCE(st.total_stock, 0) > 0
                 AND i.reorder_level > 0
                 AND COALESCE(st.total_stock, 0) <= i.reorder_level
                THEN 1
                ELSE 0
            END
        ) AS low_stock,

        SUM(
            CASE
                WHEN COALESCE(st.total_stock, 0) <= 0
                THEN 1
                ELSE 0
            END
        ) AS out_of_stock,

        SUM(
            CASE
                WHEN st.nearest_expiry >= CURDATE()
                 AND st.nearest_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND COALESCE(st.total_stock, 0) > 0
                THEN 1
                ELSE 0
            END
        ) AS expiring_soon

    FROM inventory_items i

    LEFT JOIN (
        SELECT
            item_id,
            SUM(quantity) AS total_stock,
            MIN(
                CASE
                    WHEN quantity > 0
                     AND expiration_date IS NOT NULL
                    THEN expiration_date
                END
            ) AS nearest_expiry
        FROM inventory_stock
        GROUP BY item_id
    ) st
        ON st.item_id = i.item_id

    WHERE i.status = 'Active'
    "
);

$totalItems = (int) ($inventorySummary['total_items'] ?? 0);
$inStock = (int) ($inventorySummary['in_stock'] ?? 0);
$lowStock = (int) ($inventorySummary['low_stock'] ?? 0);
$outOfStock = (int) ($inventorySummary['out_of_stock'] ?? 0);
$expiringSoon = (int) ($inventorySummary['expiring_soon'] ?? 0);

$inventoryOverview = qall(
    $conn,
    "
    SELECT
        i.item_code,
        i.item_name,
        c.category_name,
        i.reorder_level,
        COALESCE(st.total_stock, 0) AS total_stock,
        st.nearest_expiry
    FROM inventory_items i

    INNER JOIN inventory_categories c
        ON c.category_id = i.category_id

    LEFT JOIN (
        SELECT
            item_id,
            SUM(quantity) AS total_stock,
            MIN(
                CASE
                    WHEN quantity > 0
                     AND expiration_date IS NOT NULL
                    THEN expiration_date
                END
            ) AS nearest_expiry
        FROM inventory_stock
        GROUP BY item_id
    ) st
        ON st.item_id = i.item_id

    WHERE i.status = 'Active'

    ORDER BY
        CASE
            WHEN COALESCE(st.total_stock, 0) <= 0
            THEN 0
            ELSE 1
        END,
        st.nearest_expiry IS NULL,
        st.nearest_expiry,
        i.item_name

    LIMIT 5
    "
);

$expiring = qall(
    $conn,
    "
    SELECT
        i.item_name,
        st.nearest_expiry,
        (
            SELECT s2.batch_number
            FROM inventory_stock s2
            WHERE s2.item_id = i.item_id
              AND s2.quantity > 0
              AND s2.expiration_date IS NOT NULL
            ORDER BY s2.expiration_date, s2.stock_id
            LIMIT 1
        ) AS batch_number
    FROM inventory_items i

    LEFT JOIN (
        SELECT
            item_id,
            MIN(
                CASE
                    WHEN quantity > 0
                     AND expiration_date IS NOT NULL
                    THEN expiration_date
                END
            ) AS nearest_expiry
        FROM inventory_stock
        GROUP BY item_id
    ) st
        ON st.item_id = i.item_id

    WHERE i.status = 'Active'
      AND st.nearest_expiry >= CURDATE()
      AND st.nearest_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)

    ORDER BY st.nearest_expiry
    LIMIT 4
    "
);

/* =========================================================
   6. CHART DATA FOR reports.js
   ========================================================= */
$reportsChartData = [
    'records' => [
        'labels' => array_column($recordsTrend, 'month_label'),
        'values' => array_map(
            'intval',
            array_column($recordsTrend, 'pet_count')
        ),
    ],
    'sales' => [
        'labels' => array_map(
            function ($row) {
                return date('M d', strtotime($row['sale_date']));
            },
            $salesTrend
        ),
        'values' => array_map(
            'floatval',
            array_column($salesTrend, 'daily_sales')
        ),
    ],
    'inventory' => [
        $inStock,
        $lowStock,
        $outOfStock,
        $expiringSoon,
    ],
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports | Veterinary MIS</title>

    <!-- Main admin layout -->
    <link rel="stylesheet" href="../assets/css/layout.css">

    <!-- Reports page styles -->
    <link rel="stylesheet" href="../assets/css/reports.css">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <!-- =====================================================
         ADMIN LAYOUT
         ===================================================== -->
    <div class="container">

        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="content">

            <?php
            // Set the page title before loading the shared topbar.
            // The topbar displays this value instead of its default "Dashboard".
            $pageTitle = "Reports";
            $showAdminInfo = false;
            include __DIR__ . '/partials/topbar.php';
            ?>

            <!-- =================================================
                 REPORTS PAGE CONTENT
                 ================================================= -->
            <div class="page-content reports-page">

                <!-- Page heading -->
                <div class="reports-head">
                    <h1>Reports</h1>
                    <p>View and analyze system data and performance.</p>
                </div>

                <!-- =================================================
                     SUMMARY CARDS
                     ================================================= -->
                <div class="summary-grid">

                    <!-- Records summary -->
                    <article class="summary">
                        <div class="sum-icon blue-bg">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div class="sum-body">
                            <h3>Records Report</h3>
                            <p>Total customers and pets in the system</p>
                            <strong><?= number_format($totalRecords) ?></strong>
                            <span>Total Records</span>
                        </div>

                        <a href="records_report.php">
                            View Report
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </article>

                    <!-- Sales summary -->
                    <article class="summary">
                        <div class="sum-icon green-bg">
                            <i class="fa-solid fa-wallet"></i>
                        </div>

                        <div class="sum-body">
                            <h3>Sales Report</h3>
                            <p>Total sales and transactions summary</p>
                            <strong><?= money($totalSales) ?></strong>
                            <span>Total Sales</span>
                        </div>

                        <a href="sales_report.php">
                            View Report
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </article>

                    <!-- Inventory summary -->
                    <article class="summary">
                        <div class="sum-icon orange-bg">
                            <i class="fa-solid fa-box"></i>
                        </div>

                        <div class="sum-body">
                            <h3>Inventory Report</h3>
                            <p>Current inventory and stock status</p>
                            <strong><?= number_format($totalItems) ?></strong>
                            <span>Total Items</span>
                        </div>

                        <a href="inventory_report.php">
                            View Report
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </article>

                </div>

                <!-- =================================================
                     REPORT PANELS
                     ================================================= -->
                <div class="panel-grid">

                    <!-- =================================================
                         RECORDS REPORT PANEL
                         ================================================= -->
                    <section class="panel">

                        <div class="panel-head">
                            <div class="panel-title">
                                <div class="panel-icon blue-bg">
                                    <i class="fa-solid fa-address-book"></i>
                                </div>
                                <h2>Records Report</h2>
                            </div>

                            <div class="filter">
                                <span>Date Registered</span>
                                <select>
                                    <option>This Month</option>
                                    <option>This Year</option>
                                    <option>All Time</option>
                                </select>
                            </div>
                        </div>

                        <!-- Records metrics -->
                        <div class="metrics four">
                            <div class="metric">
                                <span>Total Customers</span>
                                <strong><?= $totalCustomers ?></strong>
                            </div>

                            <div class="metric">
                                <span>Total Pets</span>
                                <strong><?= $totalPets ?></strong>
                            </div>

                            <div class="metric">
                                <span>Active Records</span>
                                <strong class="green"><?= $activeRecords ?></strong>
                            </div>

                            <div class="metric">
                                <span>Archived Records</span>
                                <strong class="red"><?= $archivedRecords ?></strong>
                            </div>
                        </div>

                        <!-- Records chart -->
                        <div class="chart">
                            <div class="chart-title">
                                <h3>Pets Registered Overview</h3>

                                <select>
                                    <option>This Year</option>
                                    <option>Last 12 Months</option>
                                </select>
                            </div>

                            <div class="chart-box">
                                <canvas id="recordsChart"></canvas>
                            </div>
                        </div>

                        <!-- Recent records table -->
                        <div class="table-section">
                            <div class="table-title">
                                <h3>Recent Records</h3>
                                <a href="records_report.php">View All</a>
                            </div>

                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>PET ID</th>
                                            <th>PET NAME</th>
                                            <th>OWNER</th>
                                            <th>SPECIES</th>
                                            <th>DATE REGISTERED</th>
                                            <th>STATUS</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php if (!$recentRecords): ?>
                                            <tr>
                                                <td colspan="6">No records found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentRecords as $record): ?>
                                                <tr>
                                                    <td>
                                                        <strong>
                                                            <?= htmlspecialchars(petId($record['pet_id'])) ?>
                                                        </strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($record['pet_name']) ?></td>
                                                    <td><?= htmlspecialchars($record['owner_name']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($record['species'])) ?></td>
                                                    <td><?= date('m/d/Y', strtotime($record['created_at'])) ?></td>
                                                    <td>
                                                        <span class="pill active">
                                                            <?= htmlspecialchars($record['record_status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <a class="full-link" href="records_report.php">
                            View Full Records Report
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </section>

                    <!-- =================================================
                         SALES REPORT PANEL
                         ================================================= -->
                    <section class="panel">

                        <div class="panel-head">
                            <div class="panel-title">
                                <div class="panel-icon green-bg">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <h2>Sales Report</h2>
                            </div>

                            <div class="filter">
                                <span>Date Range</span>
                                <select>
                                    <option>This Month</option>
                                    <option>Last Month</option>
                                    <option>This Year</option>
                                </select>
                            </div>
                        </div>

                        <!-- Sales metrics -->
                        <div class="metrics four">
                            <div class="metric">
                                <span>Total Sales</span>
                                <strong class="money"><?= money($totalSales) ?></strong>
                            </div>

                            <div class="metric">
                                <span>Transactions</span>
                                <strong><?= $salesTransactions ?></strong>
                            </div>

                            <div class="metric">
                                <span>Paid</span>
                                <strong class="green"><?= $paidTransactions ?></strong>
                            </div>

                            <div class="metric">
                                <span>Pending</span>
                                <strong class="orange"><?= $pendingTransactions ?></strong>
                            </div>
                        </div>

                        <!-- Sales chart -->
                        <div class="chart">
                            <div class="chart-title">
                                <h3>Sales Trend</h3>
                            </div>

                            <div class="chart-box">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>

                        <!-- Recent sales table -->
                        <div class="table-section">
                            <div class="table-title">
                                <h3>Recent Sales</h3>
                                <a href="sales_report.php">View All</a>
                            </div>

                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>DATE</th>
                                            <th>BILLING</th>
                                            <th>PET</th>
                                            <th>OWNER</th>
                                            <th>AMOUNT</th>
                                            <th>STATUS</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php if (!$recentSales): ?>
                                            <tr>
                                                <td colspan="6">No sales found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentSales as $sale): ?>
                                                <tr>
                                                    <td><?= date('m/d/Y', strtotime($sale['created_at'])) ?></td>
                                                    <td>
                                                        INV-<?= str_pad((int) $sale['billing_id'], 6, '0', STR_PAD_LEFT) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($sale['pet_name']) ?></td>
                                                    <td><?= htmlspecialchars($sale['owner_name']) ?></td>
                                                    <td><?= money($sale['total_amount']) ?></td>
                                                    <td>
                                                        <span class="pill <?= $sale['payment_status'] === 'Paid' ? 'paid' : 'pending' ?>">
                                                            <?= htmlspecialchars($sale['payment_status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <a class="full-link" href="sales_report.php">
                            View Full Sales Report
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </section>

                    <!-- =================================================
                         INVENTORY REPORT PANEL
                         ================================================= -->
                    <section class="panel">

                        <div class="panel-head">
                            <div class="panel-title">
                                <div class="panel-icon orange-bg">
                                    <i class="fa-solid fa-box"></i>
                                </div>
                                <h2>Inventory Report</h2>
                            </div>

                            <div class="filter">
                                <span>Category</span>
                                <select>
                                    <option>All Categories</option>
                                </select>
                            </div>
                        </div>

                        <!-- Inventory metrics -->
                        <div class="metrics five">
                            <div class="metric">
                                <span>Total Items</span>
                                <strong><?= $totalItems ?></strong>
                            </div>

                            <div class="metric">
                                <span>In Stock</span>
                                <strong class="green"><?= $inStock ?></strong>
                            </div>

                            <div class="metric">
                                <span>Low Stock</span>
                                <strong class="orange"><?= $lowStock ?></strong>
                            </div>

                            <div class="metric">
                                <span>Out of Stock</span>
                                <strong class="red"><?= $outOfStock ?></strong>
                            </div>

                            <div class="metric">
                                <span>Expiring Soon</span>
                                <strong class="purple"><?= $expiringSoon ?></strong>
                            </div>
                        </div>

                        <!-- Inventory visual section -->
                        <div class="inventory-visual">

                            <!-- Stock status chart -->
                            <div class="chart">
                                <div class="chart-title">
                                    <h3>Stock Status</h3>
                                </div>

                                <div class="donut">
                                    <canvas id="inventoryChart"></canvas>
                                </div>
                            </div>

                            <!-- Expiring soon list -->
                            <div class="expiring">
                                <div class="table-title">
                                    <h3>Expiring Soon</h3>
                                    <a href="inventory_report.php">View All</a>
                                </div>

                                <?php if (!$expiring): ?>
                                    <p class="empty-expiring">
                                        No items expiring within 30 days.
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($expiring as $item): ?>
                                        <div class="exp-item">
                                            <i class="fa-solid fa-triangle-exclamation"></i>

                                            <div class="exp-info">
                                                <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                                <span>
                                                    Batch:
                                                    <?= htmlspecialchars($item['batch_number'] ?: '—') ?>
                                                </span>
                                            </div>

                                            <time>
                                                <?= date('m/d/Y', strtotime($item['nearest_expiry'])) ?>
                                            </time>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                        </div>

                        <!-- Inventory overview table -->
                        <div class="table-section">
                            <div class="table-title">
                                <h3>Inventory Overview</h3>
                                <a href="inventory_report.php">View All</a>
                            </div>

                            <div class="table-wrap">
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>ITEM ID</th>
                                            <th>ITEM NAME</th>
                                            <th>CATEGORY</th>
                                            <th>CURRENT STOCK</th>
                                            <th>STATUS</th>
                                            <th>EXPIRY DATE</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php if (!$inventoryOverview): ?>
                                            <tr>
                                                <td colspan="6">No inventory items found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($inventoryOverview as $item): ?>
                                                <?php
                                                [$statusText, $statusClass] = stockStatus(
                                                    $item['total_stock'],
                                                    $item['reorder_level']
                                                );
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['item_code']) ?></td>
                                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                                    <td><?= htmlspecialchars($item['category_name']) ?></td>
                                                    <td><?= number_format((float) $item['total_stock'], 0) ?></td>
                                                    <td>
                                                        <span class="pill <?= $statusClass ?>">
                                                            <?= htmlspecialchars($statusText) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($item['nearest_expiry']): ?>
                                                            <?= date('m/d/Y', strtotime($item['nearest_expiry'])) ?>
                                                        <?php else: ?>
                                                            —
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <a class="full-link" href="inventory_report.php">
                            View Full Inventory Report
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </section>

                </div>

                <!-- Footer -->
                <div class="footer">
                    © <?= date('Y') ?> 3K Pet Solution Animal Clinic. All rights reserved.
                </div>

            </div>

        </main>

    </div>

    <!-- =====================================================
         JAVASCRIPT
         ===================================================== -->

    <!-- Main admin layout JS -->
    <script src="../assets/js/layout.js"></script>

    <!-- Reports chart data -->
    <script id="reportsData" type="application/json">
        <?= json_encode($reportsChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>

    <!-- Reports page JS -->
    
    <script src="../assets/js/reports.js"></script>

</body>

</html>