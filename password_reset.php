<?php
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$ipInfo = json_decode(file_get_contents("http://ipinfo.io/json"));

// Set timezone based on IP
if (isset($ipInfo->timezone)) {
    date_default_timezone_set($ipInfo->timezone);
}

$conn = new mysqli('localhost', 'username', 'password', 'database');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$code = rand(100000, 999999);
$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Update verification code for password reset
$stmt = $conn->prepare("UPDATE users SET verification_code = ?, expiry_time = ? WHERE email = ?");
$stmt->bind_param("iss", $code, $expiry, $email);
$stmt->execute();

// Send password reset email
$mail = new PHPMailer\PHPMailer\PHPMailer();
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
$mail->Subject = 'Password Reset Code';
$mail->Body = "
    <h2>Hello,</h2>
    <p>You requested to reset your password. Your verification code is:</p>
    <h1 style='color: #4CAF50;'>$code</h1>
    <p>Enter this code on the website to reset your password. The code is valid for 10 minutes.</p>
";

if ($mail->send()) {
    header("Location: verify_reset_code.php?email=$email");
    exit();
} else {
    echo "Error sending email: " . $mail->ErrorInfo;
}
?> 