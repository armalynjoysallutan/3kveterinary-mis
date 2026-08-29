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

$key = trim($_POST["key"] ?? "");

if ($key === "") {
    echo json_encode([
        "success" => false,
        "message" => "Schedule type is required."
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| AVAILABLE DAYS
|--------------------------------------------------------------------------
*/

if ($key === "days") {

    $selectedDaysRaw = $_POST["selected_days"] ?? "";

    $selectedDays = json_decode(
        $selectedDaysRaw,
        true
    );

    if (!is_array($selectedDays)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid available days."
        ]);
        exit;
    }

    $validDays = [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday"
    ];

    $selectedDays = array_values(
        array_intersect(
            $selectedDays,
            $validDays
        )
    );

    if (count($selectedDays) === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Please select at least one available day."
        ]);
        exit;
    }

    foreach ($validDays as $day) {

        $isAvailable =
            in_array(
                $day,
                $selectedDays,
                true
            ) ? 1 : 0;

        $check = mysqli_prepare(
            $conn,
            "SELECT schedule_id
             FROM appointment_schedule
             WHERE day_of_week = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $day
        );

        mysqli_stmt_execute($check);

        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {

            $row = mysqli_fetch_assoc($result);

            $scheduleId =
                (int)$row["schedule_id"];

            mysqli_stmt_close($check);

            $update = mysqli_prepare(
                $conn,
                "UPDATE appointment_schedule
                 SET is_available = ?
                 WHERE schedule_id = ?"
            );

            mysqli_stmt_bind_param(
                $update,
                "ii",
                $isAvailable,
                $scheduleId
            );

            mysqli_stmt_execute($update);

            mysqli_stmt_close($update);

        } else {

            mysqli_stmt_close($check);

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO appointment_schedule
                    (day_of_week, is_available)
                 VALUES
                    (?, ?)"
            );

            mysqli_stmt_bind_param(
                $insert,
                "si",
                $day,
                $isAvailable
            );

            mysqli_stmt_execute($insert);

            mysqli_stmt_close($insert);
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Available days updated successfully."
    ]);

    mysqli_close($conn);
    exit;
}


/*
|--------------------------------------------------------------------------
| AVAILABLE TIME SLOTS
|--------------------------------------------------------------------------
*/

if ($key === "slots") {

    $slotInterval =
        (int)($_POST["slot_interval"] ?? 0);

    if (!in_array(
        $slotInterval,
        [15, 30, 60],
        true
    )) {

        echo json_encode([
            "success" => false,
            "message" => "Invalid time slot duration."
        ]);

        exit;
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE appointment_schedule
         SET slot_interval = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $slotInterval
    );

    if (!mysqli_stmt_execute($stmt)) {

        echo json_encode([
            "success" => false,
            "message" => mysqli_error($conn)
        ]);

        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit;
    }

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => true,
        "message" => "Time slot duration updated successfully."
    ]);

    mysqli_close($conn);
    exit;
}


/*
|--------------------------------------------------------------------------
| CLINIC HOURS
|--------------------------------------------------------------------------
*/

if ($key === "hours") {

    $openingTime =
        trim($_POST["opening_time"] ?? "");

    $closingTime =
        trim($_POST["closing_time"] ?? "");

    if (
        $openingTime === "" ||
        $closingTime === ""
    ) {

        echo json_encode([
            "success" => false,
            "message" => "Opening and closing time are required."
        ]);

        exit;
    }

    if ($openingTime >= $closingTime) {

        echo json_encode([
            "success" => false,
            "message" => "Closing time must be later than opening time."
        ]);

        exit;
    }

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE appointment_schedule
         SET opening_time = ?,
             closing_time = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $openingTime,
        $closingTime
    );

    if (!mysqli_stmt_execute($stmt)) {

        echo json_encode([
            "success" => false,
            "message" => mysqli_error($conn)
        ]);

        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit;
    }

    mysqli_stmt_close($stmt);

    echo json_encode([
        "success" => true,
        "message" => "Clinic hours updated successfully."
    ]);

    mysqli_close($conn);
    exit;
}


echo json_encode([
    "success" => false,
    "message" => "Invalid schedule type."
]);

mysqli_close($conn);

?>