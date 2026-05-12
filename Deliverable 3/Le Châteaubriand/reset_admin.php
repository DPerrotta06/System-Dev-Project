<?php
// reset_admin.php — place in your project root, run once, then DELETE it
require __DIR__ . '/vendor/autoload.php';

use RedBeanPHP\R;

R::setup(
    'mysql:host=127.0.0.1;dbname=chateaubriand;charset=utf8mb4',
    'your_db_user',
    'your_db_password'
);

$newPassword = 'password'; // change to whatever you want
$hash = password_hash($newPassword, PASSWORD_BCRYPT);

R::exec("UPDATE admin SET password_hash = ? WHERE id = 1", [$hash]);
echo "Password reset successfully.";