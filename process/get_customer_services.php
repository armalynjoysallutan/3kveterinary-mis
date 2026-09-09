<?php

require_once("../config/database.php");

header("Content-Type: application/json; charset=UTF-8");

$sql = "
    SELECT
        s.service_id,
        sc.category_id,
        sc.category_name,
        s.service_name
    FROM services s
    INNER JOIN service_categories sc
        ON s.category_id = sc.category_id
    WHERE
        s.status = 'Active'
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
        "message" => "Unable to load services."
    ]);

    exit();
}

$services = [];

while ($row = mysqli_fetch_assoc($result)) {

    $services[] = [
        "service_id" => (int) $row["service_id"],
        "category_id" => (int) $row["category_id"],
        "category_name" => $row["category_name"],
        "service_name" => $row["service_name"]
    ];
}

echo json_encode([
    "success" => true,
    "services" => $services
]);

mysqli_close($conn);
?>