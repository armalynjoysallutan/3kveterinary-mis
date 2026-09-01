<?php

session_start();

include("../config/database.php");

header("Content-Type: application/json");


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

$sql = "
    SELECT
        customer_id,
        owner_name,
        contact_number,
        email,
        address
    FROM customers
    WHERE record_status = 'Active'
    ORDER BY owner_name ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);
    exit;
}

$clients = [];

while ($row = mysqli_fetch_assoc($result)) {
    $clients[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $clients
]);