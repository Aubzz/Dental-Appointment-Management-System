<?php
require_once '../config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'errors' => [], 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_data_to_return = $_POST;

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email_input = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $experience_years = trim($_POST['experience_years'] ?? '');
    $consultation_fee = trim($_POST['consultation_fee'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    // --- Profile Picture Upload ---
    $profile_picture_path = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $target = 'uploads/doctors/' . uniqid('doc_', true) . '.' . $ext;
        if (!is_dir('uploads/doctors')) mkdir('uploads/doctors', 0777, true);
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target)) {
            $profile_picture_path = $target;
        }
    }

    // --- Server-Side Validation ---
    if (empty($firstName)) { $response['errors'][] = "First name is required."; }
    if (empty($lastName)) { $response['errors'][] = "Last name is required."; }
    if (empty($email_input)) { $response['errors'][] = "Email is required."; }
    elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) { $response['errors'][] = "Invalid email format."; }
    if (empty($password_input)) { $response['errors'][] = "Password is required."; }

    // Password strength checks
    if (strlen($password_input) < 8) { $response['errors'][] = "Password must be at least 8 characters long."; }
    if (!preg_match('/[A-Z]/', $password_input)) { $response['errors'][] = "Password must contain at least one uppercase letter."; }
    if (!preg_match('/[a-z]/', $password_input)) { $response['errors'][] = "Password must contain at least one lowercase letter."; }
    if (!preg_match('/[0-9]/', $password_input)) { $response['errors'][] = "Password must contain at least one number."; }

    if ($password_input !== $confirmPassword) { $response['errors'][] = "Passwords do not match."; }

    if (empty($phoneNumber)) { $response['errors'][] = "Phone number is required."; }
    elseif (!preg_match('/^\d{11}$/', $phoneNumber)) { $response['errors'][] = "Phone number must be exactly 11 digits."; }

    if (empty($specialty)) { $response['errors'][] = "Specialty is required."; }
    if ($experience_years === '' || !is_numeric($experience_years) || $experience_years < 0) { $response['errors'][] = "Years of experience must be a non-negative number."; }
    if ($consultation_fee === '' || !is_numeric($consultation_fee) || $consultation_fee < 0) { $response['errors'][] = "Consultation fee must be a non-negative number."; }

    // --- Uniqueness Checks ---
    // Check if email already exists
    if (empty($response['errors']) && !empty($email_input)) {
        $sql_check_email = "SELECT id FROM doctors WHERE email = ?";
        if ($stmt_check_email = $conn->prepare($sql_check_email)) {
            $stmt_check_email->bind_param("s", $email_input);
            if ($stmt_check_email->execute()) {
                $stmt_check_email->store_result();
                if ($stmt_check_email->num_rows > 0) {
                    $response['errors'][] = "This email is already registered as a doctor.";
                }
            } else {
                $response['errors'][] = "Error validating email uniqueness.";
                error_log("Doctor email check execute error: " . $stmt_check_email->error);
            }
            $stmt_check_email->close();
        } else {
            $response['errors'][] = "Error preparing email uniqueness check.";
            error_log("Doctor email check prepare error: " . $conn->error);
        }
    }

    // Check if name (first + last) already exists
    if (empty($response['errors']) && !empty($firstName) && !empty($lastName)) {
        $sql_check_name = "SELECT id FROM doctors WHERE firstName = ? AND lastName = ?";
        if ($stmt_check_name = $conn->prepare($sql_check_name)) {
            $stmt_check_name->bind_param("ss", $firstName, $lastName);
            if ($stmt_check_name->execute()) {
                $stmt_check_name->store_result();
                if ($stmt_check_name->num_rows > 0) {
                    $response['errors'][] = "A doctor with this name already exists.";
                }
            } else {
                $response['errors'][] = "Error validating name uniqueness.";
                error_log("Doctor name check execute error: " . $stmt_check_name->error);
            }
            $stmt_check_name->close();
        } else {
            $response['errors'][] = "Error preparing name uniqueness check.";
            error_log("Doctor name check prepare error: " . $conn->error);
        }
    }

    // --- Database Insertion ---
    if (empty($response['errors'])) {
        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
        $sql_insert = "INSERT INTO doctors (firstName, lastName, email, password, phoneNumber, specialty, experience_years, consultation_fee, profile_picture_path, bio, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param(
                "sssssssdss",
                $firstName,
                $lastName,
                $email_input,
                $hashed_password,
                $phoneNumber,
                $specialty,
                $experience_years,
                $consultation_fee,
                $profile_picture_path,
                $bio
            );
            if ($stmt_insert->execute()) {
                $response['success'] = true;
                $response['message'] = "Doctor account created. Please wait for admin verification.";
                $_SESSION['success_message'] = "Doctor account created. Please wait for admin verification before signing in.";
            } else {
                $response['errors'][] = "Account creation failed. Please try again.";
                error_log("Doctor insert execute error: " . $stmt_insert->error . " SQL: " . $sql_insert);
            }
            $stmt_insert->close();
        } else {
            $response['errors'][] = "Error preparing account creation statement.";
            error_log("Doctor insert prepare error: " . $conn->error);
        }
    }

    if (!$response['success'] && !empty($response['errors'])) {
        $_SESSION['signup_errors'] = $response['errors'];
        unset($form_data_to_return['password'], $form_data_to_return['confirm_password']);
        $_SESSION['form_data'] = $form_data_to_return;
    }
}
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
echo json_encode($response);
exit;
?>