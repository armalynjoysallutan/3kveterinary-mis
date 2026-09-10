<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/* =========================================================
   ADMIN / STAFF AUTHENTICATION
========================================================= */

if (
    !(
        isset($_SESSION["admin_id"]) ||
        isset($_SESSION["admin_username"]) ||
        (
            isset($_SESSION["account_id"]) &&
            isset($_SESSION["role"]) &&
            $_SESSION["role"] === "Staff"
        )
    )
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit;
}


/* =========================================================
   REQUEST METHOD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}


/* =========================================================
   GET POST DATA
========================================================= */

$customerId = isset($_POST["customer_id"]) && $_POST["customer_id"] !== ""
    ? (int) $_POST["customer_id"]
    : null;

$buyerName = isset($_POST["buyer_name"])
    ? trim($_POST["buyer_name"])
    : "";

$petId = isset($_POST["pet_id"]) && $_POST["pet_id"] !== ""
    ? (int) $_POST["pet_id"]
    : null;

$itemsJson = $_POST["items"] ?? "";


/* =========================================================
   VALIDATE BUYER NAME
========================================================= */

if ($buyerName !== "") {

    if (mb_strlen($buyerName) > 150) {

        echo json_encode([
            "success" => false,
            "message" => "Buyer name is too long."
        ]);

        exit;
    }
}


/* =========================================================
   DECODE ITEMS
========================================================= */

$items = json_decode($itemsJson, true);

if (!is_array($items) || count($items) === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Please add at least one purchase item."
    ]);

    exit;
}


/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction($conn);

try {


    /* =====================================================
       VALIDATE CUSTOMER
    ===================================================== */

    if ($customerId !== null) {

        $customerSql = "
            SELECT customer_id
            FROM customers
            WHERE customer_id = ?
            LIMIT 1
        ";

        $customerStmt = mysqli_prepare(
            $conn,
            $customerSql
        );

        mysqli_stmt_bind_param(
            $customerStmt,
            "i",
            $customerId
        );

        mysqli_stmt_execute(
            $customerStmt
        );

        $customerResult =
            mysqli_stmt_get_result(
                $customerStmt
            );

        $customer = mysqli_fetch_assoc(
            $customerResult
        );

        mysqli_stmt_close(
            $customerStmt
        );

        if (!$customer) {

            throw new Exception(
                "Selected customer record was not found."
            );
        }
    }


    /* =====================================================
       VALIDATE PET
    ===================================================== */

    if ($petId !== null) {

        $petSql = "
            SELECT
                pet_id,
                customer_id
            FROM pets
            WHERE pet_id = ?
            LIMIT 1
        ";

        $petStmt = mysqli_prepare(
            $conn,
            $petSql
        );

        mysqli_stmt_bind_param(
            $petStmt,
            "i",
            $petId
        );

        mysqli_stmt_execute(
            $petStmt
        );

        $petResult =
            mysqli_stmt_get_result(
                $petStmt
            );

        $pet = mysqli_fetch_assoc(
            $petResult
        );

        mysqli_stmt_close(
            $petStmt
        );

        if (!$pet) {

            throw new Exception(
                "Selected pet record was not found."
            );
        }


        /*
         * If a customer was selected,
         * make sure the pet belongs to that customer.
         */

        if (
            $customerId !== null &&
            (int)$pet["customer_id"] !== $customerId
        ) {

            throw new Exception(
                "The selected pet does not belong to the selected customer."
            );
        }
    }


    /* =====================================================
       PREPARE BILLING ITEM QUERY
       
       NOTE:
       Inventory is NOT deducted here yet.
    ===================================================== */

    $itemSql = "
        SELECT
            i.item_id,
            i.item_code,
            i.item_name,
            i.category_id,
            c.category_name,
            i.retail_price
        FROM inventory_items i

        INNER JOIN inventory_categories c
            ON c.category_id = i.category_id

        WHERE i.item_id = ?
          AND i.status = 'Active'
          AND i.category_id IN (1, 2, 3)

        LIMIT 1
    ";

    $itemStmt = mysqli_prepare(
        $conn,
        $itemSql
    );


    /* =====================================================
       PREPARE BILLING ITEM INSERT
    ===================================================== */

    $insertItemSql = "
    INSERT INTO billing_items (
        billing_id,
        inventory_item_id,
        item_type,
        item_name,
        quantity,
        unit,
        unit_price,
        amount
    )
    VALUES (
        ?,
        ?,
        'Product',
        ?,
        ?,
        NULL,
        ?,
        ?
    )
";

    $insertItemStmt = mysqli_prepare(
        $conn,
        $insertItemSql
    );


    $validatedItems = [];

    $billingTotal = 0.00;


    /* =====================================================
       VALIDATE ALL PURCHASE ITEMS FIRST
    ===================================================== */

    foreach ($items as $item) {

        $itemId = isset($item["item_id"])
            ? (int)$item["item_id"]
            : 0;

        $quantity = isset($item["quantity"])
            ? (float)$item["quantity"]
            : 0;


        if ($itemId <= 0) {

            throw new Exception(
                "Invalid purchase item."
            );
        }


        if ($quantity <= 0) {

            throw new Exception(
                "Purchase quantity must be greater than zero."
            );
        }


        /*
         * Since this is a product sale,
         * quantity must be a whole number.
         */

        if (floor($quantity) != $quantity) {

            throw new Exception(
                "Purchase quantity must be a whole number."
            );
        }


        /* ---------------------------------------------
           GET REAL ITEM DATA FROM DATABASE
        --------------------------------------------- */

        mysqli_stmt_bind_param(
            $itemStmt,
            "i",
            $itemId
        );

        mysqli_stmt_execute(
            $itemStmt
        );

        $itemResult =
            mysqli_stmt_get_result(
                $itemStmt
            );

        $dbItem =
            mysqli_fetch_assoc(
                $itemResult
            );


        if (!$dbItem) {

            throw new Exception(
                "One of the selected purchase items is no longer available."
            );
        }


        /*
         * IMPORTANT:
         * Use the database retail price.
         *
         * Do not trust the price coming from JavaScript.
         */

        $unitPrice =
            round(
                (float)$dbItem["retail_price"],
                2
            );


        $amount =
            round(
                $quantity * $unitPrice,
                2
            );


        $billingTotal += $amount;


        $validatedItems[] = [
            "item_id" =>
                $itemId,   

            "item_name" =>
                $dbItem["item_name"],

            "quantity" =>
                $quantity,

            "unit_price" =>
                $unitPrice,

            "amount" =>
                $amount
        ];
    }


    mysqli_stmt_close(
        $itemStmt
    );


    $billingTotal =
        round(
            $billingTotal,
            2
        );


    /* =====================================================
       CREATE BILLING RECORD
       
       Purchase Billing:
       appointment_id = NULL
    ===================================================== */

    $insertBillingSql = "
        INSERT INTO billing (
            appointment_id,
            customer_id,
            buyer_name,
            pet_id,
            pet_weight,
            total_amount,
            payment_status,
            billing_status
        )
        VALUES (
            NULL,
            ?,
            ?,
            ?,
            NULL,
            ?,
            'Pending',
            'Unpaid'
        )
    ";

    $billingStmt = mysqli_prepare(
        $conn,
        $insertBillingSql
    );


    mysqli_stmt_bind_param(
        $billingStmt,
        "isid",
        $customerId,
        $buyerName,
        $petId,
        $billingTotal
    );


    if (!mysqli_stmt_execute($billingStmt)) {

        throw new Exception(
            "Failed to create purchase billing."
        );
    }


    $billingId =
        mysqli_insert_id($conn);


    mysqli_stmt_close(
        $billingStmt
    );


    /* =====================================================
       INSERT BILLING ITEMS
    ===================================================== */

    foreach ($validatedItems as $item) {

        $itemName =
            $item["item_name"];

        $quantity =
            $item["quantity"];

        $unitPrice =
            $item["unit_price"];

        $amount =
            $item["amount"];

        
        $inventoryItemId =
            $item["item_id"];

        mysqli_stmt_bind_param(
            $insertItemStmt,
            "iisddd",
            $billingId,
            $inventoryItemId,
            $itemName,
            $quantity,
            $unitPrice,
            $amount
        );


        if (!mysqli_stmt_execute($insertItemStmt)) {

            throw new Exception(
                "Failed to save one of the purchase items."
            );
        }
    }


    mysqli_stmt_close(
        $insertItemStmt
    );


    /* =====================================================
       COMMIT
       
       NO INVENTORY DEDUCTION YET.
    ===================================================== */

    mysqli_commit($conn);


    /* =====================================================
       SUCCESS
    ===================================================== */

    echo json_encode([
        "success" => true,
        "message" => "Purchase billing created successfully.",
        "billing_id" => (int)$billingId,
        "billing_total" => $billingTotal
    ]);

    exit;


} catch (Throwable $e) {


    /* =====================================================
       ROLLBACK
    ===================================================== */

    mysqli_rollback($conn);


    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

    exit;
}

?>