<?php

header("Content-Type: application/json");

include("../config/database.php");

$response = [
    "success" => false,
    "message" => ""
];


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    $response["message"] =
        "Invalid request method.";

    echo json_encode($response);
    exit;
}


/*
|--------------------------------------------------------------------------
| GET DATA
|--------------------------------------------------------------------------
*/

$medication_id =
    (int)($_POST["medication_id"] ?? 0);

$medication_name =
    trim($_POST["medication_name"] ?? "");

$unit_price =
    trim($_POST["unit_price"] ?? "");

$status =
    trim($_POST["status"] ?? "Active");


/*
|--------------------------------------------------------------------------
| REQUIRED FIELDS
|--------------------------------------------------------------------------
*/

if (
    $medication_id <= 0 ||
    $medication_name === "" ||
    $unit_price === ""
) {

    $response["message"] =
        "Please complete all required fields.";

    echo json_encode($response);
    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE PRICE
|--------------------------------------------------------------------------
*/

if (
    !is_numeric($unit_price) ||
    (float)$unit_price < 0
) {

    $response["message"] =
        "Please enter a valid unit price.";

    echo json_encode($response);
    exit;
}


$unit_price =
    (float)$unit_price;


/*
|--------------------------------------------------------------------------
| VALIDATE STATUS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    "Active",
    "Inactive"
];

if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    $response["message"] =
        "Invalid medication status.";

    echo json_encode($response);
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK MEDICATION EXISTS
|--------------------------------------------------------------------------
*/

$checkStmt = mysqli_prepare(
    $conn,
    "SELECT medication_id
     FROM medications
     WHERE medication_id = ?
     LIMIT 1"
);


if (!$checkStmt) {

    $response["message"] =
        "Failed to prepare medication query.";

    echo json_encode($response);
    exit;
}


mysqli_stmt_bind_param(
    $checkStmt,
    "i",
    $medication_id
);


mysqli_stmt_execute(
    $checkStmt
);


$checkResult =
    mysqli_stmt_get_result(
        $checkStmt
    );


if (
    !$checkResult ||
    mysqli_num_rows($checkResult) === 0
) {

    mysqli_stmt_close(
        $checkStmt
    );

    $response["message"] =
        "Medication not found.";

    echo json_encode($response);
    exit;
}


mysqli_stmt_close(
    $checkStmt
);


/*
|--------------------------------------------------------------------------
| CHECK DUPLICATE NAME
|--------------------------------------------------------------------------
*/

$duplicateStmt = mysqli_prepare(
    $conn,
    "SELECT medication_id
     FROM medications
     WHERE medication_name = ?
     AND medication_id != ?
     LIMIT 1"
);


if (!$duplicateStmt) {

    $response["message"] =
        "Failed to prepare duplicate check.";

    echo json_encode($response);
    exit;
}


mysqli_stmt_bind_param(
    $duplicateStmt,
    "si",
    $medication_name,
    $medication_id
);


mysqli_stmt_execute(
    $duplicateStmt
);


$duplicateResult =
    mysqli_stmt_get_result(
        $duplicateStmt
    );


if (
    $duplicateResult &&
    mysqli_num_rows($duplicateResult) > 0
) {

    mysqli_stmt_close(
        $duplicateStmt
    );

    $response["message"] =
        "This medication already exists.";

    echo json_encode($response);
    exit;
}


mysqli_stmt_close(
    $duplicateStmt
);


/*
|--------------------------------------------------------------------------
| UPDATE MEDICATION
|--------------------------------------------------------------------------
*/

$updateStmt = mysqli_prepare(
    $conn,
    "UPDATE medications
     SET
        medication_name = ?,
        unit_price = ?,
        status = ?,
        updated_at = CURRENT_TIMESTAMP
     WHERE medication_id = ?"
);


if (!$updateStmt) {

    $response["message"] =
        "Failed to prepare update.";

    echo json_encode($response);
    exit;
}


mysqli_stmt_bind_param(
    $updateStmt,
    "sdsi",
    $medication_name,
    $unit_price,
    $status,
    $medication_id
);


if (
    mysqli_stmt_execute(
        $updateStmt
    )
) {

    $response["success"] =
        true;

    $response["message"] =
        "Medication updated successfully.";

} else {

    $response["message"] =
        "Failed to update medication: " .
        mysqli_stmt_error(
            $updateStmt
        );

}


mysqli_stmt_close(
    $updateStmt
);


echo json_encode(
    $response
);

?>