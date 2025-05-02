<?php
// signup.php (using MySQL AES_ENCRYPT)
header('Content-Type: application/json');

require_once 'db_config.php'; // Includes DB connection and MYSQL_ENCRYPTION_KEY

// --- Get and Decode JSON Data ---
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// --- Basic Validation ---
if (
    !isset($data['firstName']) || empty(trim($data['firstName'])) ||
    !isset($data['lastName']) || empty(trim($data['lastName'])) ||
    !isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
    !isset($data['password']) || empty($data['password']) ||
    !isset($data['role']) || !in_array($data['role'], ['client', 'receptionist', 'dentist'])
) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid input data. Please check all fields.']);
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
    exit;
}

// --- Assign variables (plaintext) ---
$firstName_plain = trim($data['firstName']);
$lastName_plain = trim($data['lastName']);
$email_plain = trim($data['email']);
$password_plain = $data['password'];
$role = $data['role'];
$key = MYSQL_ENCRYPTION_KEY; // Get the key from config

// --- Hash the password ---
$hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);
if ($hashed_password === false) {
    error_log("Password hashing failed.");
    http_response_code(500);
    echo json_encode(['message' => 'Error processing password.']);
    if (isset($mysqli)) $mysqli->close();
    exit;
}

// --- Prepare SQL with MySQL AES_ENCRYPT function ---
// Ensure DB columns for firstName, lastName, email are BLOB or VARBINARY
$sql_insert = "INSERT INTO users (firstName, lastName, email, password, role)
               VALUES (AES_ENCRYPT(?, ?), AES_ENCRYPT(?, ?), AES_ENCRYPT(?, ?), ?, ?)";

if ($stmt_insert = $mysqli->prepare($sql_insert)) {

    // Bind parameters: 8 strings total
    $stmt_insert->bind_param("ssssssss",
        $firstName_plain, $key,
        $lastName_plain, $key,
        $email_plain, $key,
        $hashed_password,
        $role
    );

    // Attempt to execute the prepared statement
    if ($stmt_insert->execute()) {
        http_response_code(201); // Created
        echo json_encode(['message' => 'Sign up successful! You can now sign in.']);
    } else {
        // Check for unique constraint violation if applicable (e.g., on email_hash column if added)
         if ($mysqli->errno == 1062) { // Error code for duplicate entry
              http_response_code(409); // Conflict
              echo json_encode(['message' => 'This email address might already be registered.']);
         } else {
            error_log("Error executing insert statement: (" . $stmt_insert->errno . ") " . $stmt_insert->error);
            http_response_code(500);
            echo json_encode(['message' => 'Registration failed. Please try again later.']);
         }
    }
    $stmt_insert->close();
} else {
     error_log("Error preparing insert statement: (" . $mysqli->errno . ") " . $mysqli->error);
     http_response_code(500);
     echo json_encode(['message' => 'Database error during registration preparation.']);
}
$mysqli->close();
?>