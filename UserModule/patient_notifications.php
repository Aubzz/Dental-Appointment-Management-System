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
$notifications = [];
$errors = [];

// --- Fetch Notifications ---
$sql_notifications = "SELECT id, title, message, link, is_read, created_at, icon_class
                      FROM notifications
                      WHERE patient_id = ?
                      ORDER BY is_read ASC, created_at DESC"; 

if ($stmt_notifications = $conn->prepare($sql_notifications)) {
    $stmt_notifications->bind_param("i", $userId);
    if ($stmt_notifications->execute()) {
        $result_notifications = $stmt_notifications->get_result();
        while ($row = $result_notifications->fetch_assoc()) {
            $notifications[] = $row;
        }
    } else {
        $errors[] = "Error fetching notifications.";
        error_log("Error executing notifications fetch: " . $stmt_notifications->error);
    }
    $stmt_notifications->close();
} else {
    $errors[] = "Database error preparing notifications.";
    error_log("Error preparing notifications statement: " . $conn->error);
}

function time_elapsed_string_patient_page($datetime, $full = false) { // Renamed to avoid conflict if included elsewhere
    $now = new DateTime;
    try { $ago = new DateTime($datetime); } catch (Exception $e) { return $datetime; }
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7); $diff->d -= $diff->w * 7;
    $string = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
    foreach ($string as $k => &$v) { if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } else { unset($string[$k]); } }
    if (!$full) $string = array_slice($string, 0, 1);
    if (isset($string['week']) || isset($string['month']) || isset($string['year']) || ($string && strpos(implode('',$string), 'day') !== false && $diff->d > 1) ) {
        return $ago->format('M d, Y');
    }
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// --- Fetch Unread Notifications Count (for main nav badge) ---
$unread_notifications_count_for_badge = 0;
// This can be derived from the $notifications array fetched above to save a query
foreach ($notifications as $notification) {
    if ($notification['is_read'] == 0) {
        $unread_notifications_count_for_badge++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escosia Dental Clinic - Notifications</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Copy relevant :root variables and notification styles here or ensure style.css has them */
        :root {
            --old-dark-green: #004d40; 
            --danger-red: #dc3545; 
            --old-white: #ffffff; 
            --primary-green: #0A744F;
            --dark-green: #004d40; 
            /* ... other necessary variables ... */
        }
        .main-nav-icon { font-size: 1.1em; padding: 0 8px; display: inline-block; vertical-align: middle; position: relative; }
        .main-nav-icon:hover { color: var(--old-dark-green); }
        .main-nav-icon .notification-badge { position: absolute; top: -5px; right: -3px; background-color: var(--danger-red); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7em; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid var(--old-white); }
        .main-nav-icon.active { color: var(--primary-green); /* Or your active color */ }

        /* Styles for Notification Dropdown (Patient Header) */
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

        /* Styles for main notification list on this page */
        .notifications-section .notifications-list .notification-item { /* Reuse styles if possible or define specifically */
            /* Styles from your patient_notifications.php for the main list */
        }

    </style>
</head>
<body class="patient-area page-patient-notifications">

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
                    <li><a href="patient_appointments.php" class="nav-item">APPOINTMENTS</a></li>
                    <li>
                        <div class="main-nav-item-wrapper">
                            <a href="#" title="Notifications" class="main-nav-icon nav-item active" id="patientNotificationBell"> {/* Mark active on this page */}
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
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                 </div>
            </nav>
        </header>

        <main>
            <section class="notifications-section">
                <h2 class="page-title">Notifications</h2>

                <?php if (isset($_SESSION['notification_message'])): ?>
                    <div class="form-feedback-message <?php echo htmlspecialchars($_SESSION['notification_message']['type']); ?>" style="margin-bottom: 20px; text-align:center;">
                        <?php echo $_SESSION['notification_message']['text']; ?>
                    </div>
                <?php unset($_SESSION['notification_message']); endif; ?>

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

                <div class="notifications-list" id="mainNotificationsList"> {/* Give the main list a unique ID */}
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo ($notification['is_read'] == 0) ? 'unread' : 'read'; ?>" data-notification-id="<?php echo htmlspecialchars($notification['id']); ?>">
                                <div class="notification-icon">
                                    <i class="<?php echo htmlspecialchars($notification['icon_class'] ?? 'fas fa-info-circle'); ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <p class="notification-message">
                                        <?php if (!empty($notification['title'])): ?>
                                            <strong><?php echo htmlspecialchars($notification['title']); ?>:</strong>
                                        <?php endif; ?>
                                        <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                    </p>
                                    <span class="notification-time">
                                        <?php echo time_elapsed_string_patient_page($notification['created_at']); ?>
                                    </span>
                                </div>
                                <div class="notification-actions">
                                    <?php if (!empty($notification['link'])): ?>
                                        <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn-action view">View Details</a>
                                    <?php endif; ?>
                                    <?php if ($notification['is_read'] == 0): ?>
                                        <button class="btn-action mark-read" aria-label="Mark as Read">Mark Read</button>
                                    <?php endif; ?>
                                     <button class="btn-action delete" aria-label="Delete Notification"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-notifications">
                            You have no notifications.
                        </div>
                    <?php endif; ?>
                </div> 
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- PATIENT NOTIFICATION BELL LOGIC (Same as dashboard) ---
            const patientNotificationBell = document.getElementById('patientNotificationBell');
            const patientNotificationsDropdown = document.getElementById('patientNotificationsDropdown');
            const patientNotificationListDropdown = document.getElementById('patientNotificationList'); // For dropdown

            function updatePatientNotificationDisplay(count, notifications = []) {
                let badge = document.getElementById('patientNotificationBadge');
                if (!badge && count > 0 && patientNotificationBell) { 
                    badge = document.createElement('span'); badge.id = 'patientNotificationBadge'; badge.className = 'notification-badge'; patientNotificationBell.appendChild(badge);
                }
                if (badge) { if (count > 0) { badge.textContent = count; badge.style.display = 'flex'; } else { badge.style.display = 'none'; } }

                if (patientNotificationListDropdown) {
                    patientNotificationListDropdown.innerHTML = ''; 
                    if (notifications.length > 0) {
                        notifications.forEach(notif => {
                            const item = document.createElement('div'); item.classList.add('notification-item-patient'); if (notif.is_read == 0) item.classList.add('unread-patient');
                            item.innerHTML = `
                                <a href="${notif.link}" data-notification-id="${notif.id}" class="notification-link-patient">
                                    <div class="notif-icon-patient"><i class="${notif.icon_class}"></i></div>
                                    <div class="notif-content-patient">
                                        <div class="notif-title-patient">${notif.title}</div>
                                        <div class="notif-message-patient">${notif.message}</div>
                                        <div class="notif-time-patient">${notif.time_ago}</div>
                                    </div>
                                </a>`;
                            if (notif.is_read == 0 && notif.link !== '#') { item.querySelector('a').addEventListener('click', function(e){ markNotificationAsReadOnServer(notif.id); }); }
                            patientNotificationListDropdown.appendChild(item);
                        });
                    } else { patientNotificationListDropdown.innerHTML = '<p class="no-notifications-patient">You have no new notifications.</p>'; }
                }
            }

            async function fetchPatientNotifications() {
                if (!patientNotificationsDropdown || !patientNotificationListDropdown) return;
                patientNotificationListDropdown.innerHTML = '<p class="no-notifications-patient">Loading...</p>';
                try {
                    const response = await fetch('fetch_patient_notifications.php'); 
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    const data = await response.json();
                    if (data.error) { console.error('Error fetching patient notifications:', data.error); patientNotificationListDropdown.innerHTML = '<p class="no-notifications-patient">Could not load notifications.</p>'; return; }
                    updatePatientNotificationDisplay(data.count, data.notifications);
                } catch (error) { console.error('Could not fetch patient notifications:', error); if (patientNotificationListDropdown) patientNotificationListDropdown.innerHTML = '<p class="no-notifications-patient">Error loading notifications.</p>'; }
            }

            async function markNotificationAsReadOnServer(notificationId) {
                try {
                    const formData = new FormData(); formData.append('notification_id', notificationId);
                    const response = await fetch('mark_notification_read.php', { method: 'POST', body: new URLSearchParams(formData) });
                    const data = await response.json();
                    if (!data.success) console.warn("Failed to mark notification as read on server:", data.message);
                } catch (error) { console.error("Error in markNotificationAsReadOnServer:", error); }
            }

            if (patientNotificationBell) {
                patientNotificationBell.addEventListener('click', (event) => {
                    event.preventDefault(); event.stopPropagation(); 
                    if (patientNotificationsDropdown) { const isShown = patientNotificationsDropdown.classList.toggle('show'); if (isShown) fetchPatientNotifications(); }
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


            // --- JS for the main notifications list on THIS page ---
            const mainNotificationsList = document.getElementById('mainNotificationsList'); // Target the main list
            if (mainNotificationsList) {
                mainNotificationsList.addEventListener('click', (e) => {
                    const targetButton = e.target.closest('button.btn-action');
                    if (!targetButton) return;

                    const notificationItem = targetButton.closest('.notification-item');
                    const notificationId = notificationItem ? notificationItem.dataset.notificationId : null;

                    if (!notificationId) return;

                    if (targetButton.classList.contains('mark-read')) {
                        fetch('mark_notification_read.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'notification_id=' + encodeURIComponent(notificationId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success) {
                                notificationItem.classList.remove('unread');
                                notificationItem.classList.add('read');
                                targetButton.remove();
                                updateBadgeCountAfterAction(-1); // Decrement badge
                            } else { alert('Could not mark as read: ' + (data.message || 'Error')); }
                        })
                        .catch(error => { console.error('Error:', error); alert('Error marking as read.'); });
                    } else if (targetButton.classList.contains('delete')) {
                        if (confirm('Are you sure you want to delete this notification?')) {
                            fetch('delete_notification.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'notification_id=' + encodeURIComponent(notificationId)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if(data.success) {
                                    if (notificationItem.classList.contains('unread')) {
                                        updateBadgeCountAfterAction(-1); // Decrement if unread
                                    }
                                    notificationItem.style.opacity = '0';
                                    setTimeout(() => { notificationItem.remove(); checkMainListEmptyState(); }, 300);
                                } else { alert('Could not delete: ' + (data.message || 'Error'));}
                            })
                            .catch(error => { console.error('Error:', error); alert('Error deleting notification.'); });
                        }
                    }
                });
            }
            
            function updateBadgeCountAfterAction(change) {
                const badge = document.querySelector('header nav .notification-badge');
                if (badge) {
                    let count = parseInt(badge.textContent) + change;
                    if (count > 0) {
                        badge.textContent = count;
                    } else {
                        badge.remove(); // Remove badge if count is 0 or less
                    }
                } else if (change > 0) { // If badge didn't exist but should now
                    const bellIcon = document.querySelector('header nav .main-nav-icon.active'); // Bell on this page
                    if(bellIcon){
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge';
                        newBadge.textContent = change;
                        bellIcon.appendChild(newBadge);
                    }
                }
            }

            function checkMainListEmptyState() {
                if (mainNotificationsList) {
                    const remainingItems = mainNotificationsList.querySelectorAll('.notification-item');
                    let noNotificationsDiv = mainNotificationsList.querySelector('.no-notifications');
                    if (remainingItems.length === 0 && !noNotificationsDiv) {
                        noNotificationsDiv = document.createElement('div');
                        noNotificationsDiv.classList.add('no-notifications');
                        noNotificationsDiv.textContent = 'You have no notifications.';
                        mainNotificationsList.appendChild(noNotificationsDiv);
                    } else if (remainingItems.length > 0 && noNotificationsDiv) {
                        noNotificationsDiv.remove();
                    }
                }
            }
            checkMainListEmptyState(); // Initial check
        });
    </script>
     <?php
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    ?>
</body>
</html>