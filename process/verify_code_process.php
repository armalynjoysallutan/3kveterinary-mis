<?php

session_start();

require_once "../config/database.php";


// =========================================================
// ONLY POST REQUESTS
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../auth/forgot_password.php");
    exit();

}


// =========================================================
// GET SESSION EMAIL
// =========================================================

$email = $_SESSION["email"] ?? "";


// =========================================================
// GET VERIFICATION CODE
// =========================================================

$code = trim($_POST["verification_code"] ?? "");


// =========================================================
// BASIC VALIDATION
// =========================================================

if ($email === "" || $code === "") {

    $_SESSION["error"] =
        "Please enter the verification code.";

    header("Location: ../auth/verify_code.php");
    exit();

}


if (!preg_match("/^[0-9]{6}$/", $code)) {

    $_SESSION["error"] =
        "Please enter a valid 6-digit verification code.";

    header("Location: ../auth/verify_code.php");
    exit();

}


// =========================================================
// VERIFY CODE
//
// IMPORTANT:
// MySQL NOW() is used here.
// The send_reset_code.php also uses MySQL NOW()
// when creating expires_at.
// =========================================================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        email,
        verification_code,
        expires_at
     FROM password_resets
     WHERE email = ?
       AND verification_code = ?
       AND expires_at > NOW()
     ORDER BY id DESC
     LIMIT 1"
);


if (!$stmt) {

    $_SESSION["error"] =
        "Unable to verify the recovery code.";

    header("Location: ../auth/verify_code.php");
    exit();

}


mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $email,
    $code
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


// =========================================================
// INVALID / EXPIRED CODE
// =========================================================

if ($result->num_rows === 0) {

    $_SESSION["error"] =
        "Invalid or expired verification code.";

    header("Location: ../auth/verify_code.php");
    exit();

}


// =========================================================
// SUCCESSFUL VERIFICATION
// =========================================================

$_SESSION["verified"] = true;


// =========================================================
// SAVE ACCOUNT ROLE
//
// This allows the final password-reset step to know
// whether the account belongs to Customer, Admin, or Staff.
// =========================================================

$account_stmt = mysqli_prepare(
    $conn,
    "SELECT role
     FROM accounts
     WHERE email = ?
     LIMIT 1"
);


if ($account_stmt) {

    mysqli_stmt_bind_param(
        $account_stmt,
        "s",
        $email
    );

    mysqli_stmt_execute($account_stmt);

    $account_result =
        mysqli_stmt_get_result($account_stmt);


    if ($account_result->num_rows > 0) {

        $account = mysqli_fetch_assoc(
            $account_result
        );

        $_SESSION["reset_role"] =
            $account["role"];

    }

}


// =========================================================
// GO TO RESET PASSWORD
// =========================================================

header(
    "Location: ../auth/reset_password.php"
);

exit();

?>