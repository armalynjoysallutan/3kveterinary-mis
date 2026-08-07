<?php
session_start();

if (!isset($_SESSION["verified"])) {
    header("Location: forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Reset Password</title>

<link rel="stylesheet" href="../assets/css/login.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="container">

    <div class="logo">
        <img src="../assets/images/logo.png">
    </div>

    <div class="login-card">

        <h1>Reset Password</h1>

        <p>Create your new password.</p>

        <form action="../process/reset_password_process.php" method="POST">

            <div class="password-container">

                <input
                    type="password"
                    name="password"
                    id="newPassword"
                    placeholder="New Password"
                    required
                >

                <i class="fa-solid fa-eye toggle-password" data-target="newPassword"></i>

            </div>

             <div class="password-container">

                <input
                    type="password"
                    name="confirm_password"
                    id="confirmPassword"
                    placeholder="Confirm Password"
                    required
                >

                <i class="fa-solid fa-eye toggle-password" data-target="confirmPassword"></i>

             </div>

            <button type="submit">
                Reset Password
            </button>

        </form>

    </div>

</div>

<script src="../assets/js/reset_password.js"></script>

</body>
</html>