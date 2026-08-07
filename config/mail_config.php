<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';

function getMailer()
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    
    //$mail->SMTPDebug = 2;
    //$mail->Debugoutput = 'html';
    
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    // Gmail mo
    $mail->Username = 'florencegem1@gmail.com';

    // ILAGAY DITO ANG 16-CHARACTER APP PASSWORD
    $mail->Password = 'saiwuntvmdkwovxy';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('florencegem1@gmail.com', '3K Pet Solution Animal Clinic');

    $mail->isHTML(true);

    return $mail;
}