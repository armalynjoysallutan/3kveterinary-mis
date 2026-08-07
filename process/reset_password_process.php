<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["verified"]) || !isset($_SESSION["email"])) {
    header("Location: ../auth/forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $password = trim($_POST["password"]);
    $confirm = trim($_POST["confirm_password"]);

    if ($password != $confirm) {

    $_SESSION["error"] = "Passwords do not match.";

    header("Location: ../auth/reset_password.php");
    exit();

    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $email = $_SESSION["email"];

    // Update password
    $stmt = mysqli_prepare($conn, "UPDATE accounts SET password=? WHERE email=?");
    mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $email);
    mysqli_stmt_execute($stmt);

    // Delete used verification code
    $delete = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email=?");
    mysqli_stmt_bind_param($delete, "s", $email);
    mysqli_stmt_execute($delete);

    // Destroy recovery session
    unset($_SESSION["verified"]);
    unset($_SESSION["email"]);

    $_SESSION["success"] = "Password changed successfully.";

    header("Location: ../auth/login.php");
    exit();
}