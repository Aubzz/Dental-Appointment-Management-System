<?php
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    $_SESSION['patient_record_message'] = ['type' => 'error', 'text' => 'Unauthorized.'];
    header('Location: receptionist_patient_records.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING));
    $last_name = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING));
    $phone_number = trim(filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $dob_str = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $assigned_doctor_id = filter_input(INPUT_POST, 'assigned_doctor_id', FILTER_VALIDATE_INT);
    // medicalInfo is no longer submitted from the form

    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (!empty($email) && !$email) $errors[] = "Invalid email format.";
    
    $dob_db_format = null;
    if (!empty($dob_str)) {
        $dob_obj = DateTime::createFromFormat('Y-m-d', $dob_str);
        if (!$dob_obj || $dob_obj->format('Y-m-d') !== $dob_str) {
            $errors[] = "Invalid Date of Birth format. Please use YYYY-MM-DD.";
        } else {
            $dob_db_format = $dob_obj->format('Y-m-d');
        }
    }

    if (empty($errors)) {
        $default_password_plain = "Patient123"; // Consider a better way to handle initial passwords
        $default_password_hash = password_hash($default_password_plain, PASSWORD_DEFAULT);

        // SQL query updated: removed medicalInfo.
        // Assuming your 'patients' table DOES NOT have an 'address' column based on previous removals.
        // If it does, you'd add 'address' here and in bind_param if you collect it.
        // Also, your table has medicalInfo, but the form doesn't send it. So we insert NULL or default.
        // Let's assume if not sent, it should be NULL or an empty string for medicalInfo.
        $medicalInfo_db = null; // Or handle as empty string '' if column doesn't allow NULL

        $sql = "INSERT INTO patients (firstName, lastName, phoneNumber, email, password, dob, assigned_doctor_id, medicalInfo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // 8 placeholders
        
        if ($stmt = $conn->prepare($sql)) {
            $assigned_doctor_id_db = empty($assigned_doctor_id) ? null : $assigned_doctor_id;
            
            $stmt->bind_param("ssssssis", 
                $first_name, 
                $last_name, 
                $phone_number, 
                $email, 
                $default_password_hash,
                $dob_db_format, 
                $assigned_doctor_id_db,
                $medicalInfo_db // Passing NULL or empty for medicalInfo
            );

            if ($stmt->execute()) {
                $_SESSION['patient_record_message'] = ['type' => 'success', 'text' => 'New patient added successfully!'];
            } else {
                $_SESSION['patient_record_message'] = ['type' => 'error', 'text' => 'Error adding patient: ' . $stmt->error];
                error_log("Add patient error: " . $stmt->error . " SQL: " . $sql);
            }
            $stmt->close();
        } else {
             $_SESSION['patient_record_message'] = ['type' => 'error', 'text' => 'Database error preparing statement.'];
             error_log("Prepare add patient error: " . $conn->error);
        }
    } else {
        $_SESSION['patient_record_message'] = ['type' => 'error', 'text' => implode("<br>", $errors)];
    }
    header('Location: receptionist_patient_records.php');
    exit;
}
?>