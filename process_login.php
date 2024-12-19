<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ipInfo = json_decode(file_get_contents("http://ipinfo.io/json"));

// Set timezone based on IP
if (isset($ipInfo->timezone)) {
    date_default_timezone_set($ipInfo->timezone);
}

session_start();
require_once 'database_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameOrEmail = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate input
    if (empty($usernameOrEmail) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: login.php");
        exit();
    }

    try {
        // First, get user data from users table
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?)");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Get additional user data from codro_database
            $stmt = $pdo->prepare("SELECT * FROM codro_database WHERE user_id = ? AND is_verified = 1");
            $stmt->execute([$user['id']]);
            $codro_data = $stmt->fetch();

            if ($codro_data) {
                // Update session with correct data from both tables
                $_SESSION['user_id'] = $user['id'];  // From users table
                $_SESSION['username'] = $user['username'];  // From users table
                $_SESSION['name'] = $codro_data['name'];  // From codro_database table

                // Send login alert email
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
                $mail->Subject = 'Security Alert - New Login';

                $email_template = file_get_contents('login-alert.html');
                $email_template = str_replace('[USER_NAME]', $codro_data['name'], $email_template);
                $email_template = str_replace('[LOGIN_LOCATION]', $ipInfo->city . ', ' . $ipInfo->region, $email_template);
                $email_template = str_replace('[LOGIN_DATE]', date('Y-m-d'), $email_template);
                $email_template = str_replace('[LOGIN_TIME]', date('H:i:s'), $email_template);
                $email_template = str_replace('[TIMEZONE]', $ipInfo->timezone, $email_template);
                $email_template = str_replace('[DEVICE_INFO]', $_SERVER['HTTP_USER_AGENT'], $email_template);
                $email_template = str_replace('[IP_ADDRESS]', $ipInfo->ip, $email_template);

                $mail->Body = $email_template;

                if (!$mail->send()) {
                    error_log("Login alert email could not be sent: " . $mail->ErrorInfo);
                }

                // Redirect to dashboard/welcome page
                header("Location: welcome.php");
                exit();
            } else {
                $_SESSION['error'] = "Please verify your email address first.";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid username/email or password.";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Login failed: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
}
?> 