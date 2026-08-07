<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3K Pet Solution - Admin Login</title>

    <link rel="stylesheet" href="../assets/css/login.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="container">

    <div class="logo">
        <img src="../assets/images/logo.png" alt="3K Pet Solution Logo">
    </div>

    <div class="login-card">

        <h1>Login</h1>

        <p>Welcome back, ready to take control</p>

        <?php
        if(isset($_SESSION["success"])){
            echo "<div class='success-message'>" . $_SESSION["success"] . "</div>";
            unset($_SESSION["success"]);
        }

        if(isset($_SESSION["error"])){
            echo "<div class='error-message'>" . $_SESSION["error"] . "</div>";
            unset($_SESSION["error"]);
        }
        ?>

        <form action="../process/login_process.php" method="POST">

        <div class="input-container">

            <input
                type="text"
                name="username"
                placeholder="Username"
                required
            >

        </div>  

            <div class="password-container">

                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Password"
                    required
                
                >
                
                <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
            
            </div>

            <div class="options">

                <label>
                    <input type="checkbox">
                    Remember me
                </label>

                <a href="forgot_password.php">Forgot Password?</a>

            </div>

            <button type="submit" class="login-btn">
                Login
            </button>

        </form>

    </div>

</div>

<script src="../assets/js/login.js"></script>

</body>
</html>