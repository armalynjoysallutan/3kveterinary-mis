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

require_once __DIR__ . '/../config/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

$appointment_type = trim($_POST["appointment_type"] ?? "");
$status = trim($_POST["status"] ?? "Active");

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

/*
|--------------------------------------------------------------------------
| CHECK DUPLICATE
|--------------------------------------------------------------------------
*/

$check = mysqli_prepare(
    $conn,
    "SELECT appointment_type_id
     FROM appointment_types
     WHERE appointment_type = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $check,
    "s",
    $appointment_type
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


/*
|--------------------------------------------------------------------------
| INSERT
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO appointment_types
        (appointment_type, status)
     VALUES
        (?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $appointment_type,
    $status
);

if (mysqli_stmt_execute($stmt)) {

    echo json_encode([
        "success" => true,
        "message" => "Appointment type added successfully."
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