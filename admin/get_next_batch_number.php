<?php

session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$itemId = (int)($_GET['item_id'] ?? 0);

if ($itemId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid inventory item.'
    ]);
    exit;
}

$currentYear = date('Y');
$batchPrefix = 'BAT-' . $currentYear . '-';

$stmt = mysqli_prepare(
    $conn,
    "SELECT batch_number
     FROM inventory_stock
     WHERE item_id = ?
       AND batch_number LIKE CONCAT(?, '%')"
);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'is',
    $itemId,
    $batchPrefix
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$maxSequence = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $batchNumber = $row['batch_number'];

    $parts = explode('-', $batchNumber);
    $lastPart = end($parts);

    if (ctype_digit($lastPart)) {

        $sequence = (int)$lastPart;

        if ($sequence > $maxSequence) {
            $maxSequence = $sequence;
        }
    }
}

mysqli_stmt_close($stmt);

$nextSequence = $maxSequence + 1;

$nextBatchNumber = $batchPrefix . str_pad(
    $nextSequence,
    3,
    '0',
    STR_PAD_LEFT
);

echo json_encode([
    'success' => true,
    'batch_number' => $nextBatchNumber
]);