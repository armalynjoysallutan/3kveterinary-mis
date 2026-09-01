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
$batchNumber = trim($_POST['batch_number'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$expirationDate = trim($_POST['expiration_date'] ?? '');
$dateReceived = trim($_POST['date_received'] ?? '');
$referenceNumber = trim($_POST['reference_number'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

if ($itemId <= 0) {
    stockInResponse(false, 'Invalid inventory item.');
}

if ($batchNumber === '') {
    stockInResponse(false, 'Batch number is required.');
}

if ($quantity <= 0) {
    stockInResponse(false, 'Quantity must be greater than 0.');
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

    $stockId = 0;

    if ($existingStock) {
        $stockId = (int)$existingStock['stock_id'];
        $oldExpiry = $existingStock['expiration_date'];
        $newExpiry = $expirationDate !== '' ? $expirationDate : null;

        // Do not allow the same item + batch to have conflicting expiration dates.
        if ($oldExpiry !== null && $newExpiry !== null && $oldExpiry !== $newExpiry) {
            throw new Exception(
                'This batch already has expiration date ' . date('M d, Y', strtotime($oldExpiry)) .
                '. Use the same batch expiration date or create a new batch number.'
            );
        }

        $newQuantity = (float)$existingStock['quantity'] + $quantity;

        if ($oldExpiry === null && $newExpiry !== null) {
            if ($supplierId === null) {
                $updateStock = mysqli_prepare($conn,
                    "UPDATE inventory_stock
                     SET quantity = ?, expiration_date = ?, date_received = ?, supplier_id = NULL, unit_cost = ?
                     WHERE stock_id = ?"
                );
                if (!$updateStock) throw new Exception(mysqli_error($conn));
                mysqli_stmt_bind_param($updateStock, 'dssdi', $newQuantity, $newExpiry, $dateReceived, $unitCost, $stockId);
            } else {
                $updateStock = mysqli_prepare($conn,
                    "UPDATE inventory_stock
                     SET quantity = ?, expiration_date = ?, date_received = ?, supplier_id = ?, unit_cost = ?
                     WHERE stock_id = ?"
                );
                if (!$updateStock) throw new Exception(mysqli_error($conn));
                mysqli_stmt_bind_param($updateStock, 'dssidi', $newQuantity, $newExpiry, $dateReceived, $supplierId, $unitCost, $stockId);
            }
        } else {
            if ($supplierId === null) {
                $updateStock = mysqli_prepare($conn,
                    "UPDATE inventory_stock
                     SET quantity = ?, date_received = ?, supplier_id = NULL, unit_cost = ?
                     WHERE stock_id = ?"
                );
                if (!$updateStock) throw new Exception(mysqli_error($conn));
                mysqli_stmt_bind_param($updateStock, 'dsdi', $newQuantity, $dateReceived, $unitCost, $stockId);
            } else {
                $updateStock = mysqli_prepare($conn,
                    "UPDATE inventory_stock
                     SET quantity = ?, date_received = ?, supplier_id = ?, unit_cost = ?
                     WHERE stock_id = ?"
                );
                if (!$updateStock) throw new Exception(mysqli_error($conn));
                mysqli_stmt_bind_param($updateStock, 'dsidi', $newQuantity, $dateReceived, $supplierId, $unitCost, $stockId);
            }
        }

        if (!mysqli_stmt_execute($updateStock)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($updateStock);
            throw new Exception($message);
        }
        mysqli_stmt_close($updateStock);
    } else {
        $newExpiry = $expirationDate !== '' ? $expirationDate : null;

        if ($supplierId === null && $newExpiry === null) {
            $insertStock = mysqli_prepare($conn,
                "INSERT INTO inventory_stock
                 (item_id, batch_number, quantity, expiration_date, date_received, supplier_id, unit_cost)
                 VALUES (?, ?, ?, NULL, ?, NULL, ?)"
            );
            if (!$insertStock) throw new Exception(mysqli_error($conn));
            mysqli_stmt_bind_param($insertStock, 'isdsd', $itemId, $batchNumber, $quantity, $dateReceived, $unitCost);
        } elseif ($supplierId === null) {
            $insertStock = mysqli_prepare($conn,
                "INSERT INTO inventory_stock
                 (item_id, batch_number, quantity, expiration_date, date_received, supplier_id, unit_cost)
                 VALUES (?, ?, ?, ?, ?, NULL, ?)"
            );
            if (!$insertStock) throw new Exception(mysqli_error($conn));
            mysqli_stmt_bind_param($insertStock, 'isdssd', $itemId, $batchNumber, $quantity, $newExpiry, $dateReceived, $unitCost);
        } elseif ($newExpiry === null) {
            $insertStock = mysqli_prepare($conn,
                "INSERT INTO inventory_stock
                 (item_id, batch_number, quantity, expiration_date, date_received, supplier_id, unit_cost)
                 VALUES (?, ?, ?, NULL, ?, ?, ?)"
            );
            if (!$insertStock) throw new Exception(mysqli_error($conn));
            mysqli_stmt_bind_param($insertStock, 'isdsid', $itemId, $batchNumber, $quantity, $dateReceived, $supplierId, $unitCost);
        } else {
            $insertStock = mysqli_prepare($conn,
                "INSERT INTO inventory_stock
                 (item_id, batch_number, quantity, expiration_date, date_received, supplier_id, unit_cost)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$insertStock) throw new Exception(mysqli_error($conn));
            mysqli_stmt_bind_param($insertStock, 'isdssid', $itemId, $batchNumber, $quantity, $newExpiry, $dateReceived, $supplierId, $unitCost);
        }

        if (!mysqli_stmt_execute($insertStock)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($insertStock);
            throw new Exception($message);
        }
        mysqli_stmt_close($insertStock);

        $stockId = mysqli_insert_id($conn);
    }

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
