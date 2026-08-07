<?php

require_once 'config/mail_config.php';

try {

    $mail = getMailer();

    $mail->addAddress('florencegem1@gmail.com');

    $mail->Subject = '3K Veterinary MIS Email Test';

    $mail->Body = '
    <h2>Email Test Successful!</h2>

    <p>Congratulations!</p>

    <p>Your PHPMailer is working correctly.</p>

    <p>This email was sent from your Veterinary MIS.</p>
    ';

    $mail->send();

    echo "Email sent successfully!";

} catch (Exception $e) {

    echo "Mailer Error: " . $mail->ErrorInfo;

}