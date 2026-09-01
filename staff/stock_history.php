<?php
session_start();

if (
    !isset($_SESSION["account_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Staff"
) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';


/* =========================================================
   FILTERS
========================================================= */

$search = trim($_GET['search'] ?? '');
$type   = $_GET['type'] ?? 'all';

$allowedTypes = ['all', 'in', 'out'];

if (!in_array($type, $allowedTypes, true)) {
    $type = 'all';
}


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatHistoryDate($date)
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return e($date);
    }

    return date('M d, Y', $timestamp);
}

function formatHistoryDateTime($date)
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return e($date);
    }

    return date('M d, Y h:i A', $timestamp);
}

function transactionLabel($type)
{
    return $type === 'in' ? 'Stock In' : 'Stock Out';
}

function transactionClass($type)
{
    return $type === 'in' ? 'stock-in' : 'stock-out';
}

function quantityPrefix($type)
{
    return $type === 'in' ? '+' : '-';
}


/* =========================================================
   STOCK HISTORY
========================================================= */

$historyRows = [];


/* =========================================================
   STOCK IN
========================================================= */

if ($type === 'all' || $type === 'in') {

    $sqlIn = "
        SELECT
            'in' AS transaction_type,
            si.stock_in_id AS transaction_id,
            st.item_id,
            i.item_code,
            i.item_name,
            st.batch_number,
            si.quantity,
            st.expiration_date,
            si.date_received AS transaction_date,
            si.reference_number,
            si.remarks,
            NULL AS reason

        FROM inventory_stock_in si

        INNER JOIN inventory_stock st
            ON st.stock_id = si.stock_id

        INNER JOIN inventory_items i
            ON i.item_id = st.item_id
    ";


    if ($search !== '') {

        $sqlIn .= "
            WHERE
                i.item_code LIKE ?
                OR i.item_name LIKE ?
                OR st.batch_number LIKE ?
                OR si.reference_number LIKE ?
        ";

        $stmt = mysqli_prepare($conn, $sqlIn);

        if ($stmt) {

            $searchParam = '%' . $search . '%';

            mysqli_stmt_bind_param(
                $stmt,
                'ssss',
                $searchParam,
                $searchParam,
                $searchParam,
                $searchParam
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $historyRows[] = $row;
            }

            mysqli_stmt_close($stmt);
        }

    } else {

        $sqlIn .= "
            ORDER BY
                si.date_received DESC,
                si.stock_in_id DESC
        ";

        $result = mysqli_query($conn, $sqlIn);

        if ($result) {

            while ($row = mysqli_fetch_assoc($result)) {
                $historyRows[] = $row;
            }
        }
    }
}


/* =========================================================
   STOCK OUT
========================================================= */

if ($type === 'all' || $type === 'out') {

    $sqlOut = "
        SELECT
            'out' AS transaction_type,
            so.stock_out_id AS transaction_id,
            st.item_id,
            i.item_code,
            i.item_name,
            st.batch_number,
            so.quantity,
            st.expiration_date,
            so.stock_out_date AS transaction_date,
            so.reference_number,
            so.remarks,
            so.reason

        FROM inventory_stock_out so

        INNER JOIN inventory_stock st
            ON st.stock_id = so.stock_id

        INNER JOIN inventory_items i
            ON i.item_id = st.item_id
    ";


    if ($search !== '') {

        $sqlOut .= "
            WHERE
                i.item_code LIKE ?
                OR i.item_name LIKE ?
                OR st.batch_number LIKE ?
                OR so.reference_number LIKE ?
        ";

        $stmt = mysqli_prepare($conn, $sqlOut);

        if ($stmt) {

            $searchParam = '%' . $search . '%';

            mysqli_stmt_bind_param(
                $stmt,
                'ssss',
                $searchParam,
                $searchParam,
                $searchParam,
                $searchParam
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $historyRows[] = $row;
            }

            mysqli_stmt_close($stmt);
        }

    } else {

        $sqlOut .= "
            ORDER BY
                so.stock_out_date DESC,
                so.stock_out_id DESC
        ";

        $result = mysqli_query($conn, $sqlOut);

        if ($result) {

            while ($row = mysqli_fetch_assoc($result)) {
                $historyRows[] = $row;
            }
        }
    }
}


/* =========================================================
   SORT COMBINED HISTORY
========================================================= */

usort($historyRows, function ($a, $b) {

    $dateA = strtotime($a['transaction_date'] ?? '');
    $dateB = strtotime($b['transaction_date'] ?? '');

    if ($dateA === $dateB) {
        return ($b['transaction_id'] ?? 0)
            <=> ($a['transaction_id'] ?? 0);
    }

    return $dateB <=> $dateA;
});

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Stock History | Veterinary MIS</title>


    <!-- SAME CSS AS INVENTORY -->

    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/appointments.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/inventory.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <!-- =====================================================
         STOCK HISTORY ONLY
         NO TOPBAR / CONTENT OVERRIDES HERE
    ====================================================== -->

    <style>

        /* =====================================================
           HISTORY FILTER BUTTONS
        ===================================================== */

        .history-filter-btn {
            height: 42px;
            padding: 0 16px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            border: 1px solid #dfe5ee;
            border-radius: 8px;

            background: #fff;
            color: #344054;

            font-size: 13px;
            font-weight: 600;

            text-decoration: none;

            white-space: nowrap;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .history-filter-btn:hover {
            border-color: #2563eb;
            color: #2563eb;
            background: #f8fbff;
        }


        .history-filter-btn.active {
            border-color: #2563eb;
            background: #2563eb;
            color: #fff;
        }


        /* =====================================================
           HISTORY LIST CARD
           Uses same inventory card style
        ===================================================== */

        .stock-history-list-card {
            margin-top: 16px;

            border: 1px solid #e5e9f0;
            border-radius: 10px;

            background: #fff;

            overflow: visible;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.04);
        }


        .stock-history-section-header {
            min-height: 68px;

            padding: 16px 20px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            border-bottom: 1px solid #edf0f5;

            box-sizing: border-box;
        }


        .stock-history-section-header h3 {
            margin: 0;

            color: #26354a;

            font-size: 17px;
        }


        .stock-history-section-header p {
            margin: 4px 0 0;

            color: #8a94a6;

            font-size: 12px;
        }


        /* =====================================================
           HISTORY TABLE
        ===================================================== */

        .stock-history-table-wrapper {
            width: 100%;

            max-width: 100%;

            overflow-x: auto;
            overflow-y: visible;

            -webkit-overflow-scrolling: touch;
        }


        .stock-history-table {
            width: 100%;

            min-width: 1000px;

            table-layout: fixed;

            border-collapse: separate;
            border-spacing: 0;

            font-size: 12px;
        }


        .stock-history-table th {

            padding: 12px 10px;

            background: #f8fafc;

            color: #5c6677;

            border-bottom: 1px solid #e5e9f0;

            text-align: left;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .stock-history-table td {

            padding: 13px 10px;

            border-bottom: 1px solid #edf0f5;

            color: #344054;

            vertical-align: middle;

            white-space: nowrap;
        }


        .stock-history-table tbody tr:hover td {
            background: #fbfcfe;
        }


        .stock-history-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* =====================================================
           COLUMN WIDTHS
        ===================================================== */

        .stock-history-table th:nth-child(1),
        .stock-history-table td:nth-child(1) {
            width: 12%;
        }


        .stock-history-table th:nth-child(2),
        .stock-history-table td:nth-child(2) {
            width: 13%;
        }


        .stock-history-table th:nth-child(3),
        .stock-history-table td:nth-child(3) {
            width: 18%;
        }


        .stock-history-table th:nth-child(4),
        .stock-history-table td:nth-child(4) {
            width: 13%;
        }


        .stock-history-table th:nth-child(5),
        .stock-history-table td:nth-child(5) {
            width: 9%;
        }


        .stock-history-table th:nth-child(6),
        .stock-history-table td:nth-child(6) {
            width: 12%;
        }


        .stock-history-table th:nth-child(7),
        .stock-history-table td:nth-child(7) {
            width: 11%;
        }


        .stock-history-table th:nth-child(8),
        .stock-history-table td:nth-child(8) {
            width: 20%;
        }


        /* =====================================================
           DATE
        ===================================================== */

        .history-date-main {

            display: block;

            color: #26354a;

            font-size: 13px;

            font-weight: 700;
        }


        .history-date-time {

            display: block;

            margin-top: 3px;

            color: #98a2b3;

            font-size: 10px;
        }


        /* =====================================================
           TRANSACTION BADGE
        ===================================================== */

        .history-transaction-badge {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .history-transaction-badge.stock-in {

            background: #e9f8ef;

            color: #15803d;
        }


        .history-transaction-badge.stock-out {

            background: #ffe9e9;

            color: #dc2626;
        }


        /* =====================================================
           ITEM
        ===================================================== */

        .history-item-name {

            display: block;

            color: #26354a;

            font-size: 13px;

            font-weight: 700;

            white-space: normal;

            line-height: 1.35;
        }


        .history-item-code {

            display: block;

            margin-top: 3px;

            color: #98a2b3;

            font-size: 11px;
        }


        /* =====================================================
           BATCH
        ===================================================== */

        .history-batch {

            color: #26354a;

            font-weight: 700;
        }


        /* =====================================================
           QUANTITY
        ===================================================== */

        .history-quantity {

            font-size: 13px;

            font-weight: 700;
        }


        .history-quantity.in {
            color: #16a34a;
        }


        .history-quantity.out {
            color: #ef4444;
        }


        /* =====================================================
           REFERENCE
        ===================================================== */

        .history-reference {

            color: #52647b;

            font-size: 11px;
        }


        /* =====================================================
           REMARKS
        ===================================================== */

        .history-remarks {

            white-space: normal !important;

            line-height: 1.35;

            color: #52647b;
        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .stock-history-empty {

            padding: 55px 20px;

            text-align: center;
        }


        .stock-history-empty i {

            color: #b7c0ce;

            font-size: 40px;

            margin-bottom: 14px;
        }


        .stock-history-empty h4 {

            margin: 0 0 6px;

            color: #344054;

            font-size: 16px;
        }


        .stock-history-empty p {

            margin: 0;

            color: #98a2b3;

            font-size: 13px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1100px) {

            .stock-history-table {
                min-width: 1000px;
            }

        }


    </style>

</head>


<body>

<div class="container">


    <!-- =====================================================
         SAME SIDEBAR AS INVENTORY
    ====================================================== -->

    <?php include 'partials/sidebar.php'; ?>


    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- =====================================================
         SAME MAIN CONTENT STRUCTURE AS INVENTORY
    ====================================================== -->

    <main
        class="content"
        id="mainContent"
    >


        <?php

        $pageTitle = "Stock History";

        $showAdminInfo = false;

        include "partials/topbar.php";

        ?>


        <!-- SAME PAGE CONTENT -->
        <div class="page-content">


            <!-- IMPORTANT:
                 USE inventory-content
                 BECAUSE THIS IS WHAT YOUR INVENTORY PAGE USES
            -->

            <div class="inventory-content">


                <!-- =================================================
                     TOOLBAR
                     SAME toolbar-card AS INVENTORY
                ================================================== -->

                <div class="toolbar-card">


                    <div class="toolbar-left">


                        <form
                            method="GET"
                            style="width: 100%;"
                            autocomplete="off"
                        >

                            <div class="search-box">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="text"
                                    name="search"
                                    value="<?= e($search) ?>"
                                    placeholder="Search Item ID, Item Name or Batch No..."
                                    autocomplete="off"
                                >

                            </div>


                            <?php if ($type !== 'all'): ?>

                                <input
                                    type="hidden"
                                    name="type"
                                    value="<?= e($type) ?>"
                                >

                            <?php endif; ?>


                        </form>


                    </div>


                    <div class="toolbar-right">


                        <!-- ALL -->

                        <a
                            href="stock_history.php<?= $search !== '' ? '?search=' . urlencode($search) : '' ?>"
                            class="history-filter-btn <?= $type === 'all' ? 'active' : '' ?>"
                        >

                            <i class="fa-solid fa-list"></i>

                            All

                        </a>


                        <!-- STOCK IN -->

                        <a
                            href="stock_history.php?type=in<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
                            class="history-filter-btn <?= $type === 'in' ? 'active' : '' ?>"
                        >

                            <i class="fa-solid fa-arrow-down"></i>

                            Stock In

                        </a>


                        <!-- STOCK OUT -->

                        <a
                            href="stock_history.php?type=out<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
                            class="history-filter-btn <?= $type === 'out' ? 'active' : '' ?>"
                        >

                            <i class="fa-solid fa-arrow-up"></i>

                            Stock Out

                        </a>


                        <!-- BACK TO INVENTORY -->

                        <a
                            href="inventory.php"
                            class="history-btn"
                        >

                            <i class="fa-solid fa-box"></i>

                            Inventory

                        </a>


                    </div>


                </div>


                <!-- =================================================
                     STOCK HISTORY CARD
                ================================================== -->

                <div class="stock-history-list-card">


                    <div class="stock-history-section-header">

                        <div>

                            <h3>
                                Stock History
                            </h3>

                            <p>
                                View all recorded stock in and stock out transactions.
                            </p>

                        </div>

                    </div>


                    <!-- =================================================
                         TABLE
                    ================================================== -->

                    <?php if (!empty($historyRows)): ?>


                        <div class="stock-history-table-wrapper">


                            <table class="stock-history-table">


                                <thead>

                                    <tr>

                                        <th>
                                            DATE
                                        </th>

                                        <th>
                                            TRANSACTION
                                        </th>

                                        <th>
                                            ITEM
                                        </th>

                                        <th>
                                            BATCH NO.
                                        </th>

                                        <th>
                                            QUANTITY
                                        </th>

                                        <th>
                                            EXPIRATION
                                        </th>

                                        <th>
                                            REFERENCE NO.
                                        </th>

                                        <th>
                                            REMARKS
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                <?php foreach ($historyRows as $row): ?>


                                    <?php

                                    $transactionType =
                                        $row['transaction_type'];

                                    ?>


                                    <tr>


                                        <!-- DATE -->

                                        <td>

                                            <span class="history-date-main">

                                                <?= formatHistoryDate(
                                                    $row['transaction_date']
                                                ) ?>

                                            </span>


                                            <span class="history-date-time">

                                                <?= formatHistoryDateTime(
                                                    $row['transaction_date']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- TRANSACTION -->

                                        <td>

                                            <span
                                                class="history-transaction-badge <?= transactionClass($transactionType) ?>"
                                            >

                                                <?php if ($transactionType === 'in'): ?>

                                                    <i class="fa-solid fa-arrow-down"></i>

                                                <?php else: ?>

                                                    <i class="fa-solid fa-arrow-up"></i>

                                                <?php endif; ?>


                                                <?= transactionLabel(
                                                    $transactionType
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ITEM -->

                                        <td>

                                            <span class="history-item-name">

                                                <?= e(
                                                    $row['item_name']
                                                ) ?>

                                            </span>


                                            <span class="history-item-code">

                                                <?= e(
                                                    $row['item_code']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- BATCH -->

                                        <td>

                                            <?php if (
                                                !empty($row['batch_number'])
                                            ): ?>

                                                <span class="history-batch">

                                                    <?= e(
                                                        $row['batch_number']
                                                    ) ?>

                                                </span>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>


                                        <!-- QUANTITY -->

                                        <td>

                                            <span
                                                class="history-quantity <?= $transactionType ?>"
                                            >

                                                <?= quantityPrefix(
                                                    $transactionType
                                                ) ?>

                                                <?= number_format(
                                                    (float)$row['quantity'],
                                                    0
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- EXPIRATION -->

                                        <td>

                                            <?= formatHistoryDate(
                                                $row['expiration_date']
                                            ) ?>

                                        </td>


                                        <!-- REFERENCE -->

                                        <td>

                                            <?php if (
                                                !empty($row['reference_number'])
                                            ): ?>

                                                <span class="history-reference">

                                                    <?= e(
                                                        $row['reference_number']
                                                    ) ?>

                                                </span>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>


                                        <!-- REMARKS -->

                                        <td class="history-remarks">

                                            <?php if (
                                                !empty($row['remarks'])
                                            ): ?>

                                                <?= e(
                                                    $row['remarks']
                                                ) ?>


                                            <?php elseif (
                                                !empty($row['reason'])
                                            ): ?>

                                                <?= e(
                                                    $row['reason']
                                                ) ?>


                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                                </tbody>


                            </table>


                        </div>


                    <?php else: ?>


                        <!-- EMPTY STATE -->

                        <div class="stock-history-empty">

                            <i
                                class="fa-solid fa-clock-rotate-left"
                            ></i>


                            <h4>
                                No Stock History Found
                            </h4>


                            <p>
                                There are no stock transactions matching your search.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </div>


    </main>


</div>


<!-- SAME LAYOUT JS AS INVENTORY -->

<script src="../assets/js/layout.js"></script>


</body>

</html>