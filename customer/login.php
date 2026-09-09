<?php
session_start();

// If already logged in as Customer, go directly to About Us
if (isset($_SESSION["role"]) && $_SESSION["role"] === "Customer") {
    header("Location: about.php");
    exit();
}

$error = $_SESSION["login_error"] ?? "";
unset($_SESSION["login_error"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>3K Pet Solution - Customer Login</title>

    <link rel="stylesheet" href="./css/login.css">
</head>

<body>

    <div class="login-page">

        <div class="login-card">

            <!-- LOGO -->
            <div class="logo-wrapper">
                <img
                    src="../assets/images/logo.png"
                    alt="3K Pet Solution Logo"
                    class="login-logo"
                >
            </div>

            <!-- SYSTEM NAME -->
            <h1>3K Pet Solution</h1>

            <p class="system-subtitle">
                Veterinary Management Information System
            </p>

            <!-- LOGIN TITLE -->
            <h2>Welcome Back!</h2>

            <p class="login-description">
                Log in to your customer account.
            </p>

            <!-- ERROR MESSAGE -->
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- LOGIN FORM -->
            <form
                action="../process/login_process.php"
                method="POST"
                id="customerLoginForm"
            >

                <!-- EMAIL -->
                <div class="form-group">

                    <label for="username">
                        Email Address <span>*</span>
                    </label>

                    <input
                        type="email"
                        id="username"
                        name="username"
                        placeholder="Enter email address"
                        autocomplete="email"
                        required
                    >

                </div>

                <!-- PASSWORD -->
                <div class="form-group">

                    <label for="password">
                        Password <span>*</span>
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >
                            <span id="eyeIcon">◉</span>
                        </button>

                    </div>

                </div>

                <!-- REMEMBER / FORGOT -->
                <div class="login-options">

                    <label class="remember-me">
                        <input
                            type="checkbox"
                            name="remember"
                            id="remember"
                        >
                        <span>Remember Me</span>
                    </label>

                    <a href="../auth/forgot_password.php?from=customer">
                        Forgot Password?
                    </a>

                </div>

                <!-- LOGIN BUTTON -->
                <button
                    type="submit"
                    class="login-button"
                >
                    Login
                </button>

            </form>

            <!-- SIGN UP -->
            <p class="signup-link">
                Don't have an account?
                <a href="./signup.php">Sign up</a>
            </p>

        </div>

    </div>

    <script src="./js/login.js"></script>

</body>
</html>