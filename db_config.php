<?php
// db_config.php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
// !! USE THE ACTUAL PASSWORD YOU SET FOR YOUR DB USER !!
define('DB_PASSWORD', 'mysecretpassword'); // Or the password you created
define('DB_NAME', 'dental_db');

// --- Encryption Settings ---
// !! WARNING: DO NOT use a hardcoded key like this in production! !!
// !! Store it securely (environment variable, KMS, secure file outside webroot) !!
// !! REPLACE this placeholder with the 64-character hex key you generated !!
define('MYSQL_ENCRYPTION_KEY', 'a3b8f0c1e2d4a5b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0');

// --- Database Connection ---
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($mysqli->connect_error) {
    error_log("ERROR: Could not connect. " . $mysqli->connect_error);
    // Send generic error only if headers not already sent
    if (!headers_sent()) {
         http_response_code(500);
         // Ensure header is set even for errors if sending JSON
         header('Content-Type: application/json');
         echo json_encode(['message' => 'Database connection error.']);
    }
    exit; // Stop script
}

// Set charset for the connection
if (!$mysqli->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $mysqli->error);
     if (!headers_sent()) {
         http_response_code(500);
         header('Content-Type: application/json');
         echo json_encode(['message' => 'Database character set error.']);
     }
    // Close connection if open before exiting
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
    exit; // Stop script
}

// No PHP encrypt/decrypt functions needed here for DB encryption method
?>