<?php

$ipInfo = json_decode(file_get_contents("http://ipinfo.io/json"));

// Set timezone based on IP
if (isset($ipInfo->timezone)) {
    date_default_timezone_set($ipInfo->timezone);
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Only start session if one doesn't exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configurations
define('DB_HOST', 'localhost');
define('DB_NAME', 'new_test_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error occurred.");
}

// Make PDO object available globally
global $pdo;

// Function to get database connection
function getDBConnection() {
    global $pdo;
    return $pdo;
}
?>