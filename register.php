<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Only start session if one doesn't exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database_connection.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard_page');
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];

    // Validate input
    if (empty($name) || empty($age) || empty($username) || empty($password) || empty($email)) {
        echo "All fields are required.";
        exit();
    }

    if ($age <= 0) {
        echo "Invalid age.";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }

    // Password strength validation
    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
        echo "Password must be at least 8 characters long and include uppercase letters, lowercase letters, and numbers.";
        exit();
    }

    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM codro_database WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            echo "Username or email already exists.";
            exit();
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Encrypt email before storing
        $encryption_key = openssl_digest(php_uname(), 'sha256', TRUE);
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted_email = openssl_encrypt($email, 'aes-256-cbc', $encryption_key, 0, $iv);
        $encrypted_email = base64_encode($iv . $encrypted_email);

        // Insert new user with encrypted email
        $stmt = $pdo->prepare("INSERT INTO codro_database (name, username, password, email, age, verification_code, expiry_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $hashed_password, $encrypted_email, $age, $verificationCode, $expiry]);

        // Send verification email
        $verificationCode = rand(100000, 999999);
        // Store verification code in the database
        
        // Send verification email
        // ...

        // Redirect to verification page
        header("Location: verify.php?email=" . urlencode($email));
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Codro Platform</title>
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
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>Create Account</h1>
            <p>Join Codro Platform today</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']); 
                ?>
            </div>
        <?php endif; ?>

        <form action="process_registration.php" method="POST" onsubmit="return validateForm()">
            <div class="input-field">
                <input type="text" name="name" id="name" placeholder="Full Name" required>
            </div>

            <div class="input-field">
                <input type="text" name="username" id="username" placeholder="Username" required>
            </div>

            <div class="input-field">
                <input type="email" name="email" id="email" placeholder="Email" required>
            </div>

            <div class="input-field">
                <input type="number" name="age" id="age" placeholder="Age (12-35)" min="12" max="35" required>
            </div>
            
            <div class="password-field">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i class="far fa-eye"></i>
                </button>
                <small class="password-hint">
                    <i class="fas fa-info-circle"></i>
                    Password must be at least 8 characters long and include uppercase letters, lowercase letters, and numbers.
                </small>
            </div>

            <div class="password-field">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                    <i class="far fa-eye"></i>
                </button>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Sign Up
            </button>
        </form>

        <div class="form-links">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>

        <footer class="form-footer">
            <p>&copy; 2024 Codro Platform. All rights reserved.</p>
        </footer>
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

        function validateForm() {
            const age = document.getElementById('age').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // التحقق من العمر
            if (age < 12 || age > 35) {
                alert('Age must be between 12 and 35 years.');
                return false;
            }

            // التحقق من تطابق كلمتي المرور
            if (password !== confirmPassword) {
                alert('Passwords do not match.');
                return false;
            }

            return true;
        }
    </script>
</body>
</html> 