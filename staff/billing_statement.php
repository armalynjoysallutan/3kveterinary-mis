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

require_once "../config/database.php";


/* =========================================================
   GET BILLING ID
========================================================= */

$billingId = isset($_GET["billing_id"])
    ? (int) $_GET["billing_id"]
    : 0;

if ($billingId <= 0) {
    die("Invalid billing ID.");
}


/* =========================================================
   GET BILLING INFORMATION
========================================================= */

$sql = "
    SELECT
        b.billing_id,
        b.appointment_id,
        b.total_amount,
        b.payment_status,
        b.billing_status,
        b.created_at,

        c.owner_name,
        b.buyer_name,

        p.pet_name,

        a.appointment_date,
        a.appointment_time

    FROM billing b

    LEFT JOIN customers c
        ON b.customer_id = c.customer_id

    LEFT JOIN pets p
        ON b.pet_id = p.pet_id

    LEFT JOIN appointments a
        ON b.appointment_id = a.appointment_id

    WHERE b.billing_id = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Billing query failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $billingId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$billing = mysqli_fetch_assoc($result);

if (!$billing) {
    die("Billing record not found.");
}


/* =========================================================
   GET BILLING ITEMS
========================================================= */

$items = [];

$itemSql = "
    SELECT
        billing_item_id,
        item_type,
        item_name,
        quantity,
        unit,
        unit_price,
        amount

    FROM billing_items

    WHERE billing_id = ?

    ORDER BY billing_item_id ASC
";

$itemStmt = mysqli_prepare(
    $conn,
    $itemSql
);

if ($itemStmt) {

    mysqli_stmt_bind_param(
        $itemStmt,
        "i",
        $billingId
    );

    mysqli_stmt_execute($itemStmt);

    $itemResult =
        mysqli_stmt_get_result($itemStmt);

    while (
        $item = mysqli_fetch_assoc($itemResult)
    ) {

        $items[] = $item;

    }
}

/* =========================================================
   SEPARATE SERVICES AND PRODUCTS
========================================================= */

$serviceItems = [];
$productItems = [];

foreach ($items as $item) {

    if (
        strtolower(
            trim($item["item_type"] ?? "")
        ) === "product"
    ) {

        $productItems[] = $item;

    } else {

        $serviceItems[] = $item;

    }
}


/* =========================================================
   BILLING NUMBER
========================================================= */

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


/* =========================================================
   APPOINTMENT / PURCHASE REFERENCE
========================================================= */

if (!empty($billing["appointment_id"])) {

    $appointmentReference =
        "APT-" .
        str_pad(
            $billing["appointment_id"],
            4,
            "0",
            STR_PAD_LEFT
        );

} else {

    $appointmentReference =
        "WALK-IN SALE";

}


/* =========================================================
   DATE / TIME
========================================================= */

if (
    !empty($billing["appointment_date"])
) {

    $formattedDate =
        date(
            "F d, Y",
            strtotime(
                $billing["appointment_date"]
            )
        );

    $formattedTime =
        !empty($billing["appointment_time"])
            ? date(
                "g:i A",
                strtotime(
                    $billing["appointment_time"]
                )
            )
            : "—";

} else {

    /*
     * Purchase Billing has no appointment.
     * Use billing creation date instead.
     */

    $formattedDate =
        date(
            "F d, Y",
            strtotime(
                $billing["created_at"]
            )
        );

    $formattedTime = "—";

}
/* =========================================================
   GET ACTIVE BILLING SERVICES
   ---------------------------------------------------------
   These records are loaded from System Variables so the
   Add Service modal uses the same active service catalog
   as the Appointment module.
========================================================= */

$billingServices = [];

$billingServicesSql = "
    SELECT
        s.service_id,
        sc.category_id,
        sc.category_name,
        s.service_name,
        s.pricing_type,
        s.fixed_price,

        (
            SELECT pr.pricing_rule_id
            FROM pricing_rules pr
            WHERE pr.service_id = s.service_id
              AND pr.status = 'Active'
            ORDER BY pr.pricing_rule_id ASC
            LIMIT 1
        ) AS pricing_rule_id,

        (
            SELECT pr.base_min_weight
            FROM pricing_rules pr
            WHERE pr.service_id = s.service_id
              AND pr.status = 'Active'
            ORDER BY pr.pricing_rule_id ASC
            LIMIT 1
        ) AS base_min_weight,

        (
            SELECT pr.base_max_weight
            FROM pricing_rules pr
            WHERE pr.service_id = s.service_id
              AND pr.status = 'Active'
            ORDER BY pr.pricing_rule_id ASC
            LIMIT 1
        ) AS base_max_weight,

        (
            SELECT pr.base_price
            FROM pricing_rules pr
            WHERE pr.service_id = s.service_id
              AND pr.status = 'Active'
            ORDER BY pr.pricing_rule_id ASC
            LIMIT 1
        ) AS base_price,

        (
            SELECT pr.weight_increment
            FROM pricing_rules pr
            WHERE pr.service_id = s.service_id
              AND pr.status = 'Active'
            ORDER BY pr.pricing_rule_id ASC
            LIMIT 1
        ) AS weight_increment,

        (
            SELECT pr.price_increment
            FROM pricing_rules pr
            WHERE pr.service_id = s.service_id
              AND pr.status = 'Active'
            ORDER BY pr.pricing_rule_id ASC
            LIMIT 1
        ) AS price_increment

    FROM services s

    INNER JOIN service_categories sc
        ON s.category_id = sc.category_id

    WHERE s.status = 'Active'
      AND sc.status = 'Active'

    ORDER BY
        sc.category_name ASC,
        s.service_name ASC
";

$billingServicesResult =
    mysqli_query(
        $conn,
        $billingServicesSql
    );

if (
    $billingServicesResult &&
    mysqli_num_rows(
        $billingServicesResult
    ) > 0
) {

    while (
        $service =
        mysqli_fetch_assoc(
            $billingServicesResult
        )
    ) {

        $billingServices[] = [
            "service_id" =>
                (int)$service["service_id"],

            "category_id" =>
                (int)$service["category_id"],

            "category_name" =>
                $service["category_name"],

            "service_name" =>
                $service["service_name"],

            "pricing_type" =>
                $service["pricing_type"],

            "fixed_price" =>
                $service["fixed_price"] !== null
                    ? (float)$service["fixed_price"]
                    : null,

            "pricing_rule_id" =>
                $service["pricing_rule_id"] !== null
                    ? (int)$service["pricing_rule_id"]
                    : null,

            "base_min_weight" =>
                $service["base_min_weight"] !== null
                    ? (float)$service["base_min_weight"]
                    : null,

            "base_max_weight" =>
                $service["base_max_weight"] !== null
                    ? (float)$service["base_max_weight"]
                    : null,

            "base_price" =>
                $service["base_price"] !== null
                    ? (float)$service["base_price"]
                    : null,

            "weight_increment" =>
                $service["weight_increment"] !== null
                    ? (float)$service["weight_increment"]
                    : null,

            "price_increment" =>
                $service["price_increment"] !== null
                    ? (float)$service["price_increment"]
                    : null
        ];
    }
}

/* =========================================================
   GET ACTIVE INVENTORY ITEMS FOR BILLING
   Medicine, Supplements, and Pet Food only
========================================================= */

$billingInventoryItems = [];

$billingInventorySql = "
    SELECT
        i.item_id,
        i.item_code,
        i.item_name,
        i.category_id,
        c.category_name,
        i.retail_price,
        u.unit_name,
        u.abbreviation,

        COALESCE(
            (
                SELECT SUM(s.quantity)
                FROM inventory_stock s
                WHERE s.item_id = i.item_id
            ),
            0
        ) AS current_stock

    FROM inventory_items i

    INNER JOIN inventory_categories c
        ON c.category_id = i.category_id

    LEFT JOIN inventory_units u
        ON u.unit_id = i.unit_id    

    WHERE i.status = 'Active'
        AND c.status = 'Active'
        AND c.category_id IN (1, 2, 3)

    ORDER BY
        c.category_name ASC,
        i.item_name ASC
";

$billingInventoryResult = mysqli_query(
    $conn,
    $billingInventorySql
);

if ($billingInventoryResult) {

    while ($row = mysqli_fetch_assoc(
        $billingInventoryResult
    )) {

        $billingInventoryItems[] = [
            "item_id" => (int) $row["item_id"],
            "item_code" => $row["item_code"],
            "item_name" => $row["item_name"],
            "category_id" => (int) $row["category_id"],
            "category_name" => $row["category_name"],
            "retail_price" => (float) $row["retail_price"],
            "unit_name" => $row["unit_name"],
            "abbreviation" => $row["abbreviation"],
            "current_stock" => (float) $row["current_stock"],
                
            
        ];
    }
}


/* =========================================================
   GET ACTIVE MEDICATIONS
========================================================= */

$billingMedications = [];

$billingMedicationsSql = "
    SELECT
        medication_id,
        medication_name,
        unit_price
    FROM medications
    WHERE status = 'Active'
    ORDER BY medication_name ASC
";

$billingMedicationsResult =
    mysqli_query(
        $conn,
        $billingMedicationsSql
    );

if (
    $billingMedicationsResult &&
    mysqli_num_rows(
        $billingMedicationsResult
    ) > 0
) {

    while (
        $medication =
        mysqli_fetch_assoc(
            $billingMedicationsResult
        )
    ) {

        $billingMedications[] = [
            "medication_id" =>
                (int)$medication["medication_id"],

            "medication_name" =>
                $medication["medication_name"],

            "unit_price" =>
                (float)$medication["unit_price"]
        ];
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

    <title>
        Billing Statement | Veterinary MIS
    </title>


    <!-- SHARED CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/appointments.css"
    >


    <!-- BILLING STATEMENT CSS -->

    <link
        rel="stylesheet"
        href="../assets/css/billing_statement.css"
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

        $pageTitle =
            "Billing Statement";

        $showAdminInfo =
            false;

        include "partials/topbar.php";

        ?>


        <div
            class="billing-statement-page"
        >


            <!-- BACK BUTTON -->

            <a
                href="billing.php"
                class="back-to-billing"
            >

                <i
                    class="fa-solid fa-arrow-left"
                ></i>

                Back to Billing

            </a>


            <!-- =================================================
                 STATEMENT CARD
            ================================================== -->

            <div
                class="statement-card"
            >


                <!-- HEADER -->

                <div
                    class="statement-header"
                >


                    <div>

                        <h2>
                            Billing Statement
                        </h2>


                        <span
                            class="statement-status <?= strtolower(
                                $billing["billing_status"]
                            ) ?>"
                        >

                            <?= htmlspecialchars(
                                $billing[
                                    "billing_status"
                                ]
                            ) ?>

                        </span>

                    </div>


                    <div
                        class="receipt-number"
                    >

                        <span>
                            RECEIPT NO.
                        </span>


                        <strong>

                            <?= htmlspecialchars(
                                $billingNumber
                            ) ?>

                        </strong>

                    </div>


                </div>


                <!-- =================================================
                     CLIENT INFORMATION
                ================================================== -->

                <div
                    class="client-info-card"
                >


                    <!-- CLIENT / PET -->

                    <div
                        class="client-info-column"
                    >


                        <!-- CLIENT -->

                        <div
                            class="info-item"
                        >

                            <div
                                class="info-icon"
                            >

                                <i
                                    class="fa-solid fa-user"
                                ></i>

                            </div>


                            <div>

                                <span>
                                    CLIENT
                                </span>


                                <strong>

                                    <?= htmlspecialchars(
                                        !empty($billing["owner_name"])
                                            ? $billing["owner_name"]
                                            : (
                                                !empty($billing["buyer_name"])
                                                    ? $billing["buyer_name"]
                                                    : "Walk-in Customer"
                                            )
                                    ) ?>        

                                        
                                        

                                </strong>

                            </div>

                        </div>


                        <!-- PET -->

                        <div
                            class="info-item"
                        >

                            <div
                                class="info-icon"
                            >

                                <i
                                    class="fa-solid fa-paw"
                                ></i>

                            </div>


                            <div>

                                <span>
                                    PET
                                </span>


                                <strong>

                                    <?= htmlspecialchars(
                                        !empty($billing["pet_name"])
                                            ? $billing["pet_name"]
                                            : "No Pet"
                                        
                                    ) ?>

                                </strong>

                            </div>

                        </div>


                    </div>


                    <!-- DATE / APPOINTMENT -->

                    <div
                        class="client-info-column"
                    >


                        <!-- DATE -->

                        <div
                            class="info-item"
                        >

                            <div
                                class="info-icon"
                            >

                                <i
                                    class="fa-solid fa-calendar"
                                ></i>

                            </div>


                            <div>

                                <span>
                                    DATE
                                </span>


                                <strong>

                                    <?= htmlspecialchars(
                                        $formattedDate
                                    ) ?>

                                </strong>

                            </div>

                        </div>


                        <!-- APPOINTMENT -->

                        <div
                            class="info-item"
                        >

                            <div
                                class="info-icon"
                            >

                                <i
                                    class="fa-solid fa-file-lines"
                                ></i>

                            </div>


                            <div>

                                <span>
                                    APPOINTMENT
                                </span>


                                <strong>

                                    <?= htmlspecialchars(
                                        $appointmentReference
                                    ) ?>

                                    <small>

                                        |
                                        <?= htmlspecialchars(
                                            $formattedTime
                                        ) ?>

                                    </small>

                                </strong>

                            </div>

                        </div>


                    </div>


                </div>


                <!-- =================================================
                     BILLING GRID
                ================================================== -->

                <div
                    class="billing-statement-grid"
                >


                    <!-- LEFT SIDE -->

                    <div
                        class="statement-left"
                    >


                        <!-- SERVICES HEADER -->

<div class="section-title">

    <div>

        <i class="fa-solid fa-paw"></i>

        <span>
            Services
        </span>

    </div>

</div>


<!-- SERVICES TABLE -->

<div class="service-table-wrapper">

    <table class="statement-table">

        <thead>

            <tr>

                <th>
                    DESCRIPTION
                </th>

                <th>
                    QTY
                </th>

                <th>
                    UNIT PRICE
                </th>

                <th>
                    TOTAL
                </th>

            </tr>

        </thead>

        <tbody>

            <?php if (empty($serviceItems)): ?>

                <tr>

                    <td
                        colspan="4"
                        class="no-items"
                    >
                        No services added.
                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($serviceItems as $item): ?>

                    <tr>

                        <td>

                            <span class="item-type">
                                <?= htmlspecialchars(
                                    $item["item_type"]
                                ) ?>
                            </span>

                            <?= htmlspecialchars(
                                $item["item_name"]
                            ) ?>

                        </td>

                        <td>

                            <?= number_format(
                                (float)$item["quantity"],
                                0
                            ) ?>

                        </td>

                        <td>

                            ₱<?= number_format(
                                (float)$item["unit_price"],
                                2
                            ) ?>

                        </td>

                        <td>

                            ₱<?= number_format(
                                (float)$item["amount"],
                                2
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</div>

<!-- PRODUCTS HEADER -->

<div class="section-title product-section-title">

    <div>

        <i class="fa-solid fa-box"></i>

        <span>
            Products
        </span>

    </div>


    <button
        type="button"
        class="add-service-btn"
        id="openAddServiceBtn"
    >

        <i class="fa-solid fa-plus"></i>

        Add Item

    </button>

</div>


<!-- PRODUCTS TABLE -->

<div class="service-table-wrapper">

    <table class="statement-table">

        <thead>

            <tr>

                <th>
                    DESCRIPTION
                </th>

                <th>
                    QTY
                </th>

                <th>
                    UNIT PRICE
                </th>

                <th>
                    TOTAL
                </th>

            </tr>

        </thead>

        <tbody>

            <?php if (empty($productItems)): ?>

                <tr>

                    <td
                        colspan="4"
                        class="no-items"
                    >
                        No products added.
                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($productItems as $item): ?>

                    <tr>

                        <td>

                            <span class="item-type">
                                PRODUCT
                            </span>

                            <?= htmlspecialchars(
                                $item["item_name"]
                            ) ?>

                        </td>


                        <td>

                            <input
                                type="number"
                                class="billing-product-quantity"
                                min="1"
                                step="1"
                                value="<?= (float)$item["quantity"] ?>"
                                data-item-id="<?= (int)$item["billing_item_id"] ?>"
                            >

                        </td>


                        <td>

                            ₱<?= number_format(
                                (float)$item["unit_price"],
                                2
                            ) ?>

                        </td>


                        <td>

                            <strong>
                                ₱<?= number_format(
                                    (float)$item["amount"],
                                    2
                                ) ?>
                            </strong>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</div>


                    <!-- RIGHT SIDE -->

                    <div
                        class="statement-right"
                    >


                        <!-- ORDER SUMMARY -->

                        <div
                            class="summary-card"
                        >


                            <h3>
                                Order Summary
                            </h3>


                            <div
                                class="summary-row total"
                            >

                                <span>
                                    Total Amount
                                </span>


                                <strong
                                    id="grandTotal"
                                >

                                    ₱<?= number_format(
                                        (float)
                                        $billing[
                                            "total_amount"
                                        ],
                                        2
                                    ) ?>

                                </strong>

                            </div>


                        </div>


                        <!-- PAYMENT -->

                        <div
                            class="payment-card"
                        >


                            <h3>
                                Payment
                            </h3>


                            <div
                                class="payment-input-group"
                            >

                                <label
                                    for="amountPaid"
                                >

                                    Amount Paid

                                </label>


                                <div
                                    class="amount-input-wrapper"
                                >

                                    <span>
                                        ₱
                                    </span>


                                    <input
                                        type="number"
                                        id="amountPaid"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                    >

                                </div>

                            </div>


                            <div
                                class="payment-row"
                            >

                                <span>
                                    Change
                                </span>


                                <strong
                                    id="changeAmount"
                                >

                                    ₱0.00

                                </strong>

                            </div>


                            <?php
$isPaid = strtolower(
    trim(
        $billing["payment_status"] ?? ""
    )
) === "paid";
?>

<button
    type="button"
    class="confirm-payment-btn"
    id="confirmPaymentBtn"
    data-id="<?= $billingId ?>"
>
    Confirm Payment
</button>

<?php if ($isPaid): ?>

    <div class="billing-document-actions">

        <button
            type="button"
            class="billing-print-btn"
            id="printReceiptBtn"
        >
            <i class="fa-solid fa-print"></i>
            Print Receipt
        </button>

        <button
            type="button"
            class="billing-download-btn"
            id="downloadBillingBtn"
            data-id="<?= $billingId ?>"
            data-number="<?= htmlspecialchars(
                $billingNumber,
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >
            <i class="fa-solid fa-download"></i>
            Download
        </button>

    </div>

<?php endif; ?>


                        </div>


                    </div>


                </div>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     ADD ITEM MODAL
========================================================= -->

<div
    class="service-modal-overlay"
    id="serviceModal"
>

    <div class="service-modal">

        <!-- MODAL HEADER -->

        <div class="service-modal-header">

            <div>

                <h3>
                    Add Items
                </h3>

                <p>
                    Add multiple medicines, pet food, or supplements to this bill.
                </p>

            </div>

            <button
                type="button"
                id="closeServiceModal"
                class="modal-close-btn"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <!-- FORM -->

        <div class="service-form">

            <!-- ITEM CATEGORY -->

            <div class="form-group">

                <label for="itemCategory">
                    Item Category
                </label>

                <select id="itemCategory">

                    <option value="">
                        Select Category
                    </option>

                    <option value="Medicine">
                        Medicine
                    </option>

                    <option value="Pet Food">
                        Pet Food
                    </option>

                    <option value="Supplements">
                        Supplements
                    </option>

                </select>

            </div>


            <!-- SEARCH ITEM -->

            <div class="form-group">

                <label for="itemSearch">
                    Search Item
                </label>

                <input
                    type="text"
                    id="itemSearch"
                    placeholder="Search item..."
                    autocomplete="off"
                >

            </div>


            <!-- SEARCH RESULTS -->

            <div
                id="itemSearchResults"
                class="item-search-results"
            >

                <div class="item-search-empty">
                    Select an item category first.
                </div>

            </div>


            <!-- SELECTED ITEM -->

            <div
                id="selectedItemInfo"
                class="selected-item-info"
                style="display: none;"
            >

                <div class="selected-item-name">
                    <strong id="selectedItemName"></strong>
                </div>

                <div class="selected-item-stock">
                    Stock:
                    <span id="selectedItemStock">0</span>
                </div>

                <div class="selected-item-stock">
                    Unit:
                    <span id="selectedItemUnit">--</span>
                </div>

            </div>


            <!-- QUANTITY -->

            <div class="form-group">

                <label for="itemQuantity">
                    Quantity
                </label>

                <input
                    type="number"
                    id="itemQuantity"
                    min="1"
                    value="1"
                >

            </div>


            <!-- UNIT PRICE -->

            <div class="form-group">

                <label for="itemPrice">
                    Unit Price
                </label>

                <input
                    type="number"
                    id="itemPrice"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    readonly
                >

            </div>


            <!-- =================================================
                 TEMPORARY ITEMS TO ADD
            ================================================== -->

            <div
                id="pendingItemsSection"
                class="pending-items-section"
                style="display: none;"
            >

                <div class="pending-items-header">

                    <strong>
                        Items to Add
                    </strong>

                </div>


                <div
                    id="pendingItemsList"
                    class="pending-items-list"
                >
                </div>


                <div class="pending-items-total">

                    <span>
                        Total to Add
                    </span>

                    <strong id="pendingItemsTotal">
                        ₱0.00
                    </strong>

                </div>

            </div>

        </div>


        <!-- MODAL ACTIONS -->

        <div class="service-modal-actions">

            <button
                type="button"
                class="modal-cancel-btn"
                id="cancelServiceBtn"
            >
                Cancel
            </button>

            <button
                type="button"
                class="modal-add-btn"
                id="addServiceBtn"
            >
                Add Item
            </button>

            <button
                type="button"
                class="modal-add-btn"
                id="savePendingItemsBtn"
                style="display: none;"
            >
                Add Items
            </button>

        </div>

    </div>

</div>


<!-- SHARED JS -->

<script
    src="../assets/js/layout.js"
></script>


<!-- BILLING STATEMENT DATA -->

<script>

    window.currentBillingId =
        <?= (int)$billingId ?>; 
        


    window.billingServices =
        <?= json_encode(
            $billingServices,
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        ) ?>;

    window.billingMedications =
        <?= json_encode(
            $billingMedications,
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        ) ?>;

    window.billingInventoryItems =
        <?= json_encode(
            $billingInventoryItems,
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        ) ?>;

</script>


<!-- BILLING STATEMENT JS -->

<script
    src="../assets/js/billing_statement.js"
></script>


</body>

</html>