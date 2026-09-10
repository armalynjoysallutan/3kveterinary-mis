<?php

session_start();

header("Content-Type: application/json");

require_once "../config/database.php";


/* =========================================================
   AUTHENTICATION
========================================================= */

if (
    !isset($_SESSION["admin_id"]) &&
    !isset($_SESSION["admin_username"]) &&
    !(
        isset($_SESSION["account_id"]) &&
        isset($_SESSION["role"]) &&
        $_SESSION["role"] === "Staff"
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
   GET POST DATA
========================================================= */

$billingItemId =
    isset($_POST["billing_item_id"])
        ? (int)$_POST["billing_item_id"]
        : 0;

$quantity =
    isset($_POST["quantity"])
        ? (float)$_POST["quantity"]
        : 0;


/* =========================================================
   VALIDATION
========================================================= */

if ($billingItemId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid billing item."
    ]);

    exit();
}


if ($quantity <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Quantity must be greater than zero."
    ]);

    exit();
}


/* =========================================================
   GET BILLING ITEM
========================================================= */

$sql = "
    SELECT
        bi.billing_item_id,
        bi.billing_id,
        bi.item_type,
        bi.unit_price,
        b.payment_status,
        b.billing_status

    FROM billing_items bi

    INNER JOIN billing b
        ON b.billing_id = bi.billing_id

    WHERE bi.billing_item_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare billing item query."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $billingItemId
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$item =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


if (!$item) {

    echo json_encode([
        "success" => false,
        "message" => "Billing item not found."
    ]);

    exit();
}


/* =========================================================
   PRODUCT ONLY
========================================================= */

if (
    strtolower(
        trim($item["item_type"])
    ) !== "product"
) {

    echo json_encode([
        "success" => false,
        "message" => "Only product quantities can be edited."
    ]);

    exit();
}


/* =========================================================
   PREVENT EDITING PAID BILLING
========================================================= */

$paymentStatus =
    strtolower(
        trim(
            $item["payment_status"] ?? ""
        )
    );

$billingStatus =
    strtolower(
        trim(
            $item["billing_status"] ?? ""
        )
    );


if (
    $paymentStatus === "paid" ||
    $billingStatus === "paid"
) {

    echo json_encode([
        "success" => false,
        "message" => "This billing has already been paid."
    ]);

    exit();
}


/* =========================================================
   COMPUTE NEW AMOUNT
========================================================= */

$unitPrice =
    (float)$item["unit_price"];

$newAmount =
    round(
        $quantity * $unitPrice,
        2
    );


/* =========================================================
   UPDATE BILLING ITEM
========================================================= */

$updateSql = "
    UPDATE billing_items

    SET
        quantity = ?,
        amount = ?

    WHERE billing_item_id = ?
";


$updateStmt =
    mysqli_prepare(
        $conn,
        $updateSql
    );


if (!$updateStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare update."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $updateStmt,
    "ddi",
    $quantity,
    $newAmount,
    $billingItemId
);


if (
    !mysqli_stmt_execute(
        $updateStmt
    )
) {

    mysqli_stmt_close($updateStmt);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update quantity."
    ]);

    exit();
}


mysqli_stmt_close(
    $updateStmt
);


/* =========================================================
   RECALCULATE BILLING TOTAL
========================================================= */

$totalSql = "
    SELECT
        COALESCE(
            SUM(amount),
            0
        ) AS total

    FROM billing_items

    WHERE billing_id = ?
";


$totalStmt =
    mysqli_prepare(
        $conn,
        $totalSql
    );


if (!$totalStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to calculate billing total."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $totalStmt,
    "i",
    $item["billing_id"]
);


mysqli_stmt_execute(
    $totalStmt
);


$totalResult =
    mysqli_stmt_get_result(
        $totalStmt
);


$totalData =
    mysqli_fetch_assoc(
        $totalResult
    );


mysqli_stmt_close(
    $totalStmt
);


$newTotal =
    (float)(
        $totalData["total"] ?? 0
    );


/* =========================================================
   UPDATE BILLING TOTAL
========================================================= */

$billingUpdateSql = "
    UPDATE billing

    SET
        total_amount = ?

    WHERE billing_id = ?
";


$billingUpdateStmt =
    mysqli_prepare(
        $conn,
        $billingUpdateSql
    );


if (!$billingUpdateStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to update billing total."
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $billingUpdateStmt,
    "di",
    $newTotal,
    $item["billing_id"]
);


if (
    !mysqli_stmt_execute(
        $billingUpdateStmt
    )
) {

    mysqli_stmt_close(
        $billingUpdateStmt
    );

    echo json_encode([
        "success" => false,
        "message" => "Failed to update billing total."
    ]);

    exit();
}


mysqli_stmt_close(
    $billingUpdateStmt
);


/* =========================================================
   SUCCESS
========================================================= */

echo json_encode([
    "success" => true,
    "message" => "Quantity updated successfully.",
    "quantity" => $quantity,
    "amount" => $newAmount,
    "total" => $newTotal
]);

exit();