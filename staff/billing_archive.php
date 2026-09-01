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
   BILLING ARCHIVE
   Paid billing records are automatically considered archived.
   ========================================================= */

$archivedBillings = [];

$sql = "
    SELECT
        b.billing_id,
        b.appointment_id,
        b.customer_id,
        b.pet_id,
        b.total_amount,
        b.payment_status,
        b.billing_status,
        b.created_at,
        c.owner_name,
        p.pet_name,
        p.species,
        p.breed
    FROM billing b
    LEFT JOIN customers c
        ON b.customer_id = c.customer_id
    LEFT JOIN pets p
        ON b.pet_id = p.pet_id
    WHERE LOWER(TRIM(b.payment_status)) = 'paid'
    ORDER BY b.created_at DESC, b.billing_id DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Unable to load archived billing records: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($result)) {
    $archivedBillings[] = $row;
}

$archivedCount = count($archivedBillings);

/* =========================================================
   SUMMARY METRICS
   ========================================================= */

$uniqueCustomers = [];
$uniquePets = [];
$totalArchivedRevenue = 0;
$billingStatuses = [];

foreach ($archivedBillings as $row) {

    if (!empty($row['customer_id'])) {
        $uniqueCustomers[$row['customer_id']] = true;
    }

    if (!empty($row['pet_id'])) {
        $uniquePets[$row['pet_id']] = true;
    }

    $totalArchivedRevenue += (float)($row['total_amount'] ?? 0);

    $billingStatus = trim((string)($row['billing_status'] ?? ''));

    if ($billingStatus !== '') {
        $billingStatuses[strtolower($billingStatus)] = true;
    }
}

$uniqueCustomerCount = count($uniqueCustomers);
$uniquePetCount = count($uniquePets);
$billingStatusCount = count($billingStatuses);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Billing Archive | Veterinary MIS</title>

    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/billing.css">
    <link rel="stylesheet" href="../assets/css/billing_archive.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

</head>

<body>

<div class="layout">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="content" id="mainContent">

        <?php

        $pageTitle = 'Billing Archive';
        $showAdminInfo = true;

        include __DIR__ . '/partials/topbar.php';

        ?>

        <div class="page-content billing-archive-page">

            <div class="billing-archive-content">

                <!-- =====================================================
                     PAGE HEADER
                     ===================================================== -->

                <div class="archive-report-header">

                    <div class="archive-report-header-left">

                        <div class="archive-report-breadcrumb">

                            <a href="archived.php">
                                Archive
                            </a>

                            <i class="fa-solid fa-chevron-right"></i>

                            <span>
                                Billing Archive
                            </span>

                        </div>

                        <h1>
                            Billing Archive
                        </h1>

                        <p>
                            View paid billing records and archived transactions.
                        </p>

                    </div>

                    <div class="archive-report-actions">

                        <a
                            href="billing.php"
                            class="archive-report-back-btn"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            Back to Billing
                        </a>

                    </div>

                </div>


                <!-- =====================================================
                     SUMMARY METRICS
                     ===================================================== -->

                <section class="archive-metrics">

                    <article class="archive-metric-card">

                        <div class="metric-icon red">

                            <i class="fa-solid fa-file-invoice-dollar"></i>

                        </div>

                        <div>

                            <span>
                                ARCHIVED BILLING
                            </span>

                            <strong>
                                <?= number_format($archivedCount) ?>
                            </strong>

                            <small>
                                Total paid billing records
                            </small>

                        </div>

                    </article>


                    <article class="archive-metric-card">

                        <div class="metric-icon green">

                            <i class="fa-solid fa-peso-sign"></i>

                        </div>

                        <div>

                            <span>
                                ARCHIVED REVENUE
                            </span>

                            <strong>
                                ₱<?= number_format($totalArchivedRevenue, 2) ?>
                            </strong>

                            <small>
                                Total paid transactions
                            </small>

                        </div>

                    </article>


                    <article class="archive-metric-card">

                        <div class="metric-icon blue-soft">

                            <i class="fa-solid fa-users"></i>

                        </div>

                        <div>

                            <span>
                                UNIQUE CUSTOMERS
                            </span>

                            <strong>
                                <?= number_format($uniqueCustomerCount) ?>
                            </strong>

                            <small>
                                Customers with archived billing
                            </small>

                        </div>

                    </article>


                    <article class="archive-metric-card">

                        <div class="metric-icon orange">

                            <i class="fa-solid fa-paw"></i>

                        </div>

                        <div>

                            <span>
                                UNIQUE PETS
                            </span>

                            <strong>
                                <?= number_format($uniquePetCount) ?>
                            </strong>

                            <small>
                                Pets with archived billing
                            </small>

                        </div>

                    </article>

                </section>


                <!-- =====================================================
                     FILTERS
                     ===================================================== -->

                <section class="report-card filters-card">

                    <div class="report-card-header compact-header">

                        <div>

                            <h2>
                                Filters
                            </h2>

                            <p>
                                Refine the archived billing list using the available filters.
                            </p>

                        </div>

                    </div>


                    <div class="archive-filters">


                        <div class="filter-field search-filter-field">

                            <label for="billingArchiveSearch">
                                Search
                            </label>

                            <div class="archive-search-box">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                <input
                                    type="text"
                                    id="billingArchiveSearch"
                                    placeholder="Search billing, owner, pet..."
                                    autocomplete="off"
                                >

                            </div>

                        </div>


                        <div class="filter-field">

                            <label for="billingArchiveDateFilter">
                                Billing Date
                            </label>

                            <select id="billingArchiveDateFilter">

                                <option value="all">
                                    All Dates
                                </option>

                                <option value="today">
                                    Today
                                </option>

                                <option value="month">
                                    This Month
                                </option>

                                <option value="year">
                                    This Year
                                </option>

                            </select>

                        </div>


                        <div class="filter-field">

                            <label for="billingArchiveStatusFilter">
                                Billing Status
                            </label>

                            <select id="billingArchiveStatusFilter">

                                <option value="all">
                                    All Statuses
                                </option>

                                <?php foreach ($billingStatuses as $statusKey => $unused): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $statusKey,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        <?= htmlspecialchars(
                                            ucwords($statusKey),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="filter-field">

                            <label for="billingArchiveAmountFilter">
                                Amount
                            </label>

                            <select id="billingArchiveAmountFilter">

                                <option value="all">
                                    All Amounts
                                </option>

                                <option value="low">
                                    Below ₱1,000
                                </option>

                                <option value="medium">
                                    ₱1,000 – ₱5,000
                                </option>

                                <option value="high">
                                    Above ₱5,000
                                </option>

                            </select>

                        </div>


                        <div class="filter-actions">

                            <button
                                type="button"
                                class="reset-filter-btn"
                                id="resetBillingArchiveFilters"
                            >
                                Reset
                            </button>

                            <button
                                type="button"
                                class="apply-filter-btn"
                                id="applyBillingArchiveFilters"
                            >
                                Apply
                            </button>

                        </div>

                    </div>

                </section>


                <!-- =====================================================
                     ARCHIVED BILLING TABLE
                     ===================================================== -->

                <section class="report-card archive-list-card">


                    <div class="report-card-header archive-list-header">

                        <div>

                            <h2>
                                Archived Billing
                            </h2>

                            <p>
                                Completed and paid billing transactions.
                            </p>

                        </div>


                        <span class="report-badge">

                            <i class="fa-solid fa-box-archive"></i>

                            <?= number_format($archivedCount) ?> Records

                        </span>

                    </div>


                    <div class="archive-table-area">

                        <table class="archive-billing-table">

                            <thead>

                                <tr>

                                    <th>
                                        Billing
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Owner
                                    </th>

                                    <th>
                                        Pet
                                    </th>

                                    <th>
                                        Amount
                                    </th>

                                    <th>
                                        Payment
                                    </th>

                                    <th>
                                        Billing Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if ($archivedCount === 0): ?>

                                <tr>

                                    <td
                                        colspan="8"
                                        class="archive-empty-state"
                                    >

                                        <i class="fa-solid fa-box-open"></i>

                                        <strong>
                                            No Archived Billing Records
                                        </strong>

                                        <span>
                                            Paid billing records will appear here automatically.
                                        </span>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($archivedBillings as $row): ?>


                                    <?php

                                    $billingNumber =
                                        'BILL-' .
                                        str_pad(
                                            (string)$row['billing_id'],
                                            3,
                                            '0',
                                            STR_PAD_LEFT
                                        );


                                    $createdTimestamp =
                                        strtotime($row['created_at']);


                                    $formattedDate =
                                        $createdTimestamp !== false
                                            ? date(
                                                'M d, Y',
                                                $createdTimestamp
                                            )
                                            : '—';


                                    $formattedTime =
                                        $createdTimestamp !== false
                                            ? date(
                                                'h:i A',
                                                $createdTimestamp
                                            )
                                            : '—';


                                    $billingStatus =
                                        trim(
                                            (string)(
                                                $row['billing_status'] ?? ''
                                            )
                                        );


                                    $billingStatusKey =
                                        strtolower(
                                            $billingStatus
                                        );


                                    $amount =
                                        (float)(
                                            $row['total_amount'] ?? 0
                                        );


                                    $searchText =
                                        strtolower(
                                            $billingNumber . ' ' .
                                            ($row['owner_name'] ?? '') . ' ' .
                                            ($row['pet_name'] ?? '') . ' ' .
                                            ($row['species'] ?? '') . ' ' .
                                            ($row['breed'] ?? '') . ' ' .
                                            $formattedDate . ' ' .
                                            $billingStatus
                                        );

                                    ?>


                                    <tr
                                        class="archive-billing-row"
                                        data-date="<?= htmlspecialchars(
                                            $row['created_at'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        data-status="<?= htmlspecialchars(
                                            $billingStatusKey,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        data-amount="<?= htmlspecialchars(
                                            (string)$amount,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        data-search="<?= htmlspecialchars(
                                            $searchText,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >


                                        <td>

                                            <strong class="billing-reference">

                                                <?= htmlspecialchars(
                                                    $billingNumber
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <div class="date-main">

                                                <?= htmlspecialchars(
                                                    $formattedDate
                                                ) ?>

                                            </div>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $formattedTime
                                                ) ?>

                                            </small>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $row['owner_name'] ?? '—'
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $row['pet_name'] ?? '—'
                                                ) ?>

                                            </strong>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $row['species'] ?? '—'
                                                ) ?>

                                            </small>

                                        </td>


                                        <td>

                                            <strong class="billing-amount">

                                                ₱<?= number_format(
                                                    $amount,
                                                    2
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <span class="billing-status paid">

                                                Paid

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="billing-status <?= htmlspecialchars(
                                                    $billingStatusKey,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $billingStatus !== ''
                                                        ? $billingStatus
                                                        : '—'
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <button
                                                type="button"
                                                class="archive-view-btn view-billing-btn"
                                                data-id="<?= (int)$row['billing_id'] ?>"
                                            >

                                                View

                                            </button>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                            </tbody>

                        </table>

                    </div>

                </section>


                <!-- =====================================================
                     INSIGHTS
                     ===================================================== -->

                <section class="insight-grid">


                    <article class="insight-card red-insight">

                        <div class="insight-icon">

                            <i class="fa-solid fa-circle-check"></i>

                        </div>

                        <div>

                            <h3>
                                Automatic Billing Archive
                            </h3>

                            <p>
                                A billing record is automatically considered archived once its payment status becomes Paid.
                            </p>

                        </div>

                    </article>


                    <article class="insight-card blue-insight">

                        <div class="insight-icon">

                            <i class="fa-solid fa-clock-rotate-left"></i>

                        </div>

                        <div>

                            <h3>
                                Historical Transactions
                            </h3>

                            <p>
                                Archived billing remains available for reviewing completed payment transactions and revenue history.
                            </p>

                        </div>

                    </article>


                </section>


                <div class="report-footer">

                    Billing Archive • Veterinary Management Information System

                </div>


            </div>

        </div>

    </main>

</div>


<script src="../assets/js/layout.js"></script>
<script src="../assets/js/billing.js"></script>


<script>

(function () {

    const search =
        document.getElementById(
            'billingArchiveSearch'
        );


    const dateFilter =
        document.getElementById(
            'billingArchiveDateFilter'
        );


    const statusFilter =
        document.getElementById(
            'billingArchiveStatusFilter'
        );


    const amountFilter =
        document.getElementById(
            'billingArchiveAmountFilter'
        );


    const applyBtn =
        document.getElementById(
            'applyBillingArchiveFilters'
        );


    const resetBtn =
        document.getElementById(
            'resetBillingArchiveFilters'
        );


    const rows =
        Array.from(
            document.querySelectorAll(
                '.archive-billing-row'
            )
        );


    function matchesDate(value, mode) {

        if (mode === 'all' || !value) {
            return true;
        }


        const date =
            new Date(
                value.replace(' ', 'T')
            );


        if (Number.isNaN(date.getTime())) {
            return true;
        }


        const now = new Date();


        if (mode === 'today') {

            return (
                date.toDateString() ===
                now.toDateString()
            );

        }


        if (mode === 'month') {

            return (
                date.getFullYear() ===
                    now.getFullYear()
                &&
                date.getMonth() ===
                    now.getMonth()
            );

        }


        if (mode === 'year') {

            return (
                date.getFullYear() ===
                now.getFullYear()
            );

        }


        return true;

    }


    function matchesAmount(value, mode) {

        if (mode === 'all') {
            return true;
        }


        const amount =
            Number(value || 0);


        if (mode === 'low') {
            return amount < 1000;
        }


        if (mode === 'medium') {

            return (
                amount >= 1000 &&
                amount <= 5000
            );

        }


        if (mode === 'high') {
            return amount > 5000;
        }


        return true;

    }


    function applyFilters() {

        const query =
            (
                search?.value || ''
            )
            .trim()
            .toLowerCase();


        const dateMode =
            dateFilter?.value || 'all';


        const statusMode =
            statusFilter?.value || 'all';


        const amountMode =
            amountFilter?.value || 'all';


        rows.forEach(row => {

            const rowSearch =
                row.dataset.search || '';


            const rowStatus =
                row.dataset.status || '';


            const matchesSearch =
                !query ||
                rowSearch.includes(query);


            const matchesStatus =
                statusMode === 'all' ||
                rowStatus === statusMode;


            const matchesDateValue =
                matchesDate(
                    row.dataset.date || '',
                    dateMode
                );


            const matchesAmountValue =
                matchesAmount(
                    row.dataset.amount || '0',
                    amountMode
                );


            row.style.display =
                (
                    matchesSearch &&
                    matchesStatus &&
                    matchesDateValue &&
                    matchesAmountValue
                )
                    ? ''
                    : 'none';

        });

    }


    applyBtn?.addEventListener(
        'click',
        applyFilters
    );


    search?.addEventListener(
        'input',
        applyFilters
    );


    dateFilter?.addEventListener(
        'change',
        applyFilters
    );


    statusFilter?.addEventListener(
        'change',
        applyFilters
    );


    amountFilter?.addEventListener(
        'change',
        applyFilters
    );


    resetBtn?.addEventListener(
        'click',
        function () {

            if (search) {
                search.value = '';
            }


            if (dateFilter) {
                dateFilter.value = 'all';
            }


            if (statusFilter) {
                statusFilter.value = 'all';
            }


            if (amountFilter) {
                amountFilter.value = 'all';
            }


            applyFilters();

        }
    );


})();

</script>


</body>

</html>