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

$medication_name =
    trim($_POST["medication_name"] ?? "");

$unit_price =
    trim($_POST["unit_price"] ?? "");

$status =
    trim($_POST["status"] ?? "Active");


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (
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
| CHECK DUPLICATE MEDICATION
|--------------------------------------------------------------------------
*/

$duplicateStmt = mysqli_prepare(
    $conn,
    "SELECT medication_id
     FROM medications
     WHERE medication_name = ?
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
    "s",
    $medication_name
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
| INSERT MEDICATION
|--------------------------------------------------------------------------
*/

$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO medications
    (
        medication_name,
        unit_price,
        status
    )
    VALUES
    (
        ?,
        ?,
        ?
    )"
);


if (!$insertStmt) {

    $response["message"] =
        "Failed to prepare medication insert.";

    echo json_encode($response);
    exit;
}


mysqli_stmt_bind_param(
    $insertStmt,
    "sds",
    $medication_name,
    $unit_price,
    $status
);


if (
    mysqli_stmt_execute(
        $insertStmt
    )
) {

    $response["success"] =
        true;

    $response["message"] =
        "Medication added successfully.";

} else {

    $response["message"] =
        "Failed to save medication: " .
        mysqli_stmt_error(
            $insertStmt
        );

}


mysqli_stmt_close(
    $insertStmt
);


echo json_encode(
    $response
);

?>