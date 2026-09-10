<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

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
        "message" => "Unauthorized."
    ]);

    exit();
}
require_once "../config/database.php";

$medicalRecordId = isset($_GET["medical_record_id"])
    ? (int) $_GET["medical_record_id"]
    : 0;

if ($medicalRecordId <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid medical record ID."
    ]);

    exit();
}

/* =========================================================
   MEDICAL RECORD
   ========================================================= */

$sql = "
    SELECT
        mr.medical_record_id,
        mr.pet_id,
        mr.appointment_id,
        mr.record_date,
        mr.weight,
        mr.temperature,
        mr.diagnosis,
        mr.treatment,
        mr.vaccination,
        mr.amount_paid,
        mr.next_visit,
        mr.no_days_return,

        a.service_category,
        a.service AS appointment_service,

        b.billing_id,
        b.payment_status,
        b.total_amount
    FROM medical_records mr

    LEFT JOIN appointments a
        ON a.appointment_id = mr.appointment_id

    LEFT JOIN billing b
        ON b.billing_id = mr.billing_id

    WHERE mr.medical_record_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare medical record query."
    ]);

    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $medicalRecordId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$record) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Medical record not found."
    ]);

    exit();
}

/* =========================================================
   SERVICES PERFORMED
   ========================================================= */

$services = [];

$serviceSql = "
    SELECT
        medical_record_service_id,
        service_id,
        service_category,
        service_name,
        quantity,
        pet_weight,
        unit_price,
        amount,
        service_source,
        next_visit,
        no_days_return
    FROM medical_record_services
    WHERE medical_record_id = ?
    ORDER BY medical_record_service_id ASC
";

$serviceStmt = mysqli_prepare(
    $conn,
    $serviceSql
);

if ($serviceStmt) {

    mysqli_stmt_bind_param(
        $serviceStmt,
        "i",
        $medicalRecordId
    );

    mysqli_stmt_execute(
        $serviceStmt
    );

    $serviceResult =
        mysqli_stmt_get_result(
            $serviceStmt
        );

    while (
        $service =
        mysqli_fetch_assoc($serviceResult)
    ) {
        $services[] = $service;
    }

    mysqli_stmt_close(
        $serviceStmt
    );
}

/* =========================================================
   RESPONSE
   ========================================================= */

echo json_encode([
    "success" => true,
    "record" => $record,
    "services" => $services
]);
?>
