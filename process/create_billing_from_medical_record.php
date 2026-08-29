<?php

session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";
require_once "../config/audit_log.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

$medicalRecordId = isset($_POST["medical_record_id"])
    ? (int) $_POST["medical_record_id"]
    : 0;

if ($medicalRecordId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid medical record ID."
    ]);
    exit;
}

try {

    /* =========================================================
       GET MEDICAL RECORD + APPOINTMENT
       ========================================================= */

    $recordSql = "
        SELECT
            mr.medical_record_id,
            mr.appointment_id,
            mr.pet_id,
            mr.billing_id,
            mr.weight,

            a.customer_id,
            a.service_category,
            a.service,
            a.status AS appointment_status

        FROM medical_records mr

        INNER JOIN appointments a
            ON a.appointment_id = mr.appointment_id

        WHERE mr.medical_record_id = ?

        LIMIT 1
    ";

    $recordStmt = mysqli_prepare($conn, $recordSql);

    mysqli_stmt_bind_param(
        $recordStmt,
        "i",
        $medicalRecordId
    );

    mysqli_stmt_execute($recordStmt);

    $recordResult = mysqli_stmt_get_result($recordStmt);
    $record = mysqli_fetch_assoc($recordResult);

    mysqli_stmt_close($recordStmt);

    if (!$record) {
        echo json_encode([
            "success" => false,
            "message" => "Medical record not found."
        ]);
        exit;
    }

    /* =========================================================
       IF THIS MEDICAL RECORD IS ALREADY LINKED TO BILLING
       ========================================================= */

    if (!empty($record["billing_id"])) {
        echo json_encode([
            "success" => true,
            "message" => "Billing already exists.",
            "billing_id" => (int) $record["billing_id"]
        ]);
        exit;
    }

    /* =========================================================
       CHECK BILLING BY APPOINTMENT TOO
       ========================================================= */

    $existingBillingSql = "
        SELECT billing_id
        FROM billing
        WHERE appointment_id = ?
        LIMIT 1
    ";

    $existingBillingStmt = mysqli_prepare(
        $conn,
        $existingBillingSql
    );

    mysqli_stmt_bind_param(
        $existingBillingStmt,
        "i",
        $record["appointment_id"]
    );

    mysqli_stmt_execute($existingBillingStmt);

    $existingBillingResult =
        mysqli_stmt_get_result($existingBillingStmt);

    $existingBilling =
        mysqli_fetch_assoc($existingBillingResult);

    mysqli_stmt_close($existingBillingStmt);

    if ($existingBilling) {

        $existingBillingId =
            (int) $existingBilling["billing_id"];

        $linkExistingSql = "
            UPDATE medical_records
            SET billing_id = ?
            WHERE medical_record_id = ?
        ";

        $linkExistingStmt =
            mysqli_prepare(
                $conn,
                $linkExistingSql
            );

        mysqli_stmt_bind_param(
            $linkExistingStmt,
            "ii",
            $existingBillingId,
            $medicalRecordId
        );

        mysqli_stmt_execute($linkExistingStmt);

        mysqli_stmt_close($linkExistingStmt);

        echo json_encode([
            "success" => true,
            "message" =>
                "Existing billing was linked to this medical record.",
            "billing_id" =>
                $existingBillingId
        ]);

        exit;
    }

    /* =========================================================
       ONLY COMPLETED APPOINTMENTS CAN BE BILLED
       ========================================================= */

    if (
        strcasecmp(
            trim($record["appointment_status"]),
            "Completed"
        ) !== 0
    ) {
        echo json_encode([
            "success" => false,
            "message" =>
                "Only completed appointments can be billed."
        ]);
        exit;
    }

    /* =========================================================
       GET MEDICAL RECORD SERVICES
       ========================================================= */

    $servicesSql = "
        SELECT
            medical_record_service_id,
            service_id,
            service_category,
            service_name,
            quantity,
            pet_weight,
            unit_price,
            amount,
            service_source

        FROM medical_record_services

        WHERE medical_record_id = ?

        ORDER BY medical_record_service_id ASC
    ";

    $servicesStmt =
        mysqli_prepare(
            $conn,
            $servicesSql
        );

    mysqli_stmt_bind_param(
        $servicesStmt,
        "i",
        $medicalRecordId
    );

    mysqli_stmt_execute($servicesStmt);

    $servicesResult =
        mysqli_stmt_get_result($servicesStmt);

    $services = [];

    while (
        $service =
            mysqli_fetch_assoc($servicesResult)
    ) {
        $services[] = $service;
    }

    mysqli_stmt_close($servicesStmt);

    if (empty($services)) {
        echo json_encode([
            "success" => false,
            "message" =>
                "No services were recorded for this medical record."
        ]);
        exit;
    }

    /* =========================================================
       IMPORTANT FIX:
       GET THE REAL PRICE OF THE APPOINTMENT SERVICE

       The medical_record_services row can have unit_price/amount
       equal to 0.00 because it is only a record of the performed
       service. The actual price must come from services +
       pricing_rules, just like the normal billing flow.
       ========================================================= */

    $appointmentServiceSql = "
        SELECT
            s.service_id,
            s.service_name,
            s.pricing_type,
            s.fixed_price,

            (
                SELECT pr.base_min_weight
                FROM pricing_rules pr
                WHERE pr.service_id = s.service_id
                  AND pr.status = 'Active'
                ORDER BY pr.pricing_rule_id ASC
                LIMIT 1
            ) AS base_min_weight,

            (
                SELECT pr.base_max_weight
                FROM pricing_rules pr
                WHERE pr.service_id = s.service_id
                  AND pr.status = 'Active'
                ORDER BY pr.pricing_rule_id ASC
                LIMIT 1
            ) AS base_max_weight,

            (
                SELECT pr.base_price
                FROM pricing_rules pr
                WHERE pr.service_id = s.service_id
                  AND pr.status = 'Active'
                ORDER BY pr.pricing_rule_id ASC
                LIMIT 1
            ) AS base_price,

            (
                SELECT pr.weight_increment
                FROM pricing_rules pr
                WHERE pr.service_id = s.service_id
                  AND pr.status = 'Active'
                ORDER BY pr.pricing_rule_id ASC
                LIMIT 1
            ) AS weight_increment,

            (
                SELECT pr.price_increment
                FROM pricing_rules pr
                WHERE pr.service_id = s.service_id
                  AND pr.status = 'Active'
                ORDER BY pr.pricing_rule_id ASC
                LIMIT 1
            ) AS price_increment

        FROM services s

        WHERE s.service_name = ?
          AND s.status = 'Active'

        LIMIT 1
    ";

    $appointmentServiceStmt =
        mysqli_prepare(
            $conn,
            $appointmentServiceSql
        );

    mysqli_stmt_bind_param(
        $appointmentServiceStmt,
        "s",
        $record["service"]
    );

    mysqli_stmt_execute(
        $appointmentServiceStmt
    );

    $appointmentServiceResult =
        mysqli_stmt_get_result(
            $appointmentServiceStmt
        );

    $appointmentService =
        mysqli_fetch_assoc(
            $appointmentServiceResult
        );

    mysqli_stmt_close(
        $appointmentServiceStmt
    );

    if (!$appointmentService) {
        echo json_encode([
            "success" => false,
            "message" =>
                "The appointment service could not be found or is no longer active."
        ]);
        exit;
    }

    /* =========================================================
       CALCULATE APPOINTMENT SERVICE PRICE
       ========================================================= */

    $petWeight =
        $record["weight"] !== null
            ? (float) $record["weight"]
            : null;

    $pricingType =
        trim(
            (string) (
                $appointmentService["pricing_type"] ?? ""
            )
        );

    $appointmentServicePrice = 0.00;

    if ($pricingType === "Fixed") {

        if ($appointmentService["fixed_price"] === null) {
            throw new Exception(
                "This service is set to Fixed pricing but has no fixed price."
            );
        }

        $appointmentServicePrice =
            (float) $appointmentService["fixed_price"];

    } elseif ($pricingType === "Weight-Based") {

        if (
            $petWeight === null ||
            $petWeight < 0
        ) {
            throw new Exception(
                "Pet weight is required for this weight-based service."
            );
        }

        if (
            $appointmentService["base_min_weight"] === null ||
            $appointmentService["base_max_weight"] === null ||
            $appointmentService["base_price"] === null ||
            $appointmentService["weight_increment"] === null ||
            $appointmentService["price_increment"] === null
        ) {
            throw new Exception(
                "No active pricing rule was found for this weight-based service."
            );
        }

        $baseMinWeight =
            (float) $appointmentService["base_min_weight"];

        $baseMaxWeight =
            (float) $appointmentService["base_max_weight"];

        $basePrice =
            (float) $appointmentService["base_price"];

        $weightIncrement =
            (float) $appointmentService["weight_increment"];

        $priceIncrement =
            (float) $appointmentService["price_increment"];

        if ($weightIncrement <= 0) {
            throw new Exception(
                "The pricing rule has an invalid weight increment."
            );
        }

        if (
            $petWeight >= $baseMinWeight &&
            $petWeight <= $baseMaxWeight
        ) {
            $appointmentServicePrice =
                $basePrice;

        } elseif ($petWeight > $baseMaxWeight) {

            $additionalWeight =
                $petWeight - $baseMaxWeight;

            $additionalIncrements =
                ceil(
                    $additionalWeight /
                    $weightIncrement
                );

            $appointmentServicePrice =
                $basePrice +
                (
                    $additionalIncrements *
                    $priceIncrement
                );

        } else {

            $appointmentServicePrice =
                $basePrice;
        }

    } elseif ($pricingType === "Manual / Variable") {

        /*
         * Manual/Variable services keep the amount entered in
         * the medical record if one exists.
         */
        $appointmentServicePrice = 0.00;

        foreach ($services as $service) {
            $source = strtolower(
                trim(
                    (string) (
                        $service["service_source"] ?? ""
                    )
                )
            );

            if (
                $source === "appointment" &&
                strcasecmp(
                    trim((string) $service["service_name"]),
                    trim((string) $record["service"])
                ) === 0
            ) {
                $appointmentServicePrice =
                    (float) ($service["amount"] ?? 0);
                break;
            }
        }

    } else {
        throw new Exception(
            "The selected service has an unsupported pricing type."
        );
    }

    $appointmentServicePrice =
        round($appointmentServicePrice, 2);

    /* =========================================================
       CALCULATE TOTAL

       Appointment service = calculated from services/pricing_rules
       Additional services = amount recorded in medical record
       ========================================================= */

    $totalAmount =
        $appointmentServicePrice;

    foreach ($services as &$service) {

        $source = strtolower(
            trim(
                (string) (
                    $service["service_source"] ?? ""
                )
            )
        );

        if ($source === "appointment") {

            $quantity =
                (float) (
                    $service["quantity"] ?? 1
                );

            if ($quantity <= 0) {
                $quantity = 1;
            }

            $service["billing_unit_price"] =
                $appointmentServicePrice;

            $service["billing_amount"] =
                round(
                    $appointmentServicePrice *
                    $quantity,
                    2
                );

            /*
             * The main appointment service is already represented
             * by appointmentServicePrice above, so replace the
             * initial total with its actual quantity-aware amount.
             */
            $totalAmount =
                $service["billing_amount"];

        } else {

            $quantity =
                (float) (
                    $service["quantity"] ?? 1
                );

            $unitPrice =
                (float) (
                    $service["unit_price"] ?? 0
                );

            $amount =
                (float) (
                    $service["amount"] ?? 0
                );

            /*
             * Prefer the recorded amount. If it is zero but a
             * unit price exists, calculate it from quantity.
             */
            if ($amount <= 0 && $unitPrice > 0) {
                $amount =
                    round(
                        $quantity * $unitPrice,
                        2
                    );
            }

            $service["billing_unit_price"] =
                $unitPrice;

            $service["billing_amount"] =
                round($amount, 2);

            $totalAmount +=
                $service["billing_amount"];
        }
    }

    unset($service);

    $totalAmount =
        round($totalAmount, 2);

    /* =========================================================
       TRANSACTION
       ========================================================= */

    mysqli_begin_transaction($conn);

    /* =========================================================
       CREATE BILLING
       ========================================================= */

    $billingSql = "
        INSERT INTO billing (
            appointment_id,
            customer_id,
            pet_id,
            pet_weight,
            total_amount,
            payment_status,
            billing_status
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            'Pending',
            'Unpaid'
        )
    ";

    $billingStmt =
        mysqli_prepare(
            $conn,
            $billingSql
        );

    mysqli_stmt_bind_param(
        $billingStmt,
        "iiidd",
        $record["appointment_id"],
        $record["customer_id"],
        $record["pet_id"],
        $petWeight,
        $totalAmount
    );

    mysqli_stmt_execute($billingStmt);

    $billingId =
        mysqli_insert_id($conn);

    mysqli_stmt_close($billingStmt);

    /* =========================================================
       CREATE BILLING ITEMS
       ========================================================= */

    $itemSql = "
        INSERT INTO billing_items (
            billing_id,
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
            ?,
            ?,
            'service',
            ?,
            ?
        )
    ";

    $itemStmt =
        mysqli_prepare(
            $conn,
            $itemSql
        );

    foreach ($services as $service) {

        $source =
            strtolower(
                trim(
                    (string) (
                        $service["service_source"] ?? ""
                    )
                )
            );

        $itemType =
            $source === "additional"
                ? "Additional"
                : "Service";

        $itemName =
            trim(
                (string) (
                    $service["service_name"] ?? ""
                )
            );

        $quantity =
            (float) (
                $service["quantity"] ?? 1
            );

        if ($quantity <= 0) {
            $quantity = 1;
        }

        if ($source === "appointment") {

            $unitPrice =
                (float) $appointmentServicePrice;

            $amount =
                round(
                    $unitPrice * $quantity,
                    2
                );

        } else {

            $unitPrice =
                (float) (
                    $service["billing_unit_price"] ?? 0
                );

            $amount =
                (float) (
                    $service["billing_amount"] ?? 0
                );
        }

        mysqli_stmt_bind_param(
            $itemStmt,
            "issddd",
            $billingId,
            $itemType,
            $itemName,
            $quantity,
            $unitPrice,
            $amount
        );

        mysqli_stmt_execute($itemStmt);
    }

    mysqli_stmt_close($itemStmt);

    /* =========================================================
       LINK BILLING TO MEDICAL RECORD
       ========================================================= */

    $updateRecordSql = "
        UPDATE medical_records
        SET billing_id = ?
        WHERE medical_record_id = ?
    ";

    $updateRecordStmt =
        mysqli_prepare(
            $conn,
            $updateRecordSql
        );

    mysqli_stmt_bind_param(
        $updateRecordStmt,
        "ii",
        $billingId,
        $medicalRecordId
    );

    mysqli_stmt_execute($updateRecordStmt);

    mysqli_stmt_close($updateRecordStmt);

    /* =========================================================
       MARK APPOINTMENT BILLING AS CREATED
       ========================================================= */

    $updateAppointmentSql = "
        UPDATE appointments
        SET billing_created = 1
        WHERE appointment_id = ?
    ";

    $updateAppointmentStmt =
        mysqli_prepare(
            $conn,
            $updateAppointmentSql
        );

    mysqli_stmt_bind_param(
        $updateAppointmentStmt,
        "i",
        $record["appointment_id"]
    );

    mysqli_stmt_execute($updateAppointmentStmt);

    mysqli_stmt_close($updateAppointmentStmt);

    mysqli_commit($conn);

    // =========================================================
// AUDIT LOG — BILLING CREATED
// =========================================================

logAudit(
    $conn,
    "Billing",
    "Created",
    "Billing #" .
        $billingId .
        " was created from Medical Record #" .
        $medicalRecordId .
        ".",
    (string) $billingId
);      

    echo json_encode([
        "success" => true,
        "message" =>
            "Billing created successfully.",
        "billing_id" =>
            (int) $billingId,
        "total_amount" =>
            $totalAmount,
        "appointment_service_price" =>
            $appointmentServicePrice
    ]);

} catch (Throwable $e) {

    if (
        isset($conn) &&
        $conn instanceof mysqli
    ) {
        try {
            mysqli_rollback($conn);
        } catch (Throwable $ignored) {
        }
    }

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to create billing.",
        "error" =>
            $e->getMessage()
    ]);
}

?>