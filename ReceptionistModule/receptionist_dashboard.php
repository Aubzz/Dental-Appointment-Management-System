<?php
require_once '../config.php'; // For session and DB connection

// --- Receptionist Authentication ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: receptionist_signin.html');
    exit;
}
$receptionist_id = $_SESSION['user_id'] ?? null;

// --- Fetch Receptionist Profile Data ---
$receptionist_profile = null;
if ($receptionist_id) {
    // CORRECTED: Query the 'receptionists' table
    $sql_profile = "SELECT id, firstName, lastName, email, phoneNumber FROM receptionists WHERE id = ?";
    
    if ($stmt_profile = $conn->prepare($sql_profile)) {
        $stmt_profile->bind_param("i", $receptionist_id);
        if ($stmt_profile->execute()) {
            $result_profile = $stmt_profile->get_result();
            if ($result_profile->num_rows == 1) {
                $receptionist_profile = $result_profile->fetch_assoc();
            } else {
                error_log("Receptionist profile not found in 'receptionists' table for ID: " . $receptionist_id);
            }
        } else {
            error_log("Error fetching receptionist profile: " . $stmt_profile->error);
        }
        $stmt_profile->close();
    } else {
        error_log("Error preparing profile statement: " . $conn->error);
    }
}
// Use session data as fallback or for initial display
$receptionist_name_display = htmlspecialchars($receptionist_profile['firstName'] ?? $_SESSION['user_firstName'] ?? 'Receptionist');
$receptionist_lastname_display = htmlspecialchars($receptionist_profile['lastName'] ?? $_SESSION['user_lastName'] ?? '');
// Assuming email might be in session from login, or fetched.
// If not in session, ensure $receptionist_profile['email'] is the primary source.
$receptionist_email_display = htmlspecialchars($receptionist_profile['email'] ?? ($_SESSION['user_email'] ?? 'N/A')); 
$receptionist_phone_display = htmlspecialchars($receptionist_profile['phoneNumber'] ?? 'N/A');


// --- Fetch Data for Dashboard (Today's Appointments, New Requests, etc.) ---
$todays_appointments = [];
$today_date = date("Y-m-d");
$sql_today = "
    SELECT 
        p.firstName as patient_firstName,
        p.lastName as patient_lastName,
        a.appointment_date,
        a.appointment_time, 
        a.service_type, 
        a.status
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.appointment_date = ? 
      AND (a.status = 'SCHEDULED' OR a.status = 'CONFIRMED')
    ORDER BY a.appointment_time ASC
";
if ($stmt_today = $conn->prepare($sql_today)) {
    $stmt_today->bind_param("s", $today_date);
    if ($stmt_today->execute()) {
        $result_today = $stmt_today->get_result();
        while ($row = $result_today->fetch_assoc()) {
            $todays_appointments[] = $row;
        }
    } else { error_log("Error fetching today's appointments: " . $stmt_today->error); }
    $stmt_today->close();
} else { error_log("Error preparing statement for today's appointments: " . $conn->error); }

$appointment_requests = [];
$sql_requests = "
    SELECT 
        a.id as request_id, 
        a.patient_id, 
        p.firstName as patient_firstName,
        p.lastName as patient_lastName,
        a.appointment_date as preferred_date, 
        a.appointment_time as preferred_time, 
        a.service_type, 
        a.notes as patient_message,
        a.created_at as request_created_at
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.status = 'Pending' 
    ORDER BY a.created_at DESC
";
$result_requests_query = $conn->query($sql_requests);
if ($result_requests_query && $result_requests_query->num_rows > 0) {
    while ($row = $result_requests_query->fetch_assoc()) {
        $appointment_requests[] = $row;
    }
}
$notification_count = count($appointment_requests);

$dentists_for_modal = [];
$sql_dentists_modal = "SELECT id, firstName, lastName FROM doctors WHERE is_active = 1 ORDER BY lastName";
$result_dentists_modal = $conn->query($sql_dentists_modal);
if ($result_dentists_modal) {
    while($row_dentist = $result_dentists_modal->fetch_assoc()){
        $dentists_for_modal[] = $row_dentist;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="receptionist_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add these to receptionist_dashboard.css ideally */
        .profile-card .profile-content .info-grid {
            display: grid;
            grid-template-columns: 1fr; /* Single column for simplicity */
            gap: 10px;
            text-align: left; /* Align text to left for info items */
            margin-top: 15px;
        }
        .profile-card .info-item {
            margin-bottom: 8px;
        }
        .profile-card .info-label {
            font-weight: 500;
            color: var(--grey-text);
            display: block;
            font-size: 0.85em;
            margin-bottom: 2px;
        }
        .profile-card .info-value { /* For displaying info */
            font-size: 0.95em;
            color: var(--dark-text);
            padding: 5px 0;
        }
        .profile-card .info-input { /* For editing info */
            width: 100%;
            padding: 6px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9em;
        }
        .profile-card .profile-actions {
            margin-top: 15px;
            text-align: right; /* Align buttons to right */
        }
        .profile-card .profile-actions .btn {
            margin-left: 8px;
            padding: 6px 12px;
            font-size: 0.9em;
        }
        .profile-card .edit-profile-btn-container { /* Not used directly now, btn is in card-header */
            text-align: right; 
            margin-top: -10px; 
            margin-bottom: 10px;
        }
        .edit-profile-btn {
             background: none; border: none; color: var(--light-text); 
             cursor: pointer; font-size: 0.9em; padding: 5px;
        }
        .edit-profile-btn:hover { color: #f1c40f; }

        .profile-card .card-header {
            display: flex;
            justify-content: space-between; 
            align-items: center;
        }

    </style>
</head>
<body class="receptionist-layout-page">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-container">
                 <img src="../images/tooth.png" alt="Escosia Dental Clinic Logo" class="logo-image">
                 <h1>Escosia Dental Clinic</h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="receptionist_dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                    <li><a href="receptionist_appointment.php" class="nav-link"><i class="fas fa-calendar-alt nav-icon"></i> Appointments</a></li>
                    <li><a href="receptionist_patient_records.php" class="nav-link"><i class="fas fa-clipboard-list nav-icon"></i> Patient Records</a></li>
                    <li><a href="receptionist_reports.php" class="nav-link"><i class="fas fa-chart-bar nav-icon"></i> Reports & Analytics</a></li>
                    <li><a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt nav-icon"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-content">
            <header class="top-header">
                <div class="header-spacer"></div>
                <div class="user-info">
                    <div class="notification-wrapper">
                        <i class="fas fa-bell notification-icon" id="notificationBell">
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-badge" id="notificationBadge"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </i>
                        <div class="notifications-dropdown" id="notificationsDropdown">
                            <div class="notification-header">Notifications</div>
                            <div class="notification-list" id="notificationList">
                                <p class="no-notifications">No new notifications.</p>
                            </div>
                            <div class="notification-footer">
                                <a href="receptionist_appointment.php?filter=pending">View All Requests</a>
                            </div>
                        </div>
                    </div>
                    <img src="../images/jen_hernandez.png" alt="User Avatar" class="user-avatar" id="profileCardAvatar">
                    <div class="user-details">
                        <span class="user-name" id="profileCardName"><?php echo $receptionist_name_display . ' ' . $receptionist_lastname_display; ?></span>
                        <span class="user-role">Receptionist</span>
                    </div>
                </div>
            </header>

            <div class="main-body-wrapper">
                <main class="content-area">
                    <div class="content-area-header-block">
                        <h2>Dashboard Overview</h2>
                    </div>
                     <?php
                    if (isset($_SESSION['dashboard_message'])): ?>
                        <div class="form-feedback-message <?php echo htmlspecialchars($_SESSION['dashboard_message']['type']); ?>">
                            <?php echo htmlspecialchars($_SESSION['dashboard_message']['text']); ?>
                        </div>
                    <?php 
                        unset($_SESSION['dashboard_message']);
                    endif; 
                    if (isset($_SESSION['form_message'])): 
                    ?>
                        <div class="form-feedback-message <?php echo htmlspecialchars($_SESSION['form_message']['type']); ?>">
                            <?php echo htmlspecialchars($_SESSION['form_message']['text']); ?>
                        </div>
                    <?php 
                        unset($_SESSION['form_message']);
                    endif; 
                    ?>

                    <section class="appointments-section card">
                         <h3>Today's Appointments</h3>
                         <div class="table-responsive-wrapper">
                            <table class="data-table" id="today-appointments-table">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Time</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($todays_appointments)): ?>
                                        <?php foreach ($todays_appointments as $apt): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($apt['patient_firstName'] . ' ' . $apt['patient_lastName']); ?></td>
                                                <td><?php echo htmlspecialchars(date("h:i A", strtotime($apt['appointment_time']))); ?></td>
                                                <td><?php echo htmlspecialchars($apt['service_type']); ?></td>
                                                <td><?php echo htmlspecialchars($apt['status']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="no-data-row">
                                            <td colspan="4">No appointments scheduled for today.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="requests-section card">
                        <h3>New Appointment Requests</h3>
                        <div class="table-responsive-wrapper">
                            <table class="data-table" id="appointment-requests-table">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Preferred Date & Time</th>
                                        <th>Service Request</th>
                                        <th class="actions-column">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($appointment_requests)): ?>
                                        <?php foreach ($appointment_requests as $req): ?>
                                            <tr data-request-id="<?php echo htmlspecialchars($req['request_id']); ?>"
                                                data-patient-id="<?php echo htmlspecialchars($req['patient_id']); ?>">
                                                <td><?php echo htmlspecialchars($req['patient_firstName'] . ' ' . $req['patient_lastName']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars(date("m.d.Y", strtotime($req['preferred_date']))); ?> / 
                                                    <?php echo htmlspecialchars(date("h:i A", strtotime($req['preferred_time']))); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($req['service_type']); ?></td>
                                                <td class="action-buttons-cell">
                                                    <a href="#" class="btn btn-action action-view-request"><i class="fas fa-eye"></i> Details</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="no-data-row">
                                            <td colspan="4">No new appointment requests.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </main>

                 <aside class="right-sidebar">
                     <div class="profile-card card" id="receptionistProfileCard">
                        <div class="card-header">
                            <h3>My Profile</h3>
                            <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile"><i class="fas fa-pencil-alt"></i></button>
                        </div>
                        <div class="profile-content">
                            <img src="../images/jen_hernandez.png" alt="User Avatar" class="profile-avatar-large" id="receptionistProfileAvatarDisplay">
                            <p class="profile-name" id="receptionistFullNameDisplay"><?php echo $receptionist_name_display . ' ' . $receptionist_lastname_display; ?></p>
                            <p class="profile-role">Receptionist</p>
                            
                            <div class="info-grid" id="profileInfoView">
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value" id="receptionistEmailDisplay"><?php echo $receptionist_email_display; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone Number:</span>
                                    <span class="info-value" id="receptionistPhoneDisplay"><?php echo $receptionist_phone_display; ?></span>
                                </div>
                            </div>

                            <form id="editProfileForm" style="display: none;">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label for="profileFirstName" class="info-label">First Name:</label>
                                        <input type="text" id="profileFirstName" name="firstName" class="info-input" value="<?php echo $receptionist_name_display; ?>" required>
                                    </div>
                                    <div class="info-item">
                                        <label for="profileLastName" class="info-label">Last Name:</label>
                                        <input type="text" id="profileLastName" name="lastName" class="info-input" value="<?php echo $receptionist_lastname_display; ?>" required>
                                    </div>
                                    <div class="info-item">
                                        <label for="profileEmail" class="info-label">Email:</label>
                                        <input type="email" id="profileEmail" name="email" class="info-input" value="<?php echo $receptionist_email_display; ?>" required>
                                    </div>
                                    <div class="info-item">
                                        <label for="profilePhoneNumber" class="info-label">Phone Number:</label>
                                        <input type="tel" id="profilePhoneNumber" name="phoneNumber" class="info-input" value="<?php echo $receptionist_phone_display !== 'N/A' ? $receptionist_phone_display : ''; ?>">
                                    </div>
                                </div>
                                <div class="profile-actions">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <button type="button" class="btn btn-secondary" id="cancelEditProfileBtn">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="calendar-card card">
                        <h3 class="card-header">My Calendar <i class="fas fa-calendar-alt card-header-icon"></i></h3>
                        <div class="calendar-widget">
                            <div class="calendar-header">
                                <button id="prev-month" class="cal-nav-btn"><i class="fas fa-chevron-left"></i></button>
                                <h4 id="month-year">Month Year</h4>
                                <button id="next-month" class="cal-nav-btn"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="calendar-weekdays">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                            </div>
                            <div class="calendar-days" id="calendar-days"></div>
                        </div>
                         <h4 class="schedule-heading">Schedule for Selected Day</h4>
                        <div class="schedule-list" id="calendarScheduleList">
                             <div class="schedule-item">
                                 <span class="schedule-desc">Select a date to see schedule.</span>
                             </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <div id="requestModalOverlay" class="modal-overlay">
        <div id="requestModalContent" class="modal-content">
            <div class="modal-header">
                <h3 id="requestModalTitle">Appointment Request Details</h3>
                <button id="requestModalCloseButton" class="modal-close">×</button>
            </div>
            <div id="requestModalBody" class="modal-body"><p>Loading request details...</p></div>
             <div id="requestModalFooter" class="modal-footer" style="display: none;"></div>
        </div>
    </div>

     <div id="appointmentModalOverlay" class="modal-overlay">
        <div id="appointmentModalContent" class="modal-content">
            <div class="modal-header">
                <h3 id="appointmentModalTitle">Book Appointment</h3>
                <button id="appointmentModalCloseButton" class="modal-close">×</button>
            </div>
            <div id="appointmentModalBody" class="modal-body"><p>Loading form...</p></div>
            <div id="appointmentModalFooter" class="modal-footer" style="display: none;"></div>
        </div>
    </div>

    <script>
        const initialNotificationCount = <?php echo $notification_count; ?>;
        const availableDentists = <?php echo json_encode($dentists_for_modal); ?>;
        const initialNotifications = <?php echo json_encode(array_map(function($req) {
            return [
                'type' => 'new_request', 
                'request_id' => $req['request_id'],
                'title' => 'New Appointment Request',
                'message' => 'Patient: ' . ($req['patient_firstName'] ?? '') . ' ' . ($req['patient_lastName'] ?? '') . ' for ' . ($req['service_type'] ?? 'N/A'),
                'time_ago' => time_elapsed_string_receptionist($req['request_created_at']), 
                'link' => '#viewRequest-' . $req['request_id'] 
            ];
        }, array_slice($appointment_requests, 0, 5))); 
        ?>;
        const allAppointmentRequests = <?php echo json_encode($appointment_requests); ?>; 
        
        <?php
        if (!function_exists('time_elapsed_string_receptionist')) {
            function time_elapsed_string_receptionist($datetime, $full = false) {
                $now = new DateTime;
                try { $ago = new DateTime($datetime); } catch (Exception $e) { return $datetime; }
                $diff = $now->diff($ago); $diff->w = floor($diff->d / 7); $diff->d -= $diff->w * 7;
                $string = ['y'=>'year','m'=>'month','w'=>'week','d'=>'day','h'=>'hour','i'=>'minute','s'=>'second'];
                foreach ($string as $k => &$v) { if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } else { unset($string[$k]); } }
                if (!$full) $string = array_slice($string, 0, 1);
                return $string ? implode(', ', $string) . ' ago' : 'just now';
            }
        }
        ?>
    </script>
    <script src="calendar.js"></script>
    <script src="receptionist_dashboard.js"></script>

</body>
</html>