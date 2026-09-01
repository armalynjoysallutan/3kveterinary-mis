<?php

session_start();

require_once "../config/database.php";

header("Content-Type: application/json");


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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$appointmentId = intval($_POST["appointmentId"] ?? 0);
$newStatus = trim($_POST["status"] ?? "");


// ======================================
// VALIDATE INPUT
// ======================================

$allowedStatuses = [
    "Pending",
    "Confirmed",
    "Arrived",
    "Completed",
    "Cancelled"
];

if ($appointmentId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid appointment."
    ]);

    exit;
}

if (!in_array($newStatus, $allowedStatuses, true)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid appointment status."
    ]);

    exit;
}


// ======================================
// START TRANSACTION
// ======================================

$conn->begin_transaction();

try {

    // ==================================
    // GET CUSTOMER ID
    // ==================================

    $getAppointmentSql = "
        SELECT customer_id
        FROM appointments
        WHERE appointment_id = ?
        LIMIT 1
    ";

    $getAppointmentStmt =
        $conn->prepare($getAppointmentSql);

    if (!$getAppointmentStmt) {
        throw new Exception("Failed to prepare appointment query.");
    }

    $getAppointmentStmt->bind_param(
        "i",
        $appointmentId
    );

    $getAppointmentStmt->execute();

    $result =
        $getAppointmentStmt->get_result();

    $appointment =
        $result->fetch_assoc();


    if (!$appointment) {
        throw new Exception("Appointment not found.");
    }

    $customerId =
        intval($appointment["customer_id"]);


    // ==================================
    // UPDATE APPOINTMENT STATUS
    // ==================================

    $updateAppointmentSql = "
        UPDATE appointments
        SET status = ?
        WHERE appointment_id = ?
    ";

    $updateAppointmentStmt =
        $conn->prepare($updateAppointmentSql);

    if (!$updateAppointmentStmt) {
        throw new Exception("Failed to prepare status update.");
    }

    $updateAppointmentStmt->bind_param(
        "si",
        $newStatus,
        $appointmentId
    );

    $updateAppointmentStmt->execute();


    // ==================================
    // COMPLETED → CUSTOMER ACTIVE
    // ==================================

    if ($newStatus === "Completed") {

        $updateCustomerSql = "
            UPDATE customers
            SET record_status = 'Active'
            WHERE customer_id = ?
        ";

        $updateCustomerStmt =
            $conn->prepare($updateCustomerSql);

        if (!$updateCustomerStmt) {
            throw new Exception("Failed to prepare customer update.");
        }

        $updateCustomerStmt->bind_param(
            "i",
            $customerId
        );

        $updateCustomerStmt->execute();

    }


    // ==================================
    // COMMIT
    // ==================================

    $conn->commit();


    echo json_encode([
        "success" => true,
        "message" => "Appointment status updated successfully.",
        "appointment_id" => $appointmentId,
        "status" => $newStatus
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

?>