<?php
include("../config/database.php");

$startOfWeek = date("Y-m-d", strtotime("monday this week"));
$endOfWeek   = date("Y-m-d", strtotime("sunday this week"));

$sql = "SELECT DAYOFWEEK(appointment_date) AS day_num, COUNT(*) AS total
        FROM appointments
        WHERE appointment_date BETWEEN '$startOfWeek' AND '$endOfWeek'
        GROUP BY DAYOFWEEK(appointment_date)";

$result = mysqli_query($conn, $sql);

$weeklyData = [
    "Mon" => 0,
    "Tue" => 0,
    "Wed" => 0,
    "Thu" => 0,
    "Fri" => 0,
    "Sat" => 0,
    "Sun" => 0
];

while ($row = mysqli_fetch_assoc($result)) {

    $dayNum = $row["day_num"];
    $total  = $row["total"];

    switch ($dayNum) {
        case 2: $weeklyData["Mon"] = $total; break;
        case 3: $weeklyData["Tue"] = $total; break;
        case 4: $weeklyData["Wed"] = $total; break;
        case 5: $weeklyData["Thu"] = $total; break;
        case 6: $weeklyData["Fri"] = $total; break;
        case 7: $weeklyData["Sat"] = $total; break;
        case 1: $weeklyData["Sun"] = $total; break;
    }
}

echo json_encode(array_values($weeklyData));
?>