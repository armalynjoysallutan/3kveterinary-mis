<?php

session_start();

require_once "../config/database.php";
require_once "../config/audit_log.php";

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


/* =========================================================
   REQUEST METHOD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);

    exit();
}


/* =========================================================
   OWNER INFORMATION
========================================================= */

$lastName =
    trim($_POST["owner_last_name"] ?? "");

$firstName =
    trim($_POST["owner_first_name"] ?? "");

$middleName =
    trim($_POST["owner_middle_name"] ?? "");

$contactNumber =
    trim($_POST["contact_number"] ?? "");

$email =
    trim($_POST["email"] ?? "");

$address =
    trim($_POST["address"] ?? "");


/* =========================================================
   PET INFORMATION
========================================================= */

$petName =
    trim($_POST["pet_name"] ?? "");

$speciesId =
    (int)($_POST["species"] ?? 0);

$breedId =
    (int)($_POST["breed"] ?? 0);

$otherBreed =
    trim($_POST["other_breed"] ?? "");

$color =
    trim($_POST["color"] ?? "");

$gender =
    trim($_POST["gender"] ?? "");

$weightInput =
    trim($_POST["weight"] ?? "");

$dateOfBirthInput =
    trim($_POST["date_of_birth"] ?? "");


/* =========================================================
   REQUIRED VALIDATION
========================================================= */

if (
    $lastName === "" ||
    $firstName === "" ||
    $contactNumber === "" ||
    $petName === "" ||
    $speciesId <= 0
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please complete all required fields."
    ]);

    exit();
}


/* =========================================================
   CONTACT NUMBER VALIDATION
========================================================= */

if (!preg_match("/^[0-9]{11}$/", $contactNumber)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Contact number must contain exactly 11 digits."
    ]);

    exit();
}


/* =========================================================
   EMAIL VALIDATION
========================================================= */

if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);

    exit();
}


/* =========================================================
   SPECIES VALIDATION
   Species comes from System Variables.
========================================================= */

$speciesStmt = mysqli_prepare(
    $conn,
    "
    SELECT
        species
    FROM pet_species
    WHERE species_id = ?
      AND status = 'Active'
    LIMIT 1
    "
);

if (!$speciesStmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to verify selected species."
    ]);

    exit();
}

mysqli_stmt_bind_param(
    $speciesStmt,
    "i",
    $speciesId
);

mysqli_stmt_execute($speciesStmt);

$speciesResult =
    mysqli_stmt_get_result($speciesStmt);

$speciesRow =
    $speciesResult
        ? mysqli_fetch_assoc($speciesResult)
        : null;

mysqli_stmt_close($speciesStmt);


if (!$speciesRow) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Selected species does not exist or is inactive."
    ]);

    exit();
}

$species =
    trim($speciesRow["species"]);


/* =========================================================
   BREED VALIDATION
   Breed comes from System Variables.
========================================================= */

$breed = "";


/* ---------------------------------------------------------
   OTHERS = MANUAL BREED
--------------------------------------------------------- */

if ($breedId === 0 && $otherBreed !== "") {

    $breed = $otherBreed;

}


/* ---------------------------------------------------------
   NORMAL SYSTEM VARIABLE BREED
--------------------------------------------------------- */

elseif ($breedId > 0) {

    $breedStmt = mysqli_prepare(
        $conn,
        "
        SELECT
            breed
        FROM pet_breeds
        WHERE breed_id = ?
          AND species_id = ?
          AND status = 'Active'
        LIMIT 1
        "
    );

    if (!$breedStmt) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to verify selected breed."
        ]);

        exit();
    }

    mysqli_stmt_bind_param(
        $breedStmt,
        "ii",
        $breedId,
        $speciesId
    );

    mysqli_stmt_execute($breedStmt);

    $breedResult =
        mysqli_stmt_get_result($breedStmt);

    $breedRow =
        $breedResult
            ? mysqli_fetch_assoc($breedResult)
            : null;

    mysqli_stmt_close($breedStmt);


    if (!$breedRow) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Selected breed does not belong to the selected species."
        ]);

        exit();
    }

    $breed =
        trim($breedRow["breed"]);
}


/* ---------------------------------------------------------
   NO BREED SELECTED
--------------------------------------------------------- */

else {

    $breed = "";
}


/* =========================================================
   OTHERS VALIDATION
========================================================= */

if (
    $breedId === 0 &&
    $otherBreed === "" &&
    isset($_POST["breed"]) &&
    trim($_POST["breed"]) === "Others"
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please specify the breed."
    ]);

    exit();
}


/* =========================================================
   WEIGHT
========================================================= */

$weight = null;

if ($weightInput !== "") {

    if (!is_numeric($weightInput) || (float)$weightInput < 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Please enter a valid weight."
        ]);

        exit();
    }

    $weight = (float)$weightInput;
}


/* =========================================================
   DATE OF BIRTH
   The input is YYYY-MM.
   Store internally as YYYY-MM-01.
========================================================= */

$dateOfBirth = null;

if ($dateOfBirthInput !== "") {

    if (
        !preg_match(
            '/^\d{4}-(0[1-9]|1[0-2])$/',
            $dateOfBirthInput
        )
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid date of birth."
        ]);

        exit();
    }

    $dateOfBirth =
        $dateOfBirthInput . "-01";
}


/* =========================================================
   OWNER NAME
========================================================= */

$ownerNameParts = [];

if ($firstName !== "") {
    $ownerNameParts[] = $firstName;
}

if ($middleName !== "") {
    $ownerNameParts[] = $middleName;
}

if ($lastName !== "") {
    $ownerNameParts[] = $lastName;
}

$ownerName =
    implode(" ", $ownerNameParts);


/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction($conn);


try {

    /* =====================================================
       CREATE CUSTOMER
       ADMIN-REGISTERED CUSTOMER = ACTIVE
    ===================================================== */

    $customerSql = "
        INSERT INTO customers
        (
            owner_name,
            contact_number,
            email,
            address,
            record_status
        )
        VALUES (?, ?, ?, ?, 'Active')
    ";

    $customerStmt =
        mysqli_prepare(
            $conn,
            $customerSql
        );

    if (!$customerStmt) {

        throw new Exception(
            "Unable to prepare customer record."
        );
    }

    mysqli_stmt_bind_param(
        $customerStmt,
        "ssss",
        $ownerName,
        $contactNumber,
        $email,
        $address
    );

    if (!mysqli_stmt_execute($customerStmt)) {

        throw new Exception(
            mysqli_stmt_error($customerStmt)
        );
    }

    $customerId =
        mysqli_insert_id($conn);

    mysqli_stmt_close($customerStmt);


    /* =====================================================
       CREATE PET
    ===================================================== */

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
        mysqli_prepare(
            $conn,
            $petSql
        );

    if (!$petStmt) {

        throw new Exception(
            "Unable to prepare pet record."
        );
    }


    mysqli_stmt_bind_param(
        $petStmt,
        "isssssds",
        $customerId,
        $petName,
        $species,
        $breed,
        $color,
        $gender,
        $weight,
        $dateOfBirth
    );


    if (!mysqli_stmt_execute($petStmt)) {

        throw new Exception(
            mysqli_stmt_error($petStmt)
        );
    }


    $petId =
        mysqli_insert_id($conn);

    mysqli_stmt_close($petStmt);


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit($conn);


    /* =====================================================
       AUDIT LOG
    ===================================================== */

    logAudit(
        $conn,
        "Customer Records",
        "Created",
        "Added new customer: " . $ownerName,
        (string)$customerId
    );


    /* =====================================================
       SUCCESS RESPONSE
    ===================================================== */

    echo json_encode([
        "success" => true,
        "message" => "Customer and pet saved successfully.",
        "customer_id" => $customerId,
        "pet_id" => $petId
    ]);

    exit();


} catch (Throwable $e) {

    mysqli_rollback($conn);

    error_log(
        "Save Customer Error: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);

    exit();
}

?>