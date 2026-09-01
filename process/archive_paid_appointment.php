<?php
/* =========================================================
   AUTO ARCHIVE APPOINTMENT AFTER BILLING IS PAID

   File:
   process/archive_paid_appointment.php

   Purpose:
   Automatically archive the appointment connected
   to a billing record once the billing is Paid.
   ========================================================= */

session_start();

header("Content-Type: application/json; charset=UTF-8");

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

require_once __DIR__ . '/../config/database.php';


/* =========================================================
   VALIDATE REQUEST
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit();
}


/* =========================================================
   GET BILLING ID
   ========================================================= */

$billingId = (int)($_POST["billing_id"] ?? 0);

if ($billingId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid billing ID."
    ]);

    exit();
}


/* =========================================================
   GET BILLING INFORMATION
   ========================================================= */

$sql = "
    SELECT
        billing_id,
        appointment_id,
        payment_status
    FROM billing
    WHERE billing_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare billing query."
    ]);

    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $billingId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$billing = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   CHECK BILLING RECORD
   ========================================================= */

if (!$billing) {

    echo json_encode([
        "success" => false,
        "message" => "Billing record not found."
    ]);

    exit();
}


/* =========================================================
   CHECK PAYMENT STATUS
   ========================================================= */

if ($billing["payment_status"] !== "Paid") {

    echo json_encode([
        "success" => false,
        "message" => "Appointment cannot be archived because the billing is not Paid."
    ]);

    exit();
}


/* =========================================================
   GET APPOINTMENT ID
   ========================================================= */

$appointmentId = (int)$billing["appointment_id"];

if ($appointmentId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "No appointment is linked to this billing record."
    ]);

    exit();
}


/* =========================================================
   ARCHIVE APPOINTMENT
   ========================================================= */

$archiveSql = "
    UPDATE appointments
    SET
        is_archived = 1,
        updated_at = NOW()
    WHERE appointment_id = ?
      AND is_archived = 0
    LIMIT 1
";

$archiveStmt = mysqli_prepare(
    $conn,
    $archiveSql
);

if (!$archiveStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare archive query."
    ]);

    exit();
}

mysqli_stmt_bind_param(
    $archiveStmt,
    "i",
    $appointmentId
);

$success = mysqli_stmt_execute(
    $archiveStmt
);

$affectedRows = mysqli_stmt_affected_rows(
    $archiveStmt
);

mysqli_stmt_close($archiveStmt);


/* =========================================================
   RESPONSE
   ========================================================= */

if (!$success) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to archive appointment."
    ]);

    exit();
}


/* =========================================================
   ALREADY ARCHIVED
   ========================================================= */

if ($affectedRows === 0) {

    echo json_encode([
        "success" => true,
        "message" => "Appointment was already archived.",
        "appointment_id" => $appointmentId
    ]);

    exit();
}


/* =========================================================
   SUCCESS
   ========================================================= */

echo json_encode([
    "success" => true,
    "message" => "Appointment automatically archived after payment.",
    "appointment_id" => $appointmentId
]);

exit();