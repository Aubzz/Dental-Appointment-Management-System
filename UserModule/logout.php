<?php
// UserModule/logout.php

// Initialize the session if it's not already started.
// This is crucial because you need to access $_SESSION to destroy it.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Unset all of the session variables.
$_SESSION = array();

// 2. Destroy the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session.
session_destroy();

// 4. Redirect to the main landing page (index.html)
// Since index.html is one level up from UserModule, we use '../'
header("location: ../index.html"); // Redirect to index.html in the parent directory
exit;
?>