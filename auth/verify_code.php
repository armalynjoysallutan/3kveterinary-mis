<?php
session_start();

if(!isset($_SESSION['email'])){
    header("Location: forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Verify Code</title>

<link rel="stylesheet" href="../assets/css/login.css">

</head>

<body>

<div class="container">

    <div class="logo">
        <img src="../assets/images/logo.png">
    </div>

    <div class="login-card">

        <h1>Verify Code</h1>

        <p>
            Enter the 6-digit verification code sent to your email.
        </p>
        
        <form action="../process/verify_code_process.php" method="POST">

            <div class="otp-container">

                <input type="tel" maxlength="1" class="otp-input">
                <input type="tel" maxlength="1" class="otp-input">
                <input type="tel" maxlength="1" class="otp-input">
                <input type="tel" maxlength="1" class="otp-input">
                <input type="tel" maxlength="1" class="otp-input">
                <input type="tel" maxlength="1" class="otp-input">

            </div>

            <input type="hidden" name="verification_code" id="verification_code">

            <button type="submit">
                Verify
            </button>

        </form>

    </div>

</div>

<script src="../assets/js/verify_code.js"></script>

</body>
</html>