<?php

session_start();

require_once "../config/database.php";
require_once "../config/mail_config.php";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);


    // =========================================================
    // CHECK IF EMAIL EXISTS
    // =========================================================

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM accounts WHERE email = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $email
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);


    if ($result->num_rows == 0) {

        $_SESSION["error"] = "Email does not exist.";

        header(
            "Location: ../auth/forgot_password.php"
        );

        exit();

    }


    // =========================================================
    // GENERATE 6-DIGIT VERIFICATION CODE
    // =========================================================

    $code = rand(100000, 999999);


    // =========================================================
    // DELETE OLD VERIFICATION CODE
    // =========================================================

    $delete = mysqli_prepare(
        $conn,
        "DELETE FROM password_resets WHERE email = ?"
    );

    mysqli_stmt_bind_param(
        $delete,
        "s",
        $email
    );

    mysqli_stmt_execute($delete);


    // =========================================================
    // SAVE NEW CODE
    //
    // IMPORTANT:
    // MySQL generates the expiration time so that it uses
    // the same database clock as NOW() during verification.
    // =========================================================

    $insert = mysqli_prepare(
        $conn,
        "INSERT INTO password_resets
        (
            email,
            verification_code,
            expires_at
        )
        VALUES
        (
            ?,
            ?,
            DATE_ADD(NOW(), INTERVAL 10 MINUTE)
        )"
    );


    mysqli_stmt_bind_param(
        $insert,
        "ss",
        $email,
        $code
    );


    mysqli_stmt_execute($insert);


    // =========================================================
    // SEND RECOVERY EMAIL
    // =========================================================

    $mail = getMailer();

    $mail->addAddress($email);

    $mail->Subject = "Password Recovery Code";


    $mail->Body = "

        <h2>
            3K Pet Solution Animal Clinic
        </h2>

        <p>
            Your verification code is:
        </p>

        <h1>
            $code
        </h1>

        <p>
            This code will expire in 10 minutes.
        </p>

    ";


    try {

        $mail->send();


        // =====================================================
        // SAVE EMAIL TO SESSION
        // =====================================================

        $_SESSION["email"] = $email;


        // =====================================================
        // GO TO VERIFY CODE PAGE
        // =====================================================

        header(
            "Location: ../auth/verify_code.php"
        );

        exit();


    } catch (Exception $e) {

        $_SESSION["error"] =
            "Unable to send recovery email.";


        header(
            "Location: ../auth/forgot_password.php"
        );

        exit();

    }

}