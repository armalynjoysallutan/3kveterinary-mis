<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

/* =========================================================
   AUTHENTICATION
   ========================================================= */

if (!isset($_SESSION["admin_username"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();
}


/* =========================================================
   DATABASE
   ========================================================= */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/audit_log.php';

if (!isset($conn) || !$conn) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed."
    ]);

    exit();
}


/* =========================================================
   GET ITEM
   Used when opening Edit modal
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $itemId = (int)($_GET["item_id"] ?? 0);

    if ($itemId <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Invalid inventory item."
        ]);

        exit();
    }


    $sql = "
        SELECT
            item_id,
            item_code,
            item_name,
            category_id,
            unit_id,
            supplier_id,
            reorder_level,
            unit_cost,
            retail_price,
            status
        FROM inventory_items
        WHERE item_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to prepare item request."
        ]);

        exit();
    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $itemId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $item = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if (!$item) {

        echo json_encode([
            "success" => false,
            "message" => "Inventory item not found."
        ]);

        exit();
    }


    echo json_encode([
        "success" => true,
        "item" => $item
    ]);

    exit();
}


/* =========================================================
   ONLY POST ALLOWED FOR UPDATE
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
   GET FORM DATA
   ========================================================= */

$itemId = (int)($_POST["item_id"] ?? 0);

$itemCode = trim(
    $_POST["item_code"] ?? ""
);

$itemName = trim(
    $_POST["item_name"] ?? ""
);

$categoryId = (int)(
    $_POST["category_id"] ?? 0
);

$unitId = (
    isset($_POST["unit_id"]) &&
    $_POST["unit_id"] !== ""
)
    ? (int)$_POST["unit_id"]
    : null;

$supplierId = (
    isset($_POST["supplier_id"]) &&
    $_POST["supplier_id"] !== ""
)
    ? (int)$_POST["supplier_id"]
    : null;

$reorderLevel = (float)(
    $_POST["reorder_level"] ?? 0
);

$unitCost = (float)(
    $_POST["unit_cost"] ?? 0
);

$retailPrice = (float)(
    $_POST["retail_price"] ?? 0
);


/* =========================================================
   VALIDATION
   ========================================================= */

if ($itemId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid inventory item."
    ]);

    exit();
}


if (
    $itemCode === "" ||
    $itemName === "" ||
    $categoryId <= 0
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Please complete Item ID, Item Name and Category."
    ]);

    exit();
}


if (
    $reorderLevel < 0 ||
    $unitCost < 0 ||
    $retailPrice < 0
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Numeric values cannot be negative."
    ]);

    exit();
}


/* =========================================================
   CHECK IF ITEM EXISTS
   ========================================================= */

$checkItem = mysqli_prepare(
    $conn,
    "
        SELECT item_id
        FROM inventory_items
        WHERE item_id = ?
        LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $checkItem,
    "i",
    $itemId
);

mysqli_stmt_execute($checkItem);

$checkResult = mysqli_stmt_get_result(
    $checkItem
);

$existingItem = mysqli_fetch_assoc(
    $checkResult
);

mysqli_stmt_close($checkItem);


if (!$existingItem) {

    echo json_encode([
        "success" => false,
        "message" => "Inventory item not found."
    ]);

    exit();
}


/* =========================================================
   CHECK DUPLICATE ITEM CODE
   ========================================================= */

$checkCode = mysqli_prepare(
    $conn,
    "
        SELECT item_id
        FROM inventory_items
        WHERE item_code = ?
        AND item_id <> ?
        LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $checkCode,
    "si",
    $itemCode,
    $itemId
);

mysqli_stmt_execute($checkCode);

$codeResult = mysqli_stmt_get_result(
    $checkCode
);

$duplicateCode = mysqli_fetch_assoc(
    $codeResult
);

mysqli_stmt_close($checkCode);


if ($duplicateCode) {

    echo json_encode([
        "success" => false,
        "message" =>
            "That Item ID is already being used by another inventory item."
    ]);

    exit();
}


/* =========================================================
   UPDATE INVENTORY ITEM
   ========================================================= */

$sql = "
    UPDATE inventory_items
    SET
        item_code = ?,
        item_name = ?,
        category_id = ?,
        unit_id = ?,
        supplier_id = ?,
        reorder_level = ?,
        unit_cost = ?,
        retail_price = ?
    WHERE item_id = ?
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to prepare inventory update."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $stmt,
    "ssiiidddi",
    $itemCode,
    $itemName,
    $categoryId,
    $unitId,
    $supplierId,
    $reorderLevel,
    $unitCost,
    $retailPrice,
    $itemId
);


/* =========================================================
   EXECUTE UPDATE
   ========================================================= */

if (!mysqli_stmt_execute($stmt)) {

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to update inventory item.",
        "error" => $error
    ]);

    exit();
}


mysqli_stmt_close($stmt);

// =========================================================
// AUDIT LOG — EDIT INVENTORY ITEM
// =========================================================

logAudit(
    $conn,
    "Inventory",
    "Updated",
    "Inventory item \"" .
        $itemName .
        "\" was updated.",
    $itemCode
);


/* =========================================================
   SUCCESS
   ========================================================= */

echo json_encode([
    "success" => true,
    "message" =>
        "Inventory item updated successfully."
]);

exit();
?>