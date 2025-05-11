<?php
// ReceptionistModule/process_confirm_booking.php
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    $_SESSION['dashboard_message'] = ['type' => 'error', 'text' => 'Unauthorized access.'];
    header('Location: receptionist_dashboard.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and Validate Inputs
    // patient_id would ideally come from a hidden field in the modal form,
    // populated when the request details are fetched, linking to the actual patient record.
    // For now, we assume it's not directly submitted or needs to be looked up.
    
    $attending_dentist_id = filter_input(INPUT_POST, 'attending_dentist_id', FILTER_VALIDATE_INT);
    $appointment_date_str = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time_24h = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING); // HH:MM (24-hour)
    $service_type = trim(filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_STRING));
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING));
    $original_request_id = filter_input(INPUT_POST, 'original_request_id', FILTER_VALIDATE_INT);

    $errors = [];

    if (empty($attending_dentist_id)) { $errors[] = "Dentist selection is required."; }
    if (empty($appointment_date_str)) { $errors[] = "Appointment date is required."; }
    if (empty($appointment_time_24h) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $appointment_time_24h)) {
        $errors[] = "Valid appointment time (HH:MM 24-hour format) is required.";
    }
    if (empty($service_type)) { $errors[] = "Service type is required."; }
    if (empty($original_request_id)) { $errors[] = "Original request ID is missing."; }


    // Fetch patient_id from the original request (appointment record)
    $patient_id = null;
    if ($original_request_id) {
        $sql_get_patient = "SELECT patient_id FROM appointments WHERE id = ?";
        if($stmt_get_pat = $conn->prepare($sql_get_patient)){
            $stmt_get_pat->bind_param("i", $original_request_id);
            if($stmt_get_pat->execute()){
                $result_pat = $stmt_get_pat->get_result();
                if($row_pat = $result_pat->fetch_assoc()){
                    $patient_id = $row_pat['patient_id'];
                } else {
                    $errors[] = "Could not find original request to get patient ID.";
                }
            } else { $errors[] = "Error fetching patient ID from request."; }
            $stmt_get_pat->close();
        } else { $errors[] = "DB error preparing patient ID fetch."; }
    }
    if (empty($patient_id) && empty($errors)) { // Add this check if patient_id is critical and not found
        $errors[] = "Patient ID could not be determined from the original request.";
    }


    $appointment_datetime = null;
    if(empty($errors)) { // Only proceed if no errors so far
        $appointment_datetime = date_create_from_format('Y-m-d', $appointment_date_str);
        if (!$appointment_datetime) {
            $errors[] = "Invalid appointment date format.";
        }
    }


    if (empty($errors)) {
        $db_appointment_date = $appointment_datetime->format('Y-m-d');
        $db_appointment_time = $appointment_time_24h;
        $status = 'SCHEDULED'; // Or 'Confirmed' directly
        $created_by_id = $_SESSION['user_id'] ?? null;
        $end_time = null; // Calculate if needed

        // Option 1: Update the existing 'Pending' appointment record
        $sql_update_appointment = "UPDATE appointments 
                                   SET attending_dentist = ?, appointment_date = ?, appointment_time = ?, end_time = ?, 
                                       service_type = ?, notes = ?, status = ?, updated_at = NOW(), created_by_id = ? 
                                   WHERE id = ? AND status = 'Pending'";
        
        if ($stmt = $conn->prepare($sql_update_appointment)) {
            $stmt->bind_param("issssssii", 
                $attending_dentist_id, 
                $db_appointment_date, 
                $db_appointment_time,
                $end_time,
                $service_type,
                $notes,
                $status,
                $created_by_id,
                $original_request_id 
            );

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['dashboard_message'] = ['type' => 'success', 'text' => 'Appointment confirmed and booked successfully!'];
                } else {
                    $_SESSION['dashboard_message'] = ['type' => 'warning', 'text' => 'Appointment may have already been processed or original request not found.'];
                }
            } else {
                $_SESSION['dashboard_message'] = ['type' => 'error', 'text' => 'Error confirming booking: ' . $stmt->error];
                error_log("Error confirming booking: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $_SESSION['dashboard_message'] = ['type' => 'error', 'text' => 'Database error preparing statement: ' . $conn->error];
            error_log("Database error (confirm booking): " . $conn->error);
        }
    } else {
        $_SESSION['dashboard_message'] = ['type' => 'error', 'text' => implode("<br>", $errors)];
    }

    if ($conn) $conn->close();
    header('Location: receptionist_dashboard.php');
    exit;

} else {
    header('Location: receptionist_dashboard.php');
    exit;
}
?>