<?php
$plainPassword = 'adminpassword123'; // Choose a strong password
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
echo "Username: admin<br>";
echo "Hashed Password: " . $hashedPassword;
// Now copy this hashed password and insert it into your admins table
?>