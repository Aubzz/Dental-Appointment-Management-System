<?php
// UserModule/get_available_slots.php
require_once '../config.php'; // Includes $conn

header('Content-Type: application/json'); // Set response type to JSON
$response = ['success' => false, 'slots' => [], 'message' => '']; // Default response

// === Check Database Connection ===
if (!$conn || $conn->connect_error) {
    error_log("get_available_slots.php - DB Connection Error: " . ($conn ? $conn->connect_error : 'conn variable not set after require'));
    $response['message'] = 'Error connecting to the database.';
    echo json_encode($response);
    exit;
}

// --- Configuration ---
$clinic_open_hour = 9;
$clinic_close_hour = 17;
$slot_duration_minutes = 60;
$days_closed = [0]; // Sunday
// --- End Configuration ---

if (!isset($_GET['date'])) {
    $response['message'] = 'Date not provided.';
    echo json_encode($response);
    exit;
}

$selected_date_str = $_GET['date'];
$selected_date = DateTime::createFromFormat('Y-m-d', $selected_date_str);

// Validate date format and ensure it's not in the past or too far in the future (optional)
$today = new DateTime();
$today->setTime(0,0,0); // Compare date part only
if (!$selected_date || $selected_date->format('Y-m-d') !== $selected_date_str || $selected_date < $today) {
     $response['message'] = ($selected_date && $selected_date < $today) ? 'Cannot book appointments for past dates.' : 'Invalid date format provided.';
     echo json_encode($response);
     exit;
}

$day_of_week = (int)$selected_date->format('w');
if (in_array($day_of_week, $days_closed)) {
    $response['message'] = 'The clinic is closed on the selected day.';
    $response['success'] = true; // Still a 'successful' check, just no slots
    echo json_encode($response);
    exit;
}

// --- Fetch Existing Appointments ---
$existing_appointments = [];
// *** CHOOSE ONE SQL VERSION BASED ON YOUR TABLE ***

// VERSION A: If you HAVE an 'end_time' column in 'appointments' table
// $sql = "SELECT appointment_time, end_time FROM appointments WHERE appointment_date = ?";

// VERSION B: If you DO NOT HAVE 'end_time', select only start time
$sql = "SELECT appointment_time FROM appointments WHERE appointment_date = ?";
// *** END OF SQL CHOICE ***


if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $selected_date_str);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
             if (!empty($row['appointment_time'])) {
                 try {
                     $appt_start = new DateTime($selected_date_str . ' ' . $row['appointment_time']);

                     // *** CHOOSE ONE END TIME LOGIC ***
                     // VERSION A: If using 'end_time' column
                     // if (!empty($row['end_time'])) {
                     //     $appt_end = new DateTime($selected_date_str . ' ' . $row['end_time']);
                     // } else {
                     //     // Fallback if end_time is somehow empty, calculate it
                     //     $appt_end = (clone $appt_start)->add(new DateInterval('PT' . $slot_duration_minutes . 'M'));
                     //     error_log("Warning: Missing end_time for appointment on {$selected_date_str} at {$row['appointment_time']}. Calculating based on duration.");
                     // }

                     // VERSION B: If NOT using 'end_time' column, calculate it here
                     $appt_end = (clone $appt_start)->add(new DateInterval('PT' . $slot_duration_minutes . 'M'));
                     // *** END OF END TIME LOGIC CHOICE ***

                     // Add to list if both start and end were determined
                     if (isset($appt_start) && isset($appt_end)) {
                           $existing_appointments[] = [
                               'start' => $appt_start,
                               'end' => $appt_end
                           ];
                     }

                 } catch (Exception $e) {
                     error_log("Error parsing existing appointment time: {$row['appointment_time']} on {$selected_date_str} - " . $e->getMessage());
                 }
            }
        }
    } else {
        error_log("Error executing existing appointments fetch: " . $stmt->error);
        $response['message'] = 'Error fetching existing appointments.';
        echo json_encode($response);
        exit;
    }
    $stmt->close();
} else {
    error_log("Error preparing existing appointments fetch: " . $conn->error);
    $response['message'] = 'Database error fetching schedule.';
    echo json_encode($response);
    exit;
}

// --- Generate Potential Slots and Filter ---
$available_slots = [];
$start_time = (new DateTime($selected_date_str))->setTime($clinic_open_hour, 0);
$end_time = (new DateTime($selected_date_str))->setTime($clinic_close_hour, 0);
$interval = new DateInterval('PT' . $slot_duration_minutes . 'M');
$now = new DateTime(); // Current time for comparison

while ($start_time < $end_time) {
    $potential_slot_start = clone $start_time;
    $potential_slot_end = (clone $start_time)->add($interval);
    $is_available = true;

    // Check if slot start time is in the past (if today)
    if ($selected_date->format('Y-m-d') === $now->format('Y-m-d') && $potential_slot_start < $now) {
        $is_available = false;
    }

    // Check for conflicts if still potentially available
    if ($is_available) {
        foreach ($existing_appointments as $existing) {
            // Standard overlap check: (ExistingStart < PotentialEnd) AND (ExistingEnd > PotentialStart)
            if ($existing['start'] < $potential_slot_end && $existing['end'] > $potential_slot_start) {
                $is_available = false;
                break;
            }
        }
    }

    if ($is_available) {
        $available_slots[] = $potential_slot_start->format('H:i'); // Format as HH:MM (24-hour)
    }

    $start_time->add($interval); // Move to next potential slot start time
}

if (empty($available_slots)) {
     $response['message'] = 'No available time slots found for the selected date.';
}

$response['success'] = true;
$response['slots'] = $available_slots;

echo json_encode($response); // Output the final JSON response

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit; // Ensure script stops here
?>