<?php
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: receptionist_signin.html');
    exit;
}
$receptionist_name = $_SESSION['user_firstName'] ?? 'Receptionist';

// Fetch Dentists for modal dropdowns
$dentists_for_modal = [];
$sql_dentists = "SELECT id, firstName, lastName FROM doctors WHERE is_active = 1 ORDER BY lastName, firstName";
$result_dentists_query = $conn->query($sql_dentists);
if ($result_dentists_query && $result_dentists_query->num_rows > 0) {
    while ($row = $result_dentists_query->fetch_assoc()) {
        $dentists_for_modal[] = $row;
    }
}

// Fetch Patient Records
$patients_records = [];
// CORRECTED SQL: Removed p.assigned_doctor_id from SELECT list.
// Also removed p.medicalInfo as per previous request to remove it from forms.
// If you need medicalInfo for view modal, add p.medicalInfo here and data-medical-info attribute below.
$sql_patients = "
    SELECT 
        p.id as patient_id,
        p.firstName as patient_firstName,
        p.lastName as patient_lastName,
        p.phoneNumber as patient_phone,
        p.email as patient_email,
        p.dob as patient_dob
    FROM patients p
    ORDER BY p.lastName, p.firstName
";
$result_patients_query = $conn->query($sql_patients); // This is around line 33
if ($result_patients_query && $result_patients_query->num_rows > 0) {
    while ($row = $result_patients_query->fetch_assoc()) {
        $patients_records[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Records - Escosia Dental Clinic</title>
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
                    <li><a href="receptionist_appointment.php" class="nav-link"><i class="fas fa-calendar-alt nav-icon"></i> Appointments</a></li>
                    <li><a href="receptionist_patient_records.php" class="nav-link active"><i class="fas fa-clipboard-list nav-icon"></i> Patient Records</a></li>
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
                        <h2>Patient Records</h2>
                     </div>
                    <?php
                    if (isset($_SESSION['patient_record_message'])): ?>
                        <div class="form-feedback-message <?php echo htmlspecialchars($_SESSION['patient_record_message']['type']); ?>">
                            <?php echo $_SESSION['patient_record_message']['text']; ?>
                        </div>
                    <?php 
                        unset($_SESSION['patient_record_message']);
                    endif; 
                    ?>

                    <section class="patient-list-section card">
                        <h3>List of Patients</h3>
                        <div class="filter-container">
                            <div class="filter-group">
                                <label for="patient-search-input">Search Patient:</label>
                                <input type="text" id="patient-search-input" class="form-control search-input" placeholder="Search by Name or ID...">
                            </div>
                             <button class="btn btn-primary" id="add-new-patient-btn" style="margin-left: auto;"><i class="fas fa-plus"></i> Add New Patient</button>
                        </div>

                        <div class="table-responsive-wrapper">
                            <table class="data-table" id="patient-records-table">
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>Patient Name</th>
                                        <th>Contact Info</th>
                                        <th>Date of Birth</th>
                                        <th class="actions-column">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($patients_records)): ?>
                                        <?php foreach ($patients_records as $patient): ?>
                                            <tr data-patient-id="<?php echo htmlspecialchars($patient['patient_id']); ?>"
                                                data-first-name="<?php echo htmlspecialchars($patient['patient_firstName']); ?>"
                                                data-last-name="<?php echo htmlspecialchars($patient['patient_lastName']); ?>"
                                                data-phone="<?php echo htmlspecialchars($patient['patient_phone'] ?? ''); ?>"
                                                data-email="<?php echo htmlspecialchars($patient['patient_email'] ?? ''); ?>"
                                                data-dob="<?php echo htmlspecialchars($patient['patient_dob'] ?? ''); ?>"
                                                <!-- data-assigned-doctor-id is removed as it's not selected -->
                                            >
                                                <td>#<?php echo str_pad(htmlspecialchars($patient['patient_id']), 3, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($patient['patient_firstName'] . ' ' . $patient['patient_lastName']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($patient['patient_phone'] ?? 'N/A'); ?><br>
                                                    <?php echo htmlspecialchars($patient['patient_email'] ?? 'N/A'); ?>
                                                </td>
                                                <td><?php echo !empty($patient['patient_dob']) ? htmlspecialchars(date("M d, Y", strtotime($patient['patient_dob']))) : 'N/A'; ?></td>
                                                <td class="action-links action-buttons-cell">
                                                    <a href="#" class="btn btn-action btn-view-patient"><i class="fas fa-eye"></i> View</a>
                                                    <a href="#" class="btn btn-action btn-edit-patient"><i class="fas fa-edit"></i> Edit</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr class="no-data-row" <?php if (!empty($patients_records)) echo 'style="display: none;"'; ?>>
                                        <td colspan="5">No patient records found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                         <p class="table-note">This list displays registered patients in the clinic.</p>
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

    <div id="patientModalOverlay" class="modal-overlay">
        <div id="patientModalContent" class="modal-content">
            <div class="modal-header">
                <h3 id="patientModalTitle">Patient Details</h3>
                <button id="patientModalCloseButton" class="modal-close">×</button>
            </div>
            <div id="patientModalBody" class="modal-body"><p>Loading patient details...</p></div>
            <div id="patientModalFooter" class="modal-footer" style="display: none;">
                 <button class="btn btn-primary" id="patientSaveChangesButton">Save Changes</button>
                 <button class="btn btn-secondary" id="patientCancelEditButton">Cancel</button>
            </div>
        </div>
    </div>
    <script>
        const availableDentistsForPatientModal = <?php echo json_encode($dentists_for_modal); ?>;
    </script>
    <script src="calendar.js"></script>
    <script src="receptionist_patient_records.js"></script>
</body>
</html>