<?php
session_start();
require_once 'database_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$email = $_GET['email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['verification_code'];

    // Check if code is correct and not expired
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND reset_code = ? AND reset_expiry >= NOW()");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        header("Location: reset_password.php?email=" . urlencode($email));
        exit();
    } else {
        $_SESSION['error'] = "Invalid or expired verification code.";
    }
}

// Resend code logic
if (isset($_GET['resend']) && $_GET['resend'] == 'true') {
    if (!isset($_SESSION['last_resend_time']) || (time() - $_SESSION['last_resend_time']) >= 60) {
        $verificationCode = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes expiry

        $stmt = $pdo->prepare("UPDATE users SET reset_code = ?, reset_expiry = ? WHERE email = ?");
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
            $_SESSION['success'] = "Verification code resent.";
        } else {
            $_SESSION['error'] = "Failed to send verification email.";
        }

        $_SESSION['last_resend_time'] = time();
    } else {
        $_SESSION['error'] = "Please wait before requesting a new code.";
    }
    header("Location: verify_reset_code.php?email=" . urlencode($email));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code</title>
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
    <script>
        var countdown = <?php echo isset($_SESSION['last_resend_time']) ? max(0, 60 - (time() - $_SESSION['last_resend_time'])) : 0; ?>;
    </script>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>Verify Reset Code</h1>
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
                <label for="verification_code">Enter Verification Code:</label>
                <input type="text" id="verification_code" name="verification_code" required>
            </div>
            <button type="submit" class="btn btn-primary">Verify</button>
        </form>
        <div class="form-links">
            <p>Didn't receive the code? <a href="verify_reset_code.php?resend=true&email=<?php echo urlencode($email); ?>" id="resend-link">Resend Code</a></p>
            <p id="countdown-timer"></p>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resendLink = document.getElementById('resend-link');
            const countdownTimer = document.getElementById('countdown-timer');

            if (countdown > 0) {
                resendLink.style.pointerEvents = 'none';
                const interval = setInterval(() => {
                    if (countdown > 0) {
                        countdownTimer.textContent = `You can resend the code in ${countdown}s`;
                        countdown--;
                    } else {
                        countdownTimer.textContent = '';
                        resendLink.style.pointerEvents = 'auto';
                        clearInterval(interval);
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html> 