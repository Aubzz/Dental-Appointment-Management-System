<?php
// signin.php
session_start(); // MUST be the very first line before any output

header('Content-Type: application/json');
require_once 'db_config.php'; // Includes DB connection and MYSQL_ENCRYPTION_KEY

// --- Get and Decode JSON Data ---
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// --- Basic Validation ---
if (
    !isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
    !isset($data['password']) || empty($data['password']) ||
    !isset($data['role']) || !in_array($data['role'], ['client', 'receptionist', 'dentist'])
) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid input data.']);
     if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
    exit;
}

// --- Assign Variables ---
$email_plain_input = trim($data['email']);
$password_plain_input = $data['password'];
$role_input = $data['role'];
$key = MYSQL_ENCRYPTION_KEY; // Get key from config

// --- Prepare SQL to SELECT by role and DECRYPT relevant fields ---
$sql = "SELECT
            id,
            AES_DECRYPT(email, ?) as decrypted_email,
            password,
            AES_DECRYPT(firstName, ?) as decrypted_firstName
        FROM users
        WHERE role = ?";

if ($stmt = $mysqli->prepare($sql)) {

    $stmt->bind_param("sss", $key, $key, $role_input);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $user_found_and_verified = false;

        while ($row = $result->fetch_assoc()) {
            if ($row['decrypted_email'] !== null && $row['decrypted_email'] === $email_plain_input) {
                if (password_verify($password_plain_input, $row['password'])) {
                    // Password matches! User is authenticated.
                    $user_found_and_verified = true;
                    $user_id = $row['id'];
                    $display_name = ($row['decrypted_firstName'] !== null) ? $row['decrypted_firstName'] : 'User';

                    // **** START SESSION ****
                    session_regenerate_id(true); // Prevent session fixation

                    $_SESSION["loggedin"] = true;
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["role"] = $role_input;
                    $_SESSION["firstName"] = $display_name;

                    // Send success response
                    http_response_code(200);
                    echo json_encode([
                        'message' => 'Sign in successful!',
                        'user' => ['firstName' => $display_name, 'role' => $role_input]
                    ]);
                    break; // Exit the while loop
                }
            }
        } // end while
        $result->free();

        if (!$user_found_and_verified) {
             http_response_code(401);
             echo json_encode(['message' => 'Invalid email, password, or role combination.']);
        }
    } else {
        error_log("Error executing signin select statement: (" . $stmt->errno . ") " . $stmt->error);
        http_response_code(500);
        echo json_encode(['message' => 'Error during login. Please try again later.']);
    }
    $stmt->close();
} else {
    error_log("Error preparing signin select statement: (" . $mysqli->errno . ") " . $mysqli->error);
    http_response_code(500);
    echo json_encode(['message' => 'Database error during login preparation.']);
}
$mysqli->close();
?>