<?php

session_start();

require_once "../config/database.php";
require_once "../config/audit_log.php";

header("Content-Type: application/json");


/* =========================================================
   REQUEST VALIDATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);

    exit();
}


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
   GET DATA
========================================================= */

$billingId =
    isset($_POST["billing_id"])
        ? (int) $_POST["billing_id"]
        : 0;

$amountPaid =
    isset($_POST["amount_paid"])
        ? (float) $_POST["amount_paid"]
        : 0;


if ($billingId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid billing ID."
    ]);

    exit();
}


/* =========================================================
   GET BILLING
========================================================= */

$getSql = "
    SELECT
        billing_id,
        appointment_id,
        total_amount,
        payment_status,
        billing_status

    FROM billing

    WHERE billing_id = ?

    LIMIT 1
";


$getStmt =
    mysqli_prepare(
        $conn,
        $getSql
    );


if (!$getStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to load billing."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $getStmt,
    "i",
    $billingId
);


mysqli_stmt_execute(
    $getStmt
);


$getResult =
    mysqli_stmt_get_result(
        $getStmt
    );


$billing =
    mysqli_fetch_assoc(
        $getResult
    );


mysqli_stmt_close(
    $getStmt
);


if (!$billing) {

    echo json_encode([
        "success" => false,
        "message" => "Billing record not found."
    ]);

    exit();
}


/* =========================================================
   PREVENT DOUBLE PAYMENT
========================================================= */

if (
    strtolower(
        trim(
            $billing["payment_status"]
        )
    ) === "paid"
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "This billing has already been paid."
    ]);

    exit();
}


/* =========================================================
   GET TOTAL
========================================================= */

$totalAmount =
    (float) $billing["total_amount"];


/* =========================================================
   VALIDATE PAYMENT
========================================================= */

if ($amountPaid < $totalAmount) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Amount paid is not enough."
    ]);

    exit();
}


/* =========================================================
   COMPUTE CHANGE
========================================================= */

$change =
    round(
        $amountPaid - $totalAmount,
        2
    );


/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try {
    /* =====================================================
   INVENTORY DEDUCTION
   FEFO — FIRST EXPIRY, FIRST OUT

   Only:
   - Medicine
   - Supplements
   - Pet Food

   are automatically deducted through Billing.
====================================================== */

$productItemsSql = "
    SELECT
        bi.billing_item_id,
        bi.inventory_item_id,
        bi.item_name,
        bi.quantity
    FROM billing_items bi

    INNER JOIN inventory_items ii
        ON ii.item_id = bi.inventory_item_id

    WHERE bi.billing_id = ?
      AND bi.item_type = 'Product'
      AND bi.inventory_item_id IS NOT NULL
      AND ii.category_id IN (1, 2, 3)

    ORDER BY bi.billing_item_id ASC
";

$productItemsStmt = mysqli_prepare(
    $conn,
    $productItemsSql
);

if (!$productItemsStmt) {
    throw new Exception(
        "Unable to load billing products."
    );
}

mysqli_stmt_bind_param(
    $productItemsStmt,
    "i",
    $billingId
);

mysqli_stmt_execute(
    $productItemsStmt
);

$productItemsResult =
    mysqli_stmt_get_result(
        $productItemsStmt
    );


while (
    $productItem =
        mysqli_fetch_assoc(
            $productItemsResult
        )
) {

    $inventoryItemId =
        (int)$productItem["inventory_item_id"];

    $remainingToDeduct =
        (float)$productItem["quantity"];


    /* -------------------------------------------------
       GET AVAILABLE BATCHES
       FEFO:
       1. Expiration date earliest first
       2. No-expiration batches last
       3. Earlier received first
       4. Stock ID as final tie-breaker
    ------------------------------------------------- */

    $batchSql = "
        SELECT
            stock_id,
            batch_number,
            quantity,
            expiration_date,
            date_received
        FROM inventory_stock
        WHERE item_id = ?
          AND quantity > 0
        ORDER BY
            (expiration_date IS NULL) ASC,
            expiration_date ASC,
            date_received ASC,
            stock_id ASC
        FOR UPDATE
    ";

    $batchStmt = mysqli_prepare(
        $conn,
        $batchSql
    );

    if (!$batchStmt) {
        throw new Exception(
            "Unable to load inventory batches."
        );
    }

    mysqli_stmt_bind_param(
        $batchStmt,
        "i",
        $inventoryItemId
    );

    mysqli_stmt_execute(
        $batchStmt
    );

    $batchResult =
        mysqli_stmt_get_result(
            $batchStmt
        );


    while (
        $batch =
            mysqli_fetch_assoc(
                $batchResult
            )
    ) {

        if ($remainingToDeduct <= 0) {
            break;
        }

        $available =
            (float)$batch["quantity"];

        if ($available <= 0) {
            continue;
        }


        /* ---------------------------------------------
           Determine how much to deduct from this batch
        --------------------------------------------- */

        $deductQuantity =
            min(
                $remainingToDeduct,
                $available
            );


        $newQuantity =
            $available - $deductQuantity;


        /* ---------------------------------------------
           UPDATE REMAINING BATCH STOCK
        --------------------------------------------- */

        $updateStockSql = "
            UPDATE inventory_stock
            SET quantity = ?
            WHERE stock_id = ?
              AND item_id = ?
        ";

        $updateStockStmt =
            mysqli_prepare(
                $conn,
                $updateStockSql
            );

        if (!$updateStockStmt) {
            throw new Exception(
                "Unable to update inventory stock."
            );
        }

        mysqli_stmt_bind_param(
            $updateStockStmt,
            "dii",
            $newQuantity,
            $batch["stock_id"],
            $inventoryItemId
        );

        if (
            !mysqli_stmt_execute(
                $updateStockStmt
            )
        ) {
            throw new Exception(
                "Failed to deduct inventory stock."
            );
        }

        mysqli_stmt_close(
            $updateStockStmt
        );


        /* ---------------------------------------------
           RECORD STOCK OUT
        --------------------------------------------- */

        $stockOutDate =
            date("Y-m-d");

        $reason =
            "Dispensed/Sold";

        $referenceNumber =
            "Billing #" . $billingId;

        $remarks =
            "Automatic inventory deduction from Billing #" .
            $billingId .
            " — FEFO batch " .
            $batch["batch_number"];


        $insertStockOutSql = "
            INSERT INTO inventory_stock_out
            (
                stock_id,
                quantity,
                stock_out_date,
                reason,
                reference_number,
                remarks
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                NULLIF(?, ''),
                NULLIF(?, '')
            )
        ";

        $insertStockOutStmt =
            mysqli_prepare(
                $conn,
                $insertStockOutSql
            );

        if (!$insertStockOutStmt) {
            throw new Exception(
                "Unable to record inventory Stock Out."
            );
        }

        mysqli_stmt_bind_param(
            $insertStockOutStmt,
            "idssss",
            $batch["stock_id"],
            $deductQuantity,
            $stockOutDate,
            $reason,
            $referenceNumber,
            $remarks
        );

        if (
            !mysqli_stmt_execute(
                $insertStockOutStmt
            )
        ) {
            throw new Exception(
                "Failed to record inventory Stock Out."
            );
        }

        mysqli_stmt_close(
            $insertStockOutStmt
        );


        $remainingToDeduct -=
            $deductQuantity;
    }


    mysqli_stmt_close(
        $batchStmt
    );


    /* ---------------------------------------------
       INSUFFICIENT STOCK
    --------------------------------------------- */

    if ($remainingToDeduct > 0.0001) {

        throw new Exception(
            "Insufficient inventory stock for " .
            $productItem["item_name"] .
            ". Available stock is not enough to complete this sale."
        );
    }
}

mysqli_stmt_close(
    $productItemsStmt
);




    /* =====================================================
       UPDATE BILLING
    ====================================================== */

    $updateBilling = "
        UPDATE billing

        SET
            payment_status = 'Paid',
            billing_status = 'Paid'

        WHERE billing_id = ?
    ";


    $billingStmt =
        mysqli_prepare(
            $conn,
            $updateBilling
        );


    if (!$billingStmt) {

        throw new Exception(
            "Unable to update billing."
        );

    }


    mysqli_stmt_bind_param(
        $billingStmt,
        "i",
        $billingId
    );


    if (
        !mysqli_stmt_execute(
            $billingStmt
        )
    ) {

        throw new Exception(
            "Failed to update billing status."
        );

    }


    mysqli_stmt_close(
        $billingStmt
    );

if (!empty($billing["appointment_id"])) {

     /* =====================================================
   UPDATE MEDICAL RECORD
   LINK BILLING + SAVE ACTUAL AMOUNT PAID
   -----------------------------------------------------
   Only appointment-based billing has a medical record.
   Purchase-only billing has no appointment.
  ====================================================== */

 if (!empty($billing["appointment_id"])) {

    $updateMedicalRecord = "
        UPDATE medical_records
        SET
            billing_id = ?,
            amount_paid = ?
        WHERE appointment_id = ?
        ORDER BY medical_record_id DESC
        LIMIT 1
    ";

    $medicalRecordStmt =
        mysqli_prepare(
            $conn,
            $updateMedicalRecord
        );

    if (!$medicalRecordStmt) {

        throw new Exception(
            "Unable to update medical record: " .
            mysqli_error($conn)
        );

    }

    mysqli_stmt_bind_param(
        $medicalRecordStmt,
        "idi",
        $billingId,
        $amountPaid,
        $billing["appointment_id"]
    );

    if (!mysqli_stmt_execute($medicalRecordStmt)) {

        throw new Exception(
            "Failed to update medical record: " .
            mysqli_stmt_error($medicalRecordStmt)
        );

    }

    mysqli_stmt_close(
        $medicalRecordStmt
    );

}    
}

if (!empty($billing["appointment_id"])) {

    /* =====================================================
   UPDATE APPOINTMENT
   COMPLETED = FINAL APPOINTMENT STATUS
   -----------------------------------------------------
   Only appointment-based billing has an appointment.
   Purchase-only billing has no appointment.
   ====================================================== */

if (!empty($billing["appointment_id"])) {

    $updateAppointment = "
        UPDATE appointments
        SET
            status = 'Completed',
            billing_created = 1
        WHERE appointment_id = ?
    ";

    $appointmentStmt =
        mysqli_prepare(
            $conn,
            $updateAppointment
        );

    if (!$appointmentStmt) {

        throw new Exception(
            "Unable to update appointment."
        );

    }

    mysqli_stmt_bind_param(
        $appointmentStmt,
        "i",
        $billing["appointment_id"]
    );

    if (
        !mysqli_stmt_execute(
            $appointmentStmt
        )
    ) {

        throw new Exception(
            "Failed to update appointment."
        );

    }

    mysqli_stmt_close(
        $appointmentStmt
    );
}
}

    /* =====================================================
       COMMIT
    ====================================================== */

    mysqli_commit(
        $conn
    );

    // =========================================================
// AUDIT LOG — PAYMENT CONFIRMED
// =========================================================

logAudit(
    $conn,
    "Billing",
    "Payment Confirmed",
    "Payment for Billing #" .
        $billingId .
        " was confirmed. Amount paid: ₱" .
        number_format($amountPaid, 2) .
        ". Change: ₱" .
        number_format($change, 2) .
        ".",
    (string) $billingId
);



    echo json_encode([

        "success" => true,

        "message" =>
            "Payment confirmed successfully.",

        "total_amount" =>
            $totalAmount,

        "amount_paid" =>
            $amountPaid,

        "change" =>
            $change

    ]);


} catch (
    Exception $e
) {


    mysqli_rollback(
        $conn
    );


    echo json_encode([

        "success" => false,

        "message" =>
            $e->getMessage()

    ]);

}

?>