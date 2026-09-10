<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";


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

    exit();
}


/* =========================================================
   GET POST DATA
========================================================= */

$billingId = isset($_POST["billing_id"])
    ? (int) $_POST["billing_id"]
    : 0;

$inventoryItemId = isset($_POST["item_id"])
    ? (int) $_POST["item_id"]
    : 0;
$itemType = isset($_POST["item_type"])
    ? trim($_POST["item_type"])
    : "";

$itemName = isset($_POST["item_name"])
    ? trim($_POST["item_name"])
    : "";

$quantity = isset($_POST["quantity"])
    ? (float) $_POST["quantity"]
    : 0;

$unitPrice = isset($_POST["unit_price"])
    ? (float) $_POST["unit_price"]
    : 0;




/* =========================================================
   VALIDATION
========================================================= */

if ($billingId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid billing ID."
    ]);

    exit();
}


if ($itemType === "") {

    echo json_encode([
        "success" => false,
        "message" => "Item category is required."
    ]);

    exit();
}


if ($itemName === "") {

    echo json_encode([
        "success" => false,
        "message" => "Item name is required."
    ]);

    exit();
}


if ($quantity <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Quantity must be greater than zero."
    ]);

    exit();
}


if ($unitPrice < 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid unit price."
    ]);

    exit();
}





/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction($conn);


try {


    /* =====================================================
       CHECK BILLING
    ===================================================== */

    $billingSql = "
        SELECT
            billing_id,
            pet_id,
            payment_status,
            billing_status
        FROM billing
        WHERE billing_id = ?
        LIMIT 1
    ";


    $billingStmt = mysqli_prepare(
        $conn,
        $billingSql
    );


    if (!$billingStmt) {

        throw new Exception(
            "Unable to prepare billing query."
        );
    }


    mysqli_stmt_bind_param(
        $billingStmt,
        "i",
        $billingId
    );


    mysqli_stmt_execute(
        $billingStmt
    );


    $billingResult =
        mysqli_stmt_get_result(
            $billingStmt
        );


    $billing =
        mysqli_fetch_assoc(
            $billingResult
        );


    mysqli_stmt_close(
        $billingStmt
    );


    if (!$billing) {

        throw new Exception(
            "Billing record not found."
        );
    }


    /* =====================================================
       PREVENT ADDING TO PAID BILLING
    ===================================================== */

    $paymentStatus =
        strtolower(
            trim(
                $billing["payment_status"] ?? ""
            )
        );


    $billingStatus =
        strtolower(
            trim(
                $billing["billing_status"] ?? ""
            )
        );


    if (
        $paymentStatus === "paid" ||
        $billingStatus === "paid"
    ) {

        throw new Exception(
            "This billing has already been paid."
        );
    }


    /* =====================================================
       COMPUTE AMOUNT
    ===================================================== */

    $amount =
        round(
            $quantity * $unitPrice,
            2
        );


   /* =====================================================
   ADD BILLING ITEM
===================================================== */

$insertSql = "
    INSERT INTO billing_items
    (
        billing_id,
        inventory_item_id,
        item_type,
        item_name,
        quantity,
        unit,
        unit_price,
        amount
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        NULL,
        ?,
        ?
    )
";


$insertStmt =
    mysqli_prepare(
        $conn,
        $insertSql
    );


if (!$insertStmt) {

    throw new Exception(
        "Unable to prepare billing item."
    );
}


mysqli_stmt_bind_param(
    $insertStmt,
    "iissddd",
    $billingId,
    $inventoryItemId,
    $itemType,
    $itemName,
    $quantity,
    $unitPrice,
    $amount
);

    if (
        !mysqli_stmt_execute(
            $insertStmt
        )
    ) {

        throw new Exception(
            "Failed to add item."
        );
    }


    mysqli_stmt_close(
        $insertStmt
    );


    /* =====================================================
       RECALCULATE TOTAL
       
       NOTE:
       There is NO subtotal anymore.
       Only total_amount is used.
    ===================================================== */

    $sumSql = "
        SELECT
            COALESCE(
                SUM(amount),
                0
            ) AS total
        FROM billing_items
        WHERE billing_id = ?
    ";


    $sumStmt =
        mysqli_prepare(
            $conn,
            $sumSql
        );


    if (!$sumStmt) {

        throw new Exception(
            "Unable to calculate billing total."
        );
    }


    mysqli_stmt_bind_param(
        $sumStmt,
        "i",
        $billingId
    );


    mysqli_stmt_execute(
        $sumStmt
    );


    $sumResult =
        mysqli_stmt_get_result(
            $sumStmt
        );


    $sumData =
        mysqli_fetch_assoc(
            $sumResult
        );


    mysqli_stmt_close(
        $sumStmt
    );


    $newTotal =
        (float) (
            $sumData["total"] ?? 0
        );


    /* =====================================================
       UPDATE BILLING
       
       Save:
       - total amount
    ===================================================== */

    $updateBillingSql = "
        UPDATE billing
        SET
            total_amount = ?
        WHERE billing_id = ?
    ";


    $updateBillingStmt =
        mysqli_prepare(
            $conn,
            $updateBillingSql
        );


    if (!$updateBillingStmt) {

        throw new Exception(
            "Unable to update billing record."
        );
    }


    mysqli_stmt_bind_param(
        $updateBillingStmt,
        "di",
        $newTotal,
        $billingId
    );


    if (
        !mysqli_stmt_execute(
            $updateBillingStmt
        )
    ) {

        throw new Exception(
            "Failed to update billing record."
        );
    }


    mysqli_stmt_close(
        $updateBillingStmt
    );


   

    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    /* =====================================================
       SUCCESS RESPONSE
    ===================================================== */

    echo json_encode([
        "success" => true,
        "message" => "Item added successfully.",
        "total" => $newTotal
        
    ]);


    exit();


}


/* =========================================================
   ERROR HANDLING
========================================================= */

catch (Throwable $e) {


    mysqli_rollback(
        $conn
    );


    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);


    exit();
}