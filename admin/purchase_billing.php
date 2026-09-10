<?php
session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$customers = [];
$pets = [];
$inventoryItems = [];

/* ========================================
   LOAD CUSTOMERS
======================================== */

$customerQuery = "
    SELECT
        customer_id,
        owner_name
    FROM customers
    ORDER BY owner_name ASC
";

$customerResult = mysqli_query($conn, $customerQuery);

if ($customerResult) {
    while ($row = mysqli_fetch_assoc($customerResult)) {
        $customers[] = $row;
    }
}

/* ========================================
   LOAD PETS
======================================== */

$petQuery = "
    SELECT
        pet_id,
        customer_id,
        pet_name
    FROM pets
    ORDER BY pet_name ASC
";

$petResult = mysqli_query($conn, $petQuery);

if ($petResult) {
    while ($row = mysqli_fetch_assoc($petResult)) {
        $pets[] = $row;
    }
}

/* ========================================
   LOAD INVENTORY ITEMS
   Medicine / Supplements / Pet Food ONLY
======================================== */

$inventoryQuery = "
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
        ON i.category_id = c.category_id

    LEFT JOIN inventory_units u
    ON u.unit_id = i.unit_id    

    WHERE i.status = 'Active'
      AND i.category_id IN (1, 2, 3)

    ORDER BY i.item_name ASC
";

$inventoryResult = mysqli_query($conn, $inventoryQuery);

if ($inventoryResult) {
    while ($row = mysqli_fetch_assoc($inventoryResult)) {
        $inventoryItems[] = $row;
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

    <title>Purchase Billing</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

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
        href="../assets/css/billing_statement.css"
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
            "Purchase Billing";

        $showAdminInfo =
            false;

        include "partials/topbar.php";

        ?>


        <div class="billing-statement-page">

            <!-- ========================================
                 BACK BUTTON
            ======================================== -->

    <a
        href="billing.php"
        class="back-to-billing"
    >
        <i class="fa-solid fa-arrow-left"></i>
        Back to Billing
    </a>


    <!-- ========================================
         MAIN STATEMENT CARD
    ======================================== -->

    <div class="statement-card">

        <!-- ====================================
             HEADER
        ==================================== -->

        <div class="statement-header">

            <div>

                <h1>
                    Purchase Billing
                </h1>

                <p>
                    Walk-in / Purchase Sale
                </p>

            </div>


            <div class="statement-status">

                <span class="status-badge">
                    Walk-in Sale
                </span>

                <div class="receipt-number">
                    NEW TRANSACTION
                </div>

            </div>

        </div>


        <!-- ====================================
             BUYER / CUSTOMER INFORMATION
        ==================================== -->

        <div class="client-info-card">

            <!-- LEFT COLUMN -->

            <div class="client-info-column">

                <div class="form-group">

                    <label for="purchaseCustomer">
                        Customer Record
                    </label>

                    <select
                        id="purchaseCustomer"
                        name="customer_id"
                    >

                        <option value="">
                            Walk-in / No Customer Record
                        </option>

                        <?php foreach ($customers as $customer): ?>

                            <option
                                value="<?= (int)$customer['customer_id'] ?>"
                            >
                                <?= htmlspecialchars($customer['owner_name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="purchaseBuyerName">
                        Buyer Name
                    </label>

                    <input
                        type="text"
                        id="purchaseBuyerName"
                        name="buyer_name"
                        maxlength="150"
                        placeholder="Optional / Enter buyer name"
                    >

                </div>

            </div>


            <!-- RIGHT COLUMN -->

            <div class="client-info-column">

                <div class="form-group">

                    <label for="purchasePet">
                        Pet
                    </label>

                    <select
                        id="purchasePet"
                        name="pet_id"
                        disabled
                    >

                        <option value="">
                            No Pet / Purchase Only
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Transaction Type
                    </label>

                    <input
                        type="text"
                        value="Walk-in Sale"
                        readonly
                    >

                </div>

            </div>

        </div>


        <!-- ====================================
             PURCHASE ITEMS
        ==================================== -->

        <div class="billing-statement-grid">

            <div class="statement-section">

                <div class="section-title">

                    <div>
                        Purchase Items
                    </div>

                    <button
                        type="button"
                        class="add-service-btn"
                        id="openPurchaseItemModal"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Items
                    </button>

                </div>


                <!-- ITEMS TABLE -->

                <div class="service-table-wrapper">

                    <table class="statement-table">

                        <thead>

                            <tr>

                                <th>
                                    ITEM
                                </th>

                                <th>
                                    TYPE
                                </th>

                                <th>
                                    QTY
                                </th>

                                <th>
                                    UNIT PRICE
                                </th>

                                <th>
                                    AMOUNT
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <tbody id="purchaseItemsBody">

                            <tr id="noPurchaseItemsRow">

                                <td
                                    colspan="6"
                                    class="no-items"
                                >
                                    No purchase items added yet.
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- ====================================
             SUMMARY
        ==================================== -->

        <div class="billing-statement-grid">

            <div></div>

            <div class="summary-card">

                <div class="summary-row">

                    <span>
                        Items
                    </span>

                    <strong id="purchaseItemCount">
                        0
                    </strong>

                </div>


                <div class="summary-row total-row">

                    <span>
                        Total Amount
                    </span>

                    <strong id="purchaseTotal">
                        ₱0.00
                    </strong>

                </div>

            </div>

        </div>


        <!-- ====================================
             CREATE BILLING
        ==================================== -->

        <div class="payment-card">

            <div class="payment-card-header">

                <div>

                    <h3>
                        Create Billing
                    </h3>

                    <p>
                        Review the purchase items before creating the billing record.
                    </p>

                </div>

            </div>


            <button
                type="button"
                class="confirm-payment-btn"
                id="createPurchaseBillingBtn"
            >
                <i class="fa-solid fa-file-invoice"></i>
                Create Billing
            </button>

        </div>

    </div>

</div>


<!-- ============================================
     ADD ITEMS MODAL
============================================= -->

<div
    class="service-modal-overlay"
    id="purchaseItemModal"
    style="display: none;"
>

    <div class="service-modal">

        <!-- MODAL HEADER -->

        <div class="service-modal-header">

            <div>

                <h3>
                    Add Items
                </h3>

                <p>
                    Add multiple medicines, pet food, or supplements to this purchase.
                </p>

            </div>

            <button
                type="button"
                class="modal-close-btn"
                id="closePurchaseItemModal"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <!-- MODAL BODY -->

        <div class="service-form">

            <!-- ITEM CATEGORY -->

            <div class="form-group">

                <label for="purchaseItemCategory">
                    Item Category
                </label>

                <select id="purchaseItemCategory">

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

                <label for="purchaseItemSearch">
                    Search Item
                </label>

                <input
                    type="text"
                    id="purchaseItemSearch"
                    placeholder="Search item..."
                    autocomplete="off"
                >

            </div>


            <!-- SEARCH RESULTS -->

            <div
                id="purchaseItemSearchResults"
                class="item-search-results"
            >

                <div class="item-search-empty">
                    Select an item category first.
                </div>

            </div>


            <!-- SELECTED ITEM -->

            <div
                id="purchaseSelectedItemInfo"
                class="selected-item-info"
                style="display: none;"
            >

                <div class="selected-item-name">
                    <strong id="purchaseSelectedItemName"></strong>
                </div>

                <div class="selected-item-stock">
                    Stock:
                    <span id="purchaseSelectedItemStock">0</span>
                </div>

                <div class="selected-item-stock">
                    Unit:
                    <span id="purchaseSelectedItemUnit">--</span>
                </div>

            </div>


            <!-- QUANTITY -->

            <div class="form-group">

                <label for="purchaseItemQuantity">
                    Quantity
                </label>

                <input
                    type="number"
                    id="purchaseItemQuantity"
                    min="1"
                    value="1"
                    disabled
                >

            </div>


            <!-- UNIT PRICE -->

            <div class="form-group">

                <label for="purchaseItemPrice">
                    Unit Price
                </label>

                <input
                    type="number"
                    id="purchaseItemPrice"
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
                id="purchasePendingItemsSection"
                class="pending-items-section"
                style="display: none;"
            >

                <div class="pending-items-header">

                    <strong>
                        Items to Add
                    </strong>

                </div>


                <div
                    id="purchasePendingItemsList"
                    class="pending-items-list"
                >
                </div>


                <div class="pending-items-total">

                    <span>
                        Total to Add
                    </span>

                    <strong id="purchasePendingItemsTotal">
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
                id="cancelPurchaseItemBtn"
            >
                Cancel
            </button>

            <button
                type="button"
                class="modal-add-btn"
                id="addPurchaseItemBtn"
            >
                Add Item
            </button>

            <button
                type="button"
                class="modal-add-btn"
                id="savePurchaseItemsBtn"
                style="display: none;"
            >
                Add Items
            </button>

        </div>

    </div>

</div>

<!-- ============================================
     DATA BRIDGE
============================================= -->

<script>

    window.purchaseCustomers =
        <?= json_encode($customers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    window.purchasePets =
        <?= json_encode($pets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    window.purchaseInventoryItems =
        <?= json_encode($inventoryItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

</script>


<!-- Existing layout JS if your project uses it -->
<script src="../assets/js/layout.js"></script>
<script src="../assets/js/purchase_billing.js"></script>

</body>

</html>