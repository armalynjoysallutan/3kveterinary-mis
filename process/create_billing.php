


<?php

session_start();

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

require_once "../config/database.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$appointmentId = isset($_POST["appointment_id"])
    ? (int) $_POST["appointment_id"]
    : 0;

if ($appointmentId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid appointment ID."
    ]);

    exit;
}


/* =========================================================
   GET APPOINTMENT + SERVICE PRICING
========================================================= */

$sql = "
    SELECT
        a.appointment_id,
        a.customer_id,
        a.pet_id,
        a.service_category,
        a.service,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.billing_created,

        c.owner_name,

        p.pet_name,
        p.weight AS pet_weight,

        s.service_id,
        s.pricing_type,
        s.fixed_price,

        (
            SELECT pr.pricing_rule_id
            FROM pricing_rules pr
            WHERE pr.service_id = s.service_id
              AND pr.status = 'Active'
            ORDER BY pr.pricing_rule_id ASC
            LIMIT 1
        ) AS pricing_rule_id,

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

    FROM appointments a

    INNER JOIN customers c
        ON a.customer_id = c.customer_id

    INNER JOIN pets p
        ON a.pet_id = p.pet_id

    INNER JOIN services s
        ON s.service_name = a.service
        AND s.status = 'Active'

    INNER JOIN service_categories sc
        ON sc.category_id = s.category_id
        AND sc.category_name = a.service_category
        AND sc.status = 'Active'

    WHERE a.appointment_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $appointmentId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$appointment = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$appointment) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Appointment not found or its service is no longer active."
    ]);

    exit;
}


/* =========================================================
   ONLY COMPLETED APPOINTMENTS CAN BE BILLED
========================================================= */

if (
    strcasecmp(
        trim($appointment["status"]),
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
   CHECK IF BILLING ALREADY EXISTS
========================================================= */

$checkSql = "
    SELECT billing_id
    FROM billing
    WHERE appointment_id = ?
    LIMIT 1
";

$checkStmt = mysqli_prepare(
    $conn,
    $checkSql
);

mysqli_stmt_bind_param(
    $checkStmt,
    "i",
    $appointmentId
);

mysqli_stmt_execute($checkStmt);

$checkResult =
    mysqli_stmt_get_result($checkStmt);

$existingBilling =
    mysqli_fetch_assoc($checkResult);

mysqli_stmt_close($checkStmt);


if ($existingBilling) {

    echo json_encode([
        "success" => true,
        "message" => "Billing already exists.",
        "billing_id" =>
            (int) $existingBilling["billing_id"]
    ]);

    exit;
}


/* =========================================================
   GET PET WEIGHT
========================================================= */

$petWeight =
    $appointment["pet_weight"] !== null
        ? (float) $appointment["pet_weight"]
        : null;


/* =========================================================
   CALCULATE SERVICE PRICE
========================================================= */

$pricingType =
    trim(
        (string) (
            $appointment["pricing_type"] ?? ""
        )
    );

$servicePrice = 0.00;


/*
|--------------------------------------------------------------------------
| FIXED PRICE
|--------------------------------------------------------------------------
*/

if ($pricingType === "Fixed") {

    if ($appointment["fixed_price"] === null) {

        echo json_encode([
            "success" => false,
            "message" =>
                "This service is set to Fixed pricing but has no fixed price."
        ]);

        exit;
    }

    $servicePrice =
        (float) $appointment["fixed_price"];
}


/*
|--------------------------------------------------------------------------
| WEIGHT-BASED PRICE
|--------------------------------------------------------------------------
*/

elseif ($pricingType === "Weight-Based") {

    if (
        $petWeight === null ||
        $petWeight < 0
    ) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Pet weight is required for this weight-based service."
        ]);

        exit;
    }


    if (
        $appointment["pricing_rule_id"] === null ||
        $appointment["base_min_weight"] === null ||
        $appointment["base_max_weight"] === null ||
        $appointment["base_price"] === null ||
        $appointment["weight_increment"] === null ||
        $appointment["price_increment"] === null
    ) {

        echo json_encode([
            "success" => false,
            "message" =>
                "No active pricing rule was found for this weight-based service."
        ]);

        exit;
    }


    $baseMinWeight =
        (float) $appointment["base_min_weight"];

    $baseMaxWeight =
        (float) $appointment["base_max_weight"];

    $basePrice =
        (float) $appointment["base_price"];

    $weightIncrement =
        (float) $appointment["weight_increment"];

    $priceIncrement =
        (float) $appointment["price_increment"];


    if ($weightIncrement <= 0) {

        echo json_encode([
            "success" => false,
            "message" =>
                "The pricing rule has an invalid weight increment."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | BASE WEIGHT RANGE
    |--------------------------------------------------------------------------
    */

    if (
        $petWeight >= $baseMinWeight &&
        $petWeight <= $baseMaxWeight
    ) {

        $servicePrice =
            $basePrice;
    }


    /*
    |--------------------------------------------------------------------------
    | ABOVE BASE MAXIMUM
    |--------------------------------------------------------------------------
    */

    elseif ($petWeight > $baseMaxWeight) {

        $additionalWeight =
            $petWeight - $baseMaxWeight;

        $additionalIncrements =
            ceil(
                $additionalWeight /
                $weightIncrement
            );

        $servicePrice =
            $basePrice +
            (
                $additionalIncrements *
                $priceIncrement
            );
    }


    /*
    |--------------------------------------------------------------------------
    | BELOW BASE MINIMUM
    |--------------------------------------------------------------------------
    |
    | Same behavior as the existing Billing Statement JavaScript:
    | use the base price.
    |
    */

    else {

        $servicePrice =
            $basePrice;
    }
}


/*
|--------------------------------------------------------------------------
| MANUAL / VARIABLE
|--------------------------------------------------------------------------
|
| This will remain 0.00 for now because the staff will enter the
| actual amount manually from the Billing Statement.
|
*/

elseif ($pricingType === "Manual / Variable") {

    $servicePrice = 0.00;
}


/*
|--------------------------------------------------------------------------
| UNSUPPORTED PRICING TYPE
|--------------------------------------------------------------------------
*/

else {

    echo json_encode([
        "success" => false,
        "message" =>
            "The selected service has an unsupported pricing type."
    ]);

    exit;
}


$servicePrice =
    round($servicePrice, 2);


/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction($conn);

try {


    /* =====================================================
       CREATE BILLING
    ===================================================== */

    $insertBilling = "
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
            $insertBilling
        );


    mysqli_stmt_bind_param(
        $billingStmt,
        "iiidd",
        $appointment["appointment_id"],
        $appointment["customer_id"],
        $appointment["pet_id"],
        $petWeight,
        $servicePrice
    );


    mysqli_stmt_execute(
        $billingStmt
    );


    $billingId =
        mysqli_insert_id($conn);


    mysqli_stmt_close(
        $billingStmt
    );


    /* =====================================================
       ADD ORIGINAL APPOINTMENT SERVICE
    ===================================================== */

    $insertItem = "
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
            'Service',
            ?,
            1,
            'service',
            ?,
            ?
        )
    ";


    $itemStmt =
        mysqli_prepare(
            $conn,
            $insertItem
        );


    $serviceName =
        trim(
            $appointment["service"] ?? ""
        );


    if ($serviceName === "") {

        throw new Exception(
            "Appointment service is missing."
        );
    }


    mysqli_stmt_bind_param(
        $itemStmt,
        "isdd",
        $billingId,
        $serviceName,
        $servicePrice,
        $servicePrice
    );


    mysqli_stmt_execute(
        $itemStmt
    );


    mysqli_stmt_close(
        $itemStmt
    );


    /* =====================================================
       ADDITIONAL SERVICES FROM MEDICAL RECORD
    ===================================================== */

    $additionalServicesSql = "
        SELECT
            mrs.service_name,
            mrs.quantity,
            mrs.unit_price,
            mrs.amount
        FROM medical_record_services mrs
        INNER JOIN medical_records mr
            ON mr.medical_record_id = mrs.medical_record_id
        WHERE mr.appointment_id = ?
          AND mrs.service_source = 'Additional'
        ORDER BY mrs.medical_record_service_id ASC
    ";

    $additionalStmt = mysqli_prepare($conn, $additionalServicesSql);

    if (!$additionalStmt) {
        throw new Exception(
            "Unable to prepare additional services query: " . mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param($additionalStmt, "i", $appointmentId);
    mysqli_stmt_execute($additionalStmt);
    $additionalResult = mysqli_stmt_get_result($additionalStmt);

    $additionalItemStmt = mysqli_prepare(
        $conn,
        "INSERT INTO billing_items (
            billing_id, item_type, item_name, quantity, unit, unit_price, amount
        ) VALUES (?, 'Additional', ?, ?, 'service', ?, ?)"
    );

    if (!$additionalItemStmt) {
        mysqli_stmt_close($additionalStmt);
        throw new Exception(
            "Unable to prepare additional billing item: " . mysqli_error($conn)
        );
    }

    while ($additionalService = mysqli_fetch_assoc($additionalResult)) {
        $additionalName = trim((string)($additionalService["service_name"] ?? ""));
        $additionalQuantity = (float)($additionalService["quantity"] ?? 1);
        $additionalUnitPrice = (float)($additionalService["unit_price"] ?? 0);

        if ($additionalName === "" || $additionalQuantity <= 0) {
            continue;
        }

        $additionalAmount = round(
            $additionalQuantity * $additionalUnitPrice,
            2
        );

        mysqli_stmt_bind_param(
            $additionalItemStmt,
            "isddd",
            $billingId,
            $additionalName,
            $additionalQuantity,
            $additionalUnitPrice,
            $additionalAmount
        );

        if (!mysqli_stmt_execute($additionalItemStmt)) {
            $error = mysqli_stmt_error($additionalItemStmt);
            mysqli_stmt_close($additionalItemStmt);
            mysqli_stmt_close($additionalStmt);
            throw new Exception(
                "Unable to add additional service to billing: " . $error
            );
        }
    }

    mysqli_stmt_close($additionalItemStmt);
    mysqli_stmt_close($additionalStmt);


    /* =====================================================
       RECALCULATE BILLING TOTAL
    ===================================================== */

    $totalSql = "
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM billing_items
        WHERE billing_id = ?
    ";

    $totalStmt = mysqli_prepare($conn, $totalSql);

    if (!$totalStmt) {
        throw new Exception(
            "Unable to calculate billing total: " . mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param($totalStmt, "i", $billingId);
    mysqli_stmt_execute($totalStmt);
    $totalResult = mysqli_stmt_get_result($totalStmt);
    $totalData = mysqli_fetch_assoc($totalResult);
    mysqli_stmt_close($totalStmt);

    $billingTotal = round(
        (float)($totalData["total"] ?? 0),
        2
    );

    $updateBillingTotal = "
        UPDATE billing
        SET total_amount = ?
        WHERE billing_id = ?
    ";

    $updateBillingStmt = mysqli_prepare($conn, $updateBillingTotal);

    if (!$updateBillingStmt) {
        throw new Exception(
            "Unable to prepare billing total update: " . mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $updateBillingStmt,
        "di",
        $billingTotal,
        $billingId
    );

    if (!mysqli_stmt_execute($updateBillingStmt)) {
        $error = mysqli_stmt_error($updateBillingStmt);
        mysqli_stmt_close($updateBillingStmt);
        throw new Exception(
            "Unable to update billing total: " . $error
        );
    }

    mysqli_stmt_close($updateBillingStmt);


    /* =====================================================
       UPDATE APPOINTMENT
    ===================================================== */

    $updateAppointment = "
        UPDATE appointments

        SET billing_created = 1

        WHERE appointment_id = ?
    ";


    $updateStmt =
        mysqli_prepare(
            $conn,
            $updateAppointment
        );


    mysqli_stmt_bind_param(
        $updateStmt,
        "i",
        $appointmentId
    );


    mysqli_stmt_execute(
        $updateStmt
    );


    mysqli_stmt_close(
        $updateStmt
    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    echo json_encode([
        "success" => true,
        "message" =>
            "Billing created successfully.",
        "billing_id" =>
            (int) $billingId,
        "service_price" =>
            $servicePrice,
        "billing_total" =>
            $billingTotal,
        "pet_weight" =>
            $petWeight
    ]);


} catch (Throwable $e) {


    mysqli_rollback(
        $conn
    );


    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to create billing.",
        "error" =>
            $e->getMessage()
    ]);
}

?>