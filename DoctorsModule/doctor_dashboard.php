<?php
require_once '../config.php';

// --- Doctor Authentication ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'doctor') {
    header('Location: doctor_signin.html');
    exit;
}
$doctor_id = $_SESSION['user_id'] ?? null;

// --- Fetch Notification Count ---
$notification_count = 0;
$sql_notifications = "
    SELECT COUNT(*) as count 
    FROM appointments 
    WHERE attending_dentist = ? 
    AND status = 'PENDING'
    AND appointment_date >= CURDATE()
";
if ($stmt_notifications = $conn->prepare($sql_notifications)) {
    $stmt_notifications->bind_param("i", $doctor_id);
    if ($stmt_notifications->execute()) {
        $result_notifications = $stmt_notifications->get_result();
        if ($row = $result_notifications->fetch_assoc()) {
            $notification_count = $row['count'];
        }
    }
    $stmt_notifications->close();
}

// --- Fetch Doctor Profile Data ---
$doctor_profile = null;
if ($doctor_id) {
    $sql_profile = "SELECT id, firstName, lastName, email, phoneNumber, specialty, experience_years, consultation_fee, profile_picture_path, bio FROM doctors WHERE id = ?";
    if ($stmt_profile = $conn->prepare($sql_profile)) {
        $stmt_profile->bind_param("i", $doctor_id);
        if ($stmt_profile->execute()) {
            $result_profile = $stmt_profile->get_result();
            if ($result_profile->num_rows == 1) {
                $doctor_profile = $result_profile->fetch_assoc();
            }
        }
        $stmt_profile->close();
    }
}
$doctor_name_display = htmlspecialchars($doctor_profile['firstName'] ?? $_SESSION['user_firstName'] ?? 'Doctor');
$doctor_lastname_display = htmlspecialchars($doctor_profile['lastName'] ?? $_SESSION['user_lastName'] ?? '');
$doctor_email_display = htmlspecialchars($doctor_profile['email'] ?? ($_SESSION['user_email'] ?? 'N/A'));
$doctor_phone_display = htmlspecialchars($doctor_profile['phoneNumber'] ?? 'N/A');
$doctor_specialty = htmlspecialchars($doctor_profile['specialty'] ?? '');
$doctor_experience = htmlspecialchars($doctor_profile['experience_years'] ?? '');
$doctor_fee = htmlspecialchars($doctor_profile['consultation_fee'] ?? '');
$doctor_bio = htmlspecialchars($doctor_profile['bio'] ?? '');
$doctor_profile_picture = !empty($doctor_profile['profile_picture_path']) ? $doctor_profile['profile_picture_path'] : '../images/doctor-ashford.png';

// --- Fetch Today's Appointments ---
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
      AND a.attending_dentist = ?
      AND (a.status = 'SCHEDULED' OR a.status = 'CONFIRMED')
    ORDER BY a.appointment_time ASC
";
if ($stmt_today = $conn->prepare($sql_today)) {
    $stmt_today->bind_param("si", $today_date, $doctor_id);
    if ($stmt_today->execute()) {
        $result_today = $stmt_today->get_result();
        while ($row = $result_today->fetch_assoc()) {
            $todays_appointments[] = $row;
        }
    }
    $stmt_today->close();
}

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="doctor_dashboard.css">
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

<body class="doctor-layout-page">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="../images/tooth.png" alt="Escosia Dental Clinic Logo" class="logo-image">
                <h1>Escosia Dental Clinic</h1>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="doctor_dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                    <li><a href="doctor_appointment.php" class="nav-link"><i class="fas fa-calendar-alt nav-icon"></i> Appointments</a></li>
                    <li><a href="doctor_patient_management.php" class="nav-link"><i class="fas fa-clipboard-list nav-icon"></i> Patient Management</a></li>
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
                            <?php endif; ?>                        </i>
                        <div class="notifications-dropdown" id="notificationsDropdown">
                            <div class="notification-header">Notifications</div>
                            <div class="notification-list" id="notificationList">
                                <p class="no-notifications">No new notifications.</p>
                            </div>
                            <div class="notification-footer">
                                <a href="doctor_appointment.php?filter=pending">View All Request</a>
                            </div>
                        </div>
                    </div>
                    <img src="<?php echo htmlspecialchars($doctor_profile_picture); ?>" alt="User Avatar" class="user-avatar" id="profileCardAvatar">
                    <div class="user-details">
                        <span class="user-name" id="profileCardName"><?php echo $doctor_name_display . ' ' . $doctor_lastname_display; ?></span>
                        <span class="user-role"><?php echo $doctor_specialty ? htmlspecialchars($doctor_specialty) : 'Doctor'; ?></span>
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
                </main>
                <aside class="right-sidebar">
                    <div class="profile-card card" id="doctorProfileCard">
                        <div class="card-header">
                            <h3>My Profile</h3>
                            <button class="edit-profile-btn" id="editProfileBtn" title="Edit Profile"><i class="fas fa-pencil-alt"></i></button>
                        </div>
                        <div class="profile-content">
                            <img src="<?php echo htmlspecialchars($doctor_profile_picture); ?>" alt="User Avatar" class="profile-avatar-large" id="doctorProfileAvatarDisplay">
                            <p class="profile-name" id="doctorFullNameDisplay"><?php echo $doctor_name_display . ' ' . $doctor_lastname_display; ?></p>
                            <p class="profile-role"><?php echo $doctor_specialty ? htmlspecialchars($doctor_specialty) : 'Doctor'; ?></p>
                            
                            <div class="info-grid" id="profileInfoView">
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value" id="doctorEmailDisplay"><?php echo $doctor_email_display; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone Number:</span>
                                    <span class="info-value" id="doctorPhoneDisplay"><?php echo $doctor_phone_display; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Years of Experience:</span>
                                    <span class="info-value"><?php echo $doctor_experience; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Consultation Fee:</span>
                                    <span class="info-value"><?php echo $doctor_fee; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Short Bio:</span>
                                    <span class="info-value"><?php echo $doctor_bio; ?></span>
                                </div>
                            </div>

                            <form id="editProfileForm" style="display: none;">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label for="profileFirstName" class="info-label">First Name:</label>
                                        <input type="text" id="profileFirstName" name="firstName" class="info-input" value="<?php echo $doctor_name_display; ?>" required>
                                    </div>
                                    <div class="info-item">
                                        <label for="profileLastName" class="info-label">Last Name:</label>
                                        <input type="text" id="profileLastName" name="lastName" class="info-input" value="<?php echo $doctor_lastname_display; ?>" required>
                                    </div>
                                    <div class="info-item">
                                        <label for="profileEmail" class="info-label">Email:</label>
                                        <input type="email" id="profileEmail" name="email" class="info-input" value="<?php echo $doctor_email_display; ?>" required>
                                    </div>
                                    <div class="info-item">
                                        <label for="profilePhoneNumber" class="info-label">Phone Number:</label>
                                        <input type="tel" id="profilePhoneNumber" name="phoneNumber" class="info-input" value="<?php echo $doctor_phone_display !== 'N/A' ? $doctor_phone_display : ''; ?>">
                                    </div>
                                    <div class="info-item">
                                        <label for="profileExperience" class="info-label">Years of Experience:</label>
                                        <input type="number" id="profileExperience" name="experience_years" class="info-input" min="0" value="<?php echo htmlspecialchars($doctor_experience); ?>">
                                    </div>
                                    <div class="info-item">
                                        <label for="profileFee" class="info-label">Consultation Fee:</label>
                                        <input type="number" id="profileFee" name="consultation_fee" class="info-input" min="0" step="0.01" value="<?php echo htmlspecialchars($doctor_fee); ?>">
                                    </div>
                                    <div class="info-item">
                                        <label for="profileBio" class="info-label">Short Bio:</label>
                                        <textarea id="profileBio" name="bio" class="info-input" rows="3"><?php echo htmlspecialchars($doctor_bio); ?></textarea>
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
    <script src="calendar.js"></script>
    <script src="doctor_dashboard.js"></script>
</body>
</html>