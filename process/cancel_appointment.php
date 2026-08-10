<?php

require_once "../config/database.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}


$appointmentId =
    intval($_POST["appointmentId"] ?? 0);

$cancellationReason =
    trim($_POST["cancellationReason"] ?? "");



/* ======================================
   VALIDATE INPUT
====================================== */

if ($appointmentId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid appointment."
    ]);

    exit;
}


if ($cancellationReason === "") {

    echo json_encode([
        "success" => false,
        "message" => "Please provide a reason for cancellation."
    ]);

    exit;
}



/* ======================================
   START TRANSACTION
====================================== */

$conn->begin_transaction();


try {


    /* ==================================
       CHECK APPOINTMENT
    ================================== */

    $checkSql = "
        SELECT status
        FROM appointments
        WHERE appointment_id = ?
        LIMIT 1
    ";

    $checkStmt =
        $conn->prepare($checkSql);

    if (!$checkStmt) {
        throw new Exception(
            "Failed to prepare appointment query."
        );
    }

    $checkStmt->bind_param(
        "i",
        $appointmentId
    );

    $checkStmt->execute();

    $result =
        $checkStmt->get_result();

    $appointment =
        $result->fetch_assoc();


    if (!$appointment) {

        throw new Exception(
            "Appointment not found."
        );

    }


    /* ==================================
       CHECK STATUS
    ================================== */

    if (
        $appointment["status"] !== "Pending" &&
        $appointment["status"] !== "Confirmed"
    ) {

        throw new Exception(
            "This appointment can no longer be cancelled."
        );

    }


    /* ==================================
       CANCEL APPOINTMENT
    ================================== */

    $updateSql = "
        UPDATE appointments
        SET
            status = 'Cancelled',
            cancellation_reason = ?
        WHERE appointment_id = ?
    ";

    $updateStmt =
        $conn->prepare($updateSql);

    if (!$updateStmt) {

        throw new Exception(
            "Failed to prepare cancellation update."
        );

    }


    $updateStmt->bind_param(
        "si",
        $cancellationReason,
        $appointmentId
    );


    $updateStmt->execute();


    /* ==================================
       COMMIT
    ================================== */

    $conn->commit();


    echo json_encode([

        "success" => true,

        "message" =>
            "Appointment cancelled successfully.",

        "appointment_id" =>
            $appointmentId

    ]);


} catch (Exception $e) {


    $conn->rollback();


    echo json_encode([

        "success" => false,

        "message" => $e->getMessage()

    ]);

}

?>