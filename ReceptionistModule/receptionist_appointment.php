<?php
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: receptionist_signin.html');
    exit;
}
$receptionist_name = $_SESSION['user_firstName'] ?? 'Receptionist';
$receptionist_id_for_creation = $_SESSION['user_id'] ?? null;

$dentists = [];
$sql_dentists = "SELECT id, firstName, lastName FROM doctors WHERE is_active = 1 ORDER BY lastName, firstName";
$result_dentists = $conn->query($sql_dentists);
if ($result_dentists && $result_dentists->num_rows > 0) {
    while ($row = $result_dentists->fetch_assoc()) {
        $dentists[] = $row; // Used for dropdowns
    }
}

$patients = [];
$sql_patients = "SELECT id, firstName, lastName FROM patients ORDER BY lastName, firstName";
$result_patients = $conn->query($sql_patients);
if ($result_patients && $result_patients->num_rows > 0) {
    while ($row = $result_patients->fetch_assoc()) {
        $patients[] = $row;
    }
}

$appointments = [];
$sql_appointments = "
    SELECT 
        a.id as appointment_id,
        p.id as patient_id, /* Added patient_id for JS */
        p.firstName as patient_firstName,
        p.lastName as patient_lastName,
        a.appointment_date,
        a.appointment_time,
        a.attending_dentist as doctor_id, /* Added doctor_id for JS */
        d.firstName as doctor_firstName,
        d.lastName as doctor_lastName,
        a.service_type,
        a.notes,
        a.status
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    LEFT JOIN doctors d ON a.attending_dentist = d.id
    WHERE a.appointment_date >= CURDATE() OR a.status = 'Pending' OR a.status = 'SCHEDULED'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
";
$result_appointments_query = $conn->query($sql_appointments);
if ($result_appointments_query && $result_appointments_query->num_rows > 0) {
    while ($row = $result_appointments_query->fetch_assoc()) {
        $appointments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Appointments - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="receptionist_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <li><a href="receptionist_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                    <li><a href="receptionist_appointment.php" class="nav-link active"><i class="fas fa-calendar-alt nav-icon"></i> Appointments</a></li>
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
                    <i class="fas fa-bell notification-icon"></i>
                    <img src="../images/jen_hernandez.png" alt="User Avatar" class="user-avatar">
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($receptionist_name); ?></span>
                        <span class="user-role">Receptionist</span>
                    </div>
                </div>
            </header>

            <div class="main-body-wrapper">
                <main class="content-area">
                    <div class="content-area-header-block">
                        <h2>Appointment Management</h2>
                     </div>
                    <?php
                    if (isset($_SESSION['form_message'])): ?>
                        <div class="form-feedback-message <?php echo htmlspecialchars($_SESSION['form_message']['type']); ?>">
                            <?php echo $_SESSION['form_message']['text']; ?>
                        </div>
                    <?php 
                        unset($_SESSION['form_message']);
                    endif; 
                    ?>

                    <section class="appointments-list-section card">
                        <h3>Upcoming Appointments</h3>
                        <div class="filter-container">
                            <label for="dentist-filter">Filter by Dentist:</label>
                            <select id="dentist-filter" class="form-control filter-select">
                                <option value="all">Show All Dentists</option>
                                <?php foreach ($dentists as $dentist): ?>
                                    <option value="<?php echo htmlspecialchars('Dr. ' . $dentist['lastName']); ?>">
                                        <?php echo htmlspecialchars('Dr. ' . $dentist['firstName'] . ' ' . $dentist['lastName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="table-responsive-wrapper">
                            <table class="data-table" id="all-appointments-table">
                                <thead>
                                    <tr>
                                        <th>Patient Name</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Dentist</th>
                                        <th>Service Type</th>
                                        <th>Status</th>
                                        <th class="actions-column">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($appointments)): ?>
                                        <?php foreach ($appointments as $apt): ?>
                                            <tr data-appointment-id="<?php echo htmlspecialchars($apt['appointment_id']); ?>"
                                                data-patient-id="<?php echo htmlspecialchars($apt['patient_id']); ?>"
                                                data-doctor-id="<?php echo htmlspecialchars($apt['doctor_id'] ?? ''); ?>"
                                                data-service-type="<?php echo htmlspecialchars($apt['service_type']); ?>"
                                                data-appointment-date="<?php echo htmlspecialchars($apt['appointment_date']); ?>"
                                                data-appointment-time="<?php echo htmlspecialchars($apt['appointment_time']); ?>"
                                                data-notes="<?php echo htmlspecialchars($apt['notes'] ?? ''); ?>"
                                                data-status="<?php echo htmlspecialchars($apt['status']); ?>"
                                            >
                                                <td><?php echo htmlspecialchars($apt['patient_firstName'] . ' ' . $apt['patient_lastName']); ?></td>
                                                <td><?php echo htmlspecialchars(date("m.d.Y", strtotime($apt['appointment_date']))); ?></td>
                                                <td><?php echo htmlspecialchars(date("h:i A", strtotime($apt['appointment_time']))); ?></td>
                                                <td>
                                                    <?php 
                                                        if (!empty($apt['doctor_firstName'])) {
                                                            echo htmlspecialchars('Dr. ' . $apt['doctor_firstName'] . ' ' . $apt['doctor_lastName']);
                                                        } else { echo '-- Not Assigned Yet --'; }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($apt['service_type']); ?></td>
                                                <td><?php echo htmlspecialchars($apt['status']); ?></td>
                                                <td class="action-buttons-cell">
                                                    <a href="#" class="btn btn-action action-view"><i class="fas fa-eye"></i> View</a>
                                                    <a href="#" class="btn btn-action action-edit"><i class="fas fa-edit"></i> Edit</a>
                                                    <?php if (strtoupper($apt['status']) !== 'CANCELLED' && strtoupper($apt['status']) !== 'COMPLETED'): ?>
                                                        <a href="#" class="btn btn-action action-cancel action-decline"><i class="fas fa-times-circle"></i> Cancel</a>
                                                    <?php endif; ?>
                                                    <?php if (empty($apt['doctor_firstName']) && (strtoupper($apt['status']) === 'PENDING' || strtoupper($apt['status']) === 'SCHEDULED')): ?>
                                                         <a href="#" class="btn btn-action action-assign"><i class="fas fa-user-plus"></i> Assign</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr class="no-data-row" <?php if (!empty($appointments)) echo 'style="display: none;"'; ?>>
                                        <td colspan="7">No upcoming appointments scheduled.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                         <p class="table-note">Note: This table lists all confirmed and upcoming appointments.</p>
                    </section>

                     <hr class="content-separator">

                    <section class="book-appointment-section card">
                        <h3 class="card-header">Book New Appointment</h3>
                        <div class="card-content">
                            <form id="book-appointment-form" action="process_new_appointment.php" method="POST">
                                <div class="form-group">
                                    <label for="patient">Patient Name:</label>
                                    <select id="patient" name="patient_id" class="form-control" required>
                                        <option value="">-- Select Patient --</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo htmlspecialchars($patient['id']); ?>">
                                                <?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                     <small class="form-help">Patient not listed? <a href="receptionist_patient_records.php">Add them via Patient Records.</a></small>
                                </div>
                                <div class="form-group">
                                    <label for="dentist-for-booking">Assign Dentist:</label>
                                    <select id="dentist-for-booking" name="attending_dentist_id" class="form-control" required>
                                        <option value="">-- Select Dentist --</option>
                                         <?php foreach ($dentists as $dentist): ?>
                                            <option value="<?php echo htmlspecialchars($dentist['id']); ?>">
                                                <?php echo htmlspecialchars('Dr. ' . $dentist['firstName'] . ' ' . $dentist['lastName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group form-group-date-time">
                                    <div class="form-group-half">
                                        <label for="appointment-date-booking">Date:</label>
                                        <input type="date" id="appointment-date-booking" name="appointment_date" class="form-control" required>
                                    </div>
                                    <div class="form-group-half">
                                         <label for="appointment-time-slots">Available Time Slots:</label>
                                          <select id="appointment-time-slots" name="appointment_time" class="form-control" required>
                                              <option value="">-- Select Date & Dentist First --</option>
                                          </select>
                                          <small id="time-slot-loader" style="display:none;">Loading slots...</small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="service_type">Service Type:</label>
                                    <input type="text" id="service_type" name="service_type" class="form-control" required placeholder="e.g., Consultation, Cleaning, Filling">
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes (Optional):</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Any additional notes for the appointment"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Book Appointment</button>
                            </form>
                        </div>
                    </section>
                </main>

                 <aside class="right-sidebar">
                     <div class="profile-card card">
                        <h3 class="card-header">My Profile</h3>
                        <div class="profile-content">
                            <img src="../images/jen_hernandez.png" alt="User Avatar" class="profile-avatar-large">
                            <p class="profile-name"><?php echo htmlspecialchars($receptionist_name); ?></p>
                            <p class="profile-role">Receptionist</p>
                        </div>
                    </div>
                    <div class="calendar-card card">
                         <h3 class="card-header">Calendar <i class="fas fa-calendar-alt card-header-icon"></i></h3>
                        <div class="calendar-widget">
                            <div class="calendar-header">
                                <button id="prev-month" class="cal-nav-btn"><i class="fas fa-chevron-left"></i></button>
                                <h4 id="month-year">June 2024</h4>
                                <button id="next-month" class="cal-nav-btn"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="calendar-weekdays">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                            </div>
                            <div class="calendar-days" id="calendar-days"></div>
                        </div>
                         <h4 class="schedule-heading">Schedule for Selected Day</h4>
                        <div class="schedule-list">
                             <div class="schedule-item">
                                 <span class="schedule-time"></span>
                                 <span class="schedule-desc">Select a date to see schedule.</span>
                             </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <div id="appointmentModalOverlay" class="modal-overlay">
        <div id="appointmentModalContent" class="modal-content">
            <div class="modal-header">
                <h3 id="appointmentModalTitle">Appointment Details</h3>
                <button id="appointmentModalCloseButton" class="modal-close">×</button>
            </div>
            <div id="appointmentModalBody" class="modal-body"><p>Loading details...</p></div>
            <div id="appointmentModalFooter" class="modal-footer" style="display: none;">
                 <!-- Buttons will be added by JS here -->
            </div>
        </div>
    </div>
    <script>
        // Pass PHP arrays to JavaScript if needed by modals or other JS logic
        const availableDentists = <?php echo json_encode($dentists); ?>; // For populating dentist dropdowns in JS
    </script>
    <script src="calendar.js"></script>
    <script src="receptionist_appointment.js"></script>

</body>
</html>