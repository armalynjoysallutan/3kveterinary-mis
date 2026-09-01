<?php
session_start();

header("Content-Type: application/json; charset=utf-8");

if (
    !isset($_SESSION["account_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Staff"
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/audit_log.php';

$itemCode = trim($_POST["item_code"] ?? "");
$itemName = trim($_POST["item_name"] ?? "");
$categoryId = (int)($_POST["category_id"] ?? 0);
$subcategoryId = ($_POST["subcategory_id"] ?? "") !== "" ? (int)$_POST["subcategory_id"] : null;
$unitId = ($_POST["unit_id"] ?? "") !== "" ? (int)$_POST["unit_id"] : null;
$supplierId = ($_POST["supplier_id"] ?? "") !== "" ? (int)$_POST["supplier_id"] : null;
$reorderLevel = (float)($_POST["reorder_level"] ?? 0);
$unitCost = (float)($_POST["unit_cost"] ?? 0);
$retailPrice = (float)($_POST["retail_price"] ?? 0);

if ($itemCode === "" || $itemName === "" || $categoryId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Please complete Item ID, Item Name and Category."
    ]);
    exit();
}

if ($subcategoryId !== null) {
    $subcategoryCheck = mysqli_prepare(
        $conn,
        "SELECT subcategory_id
         FROM inventory_subcategories
         WHERE subcategory_id = ?
           AND category_id = ?
           AND status = 'Active'
         LIMIT 1"
    );

    if (!$subcategoryCheck) {
        echo json_encode([
            "success" => false,
            "message" => "Unable to validate the selected subcategory."
        ]);
        exit();
    }

    mysqli_stmt_bind_param($subcategoryCheck, "ii", $subcategoryId, $categoryId);
    mysqli_stmt_execute($subcategoryCheck);
    $subcategoryResult = mysqli_stmt_get_result($subcategoryCheck);

    if (!$subcategoryResult || mysqli_num_rows($subcategoryResult) === 0) {
        mysqli_stmt_close($subcategoryCheck);
        echo json_encode([
            "success" => false,
            "message" => "The selected subcategory does not belong to the selected category."
        ]);
        exit();
    }

    mysqli_stmt_close($subcategoryCheck);
}

if ($reorderLevel < 0 || $unitCost < 0 || $retailPrice < 0) {
    echo json_encode([
        "success" => false,
        "message" => "Numeric values cannot be negative."
    ]);
    exit();
}

$check = mysqli_prepare(
    $conn,
    "SELECT item_id FROM inventory_items WHERE item_code = ? LIMIT 1"
);

mysqli_stmt_bind_param($check, "s", $itemCode);
mysqli_stmt_execute($check);
$checkResult = mysqli_stmt_get_result($check);

if (mysqli_num_rows($checkResult) > 0) {
    echo json_encode([
        "success" => false,
        "message" => "That Item ID already exists."
    ]);
    exit();
}

$sql = "
    INSERT INTO inventory_items
    (
        item_code,
        item_name,
        category_id,
        subcategory_id,
        unit_id,
        supplier_id,
        reorder_level,
        unit_cost,
        retail_price,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare the inventory save request."
    ]);
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "ssiiiiddd",
    $itemCode,
    $itemName,
    $categoryId,
    $subcategoryId,
    $unitId,
    $supplierId,
    $reorderLevel,
    $unitCost,
    $retailPrice
);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to save inventory item: " . mysqli_error($conn)
    ]);
    exit();
}

// =========================================================
// AUDIT LOG — ADD INVENTORY ITEM
// =========================================================

$newItemId = mysqli_insert_id($conn);

logAudit(
    $conn,
    "Inventory",
    "Added",
    "New inventory item \"" .
        $itemName .
        "\" was added.",
    $itemCode
);

echo json_encode([
    "success" => true,
    "message" => "Inventory item saved successfully."
]);
