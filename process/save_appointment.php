<?php

session_start();

require_once "../config/database.php";

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


// ======================================
// APPOINTMENT INFORMATION
// ======================================

$serviceCategory = trim($_POST["serviceCategory"] ?? "");
$service = trim($_POST["service"] ?? "");
$appointmentDate = trim($_POST["appointmentDate"] ?? "");
$appointmentTime = trim($_POST["appointmentTime"] ?? "");
$appointmentType = trim($_POST["appointmentType"] ?? "");
$reason = trim($_POST["reason"] ?? "");


// ======================================
// CONVERT APPOINTMENT TIME TO 24-HOUR FORMAT
// ======================================

if ($appointmentTime !== "") {

    $timeObject = DateTime::createFromFormat(
        "g:i A",
        $appointmentTime
    );

    if ($timeObject === false) {

        echo json_encode([
            "success" => false,
            "message" => "Invalid appointment time."
        ]);

        exit;
    }

    $appointmentTime = $timeObject->format("H:i:s");
}


// ======================================
// NEW CLIENT INFORMATION
// ======================================

$ownerName = trim($_POST["ownerName"] ?? "");
$contactNumber = trim($_POST["contactNumber"] ?? "");
$email = trim($_POST["email"] ?? "");
$address = trim($_POST["address"] ?? "");


// ======================================
// PET INFORMATION
// ======================================

$petName = trim($_POST["petName"] ?? "");
$species = trim($_POST["species"] ?? "");
$breed = trim($_POST["breed"] ?? "");
$otherBreed = trim($_POST["otherBreed"] ?? "");
$color = trim($_POST["color"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$weight = $_POST["weight"] ?? null;

$dateOfBirthInput = trim($_POST["dateOfBirth"] ?? "");
$dateOfBirth = null;

if ($dateOfBirthInput !== "") {

    if (
        !preg_match(
            '/^\d{4}-(0[1-9]|1[0-2])$/',
            $dateOfBirthInput
        )
    ) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid date of birth format."
        ]);

        exit;
    }

    $dateOfBirth = $dateOfBirthInput . "-01";
}



// ======================================
// USE CUSTOM BREED
// ======================================

if ($breed === "Others") {

    $breed = $otherBreed;

}


// ======================================
// CLIENT TYPE
// ======================================

$customerId =
    intval($_POST["customer_id"] ?? 0);

$petId =
    intval($_POST["pet_id"] ?? 0);


// ======================================
// EXISTING CLIENT + NEW PET FLAG
// ======================================

$newPetForExisting =
    ($_POST["newPetForExisting"] ?? "0") === "1";


// ======================================
// DETERMINE CLIENT TYPE
// ======================================

$isExistingClient =
    $customerId > 0;


// ======================================
// BASIC SERVER VALIDATION
// ======================================

if (
    $serviceCategory === "" ||
    $service === "" ||
    $appointmentDate === "" ||
    $appointmentTime === "" ||
    $appointmentType === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "Please complete all required fields."
    ]);

    exit;
}


// ======================================
// EXISTING CLIENT VALIDATION
// ======================================

if ($isExistingClient) {

    // ==================================
    // OWNER MUST EXIST
    // ==================================

    if ($customerId <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Please select an existing client."
        ]);

        exit;
    }


    // ==================================
    // EXISTING OWNER + NEW PET
    // ==================================

    if ($newPetForExisting) {

        if (
            $petName === "" ||
            $species === "" ||
            $breed === "" ||
            $gender === "" ||
            $weight === null ||
            $weight === "" 
        ) {

            echo json_encode([
                "success" => false,
                "message" => "Please complete all required pet fields."
            ]);

            exit;
        }

    }


    // ==================================
    // EXISTING OWNER + EXISTING PET
    // ==================================

    else {

        if ($petId <= 0) {

            echo json_encode([
                "success" => false,
                "message" => "Please select an existing pet."
            ]);

            exit;
        }

    }

}


// ======================================
// NEW CLIENT VALIDATION
// ======================================

else {

    if (
        $ownerName === "" ||
        $contactNumber === "" ||
        $petName === "" ||
        $species === ""
    ) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Please complete all required client and pet fields."
        ]);

        exit;
    }

}


// ======================================
// START TRANSACTION
// ======================================

$conn->begin_transaction();


try {
    // ==================================
    // CHECK APPOINTMENT SLOT CAPACITY
    // MAXIMUM 3 ACTIVE APPOINTMENTS
    // PER DATE + TIME
    // ==================================

    $slotSql = "
        SELECT COUNT(*) AS booked_count
        FROM appointments
        WHERE appointment_date = ?
        AND appointment_time = ?
        AND is_archived = 0
        AND status IN (
            'Pending',
            'Confirmed',
            'Arrived'
        )
    ";

    $slotStmt = $conn->prepare($slotSql);

    if (!$slotStmt) {

        throw new Exception(
            "Appointment slot check failed."
        );

    }

    $slotStmt->bind_param(
        "ss",
        $appointmentDate,
        $appointmentTime
    );

    $slotStmt->execute();

    $slotResult =
        $slotStmt->get_result();

    $slotRow =
        $slotResult->fetch_assoc();

    $bookedCount =
        intval($slotRow["booked_count"]);

    $slotStmt->close();


    // ==================================
    // SLOT IS FULL
    // ==================================

    if ($bookedCount >= 3) {

        throw new Exception(
            "This appointment time is already full. Please select another available time."
        );

    }




    // ==================================
    // EXISTING CLIENT
    // ==================================

    if ($isExistingClient) {


        // ==================================
        // EXISTING CLIENT + NEW PET
        // ==================================

        if ($newPetForExisting) {


            $petSql = "
                INSERT INTO pets
                (
                    customer_id,
                    pet_name,
                    species,
                    breed,
                    color,
                    gender,
                    weight,
                    date_of_birth
                    
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";


            $petStmt =
                $conn->prepare($petSql);


            if (!$petStmt) {

                throw new Exception(
                    "Pet statement failed."
                );

            }


            $petStmt->bind_param(
                "isssssds",
                $customerId,
                $petName,
                $species,
                $breed,
                $color,
                $gender,
                $weight,
                $dateOfBirth,
                
            );


            $petStmt->execute();


            $petId =
                $conn->insert_id;


        }


        // ==================================
        // EXISTING CLIENT + EXISTING PET
        // ==================================

        else {


            // Verify that the selected pet
            // actually belongs to the selected customer

            $checkPetSql = "
                SELECT pet_id
                FROM pets
                WHERE pet_id = ?
                AND customer_id = ?
            ";


            $checkPetStmt =
                $conn->prepare($checkPetSql);


            if (!$checkPetStmt) {

                throw new Exception(
                    "Pet verification failed."
                );

            }


            $checkPetStmt->bind_param(
                "ii",
                $petId,
                $customerId
            );


            $checkPetStmt->execute();


            $checkPetResult =
                $checkPetStmt->get_result();


            if ($checkPetResult->num_rows === 0) {

                throw new Exception(
                    "Selected pet does not belong to the selected client."
                );

            }

        }

    }


    // ==================================
    // NEW CLIENT
    // ==================================

    else {


        // ==================================
        // CREATE CUSTOMER
        // STATUS = PENDING
        // ==================================

        $customerSql = "
            INSERT INTO customers
            (
                owner_name,
                contact_number,
                email,
                address,
                record_status
            )
            VALUES (?, ?, ?, ?, 'Pending')
        ";


        $customerStmt =
            $conn->prepare($customerSql);


        if (!$customerStmt) {

            throw new Exception(
                "Customer statement failed."
            );

        }


        $customerStmt->bind_param(
            "ssss",
            $ownerName,
            $contactNumber,
            $email,
            $address
        );


        $customerStmt->execute();


        $customerId =
            $conn->insert_id;



        // ==================================
        // CREATE PET
        // ==================================

        $petSql = "
            INSERT INTO pets
            (
                customer_id,
                pet_name,
                species,
                breed,
                color,
                gender,
                weight,
                date_of_birth
                
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";


        $petStmt =
            $conn->prepare($petSql);


        if (!$petStmt) {

            throw new Exception(
                "Pet statement failed."
            );

        }


        $petStmt->bind_param(
            "isssssds",
            $customerId,
            $petName,
            $species,
            $breed,
            $color,
            $gender,
            $weight,
            $dateOfBirth,

        );


        $petStmt->execute();


        $petId =
            $conn->insert_id;

    }



    // ==================================
    // CREATE APPOINTMENT
    // STATUS = PENDING
    // ==================================

    $appointmentSql = "
        INSERT INTO appointments
        (
            customer_id,
            pet_id,
            service_category,
            service,
            appointment_date,
            appointment_time,
            appointment_type,
            reason,
            status
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
    ";


    $appointmentStmt =
        $conn->prepare($appointmentSql);


    if (!$appointmentStmt) {

        throw new Exception(
            "Appointment statement failed."
        );

    }


    $appointmentStmt->bind_param(
        "iissssss",
        $customerId,
        $petId,
        $serviceCategory,
        $service,
        $appointmentDate,
        $appointmentTime,
        $appointmentType,
        $reason
    );


    $appointmentStmt->execute();


    $appointmentId =
        $conn->insert_id;



    // ==================================
    // EVERYTHING SUCCESSFUL
    // ==================================

    $conn->commit();


    echo json_encode([
        "success" => true,
        "message" => "Appointment booked successfully.",
        "appointment_id" => $appointmentId
    ]);


} catch (Throwable $e) {


    // Undo everything if something fails

    $conn->rollback();


    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

}

?>