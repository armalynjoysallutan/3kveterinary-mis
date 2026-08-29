<?php

require_once "../config/database.php";

header("Content-Type: application/json");

$pricingRuleId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($pricingRuleId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid pricing rule ID."
    ]);

    exit;
}


$sql = "
    SELECT
        pr.pricing_rule_id,
        pr.service_id,
        pr.base_min_weight,
        pr.base_max_weight,
        pr.base_price,
        pr.weight_increment,
        pr.price_increment,
        pr.status,
        s.service_name

    FROM pricing_rules pr

    INNER JOIN services s
        ON pr.service_id = s.service_id

    WHERE pr.pricing_rule_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database query failed."
    ]);

    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $pricingRuleId
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$rule =
    mysqli_fetch_assoc($result);


if (!$rule) {

    echo json_encode([
        "success" => false,
        "message" => "Pricing rule not found."
    ]);

    exit;
}


echo json_encode([
    "success" => true,
    "rule" => $rule
]);

?>