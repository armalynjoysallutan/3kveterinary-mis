<?php

header("Content-Type: application/json");

include("../config/database.php");

$response = [
    "success" => false,
    "message" => ""
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response["message"] = "Invalid request method.";
    echo json_encode($response);
    exit;
}

$category_name = trim($_POST["category_name"] ?? "");
$service_name  = trim($_POST["service_name"] ?? "");
$pricing_type  = trim($_POST["pricing_type"] ?? "");
$fixed_price   = trim($_POST["fixed_price"] ?? "");
$status        = trim($_POST["status"] ?? "Active");


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $category_name === "" ||
    $service_name === "" ||
    $pricing_type === ""
) {
    $response["message"] = "Please complete all required fields.";
    echo json_encode($response);
    exit;
}

$allowedPricingTypes = [
    "Fixed",
    "Weight-Based",
    "Manual / Variable"
];

if (!in_array($pricing_type, $allowedPricingTypes, true)) {
    $response["message"] = "Invalid pricing type.";
    echo json_encode($response);
    exit;
}

$allowedStatuses = [
    "Active",
    "Inactive"
];

if (!in_array($status, $allowedStatuses, true)) {
    $response["message"] = "Invalid status.";
    echo json_encode($response);
    exit;
}


/*
|--------------------------------------------------------------------------
| FIXED PRICE
|--------------------------------------------------------------------------
*/

if ($pricing_type === "Fixed") {

    if ($fixed_price === "") {
        $response["message"] = "Please enter the fixed price.";
        echo json_encode($response);
        exit;
    }

    if (!is_numeric($fixed_price) || (float)$fixed_price < 0) {
        $response["message"] = "Invalid fixed price.";
        echo json_encode($response);
        exit;
    }

    $fixed_price = (float)$fixed_price;

} else {

    $fixed_price = null;
}


/*
|--------------------------------------------------------------------------
| GET CATEGORY ID
|--------------------------------------------------------------------------
*/

$categoryStmt = mysqli_prepare(
    $conn,
    "SELECT category_id
     FROM service_categories
     WHERE category_name = ?
     AND status = 'Active'
     LIMIT 1"
);

if (!$categoryStmt) {
    $response["message"] = "Failed to prepare category query.";
    echo json_encode($response);
    exit;
}

mysqli_stmt_bind_param(
    $categoryStmt,
    "s",
    $category_name
);

mysqli_stmt_execute($categoryStmt);

$categoryResult = mysqli_stmt_get_result($categoryStmt);

if (!$categoryResult || mysqli_num_rows($categoryResult) === 0) {

    mysqli_stmt_close($categoryStmt);

    $response["message"] =
        "Selected service category does not exist.";

    echo json_encode($response);
    exit;
}

$categoryRow = mysqli_fetch_assoc($categoryResult);

$category_id = (int)$categoryRow["category_id"];

mysqli_stmt_close($categoryStmt);


/*
|--------------------------------------------------------------------------
| CHECK DUPLICATE SERVICE
|--------------------------------------------------------------------------
*/

$duplicateStmt = mysqli_prepare(
    $conn,
    "SELECT service_id
     FROM services
     WHERE service_name = ?
     AND category_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $duplicateStmt,
    "si",
    $service_name,
    $category_id
);

mysqli_stmt_execute($duplicateStmt);

$duplicateResult = mysqli_stmt_get_result($duplicateStmt);

if ($duplicateResult && mysqli_num_rows($duplicateResult) > 0) {

    mysqli_stmt_close($duplicateStmt);

    $response["message"] =
        "This service already exists under the selected category.";

    echo json_encode($response);
    exit;
}

mysqli_stmt_close($duplicateStmt);


/*
|--------------------------------------------------------------------------
| INSERT SERVICE
|--------------------------------------------------------------------------
*/

$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO services
    (
        category_id,
        service_name,
        pricing_type,
        fixed_price,
        status
    )
    VALUES (?, ?, ?, ?, ?)"
);

if (!$insertStmt) {
    $response["message"] = "Failed to prepare save query.";
    echo json_encode($response);
    exit;
}

mysqli_stmt_bind_param(
    $insertStmt,
    "issds",
    $category_id,
    $service_name,
    $pricing_type,
    $fixed_price,
    $status
);

if (mysqli_stmt_execute($insertStmt)) {

    $response["success"] = true;
    $response["message"] = "Service saved successfully.";

} else {

    $response["message"] =
        "Failed to save service: " .
        mysqli_stmt_error($insertStmt);
}

mysqli_stmt_close($insertStmt);

echo json_encode($response);

?>