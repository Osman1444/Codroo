<?php
session_start();
require_once 'database_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// تأكد من وجود البريد الإلكتروني في الجلسة
if (!isset($_SESSION['registration_email'])) {
    $_SESSION['error'] = "Please register first.";
    header("Location: register.php");
    exit();
}

$email = $_SESSION['registration_email'];

if (!isset($_SESSION['last_resend_time']) || (time() - $_SESSION['last_resend_time']) >= 60) {
    // توليد كود تحقق جديد
    $verificationCode = sprintf("%06d", mt_rand(1, 999999));
    
    // حساب وقت انتهاء الصلاحية الجديد (الوقت الحالي + 10 دقائق)
    $expiry_timestamp = time() + 600; // 10 minutes
    $expiry = date('Y-m-d H:i:s', $expiry_timestamp);

    // تحديث الكود ووقت الانتهاء في قاعدة البيانات
    $update_stmt = $pdo->prepare("UPDATE temp_users SET verification_code = ?, expiry_time = ? WHERE email = ?");
    $update_stmt->execute([$verificationCode, $expiry, $email]);

    // إرسال البريد الإلكتروني باستخدام PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'codro.platform@gmail.com';
    $mail->Password = 'hrgs ymcv ohds wiew';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('codro.platform@gmail.com', 'Codro Platform');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your Email Verification Code';

    // استخدام القالب
    $email_template = file_get_contents('email.html');
    $email_template = str_replace('[User_Name]', $_SESSION['name'], $email_template);
    $email_template = str_replace('[XXXXXX]', $verificationCode, $email_template);

    $mail->Body = $email_template;

    if ($mail->send()) {
        $_SESSION['success'] = "Verification code resent.";
    } else {
        $_SESSION['error'] = "Failed to send verification email.";
    }

    $_SESSION['last_resend_time'] = time();
} else {
    $_SESSION['error'] = "Please wait before requesting a new code.";
}

header("Location: verify.php");
exit();
?> 