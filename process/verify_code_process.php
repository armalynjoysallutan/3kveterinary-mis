<?php
session_start();

require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_SESSION["email"];
    $code = trim($_POST["verification_code"]);

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM password_resets
         WHERE email = ?
         AND verification_code = ?
         AND expires_at > NOW()"
    );

    mysqli_stmt_bind_param($stmt, "ss", $email, $code);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows > 0) {

        $_SESSION["verified"] = true;

        header("Location: ../auth/reset_password.php");
        exit();

    } else {

        echo "Invalid or expired verification code.";

    }

}