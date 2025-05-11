<?php
require_once '../config.php'; // For session and DB connection

// --- Receptionist Authentication ---
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: receptionist_signin.html');
    exit;
}
$receptionist_name = $_SESSION['user_firstName'] ?? 'Receptionist';

// --- Fetch Data for Reports ---

// -- Key Metrics (Last Month) --
$metrics = [
    'appointmentsBooked' => 0,
    'appointmentsRescheduled' => 0,
    'cancellations' => 0,
    'newRegisteredPatients' => 0,
    'patientsSeenToday' => 0,
    'showUpRate' => 'N/A'
];

$first_day_last_month = date('Y-m-01', strtotime('first day of last month'));
$last_day_last_month = date('Y-m-t', strtotime('last day of last month'));
$today_date = date('Y-m-d');

// 1. Appointments Booked Last Month
$sql_booked_last_month = "SELECT COUNT(id) as count FROM appointments WHERE DATE(created_at) BETWEEN ? AND ?";
if ($stmt = $conn->prepare($sql_booked_last_month)) {
    $stmt->bind_param("ss", $first_day_last_month, $last_day_last_month);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $metrics['appointmentsBooked'] = (int)$row['count'];
    } else { error_log("Metric Error (Booked LM): " . $stmt->error); }
    $stmt->close();
} else { error_log("Metric Prepare Error (Booked LM): " . $conn->error); }

// 2. Cancellations Last Month
$sql_cancelled_last_month = "SELECT COUNT(id) as count FROM appointments WHERE appointment_date BETWEEN ? AND ? AND status = 'Cancelled'";
if ($stmt = $conn->prepare($sql_cancelled_last_month)) {
    $stmt->bind_param("ss", $first_day_last_month, $last_day_last_month);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $metrics['cancellations'] = (int)$row['count'];
    } else { error_log("Metric Error (Cancelled LM): " . $stmt->error); }
    $stmt->close();
} else { error_log("Metric Prepare Error (Cancelled LM): " . $conn->error); }

// 3. New Registered Patients Last Month
$sql_new_patients_last_month = "SELECT COUNT(id) as count FROM patients WHERE DATE(created_at) BETWEEN ? AND ?";
if ($stmt = $conn->prepare($sql_new_patients_last_month)) {
    $stmt->bind_param("ss", $first_day_last_month, $last_day_last_month);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $metrics['newRegisteredPatients'] = (int)$row['count'];
    } else { error_log("Metric Error (New Patients LM): " . $stmt->error); }
    $stmt->close();
} else { error_log("Metric Prepare Error (New Patients LM): " . $conn->error); }

// 4. Patients Seen Today
$sql_seen_today = "SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE appointment_date = ? AND status = 'Completed'";
if ($stmt = $conn->prepare($sql_seen_today)) {
    $stmt->bind_param("s", $today_date);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $metrics['patientsSeenToday'] = (int)$row['count'];
    } else { error_log("Metric Error (Seen Today): " . $stmt->error); }
    $stmt->close();
} else { error_log("Metric Prepare Error (Seen Today): " . $conn->error); }

// 5. Show-up Rate (Last Month)
$sql_show_rate_lm_completed = "SELECT COUNT(id) as count FROM appointments WHERE appointment_date BETWEEN ? AND ? AND status = 'Completed'";
$sql_show_rate_lm_noshow = "SELECT COUNT(id) as count FROM appointments WHERE appointment_date BETWEEN ? AND ? AND status = 'No Show'"; // Assuming 'No Show' status
$completed_lm = 0; $noshow_lm = 0;

if ($stmt = $conn->prepare($sql_show_rate_lm_completed)) {
    $stmt->bind_param("ss", $first_day_last_month, $last_day_last_month);
    if($stmt->execute()){ $res = $stmt->get_result(); if($r = $res->fetch_assoc()) $completed_lm = (int)$r['count']; } else { error_log("Metric Error (ShowRate Comp): ".$stmt->error); }
    $stmt->close();
} else { error_log("Metric Prepare Error (ShowRate Comp): ".$conn->error); }

if ($stmt = $conn->prepare($sql_show_rate_lm_noshow)) {
    $stmt->bind_param("ss", $first_day_last_month, $last_day_last_month);
    if($stmt->execute()){ $res = $stmt->get_result(); if($r = $res->fetch_assoc()) $noshow_lm = (int)$r['count']; } else { error_log("Metric Error (ShowRate NoShow): ".$stmt->error); }
    $stmt->close();
} else { error_log("Metric Prepare Error (ShowRate NoShow): ".$conn->error); }

if (($completed_lm + $noshow_lm) > 0) {
    $metrics['showUpRate'] = round(($completed_lm / ($completed_lm + $noshow_lm)) * 100) . '%';
}


// -- Monthly Engagement Rate (Appointments per month for last 12 months) --
$monthly_engagement_labels = [];
$monthly_engagement_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month_date_obj = new DateTime("first day of -$i months");
    $month_date_sql = $month_date_obj->format('Y-m-d'); // For SQL query
    $month_name_label = $month_date_obj->format('M');   // For chart label

    $monthly_engagement_labels[] = $month_name_label;

    $sql_month_engagement = "SELECT COUNT(id) as count FROM appointments 
                             WHERE YEAR(appointment_date) = ? AND MONTH(appointment_date) = ?
                             AND status NOT IN ('Cancelled', 'Pending', 'No Show')";
    if ($stmt = $conn->prepare($sql_month_engagement)) {
        $year_to_query = $month_date_obj->format('Y');
        $month_to_query = $month_date_obj->format('m');
        $stmt->bind_param("ss", $year_to_query, $month_to_query);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $monthly_engagement_data[] = ($row = $result->fetch_assoc()) ? (int)$row['count'] : 0;
        } else { 
            $monthly_engagement_data[] = 0; 
            error_log("Chart Data Error (Month: $month_date_sql): " . $stmt->error);
        }
        $stmt->close();
    } else {
        $monthly_engagement_data[] = 0;
        error_log("Chart Data Prepare Error (Month: $month_date_sql): " . $conn->error);
    }
}
$chart_data = [
    'labels' => $monthly_engagement_labels,
    'data' => $monthly_engagement_data
];

// $conn->close(); // Usually not needed explicitly if script ends
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Reports & Analytics - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="receptionist_dashboard.css"> <!-- Main Receptionist Styles -->
    <!-- Add any report-specific CSS if needed, or ensure receptionist_dashboard.css covers it -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="receptionist-layout-page"> <!-- Consistent body class -->
    <div class="dashboard-container"> <!-- Or admin-page-wrapper if using that general class -->
        <aside class="sidebar"> <!-- Or admin-sidebar -->
            <div class="logo-container">
                 <img src="../images/tooth.png" alt="Escosia Dental Clinic Logo" class="logo-image">
                 <h1>Escosia Dental Clinic</h1>
            </div>
            <nav class="main-nav"> <!-- Or sidebar-nav -->
                <ul>
                    <li><a href="receptionist_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a></li>
                    <li><a href="receptionist_appointment.php" class="nav-link"><i class="fas fa-calendar-alt nav-icon"></i> Appointments</a></li>
                    <li><a href="receptionist_patient_records.php" class="nav-link"><i class="fas fa-clipboard-list nav-icon"></i> Patient Records</a></li>
                    <li><a href="receptionist_reports.php" class="nav-link active"><i class="fas fa-chart-bar nav-icon"></i> Reports & Analytics</a></li>
                    <!-- DOCTORS LINK REMOVED -->
                    <li><a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt nav-icon"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-content"> <!-- Or admin-main-content -->
            <header class="top-header"> <!-- Or admin-top-header -->
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

            <div class="main-body-wrapper"> <!-- Or admin-body-content -->
                <main class="content-area"> <!-- Or main-panel -> main-panel-content-wrapper -->
                    <div class="content-area-header-block">
                        <h2>Reports & Analytics</h2>
                     </div>

                    <section class="chart-section card">
                        <h3 class="card-header">Monthly Engagement Rate</h3>
                        <div class="card-content">
                            <div class="chart-placeholder">
                                 <canvas id="engagementChart"></canvas>
                            </div>
                             <p class="table-note" style="text-align: center; margin-top: 15px;">Active appointments over the last 12 months.</p>
                        </div>
                    </section>

                     <hr class="content-separator">

                    <section class="metrics-section">
                         <h3>Key Metrics (Last Month)</h3>
                         <div class="metrics-grid">
                             <div class="metric-card card">
                                 <div class="metric-value" id="metric-booked"><?php echo htmlspecialchars($metrics['appointmentsBooked']); ?></div>
                                 <div class="metric-label">Appointments Booked</div>
                             </div>
                             <div class="metric-card card">
                                 <div class="metric-value" id="metric-rescheduled"><?php echo htmlspecialchars($metrics['appointmentsRescheduled']); ?></div>
                                 <div class="metric-label">Appointments Rescheduled</div>
                             </div>
                             <div class="metric-card card">
                                 <div class="metric-value" id="metric-cancellations"><?php echo htmlspecialchars($metrics['cancellations']); ?></div>
                                 <div class="metric-label">Cancellations</div>
                             </div>
                             <div class="metric-card card">
                                 <div class="metric-value" id="metric-new-patients"><?php echo str_pad(htmlspecialchars($metrics['newRegisteredPatients']), 2, '0', STR_PAD_LEFT); ?></div>
                                 <div class="metric-label">New Registered Patients</div>
                             </div>
                              <div class="metric-card card">
                                  <div class="metric-value" id="metric-seen-today"><?php echo htmlspecialchars($metrics['patientsSeenToday']); ?></div>
                                  <div class="metric-label">Patients Seen Today</div>
                              </div>
                               <div class="metric-card card">
                                  <div class="metric-value" id="metric-show-rate"><?php echo htmlspecialchars($metrics['showUpRate']); ?></div>
                                  <div class="metric-label">Show-up Rate</div>
                              </div>
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
                             <div class="time-selector">
                                <span class="time-label">Selected Time</span>
                                <input type="text" value="3:00" class="time-input" disabled>
                                <button class="ampm-btn active" disabled>PM</button>
                                <button class="ampm-btn" disabled>AM</button>
                            </div>
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

    <script>
        const phpChartData = <?php echo json_encode($chart_data); ?>;
        // const phpMetrics = <?php echo json_encode($metrics); ?>; // Metrics are directly echoed
    </script>
    <script src="calendar.js"></script>
    <script src="receptionist_reports.js"></script>
</body>
</html>