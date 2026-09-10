<?php

session_start();

header('Content-Type: application/json');

if (
    !isset($_SESSION['account_id']) ||
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['Admin', 'Staff'], true)
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);

    exit();
}

require_once __DIR__ . '/../config/database.php';

/* REST OF YOUR EXISTING CODE BELOW */

/* =========================
   REQUEST METHOD
========================= */

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit();

}


/* =========================
   GET FORM DATA
========================= */

$eventTitle =
    trim(
        $_POST['event_title'] ?? ''
    );


$eventType =
    trim(
        $_POST['event_type'] ?? ''
    );


$eventDate =
    trim(
        $_POST['event_date'] ?? ''
    );


$eventTime =
    trim(
        $_POST['event_time'] ?? ''
    );


$notes =
    trim(
        $_POST['notes'] ?? ''
    );


/* =========================
   ALLOWED EVENT TYPES
========================= */

$allowedTypes = [

    'Delivery Day',

    'Order/Restock',

    'Other Event'

];


/* =========================
   VALIDATE TITLE
========================= */

if ($eventTitle === '') {

    echo json_encode([

        "success" => false,

        "message" =>
            "Event title is required."

    ]);

    exit();

}


/* =========================
   VALIDATE EVENT TYPE
========================= */

if (
    !in_array(
        $eventType,
        $allowedTypes,
        true
    )
) {

    echo json_encode([

        "success" => false,

        "message" =>
            "Invalid event type."

    ]);

    exit();

}


/* =========================
   VALIDATE DATE
========================= */

$dateObj =
    DateTime::createFromFormat(
        'Y-m-d',
        $eventDate
    );


if (
    !$dateObj ||
    $dateObj->format('Y-m-d')
        !== $eventDate
) {

    echo json_encode([

        "success" => false,

        "message" =>
            "Invalid event date."

    ]);

    exit();

}


/* =========================
   VALIDATE TIME
========================= */

if ($eventTime !== '') {

    $timeObj =
        DateTime::createFromFormat(
            'H:i',
            $eventTime
        );


    if (
        !$timeObj ||
        $timeObj->format('H:i')
            !== $eventTime
    ) {

        echo json_encode([

            "success" => false,

            "message" =>
                "Invalid event time."

        ]);

        exit();

    }


    $eventTime .= ':00';

} else {

    $eventTime = null;

}


/* =========================
   INSERT EVENT
========================= */

$stmt = mysqli_prepare(

    $conn,

    "INSERT INTO calendar_events
    (
        event_title,
        event_type,
        event_date,
        event_time,
        notes
    )

    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?
    )"

);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Failed to prepare event save."

    ]);

    exit();

}


/* =========================
   BIND
========================= */

mysqli_stmt_bind_param(

    $stmt,

    "sssss",

    $eventTitle,

    $eventType,

    $eventDate,

    $eventTime,

    $notes

);


/* =========================
   EXECUTE
========================= */

if (
    !mysqli_stmt_execute($stmt)
) {

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Failed to save event: "
            . mysqli_stmt_error($stmt)

    ]);

    exit();

}


/* =========================
   SUCCESS
========================= */

echo json_encode([

    "success" => true,

    "message" =>
        "Event added successfully.",

    "event_id" =>
        mysqli_insert_id($conn)

]);


mysqli_stmt_close($stmt);

mysqli_close($conn);

?>