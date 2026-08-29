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
   CONFIRMED ONLY
========================= */

$stmt = mysqli_prepare($conn, "
    SELECT
        c.owner_name,
        p.pet_name,
        a.service,
        a.appointment_time,
        a.status

    FROM appointments a

    INNER JOIN customers c
        ON c.customer_id = a.customer_id

    INNER JOIN pets p
        ON p.pet_id = a.pet_id

    WHERE a.appointment_date = ?
      AND a.status = 'Confirmed'
      AND a.is_archived = 0

    ORDER BY a.appointment_time ASC
");

if (!$stmt) {
    echo json_encode([
        "appointments" => [],
        "delivery_events" => [],
        "restock_events" => [],
        "other_events" => [],
        "error" => "Appointment query preparation failed: " . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $date);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "appointments" => [],
        "delivery_events" => [],
        "restock_events" => [],
        "other_events" => [],
        "error" => "Appointment query execution failed: " . mysqli_stmt_error($stmt)
    ]);
    exit();
}

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