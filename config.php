<?php
// config.php

// Start the session (if not already started)
// Place this at the very top before any output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Database Configuration ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Your DB password, likely empty for default XAMPP
// define('DB_PASSWORD', 'mysecretpassword'); // Your DB password, likely empty for default XAMPP
define('DB_NAME', 'dams_db'); // Your database name

// --- Database Connection ---
// Create connection object
// $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
$conn = new mysqli(DB_SERVER, DB_USERNAME, '', DB_NAME);

// Check connection status (without using die())
if ($conn->connect_error) {
    // Log the error for server administrators/developers
    // Use error_log() for server-side logging
    error_log("Database Connection Error (" . $conn->connect_errno . "): " . $conn->connect_error);

    // You might set a flag or let $conn be checked in calling scripts
    // For example, scripts requiring $conn should start with:
    // if (!$conn || $conn->connect_error) { /* handle error, maybe output JSON error */ exit; }
} else {
    // Optional: Set character set (recommended)
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $conn->error);
    }
}


// --- Encryption Settings ---
// IMPORTANT: Replace 'your-strong-encryption-key' with a long, random, securely generated key.
// You can generate one using PHP: bin2hex(random_bytes(32))
// Store this key securely and DO NOT commit it directly into version control if possible.
// Consider using environment variables or a configuration file outside the web root for production.
define('ENCRYPTION_KEY', 'your-strong-encryption-key'); // <<< REPLACE THIS !!!
define('ENCRYPTION_CIPHER', 'aes-256-cbc'); // AES 256-bit in CBC mode is a strong standard

/**
 * Encrypts data using AES-256-CBC with HMAC authentication.
 *
 * @param string $data The plaintext data to encrypt.
 * @return string|false The base64 encoded encrypted string (IV + HMAC + Ciphertext), or false on failure.
 */
function encrypt_data($data) {
    $key = ENCRYPTION_KEY;
    $cipher = ENCRYPTION_CIPHER;

    // Check if key and cipher are defined and valid
    if (empty($key) || !in_array($cipher, openssl_get_cipher_methods())) {
        error_log("Encryption failed: Invalid key or cipher defined.");
        return false;
    }

    $ivlen = openssl_cipher_iv_length($cipher);
    if ($ivlen === false) {
         error_log("Encryption failed: Could not get IV length for cipher " . $cipher);
         return false;
    }
    $iv = openssl_random_pseudo_bytes($ivlen);

    $ciphertext_raw = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext_raw === false) {
        error_log("Encryption failed: openssl_encrypt returned false. Error: " . openssl_error_string());
        return false; // Indicate failure
    }

    // Create HMAC (Hash-based Message Authentication Code) using SHA256
    $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true); // true for raw binary output

    // Combine IV, HMAC, and raw ciphertext, then Base64 encode
    // Order: IV (fixed length) + HMAC (fixed length, SHA256=32 bytes) + Ciphertext
    return base64_encode($iv . $hmac . $ciphertext_raw);
}

/**
 * Decrypts data encrypted with encrypt_data function.
 * Verifies HMAC before decryption.
 *
 * @param string $data The base64 encoded encrypted string (IV + HMAC + Ciphertext).
 * @return string|false The original plaintext data, or false on failure (e.g., bad data, HMAC mismatch).
 */
function decrypt_data($data) {
    $key = ENCRYPTION_KEY;
    $cipher = ENCRYPTION_CIPHER;

    // Check if key and cipher are defined and valid
    if (empty($key) || !in_array($cipher, openssl_get_cipher_methods())) {
        error_log("Decryption failed: Invalid key or cipher defined.");
        return false;
    }

    // Decode Base64
    $c = base64_decode($data);
    if ($c === false) {
        error_log("Decryption failed: Input data is not valid Base64.");
        return false;
    }

    $ivlen = openssl_cipher_iv_length($cipher);
     if ($ivlen === false) {
         error_log("Decryption failed: Could not get IV length for cipher " . $cipher);
         return false;
    }
    $hmac_len = 32; // SHA256 produces a 32-byte hash

    // Check minimum length (must have at least IV and HMAC)
    if (strlen($c) < $ivlen + $hmac_len) {
         error_log("Decryption failed: Input data too short to contain IV and HMAC.");
         return false;
    }

    // Extract components
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, $hmac_len);
    $ciphertext_raw = substr($c, $ivlen + $hmac_len);

    // Calculate expected HMAC on the ciphertext part only
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true); // true for raw binary output

    // Verify HMAC using timing attack safe comparison
    if (!hash_equals($hmac, $calcmac)) {
        error_log("Decryption failed: HMAC verification failed (possible tampering or wrong key).");
        return false; // MAC mismatch
    }

    // Proceed with decryption only if HMAC is valid
    $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    if ($original_plaintext === false) {
        error_log("Decryption failed: openssl_decrypt returned false after HMAC verification. Error: " . openssl_error_string());
        return false; // Decryption failed after MAC check (less likely, maybe padding error)
    }

    return $original_plaintext;
}

?>