<?php
$ipInfo = json_decode(file_get_contents("http://ipinfo.io/json"));

// Set timezone based on IP
if (isset($ipInfo->timezone)) {
    date_default_timezone_set($ipInfo->timezone);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require_once 'database_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $age = (int)$_POST['age'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($age)) {
        $_SESSION['error'] = "All fields are required.";
        header('Location: register.php');
        exit();
    }

    // Validate age
    if ($age < 12 || $age > 35) {
        $_SESSION['error'] = "Age must be between 12 and 35 years.";
        header('Location: register.php');
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header('Location: register.php');
        exit();
    }

    // Validate password match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: register.php');
        exit();
    }

    // Password strength validation
    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters long and include uppercase letters, lowercase letters, and numbers.";
        header('Location: register.php');
        exit();
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Check if username or email already exists in permanent table
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $pdo->rollBack();
            $_SESSION['error'] = "Username or email already exists.";
            header('Location: register.php');
            exit();
        }

        // Delete expired temporary records before checking for active registration
        $stmt = $pdo->prepare("DELETE FROM temp_users WHERE expiry_time < NOW()");
        $stmt->execute();

        // Check for active temporary registration with both email and username
        $stmt = $pdo->prepare("SELECT id, expiry_time FROM temp_users WHERE (email = ? OR username = ?) AND expiry_time >= NOW()");
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->rollBack();
            $_SESSION['error'] = "A verification email has already been sent. Please check your inbox or wait for the current code to expire.";
            header('Location: register.php');
            exit();
        }

        // Generate verification code
        $verificationCode = sprintf("%06d", mt_rand(1, 999999));
        
        // Calculate expiry time (current time + 2 minutes)
        $current_time = time();
        $expiry_timestamp = $current_time + (600);
        $expiry = date('Y-m-d H:i:s', $expiry_timestamp);

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into temp_users table without deleting other records
        $stmt = $pdo->prepare("INSERT INTO temp_users (username, email, password, name, age, verification_code, expiry_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt->execute([$username, $email, $hashed_password, $name, $age, $verificationCode, $expiry])) {
            $pdo->rollBack();
            $error = $stmt->errorInfo();
            die("Database Error: " . $error[2]);
        }

        // Commit transaction
        $pdo->commit();

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
        $mail->Subject = 'Your Email Verification Code';

        // استخدام القالب
        $email_template = file_get_contents('email.html');
        $email_template = str_replace('[User_Name]', $name, $email_template);
        $email_template = str_replace('[XXXXXX]', $verificationCode, $email_template);

        $mail->Body = $email_template;

        if (!$mail->send()) {
            die("Registration successful but verification email could not be sent: " . $mail->ErrorInfo);
        }

        $_SESSION['registration_email'] = $email;
        header("Location: verify.php");
        exit();

    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        die("Registration failed: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        die("Email sending failed: " . $e->getMessage());
    }
}

// Fetch all temporary users
$stmt = $pdo->prepare("SELECT id, expiry_time FROM temp_users");
$stmt->execute();
$temp_users = $stmt->fetchAll();

foreach ($temp_users as $user) {
    // Check if the expiry time has passed
    if (strtotime($user['expiry_time']) < time()) {
        // Delete the expired record
        $delete_stmt = $pdo->prepare("DELETE FROM temp_users WHERE id = ?");
        $delete_stmt->execute([$user['id']]);
    }
}
?> 