<?php
// قائمة بالملفات وأسمائها الجديدة
$file_mappings = [
    'index.php' => 'home_page',
    'login.php' => 'signin_page',
    'register.php' => 'signup_page',
    'logout.php' => 'signout_page',
    'welcome.php' => 'dashboard_page'
];

// تحديث ملف .htaccess
$htaccess_content = "RewriteEngine On

# Redirect HTTP to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Hide PHP extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.php -f
RewriteRule ^(.*)$ $1.php [L]

# Custom URL mappings\n";

foreach ($file_mappings as $old_name => $new_name) {
    $htaccess_content .= "RewriteRule ^{$new_name}$ {$old_name} [L]\n";
}

// كتابة المحتوى الجديد إلى ملف .htaccess
file_put_contents('.htaccess', $htaccess_content);

echo "تم تحديث ملف .htaccess بنجاح!";
?> 