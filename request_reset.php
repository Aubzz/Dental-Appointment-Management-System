<?php
// request_reset.php
header('Content-Type: application/json');
require_once 'db_config.php'; // Includes DB connection and MYSQL_ENCRYPTION_KEY

// --- Get and Decode JSON Data ---
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// --- Basic Validation ---
if (
    !isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
    !isset($data['role']) || !in_array($data['role'], ['client', 'receptionist', 'dentist'])
) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid input data provided.']);
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
    exit;
}

// --- Assign Validated Variables ---
$email_plain_input = trim($data['email']);
$role_input = $data['role'];
$key = MYSQL_ENCRYPTION_KEY;

// Variable to store the ID of the user if found
$user_id_found = null;

// --- Find User: Select by role, decrypt email in SQL ---
$sql_find = "SELECT
                 id,
                 AES_DECRYPT(email, ?) AS decrypted_email
             FROM users
             WHERE role = ?";

if ($stmt_find = $mysqli->prepare($sql_find)) {
    $stmt_find->bind_param("ss", $key, $role_input);
    if ($stmt_find->execute()) {
        $result = $stmt_find->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($row['decrypted_email'] !== null && $row['decrypted_email'] === $email_plain_input) {
                $user_id_found = $row['id'];
                break;
            }
        }
        if(isset($result)) $result->free();
    } else { error_log("Error executing user find statement: " . $stmt_find->error); }
    $stmt_find->close();
} else { error_log("Error preparing user find statement: " . $mysqli->error); }

// --- Process Request Based on Whether User Was Found ---
if ($user_id_found) {
    try {
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires_at = new DateTime('+1 hour');
        $expires_at_formatted = $expires_at->format('Y-m-d H:i:s');

        $sql_update = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?";
        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param("ssi", $token_hash, $expires_at_formatted, $user_id_found);
            if ($stmt_update->execute()) {
                // --- Simulate Sending Email ---
                $reset_link = "http://localhost/dental_app/reset_password.php?token=" . $token; // Adjust path if needed
                error_log("Simulating email send for password reset.");
                error_log("  User ID: " . $user_id_found);
                error_log("  Recipient (intended): " . $email_plain_input);
                error_log("  Reset Link: " . $reset_link);
                // --- End Simulation ---
            } else { error_log("Failed to update user record with reset token: " . $stmt_update->error); }
            $stmt_update->close();
        } else { error_log("Failed to prepare token update statement: " . $mysqli->error); }
    } catch (Exception $e) { error_log("Exception during token generation or date creation: " . $e->getMessage()); }
} else { error_log("Password reset requested but no user found for email/role: " . $email_plain_input . " / " . $role_input); }

// --- Always send a generic success message ---
http_response_code(200);
echo json_encode(['message' => 'If an account with that email and role exists, password reset instructions have been sent. Please check your email (and spam folder).']);

if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
exit;
?>