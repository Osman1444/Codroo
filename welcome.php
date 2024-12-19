<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Codro Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .welcome-container {
            text-align: center;
            padding: 2rem;
        }
        .user-info {
            margin: 2rem 0;
        }
        .logout-btn {
            background-color: #e74c3c;
        }
        .logout-btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="welcome-container">
            <div class="form-header">
                <h1>Welcome to Codro</h1>
                <p>We're glad you're here!</p>
            </div>

            <div class="user-info">
                <h2>Hello, <?php echo htmlspecialchars($name); ?>!</h2>
                <p>Username: <?php echo htmlspecialchars($username); ?></p>
            </div>

            <a href="logout.php" class="btn btn-primary logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <footer class="form-footer">
            <p>&copy; 2024 Codro Platform. All rights reserved.</p>
        </footer>
    </div>
</body>
</html> 