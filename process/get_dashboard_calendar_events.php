<?php
include("../config/database.php");

header("Content-Type: application/json");

$events = [];

/* =========================
   APPOINTMENTS
========================= */
$apptQuery = mysqli_query($conn, "SELECT pet_name, service, appointment_date, appointment_time
                                  FROM appointments");

while ($row = mysqli_fetch_assoc($apptQuery)) {
    $time = !empty($row['appointment_time']) ? date("H:i:s", strtotime($row['appointment_time'])) : "09:00:00";

    $events[] = [
        "title" => $row['pet_name'] . " - " . $row['service'],
        "start" => $row['appointment_date'] . "T" . $time,
        "color" => "#3b82f6",
        "extendedProps" => [
            "event_type" => "Appointment"
        ]
    ];
}

/* =========================
   CALENDAR EVENTS
========================= */
$eventQuery = mysqli_query($conn, "SELECT event_title, event_type, event_date, event_time
                                   FROM calendar_events");

while ($row = mysqli_fetch_assoc($eventQuery)) {
    $time = !empty($row['event_time']) ? date("H:i:s", strtotime($row['event_time'])) : "09:00:00";

    $color = "#a855f7"; // default other

    if ($row['event_type'] === 'Delivery Day') {
        $color = "#22c55e";
    } elseif ($row['event_type'] === 'Order/Restock') {
        $color = "#f59e0b";
    } elseif ($row['event_type'] === 'Other Event') {
        $color = "#a855f7";
    }

    $events[] = [
        "title" => $row['event_title'],
        "start" => $row['event_date'] . "T" . $time,
        "color" => $color,
        "extendedProps" => [
            "event_type" => $row['event_type']
        ]
    ];
}

echo json_encode($events);
?>