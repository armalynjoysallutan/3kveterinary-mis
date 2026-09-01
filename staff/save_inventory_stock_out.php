<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

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

$itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$stockId = filter_input(INPUT_POST, 'stock_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
$stockOutDate = trim($_POST['stock_out_date'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$referenceNumber = trim($_POST['reference_number'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

$allowedReasons = [
    'Used for Treatment/Procedure',
    'Dispensed/Sold',
    'Expired',
    'Damaged',
    'Lost/Missing',
    'Returned to Supplier',
    'Other'
];

if (!$itemId || !$stockId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select a valid inventory item and batch.']);
    exit();
}

if ($quantity === false || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0.']);
    exit();
}

if ($stockOutDate === '') {
    $stockOutDate = date('Y-m-d');
}

$dateObject = DateTime::createFromFormat('Y-m-d', $stockOutDate);
if (!$dateObject || $dateObject->format('Y-m-d') !== $stockOutDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Stock Out date.']);
    exit();
}

if (!in_array($reason, $allowedReasons, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select a valid reason for Stock Out.']);
    exit();
}

if (strlen($referenceNumber) > 100 || strlen($remarks) > 255) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Reference number or remarks are too long.']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    // Lock the selected batch so two Stock Out requests cannot overdraw it.
    $stockSql = "
        SELECT stock_id, item_id, batch_number, quantity, expiration_date
        FROM inventory_stock
        WHERE stock_id = ?
          AND item_id = ?
        FOR UPDATE
    ";

    $stockStmt = mysqli_prepare($conn, $stockSql);
    if (!$stockStmt) {
        throw new Exception('Unable to prepare stock validation.');
    }

    mysqli_stmt_bind_param($stockStmt, 'ii', $stockId, $itemId);
    mysqli_stmt_execute($stockStmt);
    $stockResult = mysqli_stmt_get_result($stockStmt);
    $stock = mysqli_fetch_assoc($stockResult);
    mysqli_stmt_close($stockStmt);

    // =========================================================
// GET ITEM DETAILS FOR AUDIT LOG
// =========================================================

$itemAuditStmt = mysqli_prepare(
    $conn,
    "
    SELECT item_code, item_name
    FROM inventory_items
    WHERE item_id = ?
    LIMIT 1
    "
);

$itemAudit = null;

if ($itemAuditStmt) {

    mysqli_stmt_bind_param(
        $itemAuditStmt,
        'i',
        $itemId
    );

    mysqli_stmt_execute(
        $itemAuditStmt
    );

    $itemAuditResult =
        mysqli_stmt_get_result(
            $itemAuditStmt
        );

    $itemAudit =
        $itemAuditResult
            ? mysqli_fetch_assoc($itemAuditResult)
            : null;

    mysqli_stmt_close(
        $itemAuditStmt
    );
}

    if (!$stock) {
        throw new Exception('The selected batch could not be found for this item.');
    }

    $available = (float)$stock['quantity'];

    if ($available <= 0) {
        throw new Exception('The selected batch is already out of stock.');
    }

    if ($quantity > $available) {
        throw new Exception('Stock Out quantity cannot exceed the available batch quantity of ' . rtrim(rtrim(number_format($available, 2, '.', ''), '0'), '.') . '.');
    }

    $newQuantity = $available - $quantity;

    $updateSql = "UPDATE inventory_stock SET quantity = ? WHERE stock_id = ? AND item_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    if (!$updateStmt) {
        throw new Exception('Unable to update inventory stock.');
    }

    mysqli_stmt_bind_param($updateStmt, 'dii', $newQuantity, $stockId, $itemId);
    if (!mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        throw new Exception('Unable to update inventory stock.');
    }
    mysqli_stmt_close($updateStmt);

    $insertSql = "
        INSERT INTO inventory_stock_out
            (stock_id, quantity, stock_out_date, reason, reference_number, remarks)
        VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))
    ";

    $insertStmt = mysqli_prepare($conn, $insertSql);
    if (!$insertStmt) {
        throw new Exception('Unable to prepare Stock Out transaction.');
    }

    mysqli_stmt_bind_param(
        $insertStmt,
        'idssss',
        $stockId,
        $quantity,
        $stockOutDate,
        $reason,
        $referenceNumber,
        $remarks
    );

    if (!mysqli_stmt_execute($insertStmt)) {
        mysqli_stmt_close($insertStmt);
        throw new Exception('Unable to record Stock Out transaction.');
    }
    mysqli_stmt_close($insertStmt);

    mysqli_commit($conn);

    // =========================================================
// AUDIT LOG — STOCK OUT
// =========================================================

if ($itemAudit) {

    logAudit(
        $conn,
        "Inventory",
        "Stock Out",
        "Inventory item \"" .
            $itemAudit["item_name"] .
            "\" had " .
            $quantity .
            " unit(s) removed. Batch: " .
            $stock["batch_number"] .
            ". Reason: " .
            $reason .
            ".",
        $itemAudit["item_code"]
    );

}

    $remainingText = rtrim(rtrim(number_format($newQuantity, 2, '.', ''), '0'), '.');
    echo json_encode([
        'success' => true,
        'message' => 'Stock Out recorded successfully. Remaining batch quantity: ' . $remainingText . '.',
        'remaining_quantity' => $newQuantity
    ]);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
