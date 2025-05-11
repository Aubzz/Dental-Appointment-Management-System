<?php
require_once '../config.php'; // Includes session_start() and $conn

// Check if the user is logged in and has the correct role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "patient") {
    $_SESSION = array();
    session_destroy();
    header("location: patient_signin.php");
    exit;
}

$userId = $_SESSION["user_id"];
$upcoming_appointments = []; 
$past_appointments = [];
$errors = [];

// --- Fetch Upcoming & Pending Appointments ---
$sql_upcoming = "SELECT 
                    a.id, 
                    a.appointment_date, 
                    a.appointment_time, 
                    a.status,
                    a.service_type, 
                    d.firstName as doctor_firstName, 
                    d.lastName as doctor_lastName,
                    a.notes as patient_notes 
                  FROM appointments a
                  LEFT JOIN doctors d ON a.attending_dentist = d.id 
                  WHERE a.patient_id = ? AND
                        (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.appointment_time >= CURTIME())) AND
                        a.status IN ('PENDING', 'SCHEDULED', 'CONFIRMED') 
                  ORDER BY a.appointment_date ASC, a.appointment_time ASC";

if ($stmt_upcoming = $conn->prepare($sql_upcoming)) {
    $stmt_upcoming->bind_param("i", $userId);
    if ($stmt_upcoming->execute()) {
        $result_upcoming = $stmt_upcoming->get_result();
        while ($row = $result_upcoming->fetch_assoc()) {
            $upcoming_appointments[] = $row;
        }
    } else {
        $errors[] = "Error fetching upcoming appointments.";
        error_log("Error executing upcoming appointments fetch (patient_id: {$userId}): " . $stmt_upcoming->error);
    }
    $stmt_upcoming->close();
} else {
    $errors[] = "Database error preparing upcoming appointments.";
    error_log("Error preparing upcoming appointments statement: " . $conn->error);
}

// --- Fetch Past Appointments (History) ---
$sql_past = "SELECT 
                a.id, 
                a.appointment_date, 
                a.appointment_time, 
                a.status,
                a.service_type,
                d.firstName as doctor_firstName, 
                d.lastName as doctor_lastName,
                a.notes as patient_notes
             FROM appointments a
             LEFT JOIN doctors d ON a.attending_dentist = d.id
             WHERE a.patient_id = ? AND
                   ( 
                       (a.appointment_date < CURDATE()) OR
                       (a.appointment_date = CURDATE() AND a.appointment_time < CURTIME()) OR
                       a.status IN ('COMPLETED', 'CANCELLED', 'NO_SHOW') 
                   )
             ORDER BY a.appointment_date DESC, a.appointment_time DESC";

if ($stmt_past = $conn->prepare($sql_past)) {
    $stmt_past->bind_param("i", $userId);
    if ($stmt_past->execute()) {
        $result_past = $stmt_past->get_result();
        while ($row = $result_past->fetch_assoc()) {
            $past_appointments[] = $row; 
        }
    } else {
        $errors[] = "Error fetching appointment history.";
        error_log("Error executing past appointments fetch (patient_id: {$userId}): " . $stmt_past->error);
    }
    $stmt_past->close();
} else {
    $errors[] = "Database error preparing appointment history.";
    error_log("Error preparing past appointments statement: " . $conn->error);
}

// --- Fetch Unread Notifications Count (for main nav badge) ---
$unread_notifications_count_for_badge = 0; // Initialize
if (isset($_SESSION['user_id'])) { // Check if $userId is actually set
    $sql_unread_notifications_badge = "SELECT COUNT(*) as count FROM notifications WHERE patient_id = ? AND is_read = 0";
    if ($stmt_unread_badge = $conn->prepare($sql_unread_notifications_badge)) {
        $stmt_unread_badge->bind_param("i", $userId);
        if ($stmt_unread_badge->execute()) {
            $result_unread_badge = $stmt_unread_badge->get_result();
            if ($row_badge = $result_unread_badge->fetch_assoc()){ 
                 $unread_notifications_count_for_badge = (int)$row_badge['count'];
            }
        } else {
             error_log("Error executing unread notifications count for badge: " . $stmt_unread_badge->error);
        }
        $stmt_unread_badge->close();
    } else {
        error_log("Error preparing unread notifications count for badge: " . $conn->error);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escosia Dental Clinic - My Appointments</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --old-dark-green: #004d40; 
            --danger-red: #dc3545; 
            --old-white: #ffffff; 
            --primary-green: #0A744F; /* Added from dashboard */
            --dark-green: #004d40; /* Added from dashboard */
            --text-muted: #6c757d; /* Added from dashboard */
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
        }
        .main-nav-icon { font-size: 1.1em; padding: 0 8px; display: inline-block; vertical-align: middle; position: relative; }
        .main-nav-icon:hover { color: var(--old-dark-green); }
        .main-nav-icon .notification-badge { position: absolute; top: -5px; right: -3px; background-color: var(--danger-red); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7em; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid var(--old-white); }
        .appointment-list .status { font-weight: 600; padding: 5px 10px; border-radius: 15px; font-size: 0.8em; text-align: center; display: inline-block; min-width: 100px; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background-color: var(--status-pending-bg); color: var(--status-pending-text); border: 1px solid var(--status-pending-text); }
        .status-scheduled { background-color: var(--status-scheduled-bg); color: var(--status-scheduled-text); border: 1px solid var(--status-scheduled-text); }
        .status-confirmed { background-color: var(--status-confirmed-bg); color: var(--status-confirmed-text); border: 1px solid var(--status-confirmed-text); }
        .status-completed { background-color: var(--status-completed-bg); color: var(--status-completed-text); border: 1px solid var(--status-completed-text); }
        .status-cancelled { background-color: var(--status-cancelled-bg); color: var(--status-cancelled-text); border: 1px solid var(--status-cancelled-text); }
        .status-no_show { background-color: var(--status-no_show-bg); color: var(--status-no_show-text); border: 1px solid var(--status-no_show-text); }

        /* Styles for Notification Dropdown (Patient Header) - Same as dashboard */
        .main-nav-item-wrapper { position: relative; display: inline-block; }
        .notifications-dropdown-patient { position: absolute; top: 100%; right: 0; width: 340px; max-height: 400px; overflow-y: auto; background-color: #fff; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.15); z-index: 1051; display: none; margin-top: 8px; }
        .notifications-dropdown-patient.show { display: block; }
        .notification-header-patient { padding: 12px 15px; font-weight: 600; font-size: 1em; border-bottom: 1px solid #eee; color: var(--dark-green); }
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
    </style>
</head>
<body class="patient-area page-patient-appointments">

    <div class="container">
        <header>
            <div class="logo">
                <img src="../images/tooth.png" alt="Tooth Logo" class="logo-icon">
                <h1>Escosia Dental Clinic</h1>
            </div>
            <nav>
                 <ul>
                    <li><a href="patient_dashboard.php" class="nav-item">DASHBOARD</a></li>
                    <li><a href="doctors_patient.php" class="nav-item">DOCTORS</a></li>
                    <li><a href="patient_appointments.php" class="nav-item active">APPOINTMENTS</a></li>
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
                     <!-- Logout button removed from here previously -->
                 </div>
            </nav>
        </header>

        <main>
            <section class="appointments-section">
                <h2 class="page-title">My Appointments</h2>

                <?php if (isset($_SESSION['appointment_message'])): ?>
                    <div class="form-feedback-message <?php echo htmlspecialchars($_SESSION['appointment_message']['type']); ?>" style="margin-bottom: 20px; text-align:center;">
                        <?php echo htmlspecialchars($_SESSION['appointment_message']['text']); ?>
                    </div>
                <?php unset($_SESSION['appointment_message']); endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-messages" style="background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin-bottom:20px; border-radius:5px;">
                        <strong>Error!</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="appointment-list upcoming-appointments">
                     <h3>Upcoming & Pending Appointments</h3>
                     <div class="table-responsive">
                         <table class="appointment-table">
                             <thead>
                                 <tr> <th>Date</th> <th>Time</th> <th>Service</th> <th>Doctor</th> <th>Status</th> <th>Actions</th> </tr>
                             </thead>
                             <tbody>
                                <?php if (!empty($upcoming_appointments)): ?>
                                    <?php foreach ($upcoming_appointments as $appt): ?>
                                    <tr data-appointment-id="<?php echo htmlspecialchars($appt['id']); ?>">
                                        <td><?php echo htmlspecialchars(date("M d, Y", strtotime($appt['appointment_date']))); ?></td>
                                        <td><?php echo htmlspecialchars(date("h:i A", strtotime($appt['appointment_time']))); ?></td>
                                        <td><?php echo htmlspecialchars($appt['service_type'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                                if (!empty($appt['doctor_firstName'])) {
                                                    echo "Dr. " . htmlspecialchars($appt['doctor_firstName'] . ' ' . $appt['doctor_lastName']);
                                                } else {
                                                    echo "<span style='color: #777; font-style: italic;'>Awaiting Assignment</span>";
                                                }
                                            ?>
                                        </td>
                                        <td> <span class="status status-<?php echo strtolower(htmlspecialchars($appt['status'])); ?>"> <?php echo strtoupper(htmlspecialchars($appt['status'])); ?> </span> </td>
                                        <td>
                                            <?php if (strtoupper($appt['status']) === 'PENDING'): ?>
                                                <button class="btn-action cancel" aria-label="Cancel Pending Request">Cancel Request</button>
                                            <?php elseif (in_array(strtoupper($appt['status']), ['SCHEDULED', 'CONFIRMED'])): ?>
                                                <button class="btn-action reschedule" aria-label="Reschedule Appointment">Reschedule</button>
                                                <button class="btn-action cancel" aria-label="Cancel Appointment">Cancel</button>
                                            <?php else: ?>
                                                <span>No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr> <td colspan="6" class="no-appointments">No upcoming or pending appointments found.</td> </tr>
                                <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                </div>

                <div class="appointment-list past-appointments"> 
                     <h3>Appointment History</h3>
                     <div class="table-responsive">
                         <table class="appointment-table">
                             <thead>
                                 <tr> <th>Date</th> <th>Time</th> <th>Service</th> <th>Doctor</th> <th>Status</th> </tr>
                             </thead>
                             <tbody>
                                <?php if (!empty($past_appointments)): ?>
                                    <?php foreach ($past_appointments as $appt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date("M d, Y", strtotime($appt['appointment_date']))); ?></td>
                                        <td><?php echo htmlspecialchars(date("h:i A", strtotime($appt['appointment_time']))); ?></td>
                                        <td><?php echo htmlspecialchars($appt['service_type'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php 
                                                if (!empty($appt['doctor_firstName'])) {
                                                    echo "Dr. " . htmlspecialchars($appt['doctor_firstName'] . ' ' . $appt['doctor_lastName']);
                                                } else {
                                                    echo "N/A";
                                                }
                                            ?>
                                        </td>
                                        <td> <span class="status status-<?php echo strtolower(htmlspecialchars($appt['status'])); ?>"> <?php echo strtoupper(htmlspecialchars($appt['status'])); ?> </span> </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr> <td colspan="5" class="no-appointments">No past appointment history found.</td> </tr>
                                <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // This script should be common for all patient pages that have the notification bell
        // Or include it via a separate common_patient_header.js file
        document.addEventListener('DOMContentLoaded', () => {
            // --- PATIENT NOTIFICATION BELL LOGIC ---
            const patientNotificationBell = document.getElementById('patientNotificationBell');
            // const patientNotificationBadge = document.getElementById('patientNotificationBadge'); // Will be updated by function
            const patientNotificationsDropdown = document.getElementById('patientNotificationsDropdown');
            const patientNotificationList = document.getElementById('patientNotificationList');

            function updatePatientNotificationDisplay(count, notifications = []) {
                let badge = document.getElementById('patientNotificationBadge'); // Re-fetch
                
                if (!badge && count > 0 && patientNotificationBell) { // Create badge if it doesn't exist and there are notifications
                    badge = document.createElement('span');
                    badge.id = 'patientNotificationBadge';
                    badge.className = 'notification-badge';
                    patientNotificationBell.appendChild(badge);
                }

                if (badge) {
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'flex'; 
                    } else {
                        badge.style.display = 'none';
                    }
                }

                if (patientNotificationList) {
                    patientNotificationList.innerHTML = ''; 
                    if (notifications.length > 0) {
                        notifications.forEach(notif => {
                            const item = document.createElement('div');
                            item.classList.add('notification-item-patient');
                            if (notif.is_read == 0) {
                                item.classList.add('unread-patient');
                            }
                            item.innerHTML = `
                                <a href="${notif.link}" data-notification-id="${notif.id}" class="notification-link-patient">
                                    <div class="notif-icon-patient"><i class="${notif.icon_class}"></i></div>
                                    <div class="notif-content-patient">
                                        <div class="notif-title-patient">${notif.title}</div>
                                        <div class="notif-message-patient">${notif.message}</div>
                                        <div class="notif-time-patient">${notif.time_ago}</div>
                                    </div>
                                </a>
                            `;
                            if (notif.is_read == 0 && notif.link !== '#') {
                                item.querySelector('a').addEventListener('click', function(e){
                                    // Allow default navigation, mark as read in background
                                    markNotificationAsReadOnServer(notif.id); 
                                });
                            }
                            patientNotificationList.appendChild(item);
                        });
                    } else {
                        patientNotificationList.innerHTML = '<p class="no-notifications-patient">You have no new notifications.</p>';
                    }
                }
            }

            async function fetchPatientNotifications() {
                if (!patientNotificationsDropdown || !patientNotificationList) return;
                patientNotificationList.innerHTML = '<p class="no-notifications-patient">Loading...</p>';
                try {
                    const response = await fetch('fetch_patient_notifications.php'); 
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    if (data.error) {
                        console.error('Error fetching patient notifications:', data.error);
                        patientNotificationList.innerHTML = '<p class="no-notifications-patient">Could not load notifications.</p>';
                        return;
                    }
                    updatePatientNotificationDisplay(data.count, data.notifications);
                } catch (error) {
                    console.error('Could not fetch patient notifications:', error);
                    if (patientNotificationList) patientNotificationList.innerHTML = '<p class="no-notifications-patient">Error loading notifications.</p>';
                }
            }

            async function markNotificationAsReadOnServer(notificationId) {
                try {
                    const formData = new FormData();
                    formData.append('notification_id', notificationId);
                    const response = await fetch('mark_notification_read.php', { 
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });
                    const data = await response.json();
                    if (!data.success) {
                        console.warn("Failed to mark notification as read on server:", data.message);
                    }
                    // The badge count will update on the next fetch when dropdown is opened
                } catch (error) {
                    console.error("Error in markNotificationAsReadOnServer:", error);
                }
            }

            if (patientNotificationBell) {
                patientNotificationBell.addEventListener('click', (event) => {
                    event.preventDefault(); 
                    event.stopPropagation(); 
                    if (patientNotificationsDropdown) {
                        const isShown = patientNotificationsDropdown.classList.toggle('show');
                        if (isShown) {
                            fetchPatientNotifications(); 
                        }
                    }
                });
            }

            document.addEventListener('click', (event) => {
                if (patientNotificationsDropdown && patientNotificationsDropdown.classList.contains('show')) {
                    if (patientNotificationBell && !patientNotificationBell.contains(event.target) && !patientNotificationsDropdown.contains(event.target)) {
                        patientNotificationsDropdown.classList.remove('show');
                    }
                }
            });
            // --- END PATIENT NOTIFICATION BELL LOGIC ---


            // --- Specific logic for patient_appointments.php table actions ---
            const appointmentTables = document.querySelectorAll('.appointment-table');
            appointmentTables.forEach(table => {
                table.addEventListener('click', (e) => {
                    const targetButton = e.target.closest('button.btn-action');
                    if (!targetButton) return;

                    const row = targetButton.closest('tr');
                    const appointmentId = row ? row.dataset.appointmentId : null;
                    if(!appointmentId) {
                        console.error("Could not find appointment ID for action.");
                        return;
                    }

                    if (targetButton.classList.contains('reschedule')) {
                        alert(`Reschedule functionality for appointment ID ${appointmentId} needs to be implemented (e.g., open booking modal with existing data).`);
                    } else if (targetButton.classList.contains('cancel')) {
                        if (confirm('Are you sure you want to cancel this appointment/request?')) {
                            fetch('cancel_patient_appointment.php', { // Ensure this backend script exists
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'appointment_id=' + encodeURIComponent(appointmentId)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert(data.message || 'Action completed successfully.');
                                    window.location.reload(); 
                                } else {
                                    alert('Error: ' + (data.message || 'Could not complete action.'));
                                }
                            })
                            .catch(error => {
                                console.error('Error processing action:', error);
                                alert('An error occurred. Please try again.');
                            });
                        }
                    }
                });
            });
        });
    </script>
     <?php
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    ?>
</body>
</html>