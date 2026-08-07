<?php
session_start();

require_once "../config/database.php";
require_once "../config/mail_config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);

    // Check kung existing ang email
    $stmt = mysqli_prepare($conn, "SELECT * FROM accounts WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows == 0) {

        $_SESSION['error'] = "Email does not exist.";

        header("Location: ../auth/forgot_password.php");
        exit();
    
    }   

    // Generate 6-digit code
    $code = rand(100000,999999);

    // Expiration (10 minutes)
    $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Delete old code
    $delete = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email=?");
    mysqli_stmt_bind_param($delete, "s", $email);
    mysqli_stmt_execute($delete);

    // Save new code
    $insert = mysqli_prepare($conn, "INSERT INTO password_resets(email,verification_code,expires_at)
    VALUES(?,?,?)");
    
    mysqli_stmt_bind_param($insert, "sss", $email, $code, $expires);
    mysqli_stmt_execute($insert);

    // Send Email
    $mail = getMailer();

    $mail->addAddress($email);

    $mail->Subject = "Password Recovery Code";

    $mail->Body = "
    <h2>3K Pet Solution Animal Clinic</h2>

    <p>Your verification code is:</p>

    <h1>$code</h1>

    <p>This code will expire in 10 minutes.</p>
    ";

    try{

        $mail->send();
        
        $_SESSION['email'] = $email;
        
        header("Location: ../auth/verify_code.php");
        exit();
        
    }catch(Exception $e){

        $_SESSION['error'] = "Unable to send recovery email.";
        
        header("Location: ../auth/forgot_password.php");
        exit();

    }

    $_SESSION['email'] = $email;
    
    header("Location: ../auth/verify_code.php");
    exit();

}