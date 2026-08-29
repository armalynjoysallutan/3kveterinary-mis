<?php

require_once "../config/database.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request."
    ]);
    exit;
}

$serviceId = isset($_POST["service_id"])
    ? (int) $_POST["service_id"]
    : 0;

$baseMinWeight = isset($_POST["base_min_weight"])
    ? (float) $_POST["base_min_weight"]
    : 0;

$baseMaxWeight = isset($_POST["base_max_weight"])
    ? (float) $_POST["base_max_weight"]
    : 5;

$basePrice = isset($_POST["base_price"])
    ? (float) $_POST["base_price"]
    : 0;

$weightIncrement = isset($_POST["weight_increment"])
    ? (float) $_POST["weight_increment"]
    : 5;

$priceIncrement = isset($_POST["price_increment"])
    ? (float) $_POST["price_increment"]
    : 0;


if ($serviceId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Please select a service."
    ]);
    exit;
}


if ($baseMinWeight < 0 || $baseMaxWeight <= $baseMinWeight) {
    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid base weight range."
    ]);
    exit;
}


if ($basePrice < 0) {
    echo json_encode([
        "success" => false,
        "message" => "Base price cannot be negative."
    ]);
    exit;
}


if ($weightIncrement <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Weight increment must be greater than zero."
    ]);
    exit;
}


if ($priceIncrement < 0) {
    echo json_encode([
        "success" => false,
        "message" => "Price increase cannot be negative."
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK SERVICE
|--------------------------------------------------------------------------
*/

$serviceSql = "
    SELECT
        service_id,
        service_name,
        pricing_type,
        status

    FROM services

    WHERE service_id = ?

    LIMIT 1
";

$serviceStmt = mysqli_prepare(
    $conn,
    $serviceSql
);

mysqli_stmt_bind_param(
    $serviceStmt,
    "i",
    $serviceId
);

mysqli_stmt_execute($serviceStmt);

$serviceResult = mysqli_stmt_get_result(
    $serviceStmt
);

$service = mysqli_fetch_assoc(
    $serviceResult
);


if (!$service) {
    echo json_encode([
        "success" => false,
        "message" => "Selected service was not found."
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| ONLY WEIGHT-BASED SERVICES
|--------------------------------------------------------------------------
*/

if ($service["pricing_type"] !== "Weight-Based") {
    echo json_encode([
        "success" => false,
        "message" => "Pricing rules can only be added to Weight-Based services."
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK DUPLICATE RULE
|--------------------------------------------------------------------------
*/

$checkSql = "
    SELECT pricing_rule_id
    FROM pricing_rules
    WHERE service_id = ?
    LIMIT 1
";

$checkStmt = mysqli_prepare(
    $conn,
    $checkSql
);

mysqli_stmt_bind_param(
    $checkStmt,
    "i",
    $serviceId
);

mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result(
    $checkStmt
);


if (mysqli_num_rows($checkResult) > 0) {
    echo json_encode([
        "success" => false,
        "message" => "A pricing rule already exists for this service."
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| INSERT PRICING RULE
|--------------------------------------------------------------------------
*/

$insertSql = "
    INSERT INTO pricing_rules
    (
        service_id,
        base_min_weight,
        base_max_weight,
        base_price,
        weight_increment,
        price_increment,
        status
    )

    VALUES
    (?, ?, ?, ?, ?, ?, 'Active')
";

$insertStmt = mysqli_prepare(
    $conn,
    $insertSql
);

mysqli_stmt_bind_param(
    $insertStmt,
    "iddddd",
    $serviceId,
    $baseMinWeight,
    $baseMaxWeight,
    $basePrice,
    $weightIncrement,
    $priceIncrement
);


if (!mysqli_stmt_execute($insertStmt)) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to save pricing rule: "
            . mysqli_error($conn)
    ]);
    exit;
}


echo json_encode([
    "success" => true,
    "message" => "Pricing rule added successfully.",
    "pricing_rule_id" =>
        mysqli_insert_id($conn)
]);

?>