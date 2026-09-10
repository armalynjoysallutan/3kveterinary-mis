<?php

session_start();

require_once "../config/database.php";
require_once "../config/audit_log.php";

header("Content-Type: application/json");


/* =========================================================
   AUTHENTICATION
========================================================= */

if (!isset($_SESSION["admin_username"])) {

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();

}


/* =========================================================
   REQUEST VALIDATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);

    exit();

}


$billingId =
    isset($_POST["billing_id"])
        ? (int) $_POST["billing_id"]
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

$sql = "
    SELECT
        billing_id,
        appointment_id,
        payment_status,
        billing_status

    FROM billing

    WHERE billing_id = ?

    LIMIT 1
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to load billing record."
    ]);

    exit();

}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $billingId
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


$billing =
    mysqli_fetch_assoc(
        $result
    );


mysqli_stmt_close(
    $stmt
);


if (!$billing) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Billing record not found."
    ]);

    exit();

}


/* =========================================================
   MUST BE PAID
========================================================= */

if (
    strtolower(
        trim(
            $billing["payment_status"]
        )
    ) !== "paid"
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Only paid billing records can be archived."
    ]);

    exit();

}


/* =========================================================
   ALREADY ARCHIVED
========================================================= */

if (
    strtolower(
        trim(
            $billing["billing_status"]
        )
    ) === "archived"
) {

    echo json_encode([
        "success" => true,
        "message" =>
            "Billing is already archived."
    ]);

    exit();

}


/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try {


    /* =====================================================
       ARCHIVE BILLING
    ====================================================== */

    $updateBilling = "

        UPDATE billing

        SET
            billing_status = 'Archived'

        WHERE billing_id = ?

    ";


    $billingStmt =
        mysqli_prepare(
            $conn,
            $updateBilling
        );


    if (!$billingStmt) {

        throw new Exception(
            "Unable to archive billing."
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
            "Failed to archive billing."
        );

    }


    mysqli_stmt_close(
        $billingStmt
    );


    /* =====================================================
       ARCHIVE CONNECTED APPOINTMENT
    ====================================================== */

    $appointmentId =
        (int)
        $billing["appointment_id"];


    if ($appointmentId > 0) {

        $updateAppointment = "

            UPDATE appointments

            SET
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
                "Unable to archive appointment."
            );

        }


        mysqli_stmt_bind_param(
            $appointmentStmt,
            "i",
            $appointmentId
        );


        if (
            !mysqli_stmt_execute(
                $appointmentStmt
            )
        ) {

            throw new Exception(
                "Failed to archive appointment."
            );

        }


        mysqli_stmt_close(
            $appointmentStmt
        );

    }


    /* =====================================================
       AUDIT LOG — BILLING ARCHIVED
    ====================================================== */

    logAudit(

        $conn,

        "Billing",

        "Archived",

        "Billing #" .
        $billingId .
        " was archived after the billing statement was downloaded.",

        (string) $billingId

    );


    /* =====================================================
       COMMIT
    ====================================================== */

    mysqli_commit(
        $conn
    );


    echo json_encode([

        "success" => true,

        "message" =>
            "Billing archived successfully."

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


mysqli_close(
    $conn
);

?>