<?php

session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);
    exit();
}

require_once __DIR__ . '/../config/database.php';

header("Content-Type: application/json");

$appointmentStatus = trim($_POST["appointment_status"] ?? "");

if ($appointmentStatus === "") {
    echo json_encode([
        "success" => false,
        "message" => "Appointment status is required."
    ]);
    exit();
}

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO appointment_statuses
        (appointment_status, status)
     VALUES
        (?, 'Active')"
);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $appointmentStatus
);

if (mysqli_stmt_execute($stmt)) {

    echo json_encode([
        "success" => true,
        "message" => "Appointment status saved successfully.",
        "id" => mysqli_insert_id($conn),
        "appointment_status" => $appointmentStatus,
        "status" => "Active"
    ]);

} else {

    if (mysqli_errno($conn) == 1062) {

        echo json_encode([
            "success" => false,
            "message" => "This appointment status already exists."
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => mysqli_error($conn)
        ]);
    }
}

mysqli_stmt_close($stmt);

?>