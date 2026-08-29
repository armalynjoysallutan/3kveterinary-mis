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

$query = "
    SELECT
        schedule_id,
        day_of_week,
        is_available,
        opening_time,
        closing_time,
        slot_interval,
        status
    FROM appointment_schedule
    ORDER BY FIELD(
        day_of_week,
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
    )
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);
    exit;
}

$schedules = [];

while ($row = mysqli_fetch_assoc($result)) {

    $schedules[] = [
        "schedule_id" => (int)$row["schedule_id"],
        "day_of_week" => $row["day_of_week"],
        "is_available" => (int)$row["is_available"],
        "opening_time" => $row["opening_time"],
        "closing_time" => $row["closing_time"],
        "slot_interval" => (int)$row["slot_interval"],
        "status" => $row["status"]
    ];
}

echo json_encode([
    "success" => true,
    "schedules" => $schedules
]);

mysqli_close($conn);

?>