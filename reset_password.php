<?php
session_start();
require_once 'database_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$email = $_GET['email'];
$newPassword = $_POST['newPassword'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if new password matches confirmation
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $newPassword)) {
        $_SESSION['error'] = "Password must be at least 8 characters long and include uppercase letters, lowercase letters, and numbers.";
    } else {
        // Check if new password is the same as the old password
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($newPassword, $user['password'])) {
            $_SESSION['error'] = "New password cannot be the same as the old password.";
        } elseif ($user) {
            // Update the user's password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expiry = NULL WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);
            $_SESSION['success'] = "Your password has been successfully reset!";

            // Send password change alert email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'codro.platform@gmail.com';
            $mail->Password = 'hrgs ymcv ohds wiew';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('codro.platform@gmail.com', 'Codro Platform');
            $mail->addAddress($user['email']);
            $mail->isHTML(true);
            $mail->Subject = 'Password Change Alert';

            $ipInfo = json_decode(file_get_contents("http://ipinfo.io/json"));
            $email_template = file_get_contents('password-change-alert.html');
            $email_template = str_replace('[USER_NAME]', $user['name'], $email_template);
            $email_template = str_replace('[CHANGE_DATE]', date('Y-m-d'), $email_template);
            $email_template = str_replace('[CHANGE_TIME]', date('H:i:s'), $email_template);
            $email_template = str_replace('[TIMEZONE]', $ipInfo->timezone, $email_template);
            $email_template = str_replace('[LOCATION]', $ipInfo->city . ', ' . $ipInfo->region, $email_template);
            $email_template = str_replace('[DEVICE_INFO]', $_SERVER['HTTP_USER_AGENT'], $email_template);

            $mail->Body = $email_template;

            if (!$mail->send()) {
                error_log("Password change alert email could not be sent: " . $mail->ErrorInfo);
            }

            header("Location: login");
            exit();
        } else {
            $_SESSION['error'] = "Invalid or expired token.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .error-message {
            color: red;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .password-toggle {
            background: none;
            border: none;
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>Reset Password</h1>
        </div>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']); 
                ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-field" style="position: relative;">
                <label for="newPassword">Enter New Password:</label>
                <input type="password" id="newPassword" name="newPassword" required>
                <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                    <i class="far fa-eye"></i>
                </button>
            </div>
            <div class="input-field" style="position: relative;">
                <label for="confirmPassword">Confirm New Password:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required>
                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                    <i class="far fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
    </div>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 