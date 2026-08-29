<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

/* =========================================================
   CHECK ADMIN SESSION
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

require_once "../config/database.php";
require_once "../config/audit_log.php";


/* =========================================================
   ONLY POST REQUESTS
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
   GET ITEM IDS
   ========================================================= */

$itemIds = $_POST["item_ids"] ?? [];

if (!is_array($itemIds)) {
    $itemIds = [$itemIds];
}


/* =========================================================
   CLEAN ITEM IDS
   ========================================================= */

$cleanItemIds = [];

foreach ($itemIds as $itemId) {

    $itemId = (int)$itemId;

    if ($itemId > 0) {
        $cleanItemIds[] = $itemId;
    }
}

$cleanItemIds = array_values(
    array_unique($cleanItemIds)
);


/* =========================================================
   VALIDATE SELECTION
   ========================================================= */

if (empty($cleanItemIds)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "No inventory items were selected."
    ]);

    exit();
}


/* =========================================================
   GET ARCHIVE REASON
   ========================================================= */

$archiveReason = trim(
    (string)($_POST["archive_reason"] ?? "")
);

if ($archiveReason === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please provide a reason for archiving the selected inventory items."
    ]);

    exit();
}


if (mb_strlen($archiveReason) > 255) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Archive reason must not exceed 255 characters."
    ]);

    exit();
}


/* =========================================================
   START TRANSACTION
   ========================================================= */

mysqli_begin_transaction($conn);

try {

    $sql = "
        UPDATE inventory_items
        SET
            status = 'Archived',
            archived_at = CURRENT_TIMESTAMP(),
            archive_reason = ?,
            updated_at = CURRENT_TIMESTAMP()
        WHERE item_id = ?
          AND status = 'Active'
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }


    $archivedCount = 0;


    foreach ($cleanItemIds as $itemId) {

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $archiveReason,
            $itemId
        );

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }


        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $archivedCount++;
        }
    }


    mysqli_stmt_close($stmt);


    if ($archivedCount === 0) {

        throw new Exception(
            "The selected inventory items were not found or are already archived."
        );
    }


    mysqli_commit($conn);

    foreach ($cleanItemIds as $itemId) {

    $auditSql = "
        SELECT
            item_code,
            item_name
        FROM inventory_items
        WHERE item_id = ?
    ";

    $auditStmt = mysqli_prepare(
        $conn,
        $auditSql
    );

    if ($auditStmt) {

        mysqli_stmt_bind_param(
            $auditStmt,
            "i",
            $itemId
        );

        mysqli_stmt_execute(
            $auditStmt
        );

        $auditResult =
            mysqli_stmt_get_result(
                $auditStmt
            );

        $auditItem =
            mysqli_fetch_assoc(
                $auditResult
            );

        mysqli_stmt_close(
            $auditStmt
        );


        if ($auditItem) {

            logAudit(
                $conn,
                "Inventory",
                "Archived",
                "Inventory item \"" .
                    $auditItem["item_name"] .
                    "\" was archived. Reason: " .
                    $archiveReason,
                $auditItem["item_code"]
            );

        }

    }

}


    echo json_encode([
        "success" => true,
        "archived_count" => $archivedCount,
        "message" =>
            $archivedCount === 1
                ? "Inventory item archived successfully."
                : $archivedCount . " inventory items archived successfully."
    ]);

    exit();


} catch (Throwable $e) {

    mysqli_rollback($conn);

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to archive the selected inventory items."
    ]);

    exit();
}