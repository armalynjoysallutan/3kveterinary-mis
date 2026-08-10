<?php

include "../config/database.php";

$events = [];


$sql = "SELECT
            a.appointment_id,
            p.pet_name,
            c.owner_name,
            a.service,
            a.appointment_date,
            a.appointment_time,
            a.status
        FROM appointments a

        INNER JOIN customers c
            ON a.customer_id = c.customer_id

        INNER JOIN pets p
            ON a.pet_id = p.pet_id

        WHERE a.is_archived = 0";


$result = mysqli_query($conn, $sql);


while ($row = mysqli_fetch_assoc($result)) {


    // ======================================
    // DEFAULT COLOR
    // ======================================

    $color = "#6c757d";


    // ======================================
    // STATUS COLOR
    // ======================================

    switch ($row["status"]) {

        case "Pending":

            $color = "#FFC107";

            break;


        case "Confirmed":

            $color = "#198754";

            break;


        case "Arrived":

            $color = "#0D6EFD";

            break;


        case "Completed":

            $color = "#6C63FF";

            break;


        case "Cancelled":

            $color = "#DC3545";

            break;

    }


    // ======================================
    // CREATE CALENDAR EVENT
    // ======================================

   // ======================================
// FORMAT TIME
// ======================================

$timeObject = DateTime::createFromFormat(
    "H:i:s",
    $row["appointment_time"]
);

$formattedTime = $timeObject
    ? $timeObject->format("g:i A")
    : $row["appointment_time"];


// ======================================
// CREATE CALENDAR EVENT
// ======================================

$events[] = [

    "id" =>
        $row["appointment_id"],

    "title" =>
        $formattedTime .
        "\n" .
        $row["owner_name"] .
        "\n" .
        $row["service"],

    "start" =>
        $row["appointment_date"] .
        "T" .
        $row["appointment_time"],

    "backgroundColor" =>
        $color,

    "borderColor" =>
        $color,

    "textColor" =>
        "#111827",

    "extendedProps" => [

        "owner" =>
            $row["owner_name"],

        "service" =>
            $row["service"],

        "status" =>
            $row["status"],

        "pet" =>
            $row["pet_name"]

    ],

    "classNames" => [
        "appointment-status-" .
        strtolower($row["status"])
    ]

];

}


header("Content-Type: application/json");


echo json_encode($events);

?>