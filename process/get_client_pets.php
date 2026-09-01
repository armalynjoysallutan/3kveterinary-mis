<?php

session_start();

require_once "../config/database.php";

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

if (!isset($_GET["customer_id"]) || empty($_GET["customer_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Customer ID is required."
    ]);

    exit;
}

$customerId = intval($_GET["customer_id"]);

$sql = "
    SELECT
        pet_id,
        customer_id,
        pet_name,
        species,
        breed,
        color,
        gender,
        weight,
        estimated_age
    FROM pets
    WHERE customer_id = ?
    ORDER BY pet_name ASC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $customerId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$pets = [];

while ($row = mysqli_fetch_assoc($result)) {

    $pets[] = $row;

}

echo json_encode([
    "success" => true,
    "data" => $pets
]);