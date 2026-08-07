<?php
include("../config/database.php");

header("Content-Type: application/json");

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode([
        "appointments" => [],
        "delivery_events" => [],
        "restock_events" => [],
        "other_events" => []
    ]);
    exit();
}

/* =========================
   APPOINTMENTS
========================= */
$stmt = mysqli_prepare($conn, "SELECT owner_name, pet_name, service, appointment_time, status
                               FROM appointments
                               WHERE appointment_date = ?
                               ORDER BY appointment_time ASC");
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$appointments = [];

while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = $row;
}

/* =========================
   CALENDAR EVENTS
========================= */
$delivery_events = [];
$restock_events = [];
$other_events = [];

$eventStmt = mysqli_prepare($conn, "SELECT event_title, event_type, event_time, notes
                                    FROM calendar_events
                                    WHERE event_date = ?
                                    ORDER BY event_time ASC");
mysqli_stmt_bind_param($eventStmt, "s", $date);
mysqli_stmt_execute($eventStmt);
$eventResult = mysqli_stmt_get_result($eventStmt);

while ($row = mysqli_fetch_assoc($eventResult)) {
    if ($row['event_type'] === 'Delivery Day') {
        $delivery_events[] = $row;
    } elseif ($row['event_type'] === 'Order/Restock') {
        $restock_events[] = $row;
    } elseif ($row['event_type'] === 'Other Event') {
        $other_events[] = $row;
    }
}

echo json_encode([
    "appointments" => $appointments,
    "delivery_events" => $delivery_events,
    "restock_events" => $restock_events,
    "other_events" => $other_events
]);
?>          