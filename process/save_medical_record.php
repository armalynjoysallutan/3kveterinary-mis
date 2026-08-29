<?php

session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized."
    ]);
    exit();
}

require_once "../config/database.php";
require_once "../config/audit_log.php";

header("Content-Type: application/json");

/* =========================================================
   GET FORM DATA
========================================================= */

$petId = isset($_POST["pet_id"])
    ? (int) $_POST["pet_id"]
    : 0;

$appointmentId = isset($_POST["appointment_id"])
    ? (int) $_POST["appointment_id"]
    : 0;

$billingId = isset($_POST["billing_id"])
    ? (int) $_POST["billing_id"]
    : 0;

$recordDate = $_POST["record_date"] ?? "";

$weight = isset($_POST["weight"])
    ? (float) $_POST["weight"]
    : 0;

$temperature = isset($_POST["temperature"])
    ? (float) $_POST["temperature"]
    : 0;

$diagnosis = trim($_POST["diagnosis"] ?? "");

$treatment = trim($_POST["treatment"] ?? "");

$vaccination = trim($_POST["vaccination"] ?? "");

$nextVisit = $_POST["next_visit"] ?? "";

$noDaysReturn = isset($_POST["no_days_return"])
    ? (int) $_POST["no_days_return"]
    : 0;

/*
 * Additional services selected inside Medical Record.
 * Prices are retained for Billing but are not displayed in the UI.
 */
$medicalRecordServicesRaw =
    $_POST["medical_record_services"] ?? "";

$medicalRecordServices = [];

/*
 * Customer Records currently submits Additional Services as
 * services[] hidden inputs. Accept that format first, while also
 * supporting the medical_record_services JSON format.
 */
if (!empty($_POST["services"]) && is_array($_POST["services"])) {

    foreach ($_POST["services"] as $serviceJson) {

        $decodedService = json_decode(
            $serviceJson,
            true
        );

        if (is_array($decodedService)) {
            $decodedService["service_source"] = "Additional";
            $medicalRecordServices[] = $decodedService;
        }
    }

} elseif ($medicalRecordServicesRaw !== "") {

    $decodedServices = json_decode(
        $medicalRecordServicesRaw,
        true
    );

    if (is_array($decodedServices)) {
        $medicalRecordServices = $decodedServices;
    }
}


/* =========================================================
   VALIDATION
========================================================= */

if ($petId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid pet."
    ]);
    exit();
}

if (empty($recordDate)) {
    echo json_encode([
        "success" => false,
        "message" => "Visit date is required."
    ]);
    exit();
}

if ($weight <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Weight is required."
    ]);
    exit();
}

if ($temperature <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Temperature is required."
    ]);
    exit();
}

if (empty($nextVisit)) {
    echo json_encode([
        "success" => false,
        "message" => "Next visit is required."
    ]);
    exit();
}


/* =========================================================
   GET AMOUNT PAID FROM BILLING
========================================================= */

$amountPaid = 0.00;

if ($billingId > 0) {

    $billingSql = "
        SELECT total_amount
        FROM billing
        WHERE billing_id = ?
        LIMIT 1
    ";

    $billingStmt = mysqli_prepare(
        $conn,
        $billingSql
    );

    if ($billingStmt) {

        mysqli_stmt_bind_param(
            $billingStmt,
            "i",
            $billingId
        );

        mysqli_stmt_execute($billingStmt);

        $billingResult =
            mysqli_stmt_get_result($billingStmt);

        $billing = mysqli_fetch_assoc(
            $billingResult
        );

        if ($billing) {
            $amountPaid =
                (float) $billing["total_amount"];
        }

        mysqli_stmt_close($billingStmt);
    }
}


/* =========================================================
   SAVE MEDICAL RECORD
========================================================= */

/*
 * Save the medical record and its performed services
 * as one transaction.
 */
mysqli_begin_transaction($conn);

$sql = "
    INSERT INTO medical_records (
        pet_id,
        appointment_id,
        billing_id,
        record_date,
        weight,
        temperature,
        diagnosis,
        treatment,
        vaccination,
        amount_paid,
        next_visit,
        no_days_return
    )
    VALUES (
        ?,
        NULLIF(?, 0),
        NULLIF(?, 0),
        ?,
        ?,
        ?,
        NULLIF(?, ''),
        NULLIF(?, ''),
        NULLIF(?, ''),
        ?,
        ?,
        ?
    )
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Prepare failed: " .
            mysqli_error($conn)
    ]);

    exit();
}


/* =========================================================
   BIND PARAMETERS
========================================================= */

mysqli_stmt_bind_param(
    $stmt,
    "iiisddsssdsi",
    $petId,
    $appointmentId,
    $billingId,
    $recordDate,
    $weight,
    $temperature,
    $diagnosis,
    $treatment,
    $vaccination,
    $amountPaid,
    $nextVisit,
    $noDaysReturn
);


/* =========================================================
   EXECUTE
========================================================= */

if (!mysqli_stmt_execute($stmt)) {

    mysqli_rollback($conn);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to save medical record: " .
            mysqli_stmt_error($stmt)
    ]);

    mysqli_stmt_close($stmt);
    exit();
}

/*
 * IMPORTANT:
 * Get the ID generated by the medical_records INSERT.
 * This ID is used as the foreign key in medical_record_services.
 */
$medicalRecordId = mysqli_insert_id($conn);

if ($medicalRecordId <= 0) {

    mysqli_rollback($conn);

    echo json_encode([
        "success" => false,
        "message" =>
            "Medical record was inserted, but its ID could not be retrieved."
    ]);

    mysqli_stmt_close($stmt);
    exit();
}


/* =========================================================
   SAVE APPOINTMENT SERVICE
========================================================= */

if ($appointmentId > 0) {

    $appointmentServiceSql = "
        SELECT
            service_category,
            service
        FROM appointments
        WHERE appointment_id = ?
        LIMIT 1
    ";

    $appointmentServiceStmt =
        mysqli_prepare(
            $conn,
            $appointmentServiceSql
        );

    if (!$appointmentServiceStmt) {
        mysqli_rollback($conn);
        mysqli_stmt_close($stmt);

        echo json_encode([
            "success" => false,
            "message" =>
                "Unable to prepare appointment service: " .
                mysqli_error($conn)
        ]);

        exit();
    }

    mysqli_stmt_bind_param(
        $appointmentServiceStmt,
        "i",
        $appointmentId
    );

    mysqli_stmt_execute(
        $appointmentServiceStmt
    );

    $appointmentResult =
        mysqli_stmt_get_result(
            $appointmentServiceStmt
        );

    $appointmentService =
        mysqli_fetch_assoc(
            $appointmentResult
        );

    mysqli_stmt_close(
        $appointmentServiceStmt
    );

    if (
        $appointmentService &&
        !empty($appointmentService["service"])
    ) {

        $insertServiceSql = "
            INSERT INTO medical_record_services (
                medical_record_id,
                service_id,
                service_category,
                service_name,
                pet_weight,
                quantity,
                unit_price,
                amount,
                service_source
            )
            VALUES (
                ?,
                NULL,
                ?,
                ?,
                ?,
                1,
                0.00,
                0.00,
                'Appointment'
            )
        ";

        $insertServiceStmt =
            mysqli_prepare(
                $conn,
                $insertServiceSql
            );

        if (!$insertServiceStmt) {
            mysqli_rollback($conn);
            mysqli_stmt_close($stmt);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Unable to prepare appointment service insert: " .
                    mysqli_error($conn)
            ]);

            exit();
        }

        $appointmentCategory =
            trim(
                $appointmentService["service_category"] ?? ""
            );

        $appointmentServiceName =
            trim(
                $appointmentService["service"] ?? ""
            );

        mysqli_stmt_bind_param(
            $insertServiceStmt,
            "issd",
            $medicalRecordId,
            $appointmentCategory,
            $appointmentServiceName,
            $weight
        );

        if (!mysqli_stmt_execute(
            $insertServiceStmt
        )) {

            $error =
                mysqli_stmt_error(
                    $insertServiceStmt
                );

            mysqli_rollback($conn);

            mysqli_stmt_close(
                $insertServiceStmt
            );

            mysqli_stmt_close($stmt);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Unable to save appointment service: " .
                    $error
            ]);

            exit();
        }

        mysqli_stmt_close(
            $insertServiceStmt
        );
    }
}


/* =========================================================
   SAVE ADDITIONAL SERVICES
========================================================= */

if (!empty($medicalRecordServices)) {

    $additionalServiceSql = "
        INSERT INTO medical_record_services (
            medical_record_id,
            service_id,
            service_category,
            service_name,
            pet_weight,
            quantity,
            unit_price,
            amount,
            service_source
        )
        VALUES (
            ?,
            NULLIF(?, 0),
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'Additional'
        )
    ";

    $additionalServiceStmt =
        mysqli_prepare(
            $conn,
            $additionalServiceSql
        );

    if (!$additionalServiceStmt) {
        mysqli_rollback($conn);
        mysqli_stmt_close($stmt);

        echo json_encode([
            "success" => false,
            "message" =>
                "Unable to prepare additional service insert: " .
                mysqli_error($conn)
        ]);

        exit();
    }

    foreach (
        $medicalRecordServices as $serviceData
    ) {

        if (!is_array($serviceData)) {
            continue;
        }

        $serviceId =
            isset($serviceData["service_id"])
                ? (int) $serviceData["service_id"]
                : 0;

        $serviceCategory =
            trim(
                (string) (
                    $serviceData["service_category"]
                    ?? ""
                )
            );

        $serviceName =
            trim(
                (string) (
                    $serviceData["service_name"]
                    ?? ""
                )
            );

        $serviceWeight =
            isset($serviceData["pet_weight"])
                ? (float) $serviceData["pet_weight"]
                : $weight;

        $quantity =
            isset($serviceData["quantity"])
                ? (float) $serviceData["quantity"]
                : 1;

        $unitPrice =
            isset($serviceData["unit_price"])
                ? (float) $serviceData["unit_price"]
                : 0;

        $amount =
            round(
                $quantity * $unitPrice,
                2
            );

        if (
            $serviceName === "" ||
            $serviceWeight <= 0 ||
            $quantity <= 0
        ) {
            continue;
        }

        mysqli_stmt_bind_param(
            $additionalServiceStmt,
            "iissdddd",
            $medicalRecordId,
            $serviceId,
            $serviceCategory,
            $serviceName,
            $serviceWeight,
            $quantity,
            $unitPrice,
            $amount
        );

        if (!mysqli_stmt_execute(
            $additionalServiceStmt
        )) {

            $error =
                mysqli_stmt_error(
                    $additionalServiceStmt
                );

            mysqli_rollback($conn);

            mysqli_stmt_close(
                $additionalServiceStmt
            );

            mysqli_stmt_close($stmt);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Unable to save additional service: " .
                    $error
            ]);

            exit();
        }
    }

    mysqli_stmt_close(
        $additionalServiceStmt
    );
}


/* =========================================================
   COMMIT
========================================================= */

if (!mysqli_commit($conn)) {

    mysqli_rollback($conn);
    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to complete medical record save."
    ]);

    exit();
}


mysqli_stmt_close($stmt);

// =========================================================
// AUDIT LOG — MEDICAL RECORD CREATED
// =========================================================

logAudit(
    $conn,
    "Medical Records",
    "Created",
    "Medical Record #" .
        $medicalRecordId .
        " was created for Pet #" .
        $petId .
        ".",
    (string) $medicalRecordId
);


/* =========================================================
   SUCCESS
========================================================= */

echo json_encode([
    "success" => true,
    "message" => "Medical record saved successfully.",
    "medical_record_id" => $medicalRecordId
]);

?>
