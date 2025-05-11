<?php
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    $_SESSION['patient_record_message'] = ['type' => 'error', 'text' => 'Unauthorized.'];
    header('Location: receptionist_patient_records.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $first_name = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING));
    $last_name = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING));
    $phone_number = trim(filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $dob_str = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $assigned_doctor_id = filter_input(INPUT_POST, 'assigned_doctor_id', FILTER_VALIDATE_INT);
    // medicalInfo is no longer submitted from the form

    $errors = [];
    if (empty($patient_id)) $errors[] = "Patient ID is missing.";
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
        // SQL query updated: removed medicalHistory (and address if it was there).
        // Assuming your DB table has medicalInfo, but since it's not editable in the form,
        // we WON'T include it in the UPDATE statement, leaving its existing value unchanged.
        // If you wanted to clear it, you would set medicalInfo = NULL or medicalInfo = ''.
        $sql = "UPDATE patients SET 
                firstName = ?, lastName = ?, phoneNumber = ?, email = ?, 
                dob = ?, assigned_doctor_id = ?,
                updated_at = NOW()
                WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $assigned_doctor_id_db = empty($assigned_doctor_id) ? null : $assigned_doctor_id;

            // Bind parameters updated: sssssii (7 params + id for WHERE)
            $stmt->bind_param("sssssii", 
                $first_name, 
                $last_name, 
                $phone_number, 
                $email,
                $dob_db_format,
                $assigned_doctor_id_db,
                $patient_id
            );

            if ($stmt->execute()) {
                $_SESSION['patient_record_message'] = ['type' => 'success', 'text' => 'Patient record updated successfully!'];
            } else {
                $_SESSION['patient_record_message'] = ['type' => 'error', 'text' => 'Error updating patient: ' . $stmt->error];
                 error_log("Update patient error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $_SESSION['patient_record_message'] = ['type' => 'error', 'text' => 'Database error preparing statement.'];
            error_log("Prepare update patient error: " . $conn->error);
        }
    } else {
        $_SESSION['patient_record_message'] = ['type' => 'error', 'text' => implode("<br>", $errors)];
    }
    header('Location: receptionist_patient_records.php');
    exit;
}
?>