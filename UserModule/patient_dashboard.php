<?php
// patient_dashboard.php

// Add this AT THE VERY TOP for debugging during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php'; // Includes session_start() and $conn

// Check if the user is logged in and has the correct role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== "patient") {
    $_SESSION = array(); 
    session_destroy();
    header("location: patient_signin.php");
    exit;
}

// Get user details from session
$userId = isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 0;
$userFirstName = htmlspecialchars($_SESSION["user_firstName"] ?? 'Patient');
$userLastName = htmlspecialchars($_SESSION["user_lastName"] ?? ''); // Initialize, might be empty


if ($userId === 0) {
    $_SESSION = array(); 
    session_destroy();
    header("location: patient_signin.php?error=session_data_missing");
    exit;
}


// --- Fetch Data for Dashboard Cards ---
$upcoming_appointments_count = 0;
$sql_upcoming_count = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND (appointment_date > CURDATE() OR (appointment_date = CURDATE() AND appointment_time >= CURTIME())) AND status IN ('SCHEDULED', 'CONFIRMED', 'PENDING')";
if ($stmt_upcoming_count = $conn->prepare($sql_upcoming_count)) { 
    $stmt_upcoming_count->bind_param("i", $userId); 
    if ($stmt_upcoming_count->execute()) { 
        $result_upcoming_count = $stmt_upcoming_count->get_result(); 
        if ($row_uc = $result_upcoming_count->fetch_assoc()) {
            $upcoming_appointments_count = (int)$row_uc['count']; 
        }
    } else { error_log("DB Error (upcoming_count): " . $stmt_upcoming_count->error); }
    $stmt_upcoming_count->close(); 
} else { error_log("DB Prepare Error (upcoming_count for user {$userId}): " . $conn->error); }

$completed_appointments_count = 0;
$sql_completed_count = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status = 'COMPLETED'";
if ($stmt_completed_count = $conn->prepare($sql_completed_count)) { 
    $stmt_completed_count->bind_param("i", $userId); 
    if ($stmt_completed_count->execute()) { 
        $result_completed_count = $stmt_completed_count->get_result(); 
        if ($row_cc = $result_completed_count->fetch_assoc()) {
            $completed_appointments_count = (int)$row_cc['count']; 
        }
    } else { error_log("DB Error (completed_count): " . $stmt_completed_count->error); }
    $stmt_completed_count->close(); 
} else { error_log("DB Prepare Error (completed_count for user {$userId}): " . $conn->error); }

// --- Fetch Upcoming Appointments for Dashboard Table ---
$upcoming_appointments_list = [];
$sql_upcoming_list = "
    SELECT a.attending_dentist, a.appointment_date, a.appointment_time, a.status, a.service_type,
           d.firstName as doctor_firstName, d.lastName as doctor_lastName 
    FROM appointments a
    LEFT JOIN doctors d ON a.attending_dentist = d.id
    WHERE a.patient_id = ? 
      AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.appointment_time >= CURTIME())) 
      AND a.status IN ('SCHEDULED', 'CONFIRMED', 'PENDING') 
    ORDER BY a.appointment_date ASC, a.appointment_time ASC 
    LIMIT 5";
if ($stmt_upcoming_list = $conn->prepare($sql_upcoming_list)) { 
    $stmt_upcoming_list->bind_param("i", $userId); 
    if ($stmt_upcoming_list->execute()) { 
        $result_upcoming_list = $stmt_upcoming_list->get_result(); 
        while ($row = $result_upcoming_list->fetch_assoc()) { 
            $upcoming_appointments_list[] = $row; 
        } 
    } else { error_log("DB Error (upcoming_list): " . $stmt_upcoming_list->error); }
    $stmt_upcoming_list->close(); 
} else { error_log("DB Prepare Error (upcoming_list for user {$userId}): " . $conn->error); }

// --- Fetch Unread Notifications Count (for nav and card) ---
$unread_notifications_count_for_badge = 0;
$sql_unread_badge = "SELECT COUNT(*) as count FROM notifications WHERE patient_id = ? AND is_read = 0";
if ($stmt_unread_badge = $conn->prepare($sql_unread_badge)) {
    $stmt_unread_badge->bind_param("i", $userId);
    if ($stmt_unread_badge->execute()) {
        $result_badge = $stmt_unread_badge->get_result();
        if($row_badge = $result_badge->fetch_assoc()){
            $unread_notifications_count_for_badge = (int)$row_badge['count'];
        }
    } else { error_log("DB Error (unread_badge_count): " . $stmt_unread_badge->error); }
    $stmt_unread_badge->close();
} else { error_log("DB Prepare Error (unread_badge_count for user {$userId}): " . $conn->error); }


// --- PHP FOR EMBEDDED PROFILE VIEW ---
$patient_profile_data = null;
$profile_errors = []; 
$next_appointment_display_profile = "No upcoming appointments"; 

$sql_patient_profile = "SELECT id, firstName, lastName, email, phoneNumber, dob, medicalInfo FROM patients WHERE id = ?";
if ($stmt_patient_prof = $conn->prepare($sql_patient_profile)) {
    $stmt_patient_prof->bind_param("i", $userId);
    if ($stmt_patient_prof->execute()) {
        $result_patient_prof = $stmt_patient_prof->get_result();
        if ($result_patient_prof->num_rows == 1) {
            $patient_profile_data = $result_patient_prof->fetch_assoc();
            if (!empty($patient_profile_data['medicalInfo'])) { 
                $decryptedMedicalInfo = decrypt_data($patient_profile_data['medicalInfo']); 
                $patient_profile_data['medicalInfoDecrypted'] = ($decryptedMedicalInfo === false || $decryptedMedicalInfo === null) ? "[Encrypted data - retrieval issue or no data]" : $decryptedMedicalInfo; 
            } else { 
                $patient_profile_data['medicalInfoDecrypted'] = 'Not provided'; 
            }
            if (!empty($patient_profile_data['dob'])) {
                try { 
                    $birthDate = new DateTime($patient_profile_data['dob']); 
                    $today = new DateTime(); 
                    $patient_profile_data['age'] = $today->diff($birthDate)->y; 
                } catch (Exception $e) { 
                    $patient_profile_data['age'] = 'N/A'; 
                    error_log("Error parsing DOB for embedded profile (patient ID {$userId}): " . $e->getMessage());
                } 
            } else { 
                $patient_profile_data['age'] = 'N/A'; 
            }
            $patient_profile_data['gender'] = $patient_profile_data['gender'] ?? 'N/A'; // Not in DB, will be N/A
            $patient_profile_data['address'] = $patient_profile_data['address'] ?? 'N/A'; // Not in DB, will be N/A
        } else { 
            $profile_errors[] = "Patient record for embedded profile view not found. (ID: {$userId})"; 
        }
    } else { 
        $profile_errors[] = "Error fetching patient profile details for embedded view."; 
        error_log("Err patient profile fetch for ID {$userId} (embedded): " . $stmt_patient_prof->error); 
    }
    $stmt_patient_prof->close();
} else { 
    $profile_errors[] = "DB error preparing patient profile details for embedded view."; 
    error_log("Err prepare patient profile (embedded): " . $conn->error); 
}

if ($patient_profile_data) { 
    $sql_next_appt_profile = "SELECT appointment_date, appointment_time FROM appointments WHERE patient_id = ? AND (appointment_date > CURDATE() OR (appointment_date = CURDATE() AND appointment_time >= CURTIME())) AND status IN ('SCHEDULED', 'CONFIRMED', 'PENDING') ORDER BY appointment_date ASC, appointment_time ASC LIMIT 1";
    if ($stmt_next_prof = $conn->prepare($sql_next_appt_profile)) {
        $stmt_next_prof->bind_param("i", $userId);
        if ($stmt_next_prof->execute()) { 
            $result_next_prof = $stmt_next_prof->get_result(); 
            if ($row_next_prof = $result_next_prof->fetch_assoc()) { 
                try { 
                    $next_date_prof = new DateTime($row_next_prof['appointment_date']); 
                    $next_appointment_display_profile = $next_date_prof->format('M d, Y'); 
                } catch (Exception $e) { /* Keep default */ } 
            } 
        } else { error_log("Err next appt profile fetch for ID {$userId}: " . $stmt_next_prof->error); }
        $stmt_next_prof->close();
    } else { error_log("Err prepare next appt profile: " . $conn->error); }
}
// --- END OF PHP FOR EMBEDDED PROFILE ---

// For Appointment Modal - Get full name for display
$patientFullNameForModal = htmlspecialchars($userFirstName); 
if (!empty($userLastName)) { // If lastName was set from session
    $patientFullNameForModal .= ' ' . htmlspecialchars($userLastName);
} elseif ($patient_profile_data && isset($patient_profile_data['lastName'])) { // Fallback to fetched profile data
    $patientFullNameForModal = htmlspecialchars($patient_profile_data['firstName'] . ' ' . $patient_profile_data['lastName']);
} 
// No further DB query for name if already constructed or session is primary source.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Basic styles for nav icon/badge, ensure vars are defined in style.css or here */
        :root {
            --primary-green: #0A744F; /* Main theme green */
            --dark-green: #004d40;    /* Darker green for text or accents */
            --white: #fff;
            --card-bg-color: #fff;
            --shadow-color: rgba(0,0,0,0.1);
            --body-bg-fallback: #eee;
            --text-muted: #6c757d;
            --text-color: #333;
            --old-dark-green: #004d40; /* For nav icon hover */
            --danger-red: #dc3545;     /* For notification badge */
            --old-white: #ffffff;      /* For notification badge border */
            --very-light-green-bg: #e0f2f1; /* For table headers */
            --light-border-color: #ddd;   /* For table borders */
            --status-pending-bg: #ffeeba; 
            --status-pending-text: #856404;
            --status-scheduled-bg: #d1ecf1; 
            --status-scheduled-text: #0c5460;
            --status-confirmed-bg: #d4edda; 
            --status-confirmed-text: #155724;
            --status-completed-bg: #e2e3e5; 
            --status-completed-text: #383d41;
            --status-cancelled-bg: #f8d7da; 
            --status-cancelled-text: #721c24;
            --status-no_show-bg: #f5c6cb; 
            --status-no_show-text: #721c24;
            --input-border: #ccc;
            --focus-border-color: #0A744F;
            --focus-box-shadow: rgba(10, 116, 79, 0.25);
            --nav-link-hover-bg: #e0f0eb;
        }

        /* --- Dashboard Specific Styles --- */
        main.dashboard-page { padding-top: 20px; }
        .dashboard-header { background-color: var(--primary-green); color: var(--white); padding: 10px 20px; border-radius: 6px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; font-size: 0.95em; }
        .dashboard-header .welcome-msg { font-weight: 600; }
        .dashboard-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background-color: var(--card-bg-color); padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px var(--shadow-color); border: 1px solid var(--body-bg-fallback); display: flex; flex-direction: column; align-items: center; gap: 10px; position: relative; }
        .summary-card i.fa-calendar-check, .summary-card i.fa-history, .summary-card i.fa-bell { font-size: 2.2em; color: var(--primary-green); margin-bottom: 5px; }
        .summary-card .count { font-size: 2em; font-weight: 700; color: var(--dark-green); line-height: 1.1; }
        .summary-card .label { font-size: 0.9em; color: var(--text-muted); font-weight: 500; }
        .summary-card .more-info { margin-top: 10px; font-size: 0.85em; color: var(--primary-green); text-decoration: none; font-weight: 600; }
        .summary-card .more-info:hover { text-decoration: underline; }
        .summary-card .notification-count-badge { position: absolute; top: 10px; right: 15px; background-color: var(--danger-red); color: white; border-radius: 50%; width: 22px; height: 22px; font-size: 0.75em; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        
        .dashboard-main-content-wrapper { }
        
        .dashboard-view-content { display: grid; grid-template-columns: 220px 1fr; gap: 30px; }
        .dashboard-sidebar .sidebar-btn { display: block; background-color: var(--white); color: var(--primary-green); border: 1px solid var(--primary-green); padding: 12px 15px; margin-bottom: 15px; border-radius: 6px; text-decoration: none; text-align: left; font-weight: 600; transition: all 0.2s ease; font-size: 0.9em; }
        .dashboard-sidebar .sidebar-btn i { margin-right: 10px; width: 1.2em; text-align: center;}
        .dashboard-sidebar .sidebar-btn:hover, .dashboard-sidebar .sidebar-btn.active-view { background-color: var(--primary-green); color: var(--white); transform: translateX(3px); }
        
        .dashboard-table-area { background-color: var(--white); padding: 25px; border-radius: 8px; box-shadow: 0 3px 8px var(--shadow-color); border: 1px solid var(--body-bg-fallback); }
        .dashboard-table-area h3 { color: var(--dark-green); font-size: 1.25em; font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--light-border-color); }
        .dashboard-table-area table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
        .dashboard-table-area th, .dashboard-table-area td { padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--light-border-color); }
        .dashboard-table-area thead th { background-color: var(--very-light-green-bg); color: var(--dark-green); font-weight: 600; white-space: nowrap; }
        .dashboard-table-area tbody tr:last-child td { border-bottom: none; }
        .dashboard-table-area tbody tr:hover { background-color: var(--very-light-green-bg); }
        .dashboard-table-area .status { font-weight: 600; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; text-align: center; display: inline-block; min-width: 90px; }
        .status-scheduled { background-color: var(--status-scheduled-bg); color: var(--status-scheduled-text); }
        .status-pending { background-color: var(--status-pending-bg); color: var(--status-pending-text); }
        .status-confirmed { background-color: var(--status-confirmed-bg); color: var(--status-confirmed-text); }
        .status-completed { background-color: var(--status-completed-bg); color: var(--status-completed-text); }
        .status-cancelled { background-color: var(--status-cancelled-bg); color: var(--status-cancelled-text); }
        .status-no_show { background-color: var(--status-no_show-bg); color: var(--status-no_show-text); }

        .main-nav-icon { font-size: 1.1em; padding: 0 8px; display: inline-block; vertical-align: middle; position: relative; }
        .main-nav-icon:hover { color: var(--old-dark-green); }
        .main-nav-icon .notification-badge { position: absolute; top: -2px; right: -2px; background-color: var(--danger-red); color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 0.65em; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid var(--old-white); }

        #profileContent { display: none; }
        #profileContent .profile-page-container { max-width: 900px; margin: 0 auto; padding: 20px 15px; }
        #profileContent .page-title { text-align: center; margin-bottom: 25px; font-size: 1.8em; color: var(--dark-green); }
        #profileContent .profile-grid { display: flex; justify-content: center; }
        #profileContent .profile-left { max-width: 700px; width: 100%; }
        #profileContent .patient-summary-card { padding: 20px; margin-bottom: 20px; background-color: var(--card-bg-color); border-radius: 8px; box-shadow: 0 2px 5px var(--shadow-color); border: 1px solid var(--body-bg-fallback); }
        #profileContent .patient-summary-card .summary-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
        #profileContent .patient-summary-card .detail-item { padding: 8px 0; }
        #profileContent .patient-summary-card .detail-label { font-weight: 600; color: var(--text-muted); display:block; margin-bottom:3px; font-size:0.9em;}
        #profileContent .patient-summary-card .detail-value { color: var(--dark-green); font-size:1em; }
        #profileContent .personal-info-card { background-color: var(--card-bg-color); padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px var(--shadow-color); border: 1px solid var(--body-bg-fallback); }
        #profileContent .personal-info-card .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid var(--light-border-color); }
        #profileContent .personal-info-card .card-header h3 { margin: 0; font-size: 1.2em; color: var(--dark-green); }
        #profileContent .personal-info-card .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px 20px; }
        #profileContent .personal-info-card .info-item {}
        #profileContent .personal-info-card .info-label { font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 3px; font-size: 0.9em; }
        #profileContent .personal-info-card .info-value, 
        #profileContent .personal-info-card input.info-input, 
        #profileContent .personal-info-card select.info-input, 
        #profileContent .personal-info-card textarea.info-input { color: var(--text-color); font-size: 0.95em; width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        #profileContent .personal-info-card .info-value { border: 1px solid transparent; padding-left: 0; padding-right: 0; background-color: transparent;} 
        #profileContent .personal-info-card .info-input { }
        #profileContent .personal-info-card .full-width { grid-column: 1 / -1; }
        #profileContent .personal-info-card .edit-info-btn { background: none; border: none; color: var(--primary-green); cursor: pointer; font-size: 1em; padding: 5px; }
        #profileContent .personal-info-card .edit-info-btn:hover { color: var(--dark-green); }
        #profileContent .personal-info-card .edit-actions { display: none; margin-top: 15px; gap: 10px; } 
        #profileContent .personal-info-card .edit-actions .btn { font-size: 0.9em; padding: 6px 12px;}
        .error-messages ul { list-style-position: inside; padding-left:0; margin:0; } 
        .error-messages li { margin-bottom: 5px;}

        /* Notification Dropdown Styles (Patient Header) */
        .main-nav-item-wrapper { position: relative; display: inline-block; }
        .notifications-dropdown-patient { position: absolute; top: 100%; right: 0; width: 340px; max-height: 400px; overflow-y: auto; background-color: #fff; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.15); z-index: 1051; display: none; margin-top: 8px; }
        .notifications-dropdown-patient.show { display: block; }
        .notification-header-patient { padding: 12px 15px; font-weight: 600; font-size: 1em; border-bottom: 1px solid #eee; color: var(--dark-green, #004d40); }
        .notification-list-patient { }
        .notification-item-patient { border-bottom: 1px solid #f0f0f0; }
        .notification-item-patient:last-child { border-bottom: none; }
        .notification-item-patient a.notification-link-patient { display: flex; align-items: flex-start; padding: 12px 15px; text-decoration: none; color: inherit; transition: background-color 0.2s ease; }
        .notification-item-patient a.notification-link-patient:hover { background-color: #f7f7f7; }
        .notification-item-patient .notif-icon-patient { margin-right: 12px; font-size: 1.2em; color: var(--primary-green); padding-top: 2px; }
        .notification-item-patient .notif-content-patient { flex-grow: 1; }
        .notification-item-patient .notif-title-patient { font-weight: 600; font-size: 0.9em; color: var(--dark-text, #333); margin-bottom: 3px; }
        .notification-item-patient .notif-message-patient { font-size: 0.85em; color: #555; line-height: 1.4; margin-bottom: 4px; white-space: normal; word-break: break-word; }
        .notification-item-patient .notif-time-patient { font-size: 0.75em; color: #888; }
        .notification-item-patient.unread-patient a.notification-link-patient { background-color: #e8f8f5; }
        .no-notifications-patient { padding: 20px 15px; text-align: center; color: #777; font-style: italic; }
        .notification-footer-patient { padding: 10px 15px; text-align: center; border-top: 1px solid #eee; background-color: #f9f9f9; }
        .notification-footer-patient a { color: var(--primary-green); text-decoration: none; font-weight: 500; font-size: 0.9em; }
        .notification-footer-patient a:hover { text-decoration: underline; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); animation: fadeInModal 0.3s ease-out; }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; width: 90%; max-width: 550px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: slideInModal 0.3s ease-out; }
        .modal-header { padding: 15px 25px; background-color: var(--primary-green); color: white; border-top-left-radius: 7px; border-top-right-radius: 7px; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { margin: 0; font-size: 1.4em; font-weight: 600; }
        .modal-close-btn { color: white; background: none; border: none; font-size: 1.5em; cursor: pointer; padding: 0; line-height: 1; opacity: 0.8; }
        .modal-close-btn:hover { opacity: 1; }
        .modal-body { padding: 25px 30px; max-height: 70vh; overflow-y: auto; }
        .required-note { font-size: 0.85em; color: #777; margin-bottom: 15px; font-style: italic; }
        .modal .form-group { margin-bottom: 18px; }
        .modal label { display: block; margin-bottom: 6px; color: var(--text-muted); font-weight: 500; font-size: 0.9em; }
        .modal input[type="date"], .modal textarea, .modal select { width: 100%; padding: 10px 12px; border: 1px solid var(--input-border); border-radius: 6px; font-size: 0.95em; background-color: var(--white); color: var(--text-color); transition: border-color 0.2s ease, box-shadow 0.2s ease; box-sizing: border-box; }
        .modal input:focus, .modal textarea:focus, .modal select:focus { outline: none; border-color: var(--focus-border-color); box-shadow: 0 0 0 3px var(--focus-box-shadow); }
        .modal textarea { min-height: 60px; resize: vertical; }
        .modal .form-actions { text-align: right; margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee; }
        .modal .btn-submit { padding: 10px 25px; } 
        #timeSlotsContainer { margin-top: 10px; border: 1px solid #eee; padding: 10px; min-height: 50px; max-height: 150px; overflow-y: auto; display: flex; flex-wrap: wrap; gap: 8px; }
        .time-slot-button { padding: 8px 12px; border: 1px solid var(--primary-green); background-color: #fff; color: var(--primary-green); border-radius: 4px; cursor: pointer; transition: background-color 0.2s, color 0.2s; font-size: 0.9em; }
        .time-slot-button:hover { background-color: var(--nav-link-hover-bg); } 
        .time-slot-button.selected { background-color: var(--primary-green); color: #fff; font-weight: bold; }
        .loading-indicator, .time-slot-message { width: 100%; text-align: center; color: #777; font-style: italic; padding: 10px 0; }
        #timeSlotsError { color: var(--danger-red); font-size: 0.85em; margin-top: 5px; min-height: 1.2em; }
        @keyframes fadeInModal { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideInModal { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @media (max-width: 992px) { .dashboard-view-content { grid-template-columns: 180px 1fr; gap: 20px; } }
        @media (max-width: 768px) { .dashboard-summary-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); } .dashboard-view-content { grid-template-columns: 1fr; } .dashboard-sidebar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; } .dashboard-sidebar .sidebar-btn { flex-grow: 1; text-align: center; margin-bottom: 0; } }
        @media (max-width: 600px) { .dashboard-header { flex-direction: column; align-items: flex-start; gap: 5px; font-size: 0.9em; } .dashboard-summary-grid { grid-template-columns: 1fr; } .dashboard-sidebar { flex-direction: column; } .dashboard-table-area { padding: 15px; } .dashboard-table-area table { font-size: 0.85em; } .dashboard-table-area th, .dashboard-table-area td { padding: 10px 5px; } #profileContent .profile-page-container { padding: 0 10px;} #profileContent .personal-info-card .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="patient-area page-patient-dashboard">

    <div class="container">
        <header>
            <div class="logo">
                <img src="../images/tooth.png" alt="Tooth Logo" class="logo-icon">
                <h1>Escosia Dental Clinic</h1>
            </div>
            <nav>
                 <ul>
                    <li><a href="#" id="showDashboardLink" class="nav-item active">DASHBOARD</a></li>
                    <li><a href="doctors_patient.php" class="nav-item">DOCTORS</a></li>
                    <li><a href="patient_appointments.php" class="nav-item">APPOINTMENTS</a></li>
                    <li>
                        <div class="main-nav-item-wrapper">
                            <a href="#" title="Notifications" class="main-nav-icon nav-item" id="patientNotificationBell">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_notifications_count_for_badge > 0): ?>
                                    <span class="notification-badge" id="patientNotificationBadge"><?php echo $unread_notifications_count_for_badge; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="notifications-dropdown-patient" id="patientNotificationsDropdown">
                                <div class="notification-header-patient">Notifications</div>
                                <div class="notification-list-patient" id="patientNotificationList">
                                    <p class="no-notifications-patient">Loading notifications...</p>
                                </div>
                                <div class="notification-footer-patient">
                                    <a href="patient_notifications.php">View All Notifications</a>
                                </div>
                            </div>
                        </div>
                    </li>
                 </ul>
                 <div class="header-actions">
                    <!-- LOGOUT BUTTON REMOVED FROM TOP NAV -->
                 </div>
            </nav>
        </header>

        <div class="dashboard-main-content-wrapper">
            <div id="dashboardContent" class="active-content"> 
                <main class="dashboard-page">
                    <div class="dashboard-header">
                        <span class="welcome-msg">Welcome, <?php echo $userFirstName; ?>!</span>
                        <span class="date-time"> <i class="far fa-calendar-alt"></i> </span>
                    </div>
                    <section class="dashboard-summary-grid">
                        <div class="summary-card"> <i class="fas fa-calendar-check"></i> <span class="count"><?php echo $upcoming_appointments_count; ?></span> <p class="label">Upcoming Appointments</p> <a href="patient_appointments.php" class="more-info">View Details →</a> </div>
                        <div class="summary-card"> <i class="fas fa-history"></i> <span class="count"><?php echo $completed_appointments_count; ?></span> <p class="label">Completed Appointments</p> <a href="patient_appointments.php?filter=completed" class="more-info">View History →</a> </div>
                        <div class="summary-card"> <i class="fas fa-bell"></i> <span class="count"><?php echo $unread_notifications_count_for_badge; ?></span> <p class="label">Unread Notifications</p> <a href="patient_notifications.php" class="more-info">View Notifications →</a> <?php if ($unread_notifications_count_for_badge > 0): ?> <span class="notification-count-badge"><?php echo $unread_notifications_count_for_badge; ?></span> <?php endif; ?> </div>
                    </section>
                    <section class="dashboard-view-content">
                        <aside class="dashboard-sidebar">
                            <a href="#" class="sidebar-btn open-modal-btn"> <i class="fas fa-calendar-plus"></i> Book Appointment </a>
                            <a href="#" id="showProfileBtn" class="sidebar-btn"> <i class="fas fa-user-edit"></i> My Profile </a>
                            <a href="#" class="sidebar-btn"> <i class="fas fa-receipt"></i> Payment History </a> 
                            <a href="logout.php" class="sidebar-btn"> <i class="fas fa-sign-out-alt"></i> Logout </a>
                        </aside>
                        <div class="dashboard-table-area" id="upcoming-appointments-table">
                            <h3>Recent Upcoming Appointments</h3>
                            <table>
                                <thead> <tr> <th>Doctor</th><th>Service</th> <th>Date and Time</th> <th>Status</th> </tr> </thead>
                                <tbody>
                                <?php if (!empty($upcoming_appointments_list)): ?>
                                    <?php foreach ($upcoming_appointments_list as $appointment): ?>
                                    <tr> 
                                        <td>
                                            <?php 
                                            if (!empty($appointment['doctor_firstName'])) {
                                                echo "Dr. " . htmlspecialchars($appointment['doctor_firstName'] . ' ' . $appointment['doctor_lastName']);
                                            } else {
                                                echo "<span style='font-style:italic; color:#777;'>Pending Assignment</span>";
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['service_type'] ?? 'N/A'); ?></td> 
                                        <td> 
                                            <?php 
                                            try { 
                                                $date = new DateTime($appointment['appointment_date']); 
                                                $time = new DateTime($appointment['appointment_time']); 
                                                echo $date->format('M d, Y') . ' at ' . $time->format('h:i A'); 
                                            } catch (Exception $e) { echo "Invalid date/time"; } 
                                            ?> 
                                        </td> 
                                        <td> 
                                            <span class="status status-<?php echo strtolower(htmlspecialchars($appointment['status'])); ?>"> 
                                                <?php echo strtoupper(htmlspecialchars($appointment['status'])); ?> 
                                            </span> 
                                        </td> 
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr> <td colspan="4" style="text-align: center; color: var(--text-muted);">No upcoming appointments found.</td> </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </main>
            </div>

            <div id="profileContent"> 
                <div class="profile-page-container">
                    <main> 
                        <h2 class="page-title">Patient Profile</h2>
                        <?php if (!empty($profile_errors)): ?>
                            <div class="error-messages" style="background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin-bottom:20px; border-radius:5px; max-width: 700px; width: 100%;"><strong>Error!</strong><ul><?php foreach ($profile_errors as $p_error) { echo '<li>' . htmlspecialchars($p_error) . '</li>'; } ?></ul></div>
                        <?php endif; ?>
                        
                        <?php if ($patient_profile_data): ?>
                        <div class="profile-grid">
                             <div class="profile-left">
                                 <section class="patient-summary-card">
                                     <div class="summary-details">
                                         <div class="detail-item"> <span class="detail-label">Patient Name</span> <span class="detail-value" data-field="fullNameDisplay"><?php echo htmlspecialchars($patient_profile_data['firstName'] . ' ' . $patient_profile_data['lastName']); ?></span> </div>
                                         <div class="detail-item"> <span class="detail-label">Gender</span> <span class="detail-value" data-field="genderDisplay"><?php echo htmlspecialchars($patient_profile_data['gender'] ?? 'N/A'); ?></span> </div>
                                         <div class="detail-item"> <span class="detail-label">Age</span> <span class="detail-value" data-field="ageDisplay"><?php echo htmlspecialchars($patient_profile_data['age'] ?? 'N/A'); ?></span> </div>
                                         <div class="detail-item"> <span class="detail-label">Next Appointment</span> <span class="detail-value"><?php echo htmlspecialchars($next_appointment_display_profile); ?></span> </div>
                                     </div>
                                 </section>
                                 <section class="personal-info-card" id="personalInfoCardProfile">
                                     <div class="card-header">
                                        <h3>Personal Information</h3>
                                        <button class="edit-info-btn" data-target-card="personalInfoCardProfile"><i class="fas fa-pencil-alt"></i></button>
                                        <div class="edit-actions" style="display: none;">
                                            <button class="btn btn-primary btn-save-info" data-target-card="personalInfoCardProfile"><i class="fas fa-save"></i> Save</button>
                                            <button class="btn btn-secondary btn-cancel-edit" data-target-card="personalInfoCardProfile"><i class="fas fa-times"></i> Cancel</button>
                                        </div>
                                    </div>
                                     <div class="info-grid">
                                         <div class="info-item"><span class="info-label">First Name</span><span class="info-value" data-field="firstName"><?php echo htmlspecialchars($patient_profile_data['firstName']); ?></span></div>
                                         <div class="info-item"><span class="info-label">Last Name</span><span class="info-value" data-field="lastName"><?php echo htmlspecialchars($patient_profile_data['lastName']); ?></span></div>
                                         <div class="info-item"><span class="info-label">Email address</span><span class="info-value" data-field="email"><?php echo htmlspecialchars($patient_profile_data['email']); ?></span></div>
                                         <div class="info-item"><span class="info-label">Phone number</span><span class="info-value" data-field="phone"><?php echo htmlspecialchars($patient_profile_data['phoneNumber'] ?? 'N/A'); ?></span></div>
                                         <div class="info-item"> <span class="info-label">Date of Birth</span> <span class="info-value" data-field="dob"> <?php echo !empty($patient_profile_data['dob']) ? htmlspecialchars(date("F j, Y", strtotime($patient_profile_data['dob']))) : 'N/A'; ?> </span> </div>
                                         <div class="info-item"><span class="info-label">Address</span><span class="info-value" data-field="address"><?php echo htmlspecialchars($patient_profile_data['address'] ?? 'N/A'); ?></span></div>
                                         <div class="info-item full-width"> <span class="info-label">Medical Information</span> <span class="info-value" data-field="medicalInfo"><?php echo nl2br(htmlspecialchars($patient_profile_data['medicalInfoDecrypted'])); ?></span> </div>
                                     </div>
                                 </section>
                             </div>
                        </div>
                        <?php elseif (empty($profile_errors)): ?>
                            <p style="text-align: center;">Could not load patient profile details. Please try again later.</p>
                        <?php endif; ?>
                    </main>
                </div>
            </div>
        </div> 

    <div id="appointmentModal" class="modal">
         <div class="modal-content"> 
            <div class="modal-header"> 
                <h2>Book Appointment</h2> 
                <button class="modal-close-btn">×</button> 
            </div> 
            <div class="modal-body"> 
                <p>Booking appointment for: <strong><?php echo $patientFullNameForModal; ?></strong></p> 
                <hr style="margin: 15px 0;"> 
                <p class="required-note">Fields with * are required.</p> 
                <form id="appointmentBookingForm" action="book_appointment_process.php" method="POST"> 
                    <input type="hidden" name="patientId" value="<?php echo $userId; ?>"> 
                    <input type="hidden" name="selectedStartTime" id="selectedStartTimeHidden"> 
                    
                    <div class="form-group">
                        <label for="appointmentDateModal">*Date</label> 
                        <input type="date" id="appointmentDateModal" name="appointmentDate" required min="<?php echo date('Y-m-d'); ?>"> 
                    </div> 
                    <div class="form-group"> 
                        <label for="availableTimeSlots">*Available Time Slots</label> 
                        <div id="timeSlotsContainer"> 
                            <p class="time-slot-message">Please select a date first.</p> 
                        </div> 
                        <div id="timeSlotsError"></div> 
                    </div> 
                    <div class="form-group"> 
                        <label for="reasonForVisitModal">Reason for Visit / Service Type (Notes)</label> 
                        <textarea id="reasonForVisitModal" name="reasonForVisit" rows="3" placeholder="e.g., Check-up, Cleaning, Toothache..."></textarea> 
                    </div> 
                    <div class="form-actions"> 
                        <button type="submit" id="bookAppointmentSubmitBtn" class="btn btn-submit btn-primary" disabled>Book Appointment</button> 
                    </div> 
                </form> 
            </div> 
        </div>
    </div>

    <script src="script.js"></script> 
    <script> 
    document.addEventListener('DOMContentLoaded', () => {
        const dateTimeSpan = document.querySelector('.dashboard-header .date-time');
        if (dateTimeSpan) { 
            const updateDateTime = () => { 
                const now = new Date(); 
                const optionsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }; 
                const optionsTime = { hour: 'numeric', minute: '2-digit', hour12: true }; 
                const formattedDate = now.toLocaleDateString('en-US', optionsDate); 
                const formattedTime = now.toLocaleTimeString('en-US', optionsTime); 
                dateTimeSpan.innerHTML = `<i class="far fa-calendar-alt"></i> ${formattedDate} | ${formattedTime}`; 
            }; 
            updateDateTime(); 
            setInterval(updateDateTime, 60000);
        }

        const modal = document.getElementById('appointmentModal');
        const openModalBtns = document.querySelectorAll('.open-modal-btn');
        const closeModalBtn = modal ? modal.querySelector('.modal-close-btn') : null;
        const dateInputModal = modal ? modal.querySelector('#appointmentDateModal') : null;
        const timeSlotsContainerModal = modal ? modal.querySelector('#timeSlotsContainer') : null;
        const timeSlotsErrorModal = modal ? modal.querySelector('#timeSlotsError') : null;
        const hiddenTimeInputModal = modal ? modal.querySelector('#selectedStartTimeHidden') : null;
        const submitButtonModal = modal ? modal.querySelector('#bookAppointmentSubmitBtn') : null;
        const bookingFormModal = modal ? modal.querySelector('#appointmentBookingForm') : null;

        function openAppointmentModal() { 
            if(modal) { 
                modal.style.display = 'block'; 
                if(dateInputModal) dateInputModal.value = ''; 
                if(bookingFormModal) bookingFormModal.reset(); 
                clearTimeSlotsModal(); 
                if(submitButtonModal) submitButtonModal.disabled = true; 
            } 
        }
        function closeAppointmentModal() { if(modal) modal.style.display = 'none'; }
        function clearTimeSlotsModal(message = 'Please select a date first.') { 
            if(timeSlotsContainerModal) timeSlotsContainerModal.innerHTML = `<p class="time-slot-message">${message}</p>`; 
            if(timeSlotsErrorModal) timeSlotsErrorModal.textContent = ''; 
            if(hiddenTimeInputModal) hiddenTimeInputModal.value = ''; 
            if(submitButtonModal) submitButtonModal.disabled = true; 
        }

        openModalBtns.forEach(btn => btn.addEventListener('click', (e) => { e.preventDefault(); openAppointmentModal(); }));
        if(closeModalBtn) closeModalBtn.addEventListener('click', closeAppointmentModal);
        window.addEventListener('click', (e) => { if (modal && e.target == modal) closeAppointmentModal(); });
        
        if (dateInputModal && timeSlotsContainerModal && timeSlotsErrorModal && hiddenTimeInputModal && submitButtonModal) { 
            const today = new Date().toISOString().split('T')[0]; 
            dateInputModal.setAttribute('min', today); 
            dateInputModal.addEventListener('change', function() { 
                const selectedDate = this.value; 
                clearTimeSlotsModal('<p class="loading-indicator">Loading available times...</p>'); 
                if (!selectedDate) { clearTimeSlotsModal(); return; } 
                
                fetch(`get_available_slots.php?date=${selectedDate}`)
                .then(response => { 
                    if (!response.ok) { throw new Error(`HTTP error! Status: ${response.status}`); } 
                    return response.json(); 
                })
                .then(data => { 
                    timeSlotsContainerModal.innerHTML = ''; 
                    timeSlotsErrorModal.textContent = ''; 
                    if (data.success && data.slots && data.slots.length > 0) { 
                        data.slots.forEach(slot => { 
                            const button = document.createElement('button'); 
                            button.type = 'button'; 
                            button.classList.add('time-slot-button'); 
                            button.dataset.time = slot;
                            const timeParts = slot.split(':');
                            const hour = parseInt(timeParts[0], 10);
                            const minute = timeParts[1];
                            const ampm = hour >= 12 ? 'PM' : 'AM';
                            const displayHour = hour % 12 === 0 ? 12 : hour % 12;
                            button.textContent = `${String(displayHour).padStart(2, '0')}:${minute} ${ampm}`;
                            button.addEventListener('click', function() { 
                                const currentlySelected = timeSlotsContainerModal.querySelector('.selected'); 
                                if (currentlySelected) { currentlySelected.classList.remove('selected'); } 
                                this.classList.add('selected'); 
                                hiddenTimeInputModal.value = this.dataset.time; 
                                submitButtonModal.disabled = false; 
                                timeSlotsErrorModal.textContent = ''; 
                            }); 
                            timeSlotsContainerModal.appendChild(button); 
                        }); 
                    } else { 
                        timeSlotsContainerModal.innerHTML = `<p class="time-slot-message">${data.message || 'No available time slots found.'}</p>`; 
                        submitButtonModal.disabled = true; 
                    } 
                })
                .catch(error => { 
                    console.error('Error fetching time slots:', error); 
                    timeSlotsContainerModal.innerHTML = ''; 
                    timeSlotsErrorModal.textContent = 'Could not load time slots. Please try again.'; 
                    submitButtonModal.disabled = true; 
                }); 
            });
        }

        if(bookingFormModal && hiddenTimeInputModal && timeSlotsErrorModal && submitButtonModal && dateInputModal) { 
            bookingFormModal.addEventListener('submit', function(e){ 
                if (!dateInputModal.value) { e.preventDefault(); alert('Please select a date.'); dateInputModal.focus(); return; } 
                if (!hiddenTimeInputModal.value) { e.preventDefault(); timeSlotsErrorModal.textContent = 'Please select an available time slot.'; return; } 
                submitButtonModal.disabled = true; 
                submitButtonModal.textContent = 'Booking...'; 
            }); 
        }
        
        const dashboardContentDiv = document.getElementById('dashboardContent');
        const profileContentDiv = document.getElementById('profileContent');
        const showProfileBtn = document.getElementById('showProfileBtn'); 
        const showDashboardLinkInNav = document.getElementById('showDashboardLink'); 

        const allNavItems = document.querySelectorAll('header nav ul li a.nav-item'); 
        const allSidebarBtns = document.querySelectorAll('.dashboard-sidebar .sidebar-btn');

        function showDashboardView() { 
            if (dashboardContentDiv) dashboardContentDiv.style.display = 'block'; 
            if (profileContentDiv) profileContentDiv.style.display = 'none'; 
            allNavItems.forEach(link => link.classList.remove('active'));
            if(showDashboardLinkInNav) showDashboardLinkInNav.classList.add('active');
            allSidebarBtns.forEach(btn => btn.classList.remove('active-view'));
        }
        function showProfileView() { 
            if (dashboardContentDiv) dashboardContentDiv.style.display = 'none'; 
            if (profileContentDiv) profileContentDiv.style.display = 'block'; 
            allNavItems.forEach(link => link.classList.remove('active'));
            allSidebarBtns.forEach(btn => btn.classList.remove('active-view'));
            if(showProfileBtn) showProfileBtn.classList.add('active-view');
        }

        if (showProfileBtn) { 
            showProfileBtn.addEventListener('click', (e) => { e.preventDefault(); showProfileView(); });
        }
        if (showDashboardLinkInNav) { 
            showDashboardLinkInNav.addEventListener('click', (e) => { e.preventDefault(); showDashboardView(); });
        }
        showDashboardView(); 

        const personalInfoCardProfile = document.getElementById('personalInfoCardProfile');
        if (personalInfoCardProfile) {
            const editBtn = personalInfoCardProfile.querySelector('.edit-info-btn');
            const saveBtn = personalInfoCardProfile.querySelector('.btn-save-info');
            const cancelBtn = personalInfoCardProfile.querySelector('.btn-cancel-edit');
            const editActions = personalInfoCardProfile.querySelector('.edit-actions');
            let originalProfileValues = {};
            if(editBtn && editActions && saveBtn && cancelBtn) {
                editBtn.addEventListener('click', function() {
                    editActions.style.display = 'flex'; editBtn.style.display = 'none'; originalProfileValues = {};
                    const infoValueSpans = personalInfoCardProfile.querySelectorAll('.info-value[data-field]');
                    infoValueSpans.forEach(span => {
                        const field = span.dataset.field; originalProfileValues[field] = span.textContent.trim();
                        if (field === 'address' || field === 'genderDisplay' || field === 'ageDisplay' || field === 'fullNameDisplay') return; 
                        let inputElement;
                        if (field === 'medicalInfo') { inputElement = document.createElement('textarea'); inputElement.rows = 4; let CMI = span.innerHTML.replace(/<br\s*\/?>/gi, "\n").trim(); inputElement.value = CMI === "Not provided" || CMI.startsWith("[") ? "" : CMI; }
                        else if (field === 'dob') { inputElement = document.createElement('input'); inputElement.type = 'date'; if (originalProfileValues[field] && originalProfileValues[field].toLowerCase() !== 'n/a') { try { const dO = new Date(originalProfileValues[field]); if (!isNaN(dO)) { inputElement.value = dO.toISOString().split('T')[0]; } else { inputElement.value = '';} } catch (e) { inputElement.value = ''; } } else { inputElement.value = ''; } }
                        else { inputElement = document.createElement('input'); inputElement.type = (field === 'email') ? 'email' : (field === 'phone') ? 'tel' : 'text'; inputElement.value = originalProfileValues[field] === 'N/A' ? '' : originalProfileValues[field]; }
                        inputElement.dataset.field = field; inputElement.classList.add('info-input', 'form-control');
                        span.style.display = 'none'; span.parentNode.insertBefore(inputElement, span.nextSibling);
                    });
                });
                cancelBtn.addEventListener('click', function() {
                    editActions.style.display = 'none'; editBtn.style.display = 'block';
                    personalInfoCardProfile.querySelectorAll('.info-input[data-field]').forEach(input => {
                        const field = input.dataset.field; const span = personalInfoCardProfile.querySelector(`.info-value[data-field="${field}"]`);
                        if (span) { if (field === 'medicalInfo' && originalProfileValues[field]) { span.innerHTML = originalProfileValues[field].replace(/\n/g, "<br>"); } else { span.textContent = originalProfileValues[field] || 'N/A'; } span.style.display = ''; }
                        input.remove();
                    });
                });
                saveBtn.addEventListener('click', function() {
                    const FD = new FormData(); let HC = false; const UFD = {};
                    personalInfoCardProfile.querySelectorAll('.info-input[data-field]').forEach(input => {
                        const F = input.dataset.field; let CV = input.value.trim(); FD.append(F, CV); UFD[F] = CV;
                        let OC = originalProfileValues[F];
                        if (F === 'dob' && originalProfileValues[F] && originalProfileValues[F].toLowerCase() !== 'n/a') { try { OC = new Date(originalProfileValues[F]).toISOString().split('T')[0]; } catch(e) {} }
                        else if (originalProfileValues[F] === 'N/A' || originalProfileValues[F] === "[Encrypted data - retrieval issue or no data]" || originalProfileValues[F] === "Not provided") { OC = ''; }
                        if (CV !== OC) HC = true;
                    });
                    if (!HC) { alert('No changes detected.'); cancelBtn.click(); return; }
                    fetch('update_profile.php', { method: 'POST', body: FD }).then(res => res.json()).then(data => {
                        if (data.success) {
                            alert('Profile updated successfully!');
                            personalInfoCardProfile.querySelectorAll('.info-input[data-field]').forEach(input => {
                                const field = input.dataset.field; const span = personalInfoCardProfile.querySelector(`.info-value[data-field="${field}"]`);
                                if(span){ let DV = UFD[field]; if (field === 'medicalInfo') { span.innerHTML = DV ? DV.replace(/\n/g, "<br>") : 'Not provided'; } else if (field === 'dob' && DV) { try { const dO = new Date(DV + 'T00:00:00'); span.textContent = dO.toLocaleDateString('en-US', {month:'long', day:'numeric', year:'numeric'}); } catch(e) { span.textContent = 'N/A';} } else { span.textContent = DV || 'N/A'; } span.style.display = ''; } input.remove();
                            });
                            const sFN = document.querySelector('#profileContent .patient-summary-card [data-field="fullNameDisplay"]'); const sA = document.querySelector('#profileContent .patient-summary-card [data-field="ageDisplay"]');
                            if (sFN && (UFD.firstName !== undefined || UFD.lastName !== undefined)) { const fn = UFD.firstName ?? originalProfileValues.firstName; const ln = UFD.lastName ?? originalProfileValues.lastName; sFN.textContent = `${fn} ${ln}`; }
                            if(sA && UFD.dob !== undefined){ const dobV = UFD.dob; if(dobV) { try {const bD = new Date(dobV+'T00:00:00'); const tD = new Date(); let aY = tD.getFullYear() - bD.getFullYear(); const m = tD.getMonth() - bD.getMonth(); if (m < 0 || (m === 0 && tD.getDate() < bD.getDate())) {aY--;} sA.textContent = aY >= 0 ? aY : 'N/A';} catch(e){ sA.textContent = 'N/A'; } } else {sA.textContent = 'N/A';} }
                            editActions.style.display = 'none'; editBtn.style.display = 'block';
                        } else { alert('Error updating profile: ' + (data.errors ? data.errors.join('\n') : (data.message || 'Unknown server error.'))); }
                    }).catch(err => { console.error('Error:', err); alert('An error occurred. Please try again.'); });
                });
            }
        }
        // Patient Notification Bell Logic (from previous integration)
        const patientNotificationBell = document.getElementById('patientNotificationBell');
        const patientNotificationsDropdown = document.getElementById('patientNotificationsDropdown');
        const patientNotificationList = document.getElementById('patientNotificationList');

        function updatePatientNotificationDisplay(count, notifications = []) {
            const badge = document.getElementById('patientNotificationBadge');
            if (badge) { if (count > 0) { badge.textContent = count; badge.style.display = 'flex'; } else { badge.style.display = 'none'; } }
            else if (count > 0 && patientNotificationBell) { const newBadge = document.createElement('span'); newBadge.id = 'patientNotificationBadge'; newBadge.className = 'notification-badge'; newBadge.textContent = count; newBadge.style.display = 'flex'; patientNotificationBell.appendChild(newBadge); }

            if (patientNotificationList) {
                patientNotificationList.innerHTML = '';
                if (notifications.length > 0) {
                    notifications.forEach(notif => {
                        const item = document.createElement('div'); item.classList.add('notification-item-patient'); if (notif.is_read == 0) item.classList.add('unread-patient');
                        item.innerHTML = `<a href="${notif.link}" data-notification-id="${notif.id}" class="notification-link-patient"><div class="notif-icon-patient"><i class="${notif.icon_class}"></i></div><div class="notif-content-patient"><div class="notif-title-patient">${notif.title}</div><div class="notif-message-patient">${notif.message}</div><div class="notif-time-patient">${notif.time_ago}</div></div></a>`;
                        if (notif.is_read == 0 && notif.link !== '#') { item.querySelector('a').addEventListener('click', function(e){ markNotificationAsReadOnServer(notif.id); }); }
                        patientNotificationList.appendChild(item);
                    });
                } else { patientNotificationList.innerHTML = '<p class="no-notifications-patient">You have no new notifications.</p>'; }
            }
        }
        async function fetchPatientNotifications() {
            if (!patientNotificationsDropdown || !patientNotificationList) return;
            patientNotificationList.innerHTML = '<p class="no-notifications-patient">Loading...</p>';
            try {
                const response = await fetch('fetch_patient_notifications.php'); if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();
                if (data.error) { console.error('Error fetching patient notifications:', data.error); patientNotificationList.innerHTML = '<p class="no-notifications-patient">Could not load notifications.</p>'; return; }
                updatePatientNotificationDisplay(data.count, data.notifications);
            } catch (error) { console.error('Could not fetch patient notifications:', error); if (patientNotificationList) patientNotificationList.innerHTML = '<p class="no-notifications-patient">Error loading notifications.</p>'; }
        }
        async function markNotificationAsReadOnServer(notificationId) {
            try { const formData = new FormData(); formData.append('notification_id', notificationId); const response = await fetch('mark_notification_read.php', { method: 'POST', body: new URLSearchParams(formData) }); const data = await response.json(); if (!data.success) console.warn("Failed to mark notification as read on server:", data.message); }
            catch (error) { console.error("Error in markNotificationAsReadOnServer:", error); }
        }
        if (patientNotificationBell) { patientNotificationBell.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); if (patientNotificationsDropdown) { const isShown = patientNotificationsDropdown.classList.toggle('show'); if (isShown) fetchPatientNotifications(); } }); }
        document.addEventListener('click', (event) => { if (patientNotificationsDropdown && patientNotificationsDropdown.classList.contains('show')) { if (patientNotificationBell && !patientNotificationBell.contains(event.target) && !patientNotificationsDropdown.contains(event.target)) patientNotificationsDropdown.classList.remove('show'); } });

    }); // End DOMContentLoaded
    </script>
    <?php
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    ?>
</body>
</html>