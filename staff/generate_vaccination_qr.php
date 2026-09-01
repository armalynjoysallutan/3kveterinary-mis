<?php

session_start();
header("Content-Type: application/json; charset=UTF-8");

if (
    !isset($_SESSION["account_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Staff"
) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$petId = isset($_POST["pet_id"]) ? (int) $_POST["pet_id"] : 0;

if ($petId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid pet ID."]);
    exit;
}

$checkPet = mysqli_prepare($conn, "SELECT pet_id, pet_name FROM pets WHERE pet_id = ? LIMIT 1");
mysqli_stmt_bind_param($checkPet, "i", $petId);
mysqli_stmt_execute($checkPet);
$result = mysqli_stmt_get_result($checkPet);
$pet = mysqli_fetch_assoc($result);
mysqli_stmt_close($checkPet);

if (!$pet) {
    echo json_encode(["success" => false, "message" => "Pet record not found."]);
    exit;
}

$checkCertificate = mysqli_prepare(
    $conn,
    "SELECT certificate_id, qr_token, status FROM vaccination_certificates WHERE pet_id = ? LIMIT 1"
);
mysqli_stmt_bind_param($checkCertificate, "i", $petId);
mysqli_stmt_execute($checkCertificate);
$result = mysqli_stmt_get_result($checkCertificate);
$certificate = mysqli_fetch_assoc($result);
mysqli_stmt_close($checkCertificate);

if ($certificate && $certificate["status"] === "Generated" && !empty($certificate["qr_token"])) {
    $qrToken = $certificate["qr_token"];
    $certificateId = (int) $certificate["certificate_id"];
} else {
    do {
        $qrToken = bin2hex(random_bytes(32));
        $checkToken = mysqli_prepare(
            $conn,
            "SELECT certificate_id FROM vaccination_certificates WHERE qr_token = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($checkToken, "s", $qrToken);
        mysqli_stmt_execute($checkToken);
        $tokenResult = mysqli_stmt_get_result($checkToken);
        $tokenExists = mysqli_num_rows($tokenResult) > 0;
        mysqli_stmt_close($checkToken);
    } while ($tokenExists);

    if ($certificate) {
        $certificateId = (int) $certificate["certificate_id"];
        $update = mysqli_prepare(
            $conn,
            "UPDATE vaccination_certificates
             SET qr_token = ?, status = 'Generated', generated_at = NOW()
             WHERE certificate_id = ?"
        );
        mysqli_stmt_bind_param($update, "si", $qrToken, $certificateId);
        $saved = mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
    } else {
        $insert = mysqli_prepare(
            $conn,
            "INSERT INTO vaccination_certificates (pet_id, qr_token, status, generated_at)
             VALUES (?, ?, 'Generated', NOW())"
        );
        mysqli_stmt_bind_param($insert, "is", $petId, $qrToken);
        $saved = mysqli_stmt_execute($insert);
        $certificateId = mysqli_insert_id($conn);
        mysqli_stmt_close($insert);
    }

    if (!$saved) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to save QR certificate: " . mysqli_error($conn)
        ]);
        exit;
    }
}

$basePath = rtrim(dirname(dirname($_SERVER["SCRIPT_NAME"])), "/\\");
$scheme = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$qrUrl = $scheme . "://" . $_SERVER["HTTP_HOST"] . $basePath . "/pet_record.php?token=" . urlencode($qrToken);

echo json_encode([
    "success" => true,
    "message" => "QR certificate generated successfully.",
    "certificate_id" => $certificateId,
    "pet_id" => $petId,
    "pet_name" => $pet["pet_name"],
    "qr_token" => $qrToken,
    "qr_url" => $qrUrl,
    "status" => "Generated"
]);
exit;
