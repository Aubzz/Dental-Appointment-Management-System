<?php
require_once '../config.php'; // Adjust path if your config is elsewhere

header('Content-Type: application/json');

// --- Basic Security/Authentication (Highly Recommended for real app) ---
// session_start(); // Assumed to be started in config.php
// if (!isset($_SESSION['loggedin'])) { // Or check for specific role
//     echo json_encode(['error' => 'Unauthorized access. Please log in.']);
//     exit;
// }

$response_data = [];

$selected_date_str = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);

if (empty($selected_date_str)) {
    echo json_encode(['error' => 'Date parameter is missing.']);
    exit;
}

// Validate date format (YYYY-MM-DD)
$selected_date_obj = DateTime::createFromFormat('Y-m-d', $selected_date_str);
if (!$selected_date_obj || $selected_date_obj->format('Y-m-d') !== $selected_date_str) {
    echo json_encode(['error' => 'Invalid date format. Please use YYYY-MM-DD.']);
    exit;
}

$formatted_date_for_db = $selected_date_obj->format('Y-m-d');

// --- Fetch Appointments for the selected date ---
$daily_appointments = [];
$sql = "
    SELECT 
        a.appointment_time, /* TIME type from DB, e.g., 09:00:00 */
        a.service_type,
        p.firstName as patient_firstName,
        p.lastName as patient_lastName,
        d.firstName as doctor_firstName,
        d.lastName as doctor_lastName
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    LEFT JOIN doctors d ON a.attending_dentist = d.id
    WHERE a.appointment_date = ? 
      AND a.status NOT IN ('Cancelled', 'No Show', 'Completed') /* Show active/upcoming statuses */
    ORDER BY a.appointment_time ASC
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $formatted_date_for_db);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Format time for display (e.g., 09:00 AM)
            $time_obj = new DateTime($row['appointment_time']);
            $formatted_time = $time_obj->format('h:i A');

            $daily_appointments[] = [
                'time' => $row['appointment_time'], // Original 24-hour time if needed by JS
                'time_formatted' => $formatted_time,
                'service_type' => htmlspecialchars($row['service_type']),
                'patient_name' => htmlspecialchars(trim($row['patient_firstName'] . ' ' . $row['patient_lastName'])),
                'doctor_name' => (!empty($row['doctor_firstName'])) ? htmlspecialchars(trim($row['doctor_lastName'])) : null, // Only last name for brevity
                // 'color' => '#somecolor' // You can add logic to assign colors based on service type or doctor
            ];
        }
        $response_data = $daily_appointments;
    } else {
        error_log("Error executing schedule query: " . $stmt->error);
        $response_data = ['error' => 'Could not retrieve schedule. Statement execution failed.'];
    }
    $stmt->close();
} else {
    error_log("Error preparing schedule query: " . $conn->error);
    $response_data = ['error' => 'Could not retrieve schedule. Database error.'];
}

if ($conn) {
    $conn->close();
}

echo json_encode($response_data);
exit;
?>