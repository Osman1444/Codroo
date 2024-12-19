<?php
session_start();
require_once 'database_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $verificationCode = rand(100000, 999999);
        
        // Store verification code in the database
        $stmt = $pdo->prepare("UPDATE users SET reset_code = ?, reset_expiry = ? WHERE email = ?");
        $expiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes expiry
        $stmt->execute([$verificationCode, $expiry, $email]);

        // Send verification email
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
        $mail->Subject = 'Password Reset Verification Code';

        $email_template = file_get_contents('email.html');
        $email_template = str_replace('[User_Name]', $user['name'], $email_template);
        $email_template = str_replace('[XXXXXX]', $verificationCode, $email_template);

        $mail->Body = $email_template;

        if ($mail->send()) {
            $_SESSION['success'] = "Verification code sent to your email.";
            header("Location: verify_reset_code.php?email=" . urlencode($email));
            exit();
        } else {
            $_SESSION['error'] = "Failed to send verification email.";
        }
    } else {
        $_SESSION['error'] = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .error-message {
            color: red;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>Forgot Password</h1>
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
            <div class="input-field">
                <label for="email">Enter your email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</body>
</html> 