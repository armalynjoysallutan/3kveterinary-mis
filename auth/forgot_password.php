<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Forgot Password</title>

<link rel="stylesheet" href="../assets/css/login.css">

</head>

<body>

<div class="container">

    <div class="logo">
        <img src="../assets/images/logo.png">
    </div>

    <div class="login-card">

        <h1>Forgot Password</h1>

        <p>
            Enter your email address. We will send a recovery code to reset your password.
        </p>

        <?php
        if(isset($_SESSION['success'])){
            echo "<p style='color:green;text-align:center;'>" . $_SESSION['success'] . "</p>";
            unset($_SESSION['success']);
            
        }
        
        if(isset($_SESSION['error'])){
            echo "<p style='color:red;text-align:center;'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
            
        }
        ?>

        <form action="../process/send_reset_code.php" method="POST">

            <input
                type="email"
                name="email"
                placeholder="Email Address"
                required
            >

            <button type="submit">
                Send Recovery Code
            </button>

        </form>

        <br>

        <a href="login.php">
            ← Back to Login
        </a>

    </div>

</div>

</body>
</html>