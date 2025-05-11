<?php
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Unauthorized access.'];
    header('Location: receptionist_appointment.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $attending_dentist_id = filter_input(INPUT_POST, 'attending_dentist_id', FILTER_VALIDATE_INT); // Changed from doctor_id
    $appointment_date_str = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time_24h = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING); // Expects HH:MM (24-hour)
    $service_type = trim(filter_input(INPUT_POST, 'service_type', FILTER_SANITIZE_STRING)); // Changed from purpose
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING)); // Added notes

    $errors = [];

    if (empty($patient_id)) { $errors[] = "Patient selection is required."; }
    if (empty($attending_dentist_id)) { $errors[] = "Dentist selection is required."; }
    if (empty($appointment_date_str)) { $errors[] = "Appointment date is required."; }
    if (empty($appointment_time_24h) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $appointment_time_24h)) {
        $errors[] = "Valid appointment time (HH:MM 24-hour format) is required.";
    }
    if (empty($service_type)) { $errors[] = "Service type is required."; }

    $appointment_datetime = date_create_from_format('Y-m-d', $appointment_date_str);
    if (!$appointment_datetime) {
        $errors[] = "Invalid appointment date format.";
    }

    if (empty($errors)) {
        $db_appointment_date = $appointment_datetime->format('Y-m-d');
        // $appointment_time_24h is already in HH:MM format, suitable for TIME type.
        // Add seconds if your DB TIME type needs it, e.g., $appointment_time_24h . ':00'
        $db_appointment_time = $appointment_time_24h; 

        // Status from your screenshot is 'SCHEDULED'. Let's use that.
        $status = 'SCHEDULED'; 
        $created_by_id = $_SESSION['user_id'] ?? null;

        // Assuming your table has end_time which might be calculated or set later
        // For simplicity, we'll set it to NULL or a calculated value if you have service durations
        $end_time = null; // Or calculate: date('H:i:s', strtotime($db_appointment_time . ' +1 hour'));

        $sql_insert_appointment = "INSERT INTO appointments 
                                   (patient_id, attending_dentist, appointment_date, appointment_time, end_time, service_type, notes, status, created_by_id) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql_insert_appointment)) {
            $stmt->bind_param("iissssssi", 
                $patient_id, 
                $attending_dentist_id, 
                $db_appointment_date, 
                $db_appointment_time,
                $end_time,
                $service_type,
                $notes,
                $status,
                $created_by_id
            );

            if ($stmt->execute()) {
                $_SESSION['form_message'] = ['type' => 'success', 'text' => 'Appointment booked successfully!'];
            } else {
                $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Error booking appointment: ' . $stmt->error];
                error_log("Error booking appointment: " . $stmt->error . " SQL: " . $sql_insert_appointment);
            }
            $stmt->close();
        } else {
            $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Database error preparing statement: ' . $conn->error];
            error_log("Database error preparing insert appointment: " . $conn->error);
        }
    } else {
        $_SESSION['form_message'] = ['type' => 'error', 'text' => implode("<br>", $errors)];
    }

    if ($conn) $conn->close();
    header('Location: receptionist_appointment.php');
    exit;

} else {
    header('Location: receptionist_appointment.php');
    exit;
}
?>