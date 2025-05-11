<?php
// UserModule/book_appointment_process.php
require_once '../config.php'; // Includes session_start(), $conn

// --- 1. Initial Checks ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['form_message'] = ['type' => 'error', 'text' => "Invalid request method."]; // Use form_message for dashboard display
    header("location: patient_dashboard.php");
    exit;
}
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    $_SESSION['form_message'] = ['type' => 'error', 'text' => "You must be logged in as a patient to book an appointment."];
    header("location: patient_signin.php");
    exit;
}
if (!$conn || $conn->connect_error) {
    error_log("book_appointment_process.php - DB Connection Error: " . ($conn ? $conn->connect_error : 'conn variable not set'));
    $_SESSION['form_message'] = ['type' => 'error', 'text' => 'Database connection error. Please try again later.'];
    header("location: patient_dashboard.php");
    exit;
}

// --- 2. Retrieve and Sanitize POST Data ---
$patientIdFromForm = $_POST['patientId'] ?? null;
$appointmentDateStr = trim($_POST['appointmentDate'] ?? '');
$selectedStartTimeStr = trim($_POST['selectedStartTime'] ?? ''); // This is HH:MM from the hidden input
$reasonAndServiceFromForm = trim($_POST['reasonForVisit'] ?? ''); // This will be used for service_type and/or notes
$loggedInUserId = (int)$_SESSION['user_id'];

// --- 3. Server-Side Validation ---
$errors = [];

if ($patientIdFromForm === null || (int)$patientIdFromForm !== $loggedInUserId) {
    $errors[] = "Authorization error: Booking user mismatch.";
    error_log("Booking attempt mismatch: Session User ID {$loggedInUserId} vs POSTed Patient ID {$patientIdFromForm}");
}

$appointmentDate = null;
if (empty($appointmentDateStr)) {
    $errors[] = "Appointment date is required.";
} else {
    $appointmentDate = DateTime::createFromFormat('Y-m-d', $appointmentDateStr);
    $today = new DateTime(); $today->setTime(0,0,0);
    if (!$appointmentDate || $appointmentDate->format('Y-m-d') !== $appointmentDateStr) {
        $errors[] = "Invalid date format provided."; $appointmentDate = null;
    } elseif ($appointmentDate < $today) {
        $errors[] = "Cannot book appointments for past dates."; $appointmentDate = null;
    }
    if ($appointmentDate) {
        $day_of_week = (int)$appointmentDate->format('w');
        $days_closed = [0]; // Sunday
        if (in_array($day_of_week, $days_closed)) {
             $errors[] = "The clinic is closed on the selected date."; $appointmentDate = null;
        }
    }
}

$appointmentStartTime = null;
$selectedStartTimeDbStr = null; // To store HH:MM:SS for DB
if (empty($selectedStartTimeStr)) {
    $errors[] = "Appointment time is required. Please select an available slot.";
} else {
    // The selectedStartTimeStr is HH:MM from the JS selection.
    // We might need to append :00 for DB storage if your column is TIME.
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $selectedStartTimeStr)) {
         $errors[] = "Invalid time format selected.";
    } else {
        $selectedStartTimeDbStr = $selectedStartTimeStr . ':00'; // Convert HH:MM to HH:MM:SS
        if ($appointmentDate) {
             try {
                 $appointmentStartTime = new DateTime($appointmentDateStr . ' ' . $selectedStartTimeDbStr);
                 $now = new DateTime();
                 if ($appointmentDate->format('Y-m-d') === $now->format('Y-m-d') && $appointmentStartTime < $now) {
                     $errors[] = "The selected time slot is in the past."; $appointmentStartTime = null;
                 }
             } catch (Exception $e) {
                 $errors[] = "Invalid date/time combination."; $appointmentStartTime = null;
                 error_log("Error creating DateTime for booking: " . $e->getMessage());
             }
        }
    }
}

if (empty($reasonAndServiceFromForm)) {
    $errors[] = "Reason for visit / Service type is required.";
} elseif (strlen($reasonAndServiceFromForm) > 500) { // Max length for service/notes combined
    $errors[] = "Reason for visit is too long (max 500 characters).";
}

// --- 4. If Validation Passes, Check Availability AGAIN (Server-side) ---
// This is important to prevent race conditions if the slot was booked by someone else
// while the patient was filling the form.
if (empty($errors) && $appointmentDate && $appointmentStartTime && $selectedStartTimeDbStr) {
    $slot_duration_minutes = 60; // Assuming 1-hour slots
    $proposedEndTime = (clone $appointmentStartTime)->add(new DateInterval('PT' . $slot_duration_minutes . 'M'));
    $proposedEndTimeStr = $proposedEndTime->format('H:i:s');

    // Check for overlap, excluding cancelled appointments.
    // This query assumes you have an `end_time` column in your appointments table.
    // If not, you can simplify by only checking if `appointment_time` matches.
    $sql_check_overlap = "SELECT id FROM appointments
                          WHERE appointment_date = ?
                          AND status NOT IN ('CANCELLED', 'REJECTED', 'PENDING_PATIENT_ACTION') /* Don't conflict with cancelled or other non-blocking statuses */
                          AND (
                                (appointment_time < ? AND end_time > ?) OR -- Overlaps if existing starts before new ends, AND existing ends after new starts
                                (appointment_time = ?) -- Exact start time match
                              )";
    // If no end_time column:
    // $sql_check_overlap = "SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status NOT IN ('CANCELLED', 'REJECTED')";

    $is_slot_available = true;
    if ($stmt_check = $conn->prepare($sql_check_overlap)) {
        // If using end_time:
        $stmt_check->bind_param("ssss", $appointmentDateStr, $proposedEndTimeStr, $selectedStartTimeDbStr, $selectedStartTimeDbStr);
        // If NOT using end_time:
        // $stmt_check->bind_param("ss", $appointmentDateStr, $selectedStartTimeDbStr);
        
        if ($stmt_check->execute()) {
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Sorry, the selected time slot ({$appointmentStartTime->format('h:i A')}) became unavailable just now. Please choose another time.";
                $is_slot_available = false;
            }
        } else {
             $errors[] = "Could not verify appointment availability. Please try again.";
             error_log("Availability check execute error: " . $stmt_check->error);
             $is_slot_available = false;
        }
        $stmt_check->close();
    } else {
         $errors[] = "Could not prepare availability check. Please try again.";
         error_log("Availability check prepare error: " . $conn->error);
         $is_slot_available = false;
    }

    // --- 5. If Still No Errors and Slot Available, Insert Appointment with PENDING status ---
    if (empty($errors) && $is_slot_available) {

        // Define service_type and notes based on form input
        $service_type = $reasonAndServiceFromForm; // Or parse it if you have a more structured input
        $notes = $reasonAndServiceFromForm;        // Could also be separate or empty

        $sql_insert = "INSERT INTO appointments 
                       (patient_id, appointment_date, appointment_time, end_time, service_type, notes, status, created_by_id, attending_dentist, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW())"; 
                       // attending_dentist is NULL for PENDING, created_by_id refers to who initiated the booking (patient in this case)
        
        $initial_status = 'PENDING'; // <<< CHANGED TO PENDING
        $created_by = $loggedInUserId; // The patient themselves created this request

        if ($stmt_insert = $conn->prepare($sql_insert)) {
            // Parameters: patient_id(i), date(s), time(s), end_time(s), service_type(s), notes(s), status(s), created_by(i)
            $stmt_insert->bind_param("issssssi",
                                     $loggedInUserId,
                                     $appointmentDateStr,
                                     $selectedStartTimeDbStr, // HH:MM:SS
                                     $proposedEndTimeStr,     // HH:MM:SS
                                     $service_type,
                                     $notes,
                                     $initial_status,
                                     $created_by
                                    );

            if ($stmt_insert->execute()) {
                $new_appointment_id = $stmt_insert->insert_id;
                $_SESSION['form_message'] = ['type' => 'success', 'text' => "Your appointment request for " . $appointmentDate->format('M d, Y') . " at " . $appointmentStartTime->format('h:i A') . " has been submitted and is PENDING review."];
                
                // TODO: Create a notification for admin/receptionist about this new PENDING request.
                // Example: insert_notification($admin_id, 'New Appointment Request', "Patient ID {$loggedInUserId} requested an appointment.", "admin_appointments.php?view_request={$new_appointment_id}");

                header("location: patient_appointments.php"); // Redirect to their appointments list
                exit();
            } else {
                $errors[] = "Failed to submit appointment request due to a database error. Please try again.";
                error_log("Appointment insert execute error: " . $stmt_insert->error . " SQL: " . $sql_insert);
            }
            $stmt_insert->close();
        } else {
            $errors[] = "Failed to prepare appointment request. Please try again later.";
            error_log("Appointment insert prepare error: " . $conn->error . " SQL: " . $sql_insert);
        }
    }
}

// --- 6. Handle Errors by Redirecting Back ---
if (!empty($errors)) {
    $_SESSION['form_message'] = ['type' => 'error', 'text' => implode("<br>", $errors)];
    // Redirect back to the dashboard, or a specific booking page if you have one.
    // The modal on dashboard should ideally re-open or show these errors.
    // For simplicity, just redirecting to dashboard for now.
    header("location: patient_dashboard.php?booking_attempt_failed=1"); 
    exit();
}

// Close connection - should be handled by PHP automatically at script end if not persistent
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>