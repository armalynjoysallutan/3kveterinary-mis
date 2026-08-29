<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["admin_username"])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);
    exit;
}

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

$appointment_type_id = intval($_POST["appointment_type_id"] ?? 0);
$appointment_type = trim($_POST["appointment_type"] ?? "");
$status = trim($_POST["status"] ?? "Active");


/* ==============================
   VALIDATE
============================== */

if ($appointment_type_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid appointment type."
    ]);

    exit;
}


if ($appointment_type === "") {

    echo json_encode([
        "success" => false,
        "message" => "Appointment type is required."
    ]);

    exit;
}


if (!in_array($status, ["Active", "Inactive"], true)) {
    $status = "Active";
}


/* ==============================
   CHECK DUPLICATE
============================== */

$check = mysqli_prepare(
    $conn,
    "SELECT appointment_type_id
     FROM appointment_types
     WHERE appointment_type = ?
     AND appointment_type_id != ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $check,
    "si",
    $appointment_type,
    $appointment_type_id
);

mysqli_stmt_execute($check);

$result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($result) > 0) {

    echo json_encode([
        "success" => false,
        "message" => "Appointment type already exists."
    ]);

    mysqli_stmt_close($check);
    exit;
}

mysqli_stmt_close($check);


/* ==============================
   UPDATE
============================== */

$stmt = mysqli_prepare(
    $conn,
    "UPDATE appointment_types
     SET appointment_type = ?,
         status = ?
     WHERE appointment_type_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ssi",
    $appointment_type,
    $status,
    $appointment_type_id
);


if (mysqli_stmt_execute($stmt)) {

    echo json_encode([
        "success" => true,
        "message" => "Appointment type updated successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);

}


mysqli_stmt_close($stmt);
mysqli_close($conn);

?>