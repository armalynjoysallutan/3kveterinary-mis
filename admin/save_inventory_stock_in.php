<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/audit_log.php';

function stockInResponse(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    stockInResponse(false, 'Invalid request method.');
}

$itemId = (int)($_POST['item_id'] ?? 0);
$batchNumber = '';
$quantity = (float)($_POST['quantity'] ?? 0);
$packagingType = trim($_POST['packaging_type'] ?? '');
$packageQuantity = (float)($_POST['package_quantity'] ?? 0);
$unitsPerPackage = (float)($_POST['units_per_package'] ?? 0);
$expirationDate = trim($_POST['expiration_date'] ?? '');
$dateReceived = trim($_POST['date_received'] ?? '');
$referenceNumber = trim($_POST['reference_number'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

if ($itemId <= 0) {
    stockInResponse(false, 'Invalid inventory item.');
}

if ($quantity <= 0) {
    stockInResponse(false, 'Quantity must be greater than 0.');
}

if ($packagingType === '') {
    stockInResponse(false, 'Packaging type is required.');
}

if ($packageQuantity <= 0) {
    stockInResponse(false, 'Number of packages must be greater than 0.');
}

if ($unitsPerPackage <= 0) {
    stockInResponse(false, 'Units per package must be greater than 0.');
}

$calculatedQuantity = $packageQuantity * $unitsPerPackage;

if (abs($calculatedQuantity - $quantity) > 0.0001) {
    stockInResponse(false, 'Actual quantity does not match the packaging details.');
}

if ($dateReceived === '') {
    stockInResponse(false, 'Date received is required.');
}

$dateObject = DateTime::createFromFormat('Y-m-d', $dateReceived);
if (!$dateObject || $dateObject->format('Y-m-d') !== $dateReceived) {
    stockInResponse(false, 'Invalid date received.');
}

if ($expirationDate !== '') {
    $expiryObject = DateTime::createFromFormat('Y-m-d', $expirationDate);
    if (!$expiryObject || $expiryObject->format('Y-m-d') !== $expirationDate) {
        stockInResponse(false, 'Invalid expiration date.');
    }
}

mysqli_begin_transaction($conn);

try {
    // Get the item details used by the stock master record.
    $itemStmt = mysqli_prepare($conn,
        "SELECT item_id, item_code, item_name, supplier_id, unit_cost, status
         FROM inventory_items
         WHERE item_id = ?
         LIMIT 1"
    );

    if (!$itemStmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($itemStmt, 'i', $itemId);
    mysqli_stmt_execute($itemStmt);
    $itemResult = mysqli_stmt_get_result($itemStmt);
    $item = $itemResult ? mysqli_fetch_assoc($itemResult) : null;
    mysqli_stmt_close($itemStmt);

    if (!$item) {
        throw new Exception('Inventory item not found.');
    }

    if ($item['status'] !== 'Active') {
        throw new Exception('This inventory item is inactive and cannot receive stock.');
    }

    $supplierId = $item['supplier_id'] !== null ? (int)$item['supplier_id'] : null;
    $unitCost = (float)$item['unit_cost'];

    // inventory_stock is the current stock master per item + batch.
    $stockStmt = mysqli_prepare($conn,
        "SELECT stock_id, quantity, expiration_date
         FROM inventory_stock
         WHERE item_id = ? AND batch_number = ?
         LIMIT 1
         FOR UPDATE"
    );

    if (!$stockStmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stockStmt, 'is', $itemId, $batchNumber);
    mysqli_stmt_execute($stockStmt);
    $stockResult = mysqli_stmt_get_result($stockStmt);
    $existingStock = $stockResult ? mysqli_fetch_assoc($stockResult) : null;
    mysqli_stmt_close($stockStmt);

        // =========================================================
    // CREATE A NEW BATCH FOR EVERY STOCK-IN DELIVERY
    // =========================================================

    // =========================================================
// AUTO-GENERATE BATCH NUMBER
// Format: BAT-YEAR-###
// Sequence is per inventory item and resets every year.
// =========================================================

$currentYear = date('Y');
$batchPrefix = 'BAT-' . $currentYear . '-';

$batchStmt = mysqli_prepare(
    $conn,
    "SELECT batch_number
     FROM inventory_stock
     WHERE item_id = ?
       AND batch_number LIKE CONCAT(?, '%')
     FOR UPDATE"
);

if (!$batchStmt) {
    throw new Exception(mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $batchStmt,
    'is',
    $itemId,
    $batchPrefix
);

mysqli_stmt_execute($batchStmt);

$batchResult = mysqli_stmt_get_result($batchStmt);

$maxSequence = 0;

while ($batchRow = mysqli_fetch_assoc($batchResult)) {

    $existingBatchNumber = $batchRow['batch_number'];

    $parts = explode('-', $existingBatchNumber);
    $lastPart = end($parts);

    if (ctype_digit($lastPart)) {
        $sequence = (int)$lastPart;

        if ($sequence > $maxSequence) {
            $maxSequence = $sequence;
        }
    }
}

mysqli_stmt_close($batchStmt);

$nextSequence = $maxSequence + 1;

$batchNumber = $batchPrefix . str_pad(
    $nextSequence,
    3,
    '0',
    STR_PAD_LEFT
);

    // Validate packaging details.
    if ($packagingType === '') {
        throw new Exception('Packaging type is required.');
    }

    if ($packageQuantity <= 0) {
        throw new Exception('Number of packages must be greater than 0.');
    }

    if ($unitsPerPackage <= 0) {
        throw new Exception('Units per package must be greater than 0.');
    }

    // Calculate the actual inventory quantity.
    $calculatedQuantity = $packageQuantity * $unitsPerPackage;

    if (abs($calculatedQuantity - $quantity) > 0.0001) {
        throw new Exception(
            'Actual quantity does not match the packaging details.'
        );
    }

    $newExpiry = $expirationDate !== ''
        ? $expirationDate
        : null;

    /*
     * IMPORTANT:
     * quantity_received = total units received in this delivery
     * quantity          = current/remaining stock for this batch
     */

    $insertStock = mysqli_prepare(
        $conn,
        "INSERT INTO inventory_stock
        (
            item_id,
            batch_number,
            packaging_type,
            package_quantity,
            units_per_package,
            quantity_received,
            quantity,
            expiration_date,
            date_received,
            supplier_id,
            unit_cost
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$insertStock) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $insertStock,
        'issddddssid',
        $itemId,
        $batchNumber,
        $packagingType,
        $packageQuantity,
        $unitsPerPackage,
        $quantity,
        $quantity,
        $newExpiry,
        $dateReceived,
        $supplierId,
        $unitCost
    );

    if (!mysqli_stmt_execute($insertStock)) {
        $message = mysqli_error($conn);
        mysqli_stmt_close($insertStock);
        throw new Exception($message);
    }

    mysqli_stmt_close($insertStock);

    $stockId = mysqli_insert_id($conn);

    // Log every Stock In transaction separately.
    $logStmt = mysqli_prepare($conn,
        "INSERT INTO inventory_stock_in
         (stock_id, quantity, date_received, reference_number, remarks)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$logStmt) throw new Exception(mysqli_error($conn));

    mysqli_stmt_bind_param(
        $logStmt,
        'idsss',
        $stockId,
        $quantity,
        $dateReceived,
        $referenceNumber,
        $remarks
    );

    if (!mysqli_stmt_execute($logStmt)) {
        $message = mysqli_error($conn);
        mysqli_stmt_close($logStmt);
        throw new Exception($message);
    }
    mysqli_stmt_close($logStmt);

    mysqli_commit($conn);

    // =========================================================
// AUDIT LOG — STOCK IN
// =========================================================

logAudit(
    $conn,
    "Inventory",
    "Stock In",
    "Inventory item \"" .
        $item['item_name'] .
        "\" received " .
        $quantity .
        " unit(s). Batch: " .
        $batchNumber .
        ".",
    $item['item_code']
);

    stockInResponse(true, 'Stock In recorded successfully.', [
        'stock_id' => $stockId
    ]);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    stockInResponse(false, $e->getMessage());
}
