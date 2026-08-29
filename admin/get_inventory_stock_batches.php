<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$itemId = filter_input(INPUT_GET, 'item_id', FILTER_VALIDATE_INT);

if (!$itemId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid inventory item.']);
    exit();
}

$sql = "
    SELECT
        stock_id,
        batch_number,
        quantity,
        expiration_date,
        date_received
    FROM inventory_stock
    WHERE item_id = ?
      AND quantity > 0
    ORDER BY
        CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END ASC,
        expiration_date ASC,
        stock_id ASC
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to prepare batch query.']);
    exit();
}

mysqli_stmt_bind_param($stmt, 'i', $itemId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$batches = [];
while ($row = mysqli_fetch_assoc($result)) {
    $batches[] = [
        'stock_id' => (int)$row['stock_id'],
        'batch_number' => $row['batch_number'],
        'quantity' => (float)$row['quantity'],
        'expiration_date' => $row['expiration_date'],
        'date_received' => $row['date_received']
    ];
}

mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'batches' => $batches
]);
