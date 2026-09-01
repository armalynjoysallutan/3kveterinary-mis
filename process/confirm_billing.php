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


    /* =====================================================
       UPDATE MEDICAL RECORD
       LINK BILLING + SAVE ACTUAL AMOUNT PAID
       -----------------------------------------------------
       Medical records are created before billing, so the
       medical record may not have billing_id yet. We link
       it using the shared appointment_id.
    ====================================================== */

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


    /* =====================================================
       UPDATE APPOINTMENT
       COMPLETED = FINAL APPOINTMENT STATUS
    ====================================================== */

    $updateAppointment = "
        UPDATE appointments

        SET
            status = 'Completed',
            billing_created = 1,
            is_archived = 1

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

// =========================================================
// AUDIT LOG — BILLING AUTOMATICALLY ARCHIVED
// =========================================================

logAudit(
    $conn,
    "Billing",
    "Archived",
    "Billing #" .
        $billingId .
        " was automatically archived after payment was confirmed.",
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