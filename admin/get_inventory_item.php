<?php

session_start();

header("Content-Type: application/json; charset=utf-8");


// =========================================================
// AUTHENTICATION
// =========================================================

if (!isset($_SESSION["admin_username"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();

}


// =========================================================
// DATABASE
// =========================================================

require_once __DIR__ . '/../config/database.php';


// =========================================================
// ITEM ID
// =========================================================

$itemId = (int)($_GET["item_id"] ?? 0);


if ($itemId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid inventory item."
    ]);

    exit();

}


// =========================================================
// GET ITEM INFORMATION
// =========================================================

$itemSql = "
    SELECT
        i.item_id,
        i.item_code,
        i.item_name,
        i.category_id,
        i.unit_id,
        i.supplier_id,
        i.reorder_level,
        i.unit_cost,
        i.retail_price,
        i.status,

        c.category_name,

        u.unit_name,
        u.abbreviation,

        s.supplier_name

    FROM inventory_items i

    INNER JOIN inventory_categories c
        ON c.category_id = i.category_id

    LEFT JOIN inventory_units u
        ON u.unit_id = i.unit_id

    LEFT JOIN inventory_suppliers s
        ON s.supplier_id = i.supplier_id

    WHERE i.item_id = ?

    LIMIT 1
";


$itemStmt = mysqli_prepare($conn, $itemSql);


if (!$itemStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to prepare item query."
    ]);

    exit();

}


mysqli_stmt_bind_param(
    $itemStmt,
    "i",
    $itemId
);


mysqli_stmt_execute($itemStmt);


$itemResult =
    mysqli_stmt_get_result($itemStmt);


$item =
    $itemResult
        ? mysqli_fetch_assoc($itemResult)
        : null;


mysqli_stmt_close($itemStmt);


if (!$item) {

    echo json_encode([
        "success" => false,
        "message" => "Inventory item not found."
    ]);

    exit();

}


// =========================================================
// GET ALL BATCHES
// =========================================================

$batchSql = "
    SELECT

        stock_id,

        batch_number,

        packaging_type,
        package_quantity,
        units_per_package,
        quantity_received,

        quantity,

        expiration_date,

        date_received,

        unit_cost

    FROM inventory_stock

    WHERE item_id = ?

    ORDER BY

        CASE
            WHEN quantity > 0
            THEN 0
            ELSE 1
        END ASC,

        CASE
            WHEN expiration_date IS NULL
            THEN 1
            ELSE 0
        END ASC,

        expiration_date ASC,

        stock_id ASC
";


$batchStmt =
    mysqli_prepare(
        $conn,
        $batchSql
    );


$batches = [];


if ($batchStmt) {

    mysqli_stmt_bind_param(
        $batchStmt,
        "i",
        $itemId
    );


    mysqli_stmt_execute(
        $batchStmt
    );


    $batchResult =
        mysqli_stmt_get_result(
            $batchStmt
        );


    if ($batchResult) {

        while (
            $batch =
            mysqli_fetch_assoc(
                $batchResult
            )
        ) {

            $quantity =
                (float)$batch["quantity"];


            // =============================================
            // DETERMINE BATCH STATUS
            // =============================================

            $status = "Available";

            $statusClass = "available";


            if ($quantity <= 0) {

                $status =
                    "Out of Stock";

                $statusClass =
                    "out-of-stock";

            }
            else if (
                !empty(
                    $batch["expiration_date"]
                )
                &&
                strtotime(
                    $batch["expiration_date"]
                ) < strtotime(
                    date("Y-m-d")
                )
            ) {

                $status =
                    "Expired";

                $statusClass =
                    "expired";

            }
            else if (
                !empty(
                    $batch["expiration_date"]
                )
                &&
                strtotime(
                    $batch["expiration_date"]
                ) <= strtotime(
                    "+30 days"
                )
            ) {

                $status =
                    "Expiring Soon";

                $statusClass =
                    "expiring-soon";

            }


            // =============================================
            // FORMAT DATES
            // =============================================

            $expirationDate = "—";

            if (
                !empty(
                    $batch["expiration_date"]
                )
            ) {

                $expirationDate =
                    date(
                        "M d, Y",
                        strtotime(
                            $batch["expiration_date"]
                        )
                    );

            }


            $dateReceived = "—";

            if (
                !empty(
                    $batch["date_received"]
                )
            ) {

                $dateReceived =
                    date(
                        "M d, Y",
                        strtotime(
                            $batch["date_received"]
                        )
                    );

            }


            $batches[] = [

                "stock_id" =>
                    (int)$batch["stock_id"],

                "batch_number" =>
                    $batch["batch_number"],

                "packaging_type" =>
                    (
                        empty($batch["packaging_type"]) ||
                        $batch["packaging_type"] === "0"
                    )
                        ? "—"
                        : $batch["packaging_type"] ?? "",

                "package_quantity" =>
                    (float)$batch["package_quantity"],

                "units_per_package" =>
                    (float)$batch["units_per_package"],

                "quantity_received" =>
                    (float)$batch["quantity_received"],
    
                "quantity" =>
                    $quantity,

                "expiration_date" =>
                    $batch["expiration_date"],

                "expiration_display" =>
                    $expirationDate,

                "date_received" =>
                    $batch["date_received"],

                "date_received_display" => 
                    (
                        empty($batch["date_received"]) ||
                        $batch["date_received"] === "0000-00-00"
                    )
                        ? "—"
                        : $dateReceived,

                "unit_cost" =>
                    (float)$batch["unit_cost"],

                "status" =>
                    $status,

                "status_class" =>
                    $statusClass

            ];

        }

    }


    mysqli_stmt_close(
        $batchStmt
    );

}


// =========================================================
// TOTAL STOCK
// =========================================================

$totalStock = 0;


foreach ($batches as $batch) {

    $totalStock +=
        (float)$batch["quantity"];

}


// =========================================================
// UNIT DISPLAY
// =========================================================

$unitDisplay = "—";


if (!empty($item["abbreviation"])) {

    $unitDisplay =
        $item["abbreviation"];

}
else if (!empty($item["unit_name"])) {

    $unitDisplay =
        $item["unit_name"];

}


// =========================================================
// RESPONSE
// =========================================================

echo json_encode([

    "success" => true,

    "item" => [

        "item_id" =>
            (int)$item["item_id"],

        "item_code" =>
            $item["item_code"],

        "item_name" =>
            $item["item_name"],

        "category_name" =>
            $item["category_name"],

        "subcategory_name" =>
            "—",

        "unit_display" =>
            $unitDisplay,

        "supplier_name" =>
            $item["supplier_name"] ?? "—",

        "reorder_level" =>
            (float)$item["reorder_level"],

        "unit_cost" =>
            (float)$item["unit_cost"],

        "retail_price" =>
            (float)$item["retail_price"],

        "total_stock" =>
            $totalStock,

        "status" =>
            $item["status"]

    ],

    "batches" =>
        $batches

]);

exit();

?>