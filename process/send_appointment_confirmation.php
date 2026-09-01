<?php

session_start();

require_once "../config/database.php";
require_once "../config/mail_config.php";

header("Content-Type: application/json");


/* =========================================================
   ADMIN / STAFF AUTHENTICATION
========================================================= */

if (
    !(
        isset($_SESSION["admin_id"]) ||
        isset($_SESSION["admin_username"]) ||
        (
            isset($_SESSION["account_id"]) &&
            isset($_SESSION["role"]) &&
            $_SESSION["role"] === "Staff"
        )
    )
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit;
}

$appointmentId = intval($_POST["appointmentId"] ?? 0);

if ($appointmentId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid appointment."
    ]);

    exit;
}


// ======================================
// GET APPOINTMENT INFORMATION
// ======================================

$sql = "
    SELECT
        a.appointment_id,
        a.service,
        a.appointment_date,
        a.appointment_time,
        a.appointment_type,

        c.owner_name,
        c.email,

        p.pet_name

    FROM appointments a

    INNER JOIN customers c
        ON a.customer_id = c.customer_id

    INNER JOIN pets p
        ON a.pet_id = p.pet_id

    WHERE a.appointment_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to prepare appointment query."
    ]);

    exit;
}

$stmt->bind_param(
    "i",
    $appointmentId
);

$stmt->execute();

$result = $stmt->get_result();

$appointment = $result->fetch_assoc();


// ======================================
// CHECK APPOINTMENT
// ======================================

if (!$appointment) {

    echo json_encode([
        "success" => false,
        "message" => "Appointment not found."
    ]);

    exit;
}


// ======================================
// CHECK EMAIL
// ======================================

$email = trim($appointment["email"] ?? "");

if ($email === "") {

    echo json_encode([
        "success" => true,
        "email_sent" => false,
        "message" =>
            "Appointment confirmed, but this customer has no registered email address."
    ]);

    exit;
}


// ======================================
// FORMAT DATE & TIME
// ======================================

$formattedDate = date(
    "F j, Y",
    strtotime($appointment["appointment_date"])
);

$formattedTime = date(
    "g:i A",
    strtotime($appointment["appointment_time"])
);


// ======================================
// CREATE EMAIL
// ======================================

try {

    $mail = getMailer();

    $mail->addAddress(
        $email,
        $appointment["owner_name"]
    );

    $mail->Subject =
        "Appointment Confirmation - 3K Pet Solution Animal Clinic";

    $ownerName =
        htmlspecialchars(
            $appointment["owner_name"],
            ENT_QUOTES,
            "UTF-8"
        );

    $petName =
        htmlspecialchars(
            $appointment["pet_name"],
            ENT_QUOTES,
            "UTF-8"
        );

    $service =
        htmlspecialchars(
            $appointment["service"],
            ENT_QUOTES,
            "UTF-8"
        );

    $appointmentType =
        htmlspecialchars(
            $appointment["appointment_type"],
            ENT_QUOTES,
            "UTF-8"
        );


    $mail->Body = "

        <div style='font-family: Arial, sans-serif; line-height: 1.6;'>

            <h2>
                Appointment Confirmation
            </h2>

            <p>
                Hello <strong>{$ownerName}</strong>,
            </p>

            <p>
                Your pet's appointment at
                <strong>
                    3K Pet Solution Animal Clinic
                </strong>
                has been confirmed.
            </p>

            <hr>

            <p>
                <strong>Pet:</strong>
                {$petName}
            </p>

            <p>
                <strong>Service:</strong>
                {$service}
            </p>

            <p>
                <strong>Date:</strong>
                {$formattedDate}
            </p>

            <p>
                <strong>Time:</strong>
                {$formattedTime}
            </p>

            <p>
                <strong>Appointment Type:</strong>
                {$appointmentType}
            </p>

            <hr>

            <p>
                Thank you for choosing
                3K Pet Solution Animal Clinic.
            </p>

        </div>

    ";


    $mail->AltBody =
        "Hello {$appointment["owner_name"]},

Your appointment at 3K Pet Solution Animal Clinic has been confirmed.

Pet: {$appointment["pet_name"]}
Service: {$appointment["service"]}
Date: {$formattedDate}
Time: {$formattedTime}
Appointment Type: {$appointment["appointment_type"]}

Thank you for choosing 3K Pet Solution Animal Clinic.";


    $mail->send();


    echo json_encode([
        "success" => true,
        "email_sent" => true,
        "message" =>
            "Appointment confirmation email sent successfully."
    ]);


} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "email_sent" => false,
        "message" =>
            "Appointment was confirmed, but the email could not be sent."
    ]);

}

?>