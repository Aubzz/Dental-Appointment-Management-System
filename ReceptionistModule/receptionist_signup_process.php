<?php
// receptionist_signup_process.php
require_once '../config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'errors' => [], 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") { // This is the IF statement starting around line 8
    $form_data_to_return = $_POST;

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email_input = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $receptionistId = trim($_POST['receptionist_id'] ?? '');

    // --- Server-Side Validation ---
    if (empty($firstName)) { $response['errors'][] = "First name is required."; }
    if (empty($lastName)) { $response['errors'][] = "Last name is required."; }
    if (empty($email_input)) { $response['errors'][] = "Email is required."; }
    elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) { $response['errors'][] = "Invalid email format."; }
    if (empty($password_input)) { $response['errors'][] = "Password is required."; }
    
    if (strlen($password_input) < 8) { $response['errors'][] = "Password must be at least 8 characters long."; }
    if (!preg_match('/[A-Z]/', $password_input)) { $response['errors'][] = "Password must contain at least one uppercase letter."; }
    if (!preg_match('/[a-z]/', $password_input)) { $response['errors'][] = "Password must contain at least one lowercase letter."; }
    if (!preg_match('/[0-9]/', $password_input)) { $response['errors'][] = "Password must contain at least one number."; }

    if ($password_input !== $confirmPassword) { $response['errors'][] = "Passwords do not match."; }
    if ($role !== 'receptionist') { $response['errors'][] = "Invalid role submission for this form."; }

    if (empty($phoneNumber)) { $response['errors'][] = "Phone number is required."; }
    elseif (!preg_match('/^\d{11}$/', $phoneNumber)) { $response['errors'][] = "Phone number must be exactly 11 digits."; }
    
    if (empty($receptionistId)) { 
        $response['errors'][] = "Receptionist ID is required.";
    } elseif (!preg_match('/^REP-\d{4}$/', $receptionistId)) { 
        $response['errors'][] = "Receptionist ID format is invalid. Expected REP-XXXX.";
    }


    // Check if email already exists
    if (empty($response['errors']) && !empty($email_input)) {
        $sql_check_email = "SELECT id FROM receptionists WHERE email = ?";
        if ($stmt_check_email = $conn->prepare($sql_check_email)) {
            $stmt_check_email->bind_param("s", $email_input);
            if ($stmt_check_email->execute()) {
                $stmt_check_email->store_result();
                if ($stmt_check_email->num_rows > 0) {
                    $response['errors'][] = "This email is already registered as a receptionist.";
                }
            } else {
                $response['errors'][] = "Error validating email uniqueness.";
                error_log("Receptionist email check execute error: " . $stmt_check_email->error);
            }
            $stmt_check_email->close();
        } else {
            $response['errors'][] = "Error preparing email uniqueness check.";
            error_log("Receptionist email check prepare error: " . $conn->error);
        }
    }

    // Check if Receptionist ID already exists
    if (empty($response['errors']) && !empty($receptionistId)) {
        $sql_check_repid = "SELECT id FROM receptionists WHERE employeeId = ?";
        if ($stmt_check_repid = $conn->prepare($sql_check_repid)) {
            $stmt_check_repid->bind_param("s", $receptionistId);
            if ($stmt_check_repid->execute()) {
                $stmt_check_repid->store_result();
                if ($stmt_check_repid->num_rows > 0) {
                    $response['errors'][] = "This Receptionist ID is already registered.";
                }
            } else {
                $response['errors'][] = "Error validating Receptionist ID uniqueness.";
                error_log("Receptionist ID check execute error: " . $stmt_check_repid->error);
            }
            $stmt_check_repid->close();
        } else {
            $response['errors'][] = "Error preparing Receptionist ID uniqueness check.";
            error_log("Receptionist ID check prepare error: " . $conn->error);
        }
    }

    // --- Database Insertion ---
    if (empty($response['errors'])) {
        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
        $sql_insert = "INSERT INTO receptionists (firstName, lastName, email, password, phoneNumber, employeeId) VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("ssssss", $firstName, $lastName, $email_input, $hashed_password, $phoneNumber, $receptionistId);

            if ($stmt_insert->execute()) {
                $response['success'] = true;
                $response['message'] = "Receptionist account created. Please wait for admin verification.";
                $_SESSION['success_message'] = "Receptionist account created. Please wait for admin verification before signing in.";
            } else {
                $response['errors'][] = "Account creation failed. Please try again.";
                error_log("Receptionist insert execute error: " . $stmt_insert->error . " SQL: " . $sql_insert);
            }
            $stmt_insert->close();
        } else {
            $response['errors'][] = "Error preparing account creation statement.";
            error_log("Receptionist insert prepare error: " . $conn->error);
        }
    }

    if (!$response['success'] && !empty($response['errors'])) {
        $_SESSION['signup_errors'] = $response['errors'];
        unset($form_data_to_return['password'], $form_data_to_return['confirm_password']);
        $_SESSION['form_data'] = $form_data_to_return;
    }

} 
else {
    $response['errors'][] = "Invalid request method.";
}

if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
echo json_encode($response);
exit;
?>