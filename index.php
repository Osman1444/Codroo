<?php
// Get IP information
$ipInfo = json_decode(file_get_contents("http://ipinfo.io/json"));

// Set timezone based on IP
if (isset($ipInfo->timezone)) {
    date_default_timezone_set($ipInfo->timezone);
}
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard_page");
} else {
    header("Location: signup_page");
}
exit();
?> 