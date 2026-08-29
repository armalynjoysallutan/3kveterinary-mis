<?php
session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

/* =========================================================
   INVENTORY REPORT FILTERS
   ========================================================= */

$categoryFilter = isset($_GET["category_id"])
    ? (int)$_GET["category_id"]
    : 0;

$statusFilter = isset($_GET["stock_status"])
    ? trim($_GET["stock_status"])
    : "";

$expiryFilter = isset($_GET["expiry"])
    ? trim($_GET["expiry"])
    : "";


/* =========================================================
   CATEGORIES
   ========================================================= */

$categories = [];

$categorySql = "
    SELECT
        category_id,
        category_name
    FROM inventory_categories
    WHERE status = 'Active'
    ORDER BY category_name ASC
";

$categoryResult = mysqli_query($conn, $categorySql);

if ($categoryResult) {
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row;
    }
}


/* =========================================================
   SUMMARY COUNTS
   ========================================================= */

$totalItems = 0;
$inStock = 0;
$reorderSoon = 0;
$outOfStock = 0;

$summarySql = "
    SELECT
        COUNT(*) AS total_items,

        COALESCE(
            SUM(
                CASE
                    WHEN COALESCE(s.total_stock, 0) > 0
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS in_stock,

        COALESCE(
            SUM(
                CASE
                    WHEN COALESCE(s.total_stock, 0) > 0
                     AND COALESCE(s.total_stock, 0) <= i.reorder_level
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS reorder_soon,

        COALESCE(
            SUM(
                CASE
                    WHEN COALESCE(s.total_stock, 0) <= 0
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS out_of_stock

    FROM inventory_items i

    LEFT JOIN (
        SELECT
            item_id,
            SUM(quantity) AS total_stock
        FROM inventory_stock
        GROUP BY item_id
    ) s
        ON s.item_id = i.item_id

    WHERE i.status = 'Active'
";

$summaryResult = mysqli_query($conn, $summarySql);

if ($summaryResult) {

    $summaryRow = mysqli_fetch_assoc($summaryResult);

    $totalItems = (int)($summaryRow["total_items"] ?? 0);
    $inStock = (int)($summaryRow["in_stock"] ?? 0);
    $reorderSoon = (int)($summaryRow["reorder_soon"] ?? 0);
    $outOfStock = (int)($summaryRow["out_of_stock"] ?? 0);
}


/* =========================================================
   TOTAL INVENTORY QUANTITY
   ========================================================= */

$totalQuantity = 0;

$quantitySql = "
    SELECT
        COALESCE(SUM(st.quantity), 0) AS total_quantity
    FROM inventory_items i
    INNER JOIN inventory_stock st
        ON st.item_id = i.item_id
    WHERE i.status = 'Active'
";

$quantityResult = mysqli_query($conn, $quantitySql);

if ($quantityResult) {

    $quantityRow = mysqli_fetch_assoc($quantityResult);

    $totalQuantity = (float)(
        $quantityRow["total_quantity"] ?? 0
    );
}


/* =========================================================
   INVENTORY VALUE
   Based on current stock × unit cost
   ========================================================= */

$totalInventoryCost = 0;
$totalRetailValue = 0;

$valueSql = "
    SELECT
        COALESCE(
            SUM(
                COALESCE(st.total_stock, 0)
                * COALESCE(i.unit_cost, 0)
            ),
            0
        ) AS inventory_cost,

        COALESCE(
            SUM(
                COALESCE(st.total_stock, 0)
                * COALESCE(i.retail_price, 0)
            ),
            0
        ) AS retail_value

    FROM inventory_items i

    LEFT JOIN (
        SELECT
            item_id,
            SUM(quantity) AS total_stock
        FROM inventory_stock
        GROUP BY item_id
    ) st
        ON st.item_id = i.item_id

    WHERE i.status = 'Active'
";

$valueResult = mysqli_query($conn, $valueSql);

if ($valueResult) {

    $valueRow = mysqli_fetch_assoc($valueResult);

    $totalInventoryCost = (float)(
        $valueRow["inventory_cost"] ?? 0
    );

    $totalRetailValue = (float)(
        $valueRow["retail_value"] ?? 0
    );
}


/* =========================================================
   STOCK STATUS CHART
   ========================================================= */

$stockStatusData = [
    "labels" => [
        "In Stock",
        "Re-order Soon",
        "Out of Stock"
    ],

    "values" => [
        $inStock,
        $reorderSoon,
        $outOfStock
    ]
];


/* =========================================================
   CATEGORY CHART
   ========================================================= */

$categoryChartLabels = [];
$categoryChartValues = [];

$categoryChartSql = "
    SELECT
        c.category_name,

        COUNT(i.item_id) AS item_count

    FROM inventory_categories c

    LEFT JOIN inventory_items i
        ON i.category_id = c.category_id
        AND i.status = 'Active'

    WHERE c.status = 'Active'

    GROUP BY
        c.category_id,
        c.category_name

    ORDER BY
        item_count DESC,
        c.category_name ASC
";

$categoryChartResult = mysqli_query(
    $conn,
    $categoryChartSql
);

if ($categoryChartResult) {

    while ($row = mysqli_fetch_assoc($categoryChartResult)) {

        $categoryChartLabels[] =
            $row["category_name"];

        $categoryChartValues[] =
            (int)$row["item_count"];
    }
}


/* =========================================================
   CATEGORY STOCK QUANTITY
   ========================================================= */

$categoryStockLabels = [];
$categoryStockValues = [];

$categoryStockSql = "
    SELECT
        c.category_name,

        COALESCE(
            SUM(
                COALESCE(st.quantity, 0)
            ),
            0
        ) AS total_stock

    FROM inventory_categories c

    INNER JOIN inventory_items i
        ON i.category_id = c.category_id
        AND i.status = 'Active'

    LEFT JOIN inventory_stock st
        ON st.item_id = i.item_id

    WHERE c.status = 'Active'

    GROUP BY
        c.category_id,
        c.category_name

    ORDER BY
        total_stock DESC,
        c.category_name ASC
";

$categoryStockResult = mysqli_query(
    $conn,
    $categoryStockSql
);

if ($categoryStockResult) {

    while ($row = mysqli_fetch_assoc($categoryStockResult)) {

        $categoryStockLabels[] =
            $row["category_name"];

        $categoryStockValues[] =
            (float)$row["total_stock"];
    }
}


/* =========================================================
   EXPIRING ITEMS
   ========================================================= */

$expiringItems = [];

$expiringSql = "
    SELECT
        i.item_id,
        i.item_code,
        i.item_name,
        c.category_name,
        st.batch_number,
        st.expiration_date,
        st.quantity,
        i.reorder_level

    FROM inventory_stock st

    INNER JOIN inventory_items i
        ON i.item_id = st.item_id

    INNER JOIN inventory_categories c
        ON c.category_id = i.category_id

    WHERE i.status = 'Active'
      AND st.quantity > 0
      AND st.expiration_date IS NOT NULL
      AND st.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)

    ORDER BY
        st.expiration_date ASC,
        i.item_name ASC

    LIMIT 10
";

$expiringResult = mysqli_query(
    $conn,
    $expiringSql
);

if ($expiringResult) {

    while ($row = mysqli_fetch_assoc($expiringResult)) {

        $expiringItems[] = $row;
    }
}


/* =========================================================
   INVENTORY LIST
   ========================================================= */

$inventoryRows = [];

$listSql = "
    SELECT

        i.item_id,
        i.item_code,
        i.item_name,
        i.reorder_level,
        i.unit_cost,
        i.retail_price,

        c.category_name,

        u.abbreviation,

        COALESCE(
            st.total_stock,
            0
        ) AS total_stock,

        (
            SELECT st2.batch_number

            FROM inventory_stock st2

            WHERE st2.item_id = i.item_id
              AND st2.quantity > 0

            ORDER BY

                CASE
                    WHEN st2.expiration_date IS NULL
                    THEN 1
                    ELSE 0
                END ASC,

                st2.expiration_date ASC,
                st2.stock_id ASC

            LIMIT 1

        ) AS current_batch,

        (
            SELECT st3.expiration_date

            FROM inventory_stock st3

            WHERE st3.item_id = i.item_id
              AND st3.quantity > 0

            ORDER BY

                CASE
                    WHEN st3.expiration_date IS NULL
                    THEN 1
                    ELSE 0
                END ASC,

                st3.expiration_date ASC,
                st3.stock_id ASC

            LIMIT 1

        ) AS current_expiry

    FROM inventory_items i

    INNER JOIN inventory_categories c
        ON c.category_id = i.category_id

    LEFT JOIN inventory_units u
        ON u.unit_id = i.unit_id

    LEFT JOIN (
        SELECT
            item_id,
            SUM(quantity) AS total_stock
        FROM inventory_stock
        GROUP BY item_id
    ) st
        ON st.item_id = i.item_id

    WHERE i.status = 'Active'
";


/* =========================================================
   CATEGORY FILTER
   ========================================================= */

if ($categoryFilter > 0) {

    $listSql .= "
        AND i.category_id = {$categoryFilter}
    ";
}


/* =========================================================
   STOCK STATUS FILTER
   ========================================================= */

if ($statusFilter === "in-stock") {

    $listSql .= "
        AND COALESCE(st.total_stock, 0) > 0
        AND (
            i.reorder_level <= 0
            OR COALESCE(st.total_stock, 0) > i.reorder_level
        )
    ";

} elseif ($statusFilter === "low-stock") {

    $listSql .= "
        AND COALESCE(st.total_stock, 0) > 0
        AND i.reorder_level > 0
        AND COALESCE(st.total_stock, 0) <= i.reorder_level
    ";

} elseif ($statusFilter === "out-of-stock") {

    $listSql .= "
        AND COALESCE(st.total_stock, 0) <= 0
    ";
}


/* =========================================================
   EXPIRY FILTER
   ========================================================= */

if ($expiryFilter === "expiring") {

    $listSql .= "
        AND EXISTS (
            SELECT 1
            FROM inventory_stock ex
            WHERE ex.item_id = i.item_id
              AND ex.quantity > 0
              AND ex.expiration_date IS NOT NULL
              AND ex.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        )
    ";

} elseif ($expiryFilter === "expired") {

    $listSql .= "
        AND EXISTS (
            SELECT 1
            FROM inventory_stock ex
            WHERE ex.item_id = i.item_id
              AND ex.quantity > 0
              AND ex.expiration_date IS NOT NULL
              AND ex.expiration_date < CURDATE()
        )
    ";
}


$listSql .= "
    ORDER BY
        c.category_name ASC,
        i.item_name ASC
";


$listResult = mysqli_query(
    $conn,
    $listSql
);

if ($listResult) {

    while ($row = mysqli_fetch_assoc($listResult)) {

        $inventoryRows[] = $row;
    }
}


/* =========================================================
   HELPER FUNCTIONS
   ========================================================= */

function inventoryReportStatus(
    $stock,
    $reorderLevel
) {

    $stock = (float)$stock;
    $reorderLevel = (float)$reorderLevel;

    if ($stock <= 0) {

        return [
            "Out of Stock",
            "out-of-stock"
        ];
    }

    if (
        $reorderLevel > 0 &&
        $stock <= $reorderLevel
    ) {

        return [
            "Low Stock",
            "low-stock"
        ];
    }

    return [
        "In Stock",
        "in-stock"
    ];
}


function expiryStatus($date)
{

    if (empty($date)) {

        return [
            "No Expiry",
            "normal"
        ];
    }

    $today = new DateTime();
    $expiry = new DateTime($date);

    if ($expiry < $today) {

        return [
            "Expired",
            "expired"
        ];
    }

    $days = (int)$today->diff($expiry)->format("%r%a");

    if ($days <= 30) {

        return [
            "Expiring Soon",
            "expiring-soon"
        ];
    }

    return [
        "Valid",
        "valid"
    ];
}


/* =========================================================
   PAGE DATA FOR JAVASCRIPT
   ========================================================= */

$inventoryReportData = [

    "stockStatus" => $stockStatusData,

    "categoryItems" => [
        "labels" => $categoryChartLabels,
        "values" => $categoryChartValues
    ],

    "categoryStock" => [
        "labels" => $categoryStockLabels,
        "values" => $categoryStockValues
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
        Inventory Report | Veterinary MIS
    </title>


    <!-- SHARED LAYOUT -->

    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >


    <!-- INVENTORY REPORT CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/inventory_report.css"
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

        $pageTitle = "Inventory Report";

        $showAdminInfo = false;

        include "partials/topbar.php";

        ?>


        <!-- REPORT PAGE -->

        <div class="inventory-report-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="report-page-header">

                <div>

                    <a
                        href="reports.php"
                        class="back-report-link"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to Reports

                    </a>


                    <h1>
                        Inventory Report
                    </h1>


                    <p>
                        Overview of stock levels,
                        inventory status, and expiring items.
                    </p>

                </div>


                <button
                    type="button"
                    class="print-report-btn"
                    id="printInventoryReport"
                >

                    <i class="fa-solid fa-print"></i>

                    Print Report

                </button>

            </div>


            <!-- =================================================
                 SUMMARY CARDS
            ================================================== -->

            <div class="inventory-report-metrics">


                <!-- TOTAL ITEMS -->

                <div class="inventory-report-card">

                    <div class="report-metric-icon blue">

                        <i class="fa-solid fa-box"></i>

                    </div>

                    <div>

                        <span>
                            Total Items
                        </span>

                        <strong>
                            <?= number_format($totalItems) ?>
                        </strong>

                        <small>
                            Active inventory items
                        </small>

                    </div>

                </div>


                <!-- IN STOCK -->

                <div class="inventory-report-card">

                    <div class="report-metric-icon green">

                        <i class="fa-solid fa-boxes-stacked"></i>

                    </div>

                    <div>

                        <span>
                            In Stock
                        </span>

                        <strong>
                            <?= number_format($inStock) ?>
                        </strong>

                        <small>
                            Items with available stock
                        </small>

                    </div>

                </div>


                <!-- LOW STOCK -->

                <div class="inventory-report-card">

                    <div class="report-metric-icon orange">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>

                    <div>

                        <span>
                            Re-order Soon
                        </span>

                        <strong>
                            <?= number_format($reorderSoon) ?>
                        </strong>

                        <small>
                            At or below reorder level
                        </small>

                    </div>

                </div>


                <!-- OUT OF STOCK -->

                <div class="inventory-report-card">

                    <div class="report-metric-icon red">

                        <i class="fa-solid fa-circle-xmark"></i>

                    </div>

                    <div>

                        <span>
                            Out of Stock
                        </span>

                        <strong>
                            <?= number_format($outOfStock) ?>
                        </strong>

                        <small>
                            Items needing restock
                        </small>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 CHART ROW
            ================================================== -->

            <div class="inventory-report-chart-grid">


                <!-- STOCK STATUS -->

                <div class="report-card">

                    <div class="report-card-header">

                        <div>

                            <h2>
                                Stock Status
                            </h2>

                            <p>
                                Current inventory availability.
                            </p>

                        </div>

                        <span class="report-badge blue-badge">

                            <i class="fa-solid fa-chart-pie"></i>

                            Overview

                        </span>

                    </div>


                    <div class="inventory-chart-wrap">

                        <canvas
                            id="inventoryStatusChart"
                        ></canvas>

                    </div>

                </div>


                <!-- CATEGORY -->

                <div class="report-card">

                    <div class="report-card-header">

                        <div>

                            <h2>
                                Items by Category
                            </h2>

                            <p>
                                Number of active items per category.
                            </p>

                        </div>

                        <span class="report-badge purple-badge">

                            <i class="fa-solid fa-layer-group"></i>

                            Categories

                        </span>

                    </div>


                    <div class="inventory-chart-wrap">

                        <canvas
                            id="inventoryCategoryChart"
                        ></canvas>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 SECOND CHART ROW
            ================================================== -->

            <div class="inventory-report-chart-grid">


                <!-- STOCK QUANTITY -->

                <div class="report-card">

                    <div class="report-card-header">

                        <div>

                            <h2>
                                Stock Quantity by Category
                            </h2>

                            <p>
                                Current quantity available per category.
                            </p>

                        </div>

                        <span class="report-badge green-badge">

                            <i class="fa-solid fa-boxes-stacked"></i>

                            Quantity

                        </span>

                    </div>


                    <div class="inventory-chart-wrap">

                        <canvas
                            id="inventoryQuantityChart"
                        ></canvas>

                    </div>

                </div>


                <!-- INVENTORY VALUE -->

                <div class="report-card">

                    <div class="report-card-header">

                        <div>

                            <h2>
                                Inventory Value
                            </h2>

                            <p>
                                Estimated value based on current stock.
                            </p>

                        </div>

                        <span class="report-badge orange-badge">

                            <i class="fa-solid fa-peso-sign"></i>

                            Value

                        </span>

                    </div>


                    <div class="inventory-value-content">

                        <div class="inventory-value-item">

                            <span>
                                Total Quantity
                            </span>

                            <strong>
                                <?= number_format($totalQuantity, 0) ?>
                            </strong>

                        </div>


                        <div class="inventory-value-item">

                            <span>
                                Inventory Cost
                            </span>

                            <strong>
                                ₱<?= number_format($totalInventoryCost, 2) ?>
                            </strong>

                        </div>


                        <div class="inventory-value-item">

                            <span>
                                Retail Value
                            </span>

                            <strong>
                                ₱<?= number_format($totalRetailValue, 2) ?>
                            </strong>

                        </div>


                    </div>

                </div>


            </div>


            <!-- =================================================
                 FILTERS
            ================================================== -->

            <div class="report-card filters-card">

                <div class="report-card-header">

                    <div>

                        <h2>
                            Inventory Filters
                        </h2>

                        <p>
                            Filter the inventory report.
                        </p>

                    </div>

                </div>


                <form
                    method="GET"
                    class="inventory-report-filters"
                >


                    <!-- CATEGORY -->

                    <div class="filter-field">

                        <label for="category_id">
                            Category
                        </label>

                        <select
                            name="category_id"
                            id="category_id"
                        >

                            <option value="">
                                All Categories
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= (int)$category["category_id"] ?>"
                                    <?= $categoryFilter === (int)$category["category_id"] ? "selected" : "" ?>
                                >

                                    <?= htmlspecialchars($category["category_name"]) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- STOCK STATUS -->

                    <div class="filter-field">

                        <label for="stock_status">
                            Stock Status
                        </label>

                        <select
                            name="stock_status"
                            id="stock_status"
                        >

                            <option
                                value=""
                                <?= $statusFilter === "" ? "selected" : "" ?>
                            >
                                All Status
                            </option>

                            <option
                                value="in-stock"
                                <?= $statusFilter === "in-stock" ? "selected" : "" ?>
                            >
                                In Stock
                            </option>

                            <option
                                value="low-stock"
                                <?= $statusFilter === "low-stock" ? "selected" : "" ?>
                            >
                                Low Stock
                            </option>

                            <option
                                value="out-of-stock"
                                <?= $statusFilter === "out-of-stock" ? "selected" : "" ?>
                            >
                                Out of Stock
                            </option>

                        </select>

                    </div>


                    <!-- EXPIRATION -->

                    <div class="filter-field">

                        <label for="expiry">
                            Expiration
                        </label>

                        <select
                            name="expiry"
                            id="expiry"
                        >

                            <option
                                value=""
                                <?= $expiryFilter === "" ? "selected" : "" ?>
                            >
                                All Items
                            </option>

                            <option
                                value="expiring"
                                <?= $expiryFilter === "expiring" ? "selected" : "" ?>
                            >
                                Expiring Within 90 Days
                            </option>

                            <option
                                value="expired"
                                <?= $expiryFilter === "expired" ? "selected" : "" ?>
                            >
                                Expired
                            </option>

                        </select>

                    </div>


                    <!-- ACTIONS -->

                    <div class="filter-actions">

                        <a
                            href="inventory_report.php"
                            class="reset-filter-btn"
                        >

                            <i class="fa-solid fa-rotate-left"></i>

                            Reset

                        </a>


                        <button
                            type="submit"
                            class="apply-filter-btn"
                        >

                            <i class="fa-solid fa-filter"></i>

                            Apply Filters

                        </button>

                    </div>


                </form>

            </div>


            <!-- =================================================
                 EXPIRING ITEMS
            ================================================== -->

            <div class="report-card expiring-card">

                <div class="report-card-header">

                    <div>

                        <h2>
                            Expiring Items
                        </h2>

                        <p>
                            Items with stock expiring within 90 days.
                        </p>

                    </div>

                    <span class="report-badge orange-badge">

                        <i class="fa-solid fa-calendar-xmark"></i>

                        Expiration

                    </span>

                </div>


                <div class="report-table-wrap">

                    <table class="inventory-report-table">

                        <thead>

                            <tr>

                                <th>
                                    ITEM ID
                                </th>

                                <th>
                                    ITEM NAME
                                </th>

                                <th>
                                    CATEGORY
                                </th>

                                <th>
                                    BATCH NO.
                                </th>

                                <th>
                                    EXPIRY DATE
                                </th>

                                <th>
                                    STOCK
                                </th>

                                <th>
                                    STATUS
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (empty($expiringItems)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-report"
                                >

                                    <i class="fa-solid fa-circle-check"></i>

                                    No items are expiring within 90 days.

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($expiringItems as $item): ?>

                                <?php

                                [$expiryText, $expiryClass] =
                                    expiryStatus(
                                        $item["expiration_date"]
                                    );

                                ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars($item["item_code"]) ?>
                                        </strong>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($item["item_name"]) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($item["category_name"]) ?>

                                    </td>

                                    <td>

                                        <?= !empty($item["batch_number"])
                                            ? htmlspecialchars($item["batch_number"])
                                            : "—"
                                        ?>

                                    </td>

                                    <td>

                                        <?= date(
                                            "M d, Y",
                                            strtotime(
                                                $item["expiration_date"]
                                            )
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= number_format(
                                            (float)$item["quantity"],
                                            0
                                        ) ?>

                                    </td>

                                    <td>

                                        <span
                                            class="expiry-pill <?= htmlspecialchars($expiryClass) ?>"
                                        >

                                            <?= htmlspecialchars($expiryText) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- =================================================
                 INVENTORY LIST
            ================================================== -->

            <div class="report-card inventory-list-report-card">

                <div class="report-card-header">

                    <div>

                        <h2>
                            Inventory Overview
                        </h2>

                        <p>
                            Current stock, batch, expiration, and pricing information.
                        </p>

                    </div>

                    <span class="report-badge blue-badge">

                        <i class="fa-solid fa-boxes-stacked"></i>

                        <?= number_format(count($inventoryRows)) ?> Items

                    </span>

                </div>


                <div class="report-table-wrap">

                    <table class="inventory-report-table">

                        <thead>

                            <tr>

                                <th>
                                    ITEM ID
                                </th>

                                <th>
                                    ITEM NAME
                                </th>

                                <th>
                                    CATEGORY
                                </th>

                                <th>
                                    STOCK
                                </th>

                                <th>
                                    UNIT COST
                                </th>

                                <th>
                                    RETAIL PRICE
                                </th>

                                <th>
                                    BATCH NO.
                                </th>

                                <th>
                                    EXPIRY DATE
                                </th>

                                <th>
                                    STATUS
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (empty($inventoryRows)): ?>

                            <tr>

                                <td
                                    colspan="9"
                                    class="empty-report"
                                >

                                    <i class="fa-solid fa-box-open"></i>

                                    No inventory items match the selected filters.

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($inventoryRows as $item): ?>

                                <?php

                                [$statusText, $statusClass] =
                                    inventoryReportStatus(
                                        $item["total_stock"],
                                        $item["reorder_level"]
                                    );

                                ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars($item["item_code"]) ?>
                                        </strong>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($item["item_name"]) ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($item["category_name"]) ?>

                                    </td>

                                    <td>

                                        <?= number_format(
                                            (float)$item["total_stock"],
                                            0
                                        ) ?>

                                        <?php if (!empty($item["abbreviation"])): ?>

                                            <?= htmlspecialchars(
                                                $item["abbreviation"]
                                            ) ?>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        ₱<?= number_format(
                                            (float)$item["unit_cost"],
                                            2
                                        ) ?>

                                    </td>

                                    <td>

                                        ₱<?= number_format(
                                            (float)$item["retail_price"],
                                            2
                                        ) ?>

                                    </td>

                                    <td>

                                        <?= !empty($item["current_batch"])
                                            ? htmlspecialchars($item["current_batch"])
                                            : "—"
                                        ?>

                                    </td>

                                    <td>

                                        <?php if (!empty($item["current_expiry"])): ?>

                                            <?= htmlspecialchars(
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $item["current_expiry"]
                                                    )
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <span
                                            class="inventory-status-pill <?= htmlspecialchars($statusClass) ?>"
                                        >

                                            <?= htmlspecialchars($statusText) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div class="report-footer">

                Inventory Report • Veterinary MIS

            </div>


        </div>

    </main>

</div>


<!-- =========================================================
     CHART DATA
========================================================= -->

<script>

window.inventoryReportData =
<?= json_encode(
    $inventoryReportData,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;

</script>


<!-- CHART.JS -->

<script
    src="https://cdn.jsdelivr.net/npm/chart.js"
></script>


<!-- SHARED JS -->

<script
    src="../assets/js/layout.js"
></script>


<!-- INVENTORY REPORT JS -->

<script
    src="../assets/js/inventory_report.js"
></script>


</body>

</html>