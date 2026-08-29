<?php

session_start();

require_once "../config/database.php";

header("Content-Type: application/json; charset=UTF-8");


/*
 * Make sure an administrator is logged in.
 */

if (!isset($_SESSION["admin_id"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();
}


/*
 * Get audit logs.
 */

$sql = "
    SELECT
        id,
        user_id,
        username,
        role,
        module,
        action,
        description,
        reference_no,
        created_at
    FROM audit_logs
    ORDER BY created_at DESC, id DESC
";


$result = mysqli_query(
    $conn,
    $sql
);


if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve audit logs."
    ]);

    exit();
}


$logs = [];


while ($row = mysqli_fetch_assoc($result)) {

    $logs[] = $row;

}


echo json_encode([
    "success" => true,
    "logs" => $logs
]);

?>