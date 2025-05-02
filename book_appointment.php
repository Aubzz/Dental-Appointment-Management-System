<?php
// book_appointment.php
session_start(); // Access session variables
header('Content-Type: application/json'); // Send JSON responses
require_once 'db_config.php'; // Need DB connection

// --- 1. Authentication Check ---
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['message' => 'You must be logged in to book.']);
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
    exit;
}

// --- 2. Get Data & Role ---
$role = $_SESSION["role"] ?? null;
$loggedInUserId = $_SESSION["user_id"] ?? null;

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$doctorId = filter_var($data['doctorId'] ?? null, FILTER_VALIDATE_INT);
$timeslotId = filter_var($data['timeslotId'] ?? null, FILTER_SANITIZE_STRING); // Adjust filter based on what timeslotId is
$appointmentDate = filter_var($data['appointmentDate'] ?? null, FILTER_SANITIZE_STRING); // Basic filter
$appointmentTime = filter_var($data['appointmentTime'] ?? null, FILTER_SANITIZE_STRING); // Basic filter

// Patient details potentially sent by staff
$patientIdFromRequest = filter_var($data['patientId'] ?? null, FILTER_VALIDATE_INT);
$newPatientData = $data['newPatientInfo'] ?? null; // Needs deeper validation if used

// --- Basic validation of essential booking info ---
// Adapt based on whether you use timeslotId or date/time primarily
if (!$doctorId || (!$timeslotId && (!$appointmentDate || !$appointmentTime))) {
     http_response_code(400);
     echo json_encode(['message' => 'Missing required appointment details.']);
     if (isset($mysqli)) $mysqli->close();
     exit;
}

$patientIdToBook = null; // The final patient ID for the booking

// --- 3. Role-Based Logic (RBAC & ABAC elements) ---
if ($role === 'client') {
    $patientIdToBook = $loggedInUserId;
    error_log("Booking attempt by PATIENT (ID: $loggedInUserId) for self.");
} elseif ($role === 'receptionist' /* || $role === 'admin' */) {
    error_log("Booking attempt by STAFF (ID: $loggedInUserId, Role: $role).");
    if ($patientIdFromRequest) {
         // TODO: Validate that $patientIdFromRequest exists in users table
         $patientIdToBook = $patientIdFromRequest;
         error_log("Staff booking for EXISTING patient ID: $patientIdToBook");
    } elseif ($newPatientData) {
        error_log("Staff booking requires NEW patient creation - LOGIC NOT IMPLEMENTED");
        http_response_code(501); echo json_encode(['message' => 'Booking for new patients is not yet implemented.']);
        if (isset($mysqli)) $mysqli->close(); exit;
    } else {
        http_response_code(400); echo json_encode(['message' => 'Staff must specify patient details.']);
        if (isset($mysqli)) $mysqli->close(); exit;
    }
} else {
    http_response_code(403); echo json_encode(['message' => 'Your role cannot book appointments this way.']);
    if (isset($mysqli)) $mysqli->close(); exit;
}

// --- 4. Proceed with Booking Creation ---
if ($patientIdToBook) {
    // --- TODO: Add ABAC Check - Is the requested slot available? ---
    $isSlotAvailable = true; // <<== REPLACE WITH ACTUAL DATABASE CHECK
    error_log("Availability check needed for Doctor: $doctorId, Slot/Time: $timeslotId / $appointmentTime on $appointmentDate");
    if (!$isSlotAvailable) {
         http_response_code(409); echo json_encode(['message' => 'Selected time slot is not available.']);
         if (isset($mysqli)) $mysqli->close(); exit;
    }
    // --- End TODO ---

    // Prepare the INSERT statement (adjust columns/parameters as needed)
    // Example uses timeslotId - modify if using date/time directly
    $sql_insert = "INSERT INTO appointments (patient_user_id, doctor_id, timeslot_id, appointment_date, appointment_time, status, created_at)
                   VALUES (?, ?, ?, ?, ?, 'Scheduled', NOW())";

    if ($stmt = $mysqli->prepare($sql_insert)) {
        // Adjust bind_param types: i=integer, s=string
        $stmt->bind_param("iisss", $patientIdToBook, $doctorId, $timeslotId, $appointmentDate, $appointmentTime);

        if ($stmt->execute()) {
            // --- TODO: Update timeslot status to 'booked' ---
            http_response_code(201); echo json_encode(['message' => 'Appointment booked successfully!']);
        } else { error_log("Error executing appointment insert: (" . $stmt->errno . ") " . $stmt->error); http_response_code(500); echo json_encode(['message' => 'Failed to save appointment.']); }
        $stmt->close();
    } else { error_log("Error preparing appointment insert: (" . $mysqli->errno . ") " . $mysqli->error); http_response_code(500); echo json_encode(['message' => 'Database error during booking preparation.']); }
} else { http_response_code(500); echo json_encode(['message' => 'Could not determine patient for booking.']); }

if (isset($mysqli)) $mysqli->close();
exit;
?>