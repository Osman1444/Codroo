<?php

$ipInfo = json_decode(file_get_contents("http://ipinfo.io/json"));

// Set timezone based on IP
if (isset($ipInfo->timezone)) {
    date_default_timezone_set($ipInfo->timezone);
}

session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require_once 'database_connection.php';

// تأكد من وجود البريد الإلكتروني في الجلسة
if (!isset($_SESSION['registration_email'])) {
    $_SESSION['error'] = "Please register first.";
    header("Location: register.php");
    exit();
}

$email = $_SESSION['registration_email'];

// تحقق من إمكانية إعادة إرسال الكود
if (isset($_GET['resend']) && $_GET['resend'] == 'true') {
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
}

// معالجة نموذج التحقق
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['verification_code']);
    
    try {
        // أولاً، تحقق من وجود السجل في قاعدة البيانات
        $check_stmt = $pdo->prepare("SELECT * FROM temp_users WHERE email = ?");
        $check_stmt->execute([$email]);
        $user_record = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_record) {
            $_SESSION['error'] = "No temporary registration found. Please register again.";
            header("Location: register.php");
            exit();
        }

        // تحقق من تطابق الكود
        if ($user_record['verification_code'] !== $code) {
            $_SESSION['error'] = "Incorrect verification code.";
            header("Location: verify.php");
            exit();
        }

        // تحقق من صلاحية الوقت
        if (strtotime($user_record['expiry_time']) < time()) {
            // Delete expired record
            $delete_stmt = $pdo->prepare("DELETE FROM temp_users WHERE id = ?");
            $delete_stmt->execute([$user_record['id']]);
            
            $_SESSION['error'] = "Verification code has expired. Please register again.";
            header("Location: register.php");
            exit();
        }

        // If verification successful, move to permanent tables
        $pdo->beginTransaction();

        try {
            // Insert into permanent tables
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_record['username'], $user_record['email'], $user_record['password']]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO codro_database (user_id, name, age, is_verified) VALUES (?, ?, ?, 1)");
            $stmt->execute([$user_id, $user_record['name'], $user_record['age']]);

            // Delete temporary record
            $stmt = $pdo->prepare("DELETE FROM temp_users WHERE id = ?");
            $stmt->execute([$user_record['id']]);

            $pdo->commit();

            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $user_record['username'];
            $_SESSION['name'] = $user_record['name'];
            unset($_SESSION['registration_email']);

            header("Location: welcome.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "An error occurred during verification. Please try again.";
            header("Location: verify.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred. Please try again.";
        header("Location: verify.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Codro Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        var countdown = <?php echo isset($_SESSION['last_resend_time']) ? max(0, 60 - (time() - $_SESSION['last_resend_time'])) : 0; ?>;
    </script>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>Email Verification</h1>
            <p>Please enter the verification code sent to your email</p>
            <p>Code will expire in 2 minutes</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']); 
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']); 
                ?>
            </div>
        <?php endif; ?>

        <form action="verify.php" method="POST">
            <div class="input-field">
                <input type="text" name="verification_code" placeholder="Enter verification code" required>
            </div>

            <button type="submit" class="btn btn-primary">
                Verify Email
            </button>
        </form>

        <div class="form-links">
            <p>Didn't receive the code? <a href="resend_code.php" id="resend-link">Resend Code</a></p>
            <p id="countdown-timer"></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>

