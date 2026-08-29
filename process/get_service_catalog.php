<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["admin_username"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized."
    ]);
    exit();
}

require_once __DIR__ . "/../config/database.php";

$services = [];

$sql = "
    SELECT
        s.service_id,
        sc.category_id,
        sc.category_name,
        s.service_name,
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

    FROM services s

    INNER JOIN service_categories sc
        ON sc.category_id = s.category_id

    WHERE s.status = 'Active'
      AND sc.status = 'Active'

    ORDER BY
        sc.category_name ASC,
        s.service_name ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load service catalog.",
        "error" => mysqli_error($conn)
    ]);

    exit();
}

while ($row = mysqli_fetch_assoc($result)) {
    $services[] = [
        "service_id" =>
            (int)$row["service_id"],

        "category_id" =>
            (int)$row["category_id"],

        "category_name" =>
            $row["category_name"],

        "service_name" =>
            $row["service_name"],

        "pricing_type" =>
            $row["pricing_type"],

        "fixed_price" =>
            $row["fixed_price"] !== null
                ? (float)$row["fixed_price"]
                : null,

        "pricing_rule_id" =>
            $row["pricing_rule_id"] !== null
                ? (int)$row["pricing_rule_id"]
                : null,

        "base_min_weight" =>
            $row["base_min_weight"] !== null
                ? (float)$row["base_min_weight"]
                : null,

        "base_max_weight" =>
            $row["base_max_weight"] !== null
                ? (float)$row["base_max_weight"]
                : null,

        "base_price" =>
            $row["base_price"] !== null
                ? (float)$row["base_price"]
                : null,

        "weight_increment" =>
            $row["weight_increment"] !== null
                ? (float)$row["weight_increment"]
                : null,

        "price_increment" =>
            $row["price_increment"] !== null
                ? (float)$row["price_increment"]
                : null
    ];
}

echo json_encode([
    "success" => true,
    "services" => $services
], JSON_UNESCAPED_UNICODE);
