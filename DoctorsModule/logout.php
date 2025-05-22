<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the doctor signin page
header("Location: doctor_signin.html");
exit;
?> 