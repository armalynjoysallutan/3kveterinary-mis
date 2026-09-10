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


// ========================================
// BILLING SUMMARY
// ========================================

$totalBilling = 0;

$countQuery = "
    SELECT COUNT(*) AS total
    FROM billing
";

$countResult = mysqli_query($conn, $countQuery);

if ($countResult) {

    $countRow = mysqli_fetch_assoc($countResult);

    $totalBilling = (int) $countRow["total"];
}


// ========================================
// TOTAL REVENUE
// ========================================

$totalRevenue = 0;

$revenueQuery = "
    SELECT COALESCE(SUM(total_amount), 0) AS total_revenue
    FROM billing
    WHERE payment_status = 'Paid'
";

$revenueResult = mysqli_query($conn, $revenueQuery);

if ($revenueResult) {

    $revenueRow = mysqli_fetch_assoc($revenueResult);

    $totalRevenue =
        (float) $revenueRow["total_revenue"];
}


// ========================================
// BILLING LIST
// ========================================

$billingRows = [];

$sql = "
    SELECT
        b.billing_id,
        b.appointment_id,
        b.total_amount,
        b.payment_status,
        b.billing_status,
        b.created_at,

        c.owner_name,

        p.pet_name

    FROM billing b

    LEFT JOIN customers c
        ON b.customer_id = c.customer_id

    LEFT JOIN pets p
        ON b.pet_id = p.pet_id

    WHERE LOWER(TRIM(b.billing_status)) <> 'archived'

    ORDER BY b.billing_id DESC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $billingRows[] = $row;

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Billing | Veterinary MIS</title>


    <!-- SHARED CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/appointments.css"
    >


    <!-- BILLING CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/billing.css"
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


        <!-- TOPBAR -->

        <?php

        $pageTitle = "Billing";

        $showAdminInfo = false;

        include "partials/topbar.php";

        ?>


        <div class="page-content">


            <div class="billing-content">


                <!-- ========================================
                     TOOLBAR
                ========================================= -->

                <div class="toolbar-card">


                    <div class="toolbar-left">


                        <div class="search-box">

                            <i
                                class="fa-solid fa-magnifying-glass"
                            ></i>


                            <input
                                type="text"
                                id="billingSearch"
                                placeholder="Search billing..."
                                autocomplete="off"
                            >

                        </div>


                    </div>


                    <div class="toolbar-right">


                        <button
                            class="add-btn"
                            type="button"
                            id="addBillingBtn"
                        >

                            <i
                                class="fa-solid fa-plus"
                            ></i>

                            Add Billing

                        </button>


                        <button
                            class="archive-btn"
                            type="button"
                            id="archiveBillingBtn"
                        >

                            <i
                                class="fa-solid fa-box-archive"
                            ></i>

                            Archived

                        </button>


                    </div>


                </div>


                <!-- ========================================
                     SUMMARY CARDS
                ========================================= -->

                <div class="billing-summary">


                    <!-- TOTAL REVENUE -->

                    <div
                        class="billing-stat-card revenue"
                    >


                        <div
                            class="billing-stat-info"
                        >

                            <span>
                                Total Revenue
                            </span>

                            <strong>
                                ₱<?= number_format(
                                    $totalRevenue,
                                    2
                                ) ?>
                            </strong>

                        </div>


                        <div
                            class="billing-stat-icon revenue"
                        >

                            <i
                                class="fa-solid fa-peso-sign"
                            ></i>

                        </div>


                    </div>


                    <!-- TOTAL BILLING -->

                    <div
                        class="billing-stat-card"
                    >


                        <div
                            class="billing-stat-info"
                        >

                            <span>
                                Total Billing
                            </span>

                            <strong>
                                <?= $totalBilling ?>
                            </strong>

                        </div>


                        <div
                            class="billing-stat-icon billing"
                        >

                            <i
                                class="fa-solid fa-file-invoice"
                            ></i>

                        </div>


                    </div>


                </div>


                <!-- ========================================
                     BILLING LIST
                ========================================= -->

                <div
                    class="billing-list-card"
                >


                    <div class="section-header">

                        <h3>
                            Billing List
                        </h3>

                    </div>


                    <div class="table-wrapper">


                        <table
                            class="billing-table"
                        >


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
                                        Status
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody
                                id="billingTableBody"
                            >


                            <?php if (
                                empty($billingRows)
                            ): ?>


                                <tr>

                                    <td
                                        colspan="7"
                                    >


                                        <div
                                            class="billing-empty"
                                        >

                                            <i
                                                class="fa-solid fa-file-invoice-dollar"
                                            ></i>


                                            <h4>
                                                No Billing Records
                                            </h4>


                                            <p>
                                                Completed appointments will appear here after billing is created.
                                            </p>

                                        </div>


                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $billingRows
                                    as $billing
                                ): ?>


                                    <?php

                                    $billingNumber =
                                        "BILL-" .
                                        date(
                                            "Y",
                                            strtotime(
                                                $billing["created_at"]
                                            )
                                        ) .
                                        "-" .
                                        str_pad(
                                            $billing["billing_id"],
                                            3,
                                            "0",
                                            STR_PAD_LEFT
                                        );


                                    $status =
                                        strtolower(
                                            trim(
                                                $billing[
                                                    "payment_status"
                                                ]
                                            )
                                        );


                                    $searchText =
                                        strtolower(
                                            $billingNumber .
                                            " " .
                                            $billing[
                                                "owner_name"
                                            ] .
                                            " " .
                                            $billing[
                                                "pet_name"
                                            ]
                                        );

                                    ?>


                                    <tr
                                        class="billing-row"
                                        data-search="<?= htmlspecialchars(
                                            $searchText
                                        ) ?>"
                                    >


                                        <!-- BILLING NUMBER -->

                                        <td>

                                            <span
                                                class="billing-number"
                                            >

                                                <?= htmlspecialchars(
                                                    $billingNumber
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <div
                                                class="date-main"
                                            >

                                                <?= date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $billing[
                                                            "created_at"
                                                        ]
                                                    )
                                                ) ?>

                                            </div>

                                        </td>


                                        <!-- CLIENT -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $billing[
                                                    "owner_name"
                                                ]
                                            ) ?>

                                        </td>


                                        <!-- PET -->

                                        <td>

                                            <div
                                                class="billing-pet"
                                            >

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $billing[
                                                            "pet_name"
                                                        ]
                                                    ) ?>

                                                </strong>

                                            </div>

                                        </td>


                                        <!-- AMOUNT -->

                                        <td>

                                            <span
                                                class="billing-amount"
                                            >

                                                ₱<?= number_format(
                                                    (float)
                                                    $billing[
                                                        "total_amount"
                                                    ],
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="billing-status <?= htmlspecialchars(
                                                    $status
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $billing[
                                                        "payment_status"
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td>

                                            <button
                                                type="button"
                                                class="link-btn view-billing-btn"
                                                data-id="<?= (int) $billing[
                                                    "billing_id"
                                                ] ?>"
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


                </div>


            </div>


        </div>


    </main>


</div>


<!-- SHARED JAVASCRIPT -->

<script
    src="../assets/js/layout.js"
></script>


<!-- BILLING JAVASCRIPT -->

<script
    src="../assets/js/billing.js"
></script>


</body>

</html>