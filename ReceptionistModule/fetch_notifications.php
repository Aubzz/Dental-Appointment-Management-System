<?php
// ReceptionistModule/fetch_notifications.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    echo json_encode(['error' => 'Unauthorized', 'count' => 0, 'notifications' => []]);
    exit;
}

$notifications_data = [];
$sql_requests = "
    SELECT 
        a.id as request_id, 
        p.firstName as patient_firstName,
        p.lastName as patient_lastName,
        a.appointment_date as preferred_date, 
        a.appointment_time as preferred_time, 
        a.service_type,
        a.created_at as request_created_at
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.status = 'Pending' 
    ORDER BY a.created_at DESC
    LIMIT 5 /* Limit to a reasonable number for the dropdown */
";

$result_requests = $conn->query($sql_requests);
if ($result_requests) {
    while ($row = $result_requests->fetch_assoc()) {
        $notifications_data[] = [
            'id' => 'req_' . $row['request_id'], // Unique ID for the notification item
            'type' => 'new_request',
            'request_id' => $row['request_id'],
            'title' => 'New Appointment Request',
            'message' => htmlspecialchars($row['patient_firstName'] . ' ' . $row['patient_lastName']) . ' requested a ' . htmlspecialchars($row['service_type']) . '.',
            'time_ago' => time_elapsed_string($row['request_created_at']), // Helper function below
            'link' => 'receptionist_dashboard.php#request-' . $row['request_id'] // Or link to appointments page
        ];
    }
} else {
    error_log("Fetch notifications DB error: " . $conn->error);
}

// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day',
        'h' => 'hour', 'i' => 'minute', 's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

echo json_encode([
    'count' => count($notifications_data), 
    'notifications' => $notifications_data
]);

$conn->close();
?>