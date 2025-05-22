<?php
require_once '../config.php'; // Adjust path if your config is elsewhere
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['error' => 'Unauthorized access. Please log in.']);
    exit;
}

$doctor_id = $_SESSION['user_id'] ?? null;
$selected_date_str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);

if (empty($selected_date_str) || empty($doctor_id)) {
    echo json_encode(['error' => 'Date parameter or doctor is missing.']);
    exit;
}

// Validate date format (YYYY-MM-DD)
$selected_date_obj = DateTime::createFromFormat('Y-m-d', $selected_date_str);
if (!$selected_date_obj || $selected_date_obj->format('Y-m-d') !== $selected_date_str) {
    echo json_encode(['error' => 'Invalid date format. Please use YYYY-MM-DD.']);
    exit;
}

$formatted_date_for_db = $selected_date_obj->format('Y-m-d');

$daily_appointments = [];
$sql = "
    SELECT 
        a.appointment_time, 
        a.service_type,
        p.firstName as patient_firstName,
        p.lastName as patient_lastName
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.appointment_date = ? 
      AND a.attending_dentist = ?
      AND a.status NOT IN ('Cancelled', 'No Show', 'Completed')
    ORDER BY a.appointment_time ASC
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("si", $formatted_date_for_db, $doctor_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $time_obj = new DateTime($row['appointment_time']);
            $formatted_time = $time_obj->format('h:i A');
            $daily_appointments[] = [
                'time' => $row['appointment_time'],
                'time_formatted' => $formatted_time,
                'service_type' => htmlspecialchars($row['service_type']),
                'patient_name' => htmlspecialchars(trim($row['patient_firstName'] . ' ' . $row['patient_lastName'])),
            ];
        }
    } else {
        error_log("Error executing schedule query: " . $stmt->error);
        $daily_appointments = ['error' => 'Could not retrieve schedule. Statement execution failed.'];
    }
    $stmt->close();
} else {
    error_log("Error preparing schedule query: " . $conn->error);
    $daily_appointments = ['error' => 'Could not retrieve schedule. Database error.'];
}

if ($conn) {
    $conn->close();
}

echo json_encode($daily_appointments);
exit;
?>