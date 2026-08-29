<?php

require_once "../config/database.php";

header("Content-Type: application/json");

$sql = "
    SELECT
        s.service_id,
        s.service_name

    FROM services s

    LEFT JOIN pricing_rules pr
        ON s.service_id = pr.service_id

    WHERE
        s.pricing_type = 'Weight-Based'
        AND s.status = 'Active'
        AND pr.pricing_rule_id IS NULL

    ORDER BY s.service_name ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);

    exit;
}


$services = [];

while ($row = mysqli_fetch_assoc($result)) {

    $services[] = [
        "service_id" =>
            (int) $row["service_id"],

        "service_name" =>
            $row["service_name"]
    ];

}


echo json_encode([
    "success" => true,
    "services" => $services
]);

?>