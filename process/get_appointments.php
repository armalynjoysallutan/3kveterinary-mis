<?php

require_once "../config/database.php";

header("Content-Type: application/json");

$sql = "SELECT
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.appointment_type,
            a.service,
            a.reason,
            a.cancellation_reason,
            a.status,

            c.owner_name,

            p.pet_name,
            p.species

        FROM appointments a

        INNER JOIN customers c
            ON a.customer_id = c.customer_id

        INNER JOIN pets p
            ON a.pet_id = p.pet_id

        WHERE a.is_archived = 0

        ORDER BY
            a.appointment_date ASC,
            a.appointment_time ASC";


$result = mysqli_query($conn, $sql);


if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);

    exit;
}


$appointments = [];


while ($row = mysqli_fetch_assoc($result)) {

    $appointments[] = $row;

}


echo json_encode([
    "success" => true,
    "data" => $appointments
]);

?>