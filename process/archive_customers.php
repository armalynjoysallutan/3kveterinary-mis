<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["admin_username"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();
}

require_once "../config/database.php";
require_once "../config/audit_log.php";


/* =========================================================
   ONLY POST REQUESTS ARE ALLOWED
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
   GET CUSTOMER IDS
   ========================================================= */

$customerIds = $_POST["customer_ids"] ?? [];

if (!is_array($customerIds)) {
    $customerIds = [$customerIds];
}


/* =========================================================
   CLEAN CUSTOMER IDS
   ========================================================= */

$cleanCustomerIds = [];

foreach ($customerIds as $customerId) {

    $customerId = (int) $customerId;

    if ($customerId > 0) {
        $cleanCustomerIds[] = $customerId;
    }
}

$cleanCustomerIds = array_values(
    array_unique($cleanCustomerIds)
);


/* =========================================================
   VALIDATE CUSTOMER SELECTION
   ========================================================= */

if (empty($cleanCustomerIds)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "No customer records were selected."
    ]);

    exit();
}


/* =========================================================
   GET ARCHIVE REASON
   ========================================================= */

$archiveReason = trim(
    $_POST["archive_reason"] ?? ""
);

if ($archiveReason === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please provide a reason for archiving the selected customer records."
    ]);

    exit();
}


/* =========================================================
   REASON LENGTH VALIDATION
   ========================================================= */

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

    /* =====================================================
       PREPARE ARCHIVE QUERY
       ===================================================== */

    $sql = "
        UPDATE customers
        SET
            record_status = 'Archived',
            archived_at = CURRENT_TIMESTAMP(),
            archive_reason = ?,
            updated_at = CURRENT_TIMESTAMP()
        WHERE customer_id = ?
          AND record_status = 'Active'
    ";

    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    if (!$stmt) {
        throw new Exception(
            mysqli_error($conn)
        );
    }


    /* =====================================================
       ARCHIVE EACH SELECTED CUSTOMER
       ===================================================== */

    $archivedCount = 0;

    foreach ($cleanCustomerIds as $customerId) {

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $archiveReason,
            $customerId
        );

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(
                mysqli_stmt_error($stmt)
            );
        }

        $affectedRows =
            mysqli_stmt_affected_rows($stmt);

        if ($affectedRows > 0) {
            $archivedCount++;
        }
    }


    /* =====================================================
       CLOSE STATEMENT
       ===================================================== */

    mysqli_stmt_close($stmt);


    /* =====================================================
       COMMIT
       ===================================================== */

    mysqli_commit($conn);

    /* =====================================================
   AUDIT LOG
   ===================================================== */

  foreach ($cleanCustomerIds as $customerId) {

    logAudit(
        $conn,
        "Customer Records",
        "Archived",
        "Archived customer record",
        (string) $customerId
    );
}


    /* =====================================================
       SUCCESS RESPONSE
       ===================================================== */

    echo json_encode([
        "success" => true,
        "archived_count" => $archivedCount,
        "message" =>
            $archivedCount === 1
                ? "Customer record archived successfully."
                : $archivedCount . " customer records archived successfully."
    ]);

    exit();

} catch (Throwable $e) {

    /* =====================================================
       ROLLBACK IF SOMETHING FAILS
       ===================================================== */

    mysqli_rollback($conn);


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to archive the selected customer records."
    ]);

    exit();
}