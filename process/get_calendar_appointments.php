<?php

include "../config/database.php";

$events = [];

$sql = "SELECT
            appointment_id,
            pet_name,
            owner_name,
            service,
            appointment_date,
            appointment_time,
            status
        FROM appointments
        WHERE is_archived = 0";

$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){


    $color = "#6c757d"; // Default Gray

    switch ($row["status"]) {

        case "Pending":
            $color = "#FFC107";
            break;

        case "Confirmed":
            $color = "#0D6EFD";
            break;

        case "Completed":
            $color = "#198754";
            break;

        case "Cancelled":
            $color = "#DC3545";
            break;
    }
    $events[] = [

        "id" => $row["appointment_id"],

        "title" => $row["pet_name"] . " - " . $row["service"],

        "start" => $row["appointment_date"] . "T" . $row["appointment_time"],

        "extendedProps" => [

            "owner" => $row["owner_name"],

            "status" => $row["status"]

        ]

    ];

}

header("Content-Type: application/json");

echo json_encode($events);