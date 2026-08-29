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


$pricingRuleId = isset($_POST["pricing_rule_id"])
    ? (int) $_POST["pricing_rule_id"]
    : 0;


$baseMinWeight = isset($_POST["base_min_weight"])
    ? (float) $_POST["base_min_weight"]
    : 0;


$baseMaxWeight = isset($_POST["base_max_weight"])
    ? (float) $_POST["base_max_weight"]
    : 0;


$basePrice = isset($_POST["base_price"])
    ? (float) $_POST["base_price"]
    : 0;


$weightIncrement = isset($_POST["weight_increment"])
    ? (float) $_POST["weight_increment"]
    : 0;


$priceIncrement = isset($_POST["price_increment"])
    ? (float) $_POST["price_increment"]
    : 0;


if ($pricingRuleId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid pricing rule."
    ]);

    exit;
}


if ($baseMinWeight < 0) {

    echo json_encode([
        "success" => false,
        "message" => "Starting weight cannot be negative."
    ]);

    exit;
}


if ($baseMaxWeight <= $baseMinWeight) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Base maximum weight must be greater than starting weight."
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
        "message" =>
            "Weight increment must be greater than zero."
    ]);

    exit;
}


if ($priceIncrement < 0) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Price increase cannot be negative."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK EXISTING RULE
|--------------------------------------------------------------------------
*/

$checkSql = "
    SELECT pricing_rule_id
    FROM pricing_rules
    WHERE pricing_rule_id = ?
    LIMIT 1
";


$checkStmt =
    mysqli_prepare(
        $conn,
        $checkSql
    );


mysqli_stmt_bind_param(
    $checkStmt,
    "i",
    $pricingRuleId
);


mysqli_stmt_execute(
    $checkStmt
);


$checkResult =
    mysqli_stmt_get_result(
        $checkStmt
    );


if (
    mysqli_num_rows(
        $checkResult
    ) === 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Pricing rule not found."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

$sql = "
    UPDATE pricing_rules

    SET
        base_min_weight = ?,
        base_max_weight = ?,
        base_price = ?,
        weight_increment = ?,
        price_increment = ?,
        updated_at = CURRENT_TIMESTAMP

    WHERE pricing_rule_id = ?
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
            "Unable to prepare update query."
    ]);

    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    "dddddi",
    $baseMinWeight,
    $baseMaxWeight,
    $basePrice,
    $weightIncrement,
    $priceIncrement,
    $pricingRuleId
);


if (
    mysqli_stmt_execute($stmt)
) {

    echo json_encode([
        "success" => true,
        "message" =>
            "Pricing rule updated successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to update pricing rule: "
            . mysqli_error($conn)
    ]);

}

?>