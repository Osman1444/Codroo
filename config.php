<?php
define('ROUTES', [
    'HOME' => 'home_page',
    'LOGIN' => 'signin_page',
    'REGISTER' => 'signup_page',
    'LOGOUT' => 'signout_page',
    'DASHBOARD' => 'dashboard_page'
]);

function redirect($route) {
    header("Location: " . ROUTES[$route]);
    exit();
}
?> 