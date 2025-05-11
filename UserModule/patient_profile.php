    <?php
    // Add this AT THE VERY TOP, before require_once
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once '../config.php'; // Includes session_start(), $conn, and encryption functions

    // Debugging block (keep it for now if still troubleshooting, remove for production)
    /*
    echo "<pre style='background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc; margin-bottom: 20px;'>";
    echo "<strong>DEBUGGING PATIENT_PROFILE.PHP</strong><br>";
    echo "-----------------------------------<br>";
    echo "SESSION Data at start of patient_profile.php:<br>";
    print_r($_SESSION);
    echo "-----------------------------------<br>";
    */

    // Check if the user is logged in as a patient, otherwise redirect to login page
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "patient") {
        // echo "Auth Check: FAILED. Redirecting to patient_signin.php<br>"; // Debug line
        // echo "</pre>"; // Debug line
        $_SESSION = array(); 
        session_destroy();
        header("location: patient_signin.php");
        exit;
    }
    // echo "Auth Check: PASSED.<br>"; // Debug line

    $userId = isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 0;
    // echo "User ID from session (userId): " . htmlspecialchars($userId) . "<br>"; // Debug line
    // echo "-----------------------------------<br>"; // Debug line

    $patient = null;
    $errors = [];
    $next_appointment_display = "No upcoming appointments";

    // --- 1. Fetch Patient's Personal Information ---
    if ($userId > 0) {
        // echo "Attempting to fetch patient info for userId: " . htmlspecialchars($userId) . "<br>"; // Debug line
        // SQL Query: Removed 'address' and 'gender' since they are not in your 'patients' table.
        // 'id' is included for potential verification/debugging.
        $sql_patient = "SELECT id, firstName, lastName, email, phoneNumber, dob, medicalInfo FROM patients WHERE id = ?";
        // echo "SQL Query for patient: " . htmlspecialchars($sql_patient) . "<br>"; // Debug line
        
        if ($stmt_patient = $conn->prepare($sql_patient)) {
            // echo "Statement prepared successfully.<br>"; // Debug line
            $stmt_patient->bind_param("i", $userId);
            // echo "Parameter bound (userId: " . htmlspecialchars($userId) . ").<br>"; // Debug line
            
            if ($stmt_patient->execute()) {
                // echo "Statement executed successfully.<br>"; // Debug line
                $result_patient = $stmt_patient->get_result();
                // echo "Number of rows found: " . $result_patient->num_rows . "<br>"; // Debug line

                if ($result_patient->num_rows == 1) {
                    $patient = $result_patient->fetch_assoc();
                    // echo "Patient data fetched:<br>"; print_r($patient); echo "<br>"; // Debug line
                    
                    if (!empty($patient['medicalInfo'])) {
                        $decryptedMedicalInfo = decrypt_data($patient['medicalInfo']);
                        $patient['medicalInfoDecrypted'] = ($decryptedMedicalInfo === false || $decryptedMedicalInfo === null) ? "[Encrypted data - retrieval issue or no data]" : $decryptedMedicalInfo;
                    } else {
                        $patient['medicalInfoDecrypted'] = 'Not provided';
                    }

                    if (!empty($patient['dob'])) {
                        try {
                            $birthDate = new DateTime($patient['dob']);
                            $today = new DateTime();
                            $patient['age'] = $today->diff($birthDate)->y;
                        } catch (Exception $e) {
                            $patient['age'] = 'N/A';
                            error_log("Error parsing DOB for patient ID {$userId}: " . $e->getMessage());
                        }
                    } else {
                        $patient['age'] = 'N/A';
                    }
                    // 'gender' is not in the table. If you add it, you'd fetch it.
                    $patient['gender'] = $patient['gender'] ?? 'N/A'; 
                    // 'address' is removed as it's not in the table.
                    // $patient['address'] = $patient['address'] ?? 'N/A'; // This line is removed
                } else {
                    $errors[] = "Patient record for profile not found in DB. (Query returned 0 rows for ID: {$userId})"; 
                }
            } else {
                $errors[] = "Error executing patient details query.";
                error_log("Error executing patient details fetch for ID {$userId}: " . $stmt_patient->error);
            }
            $stmt_patient->close();
        } else {
            $errors[] = "Database error preparing patient details statement.";
            error_log("Error preparing patient details statement: " . $conn->error);
        }
    } else {
        $errors[] = "User ID not found in session or invalid (was 0 or not set). Please log in again.";
    }

    /*
    // Debugging block end (keep for now if still troubleshooting)
    echo "-----------------------------------<br>";
    echo "Errors array after patient fetch:<br>";
    print_r($errors);
    echo "<br>";
    echo "Patient variable after fetch attempt:<br>";
    var_dump($patient);
    echo "<br>";
    echo "-----------------------------------<br>";
    echo "</pre>"; 
    // exit; // UNCOMMENT THIS TO STOP EXECUTION AFTER DEBUG BLOCK
    */
    ?>
    <?php
    // --- 2. Fetch Next Appointment (Only if patient data was found) ---
    if ($patient) { // Check if $patient is not null
        $sql_next_appointment = "
            SELECT appointment_date, appointment_time 
            FROM appointments 
            WHERE patient_id = ? 
            AND (appointment_date > CURDATE() OR (appointment_date = CURDATE() AND appointment_time >= CURTIME())) 
            AND status IN ('SCHEDULED', 'CONFIRMED')
            ORDER BY appointment_date ASC, appointment_time ASC 
            LIMIT 1";
        if ($stmt_next = $conn->prepare($sql_next_appointment)) {
            $stmt_next->bind_param("i", $userId);
            if ($stmt_next->execute()) {
                $result_next = $stmt_next->get_result();
                if ($row_next = $result_next->fetch_assoc()) {
                    try {
                        $next_date = new DateTime($row_next['appointment_date']);
                        $next_appointment_display = $next_date->format('M d, Y');
                    } catch (Exception $e) {
                        $next_appointment_display = "Error with date";
                        error_log("Error parsing next appointment date for patient ID {$userId}: " . $e->getMessage());
                    }
                }
            } else {
                error_log("Error executing next appointment fetch for patient ID {$userId}: " . $stmt_next->error);
            }
            $stmt_next->close();
        } else {
            error_log("Error preparing next appointment statement for patient ID {$userId}: " . $conn->error);
        }
    }

    // --- Fetch Unread Notifications Count ---
    $unread_notifications_count = 0;
    if ($userId > 0) {
        $sql_unread_notifications = "SELECT COUNT(*) as count FROM notifications WHERE patient_id = ? AND is_read = 0";
        if ($stmt_unread_notifications = $conn->prepare($sql_unread_notifications)) {
            $stmt_unread_notifications->bind_param("i", $userId);
            if ($stmt_unread_notifications->execute()) {
                $result_unread = $stmt_unread_notifications->get_result();
                if($row_unread = $result_unread->fetch_assoc()){
                    $unread_notifications_count = (int)$row_unread['count'];
                }
            } else { error_log("Error executing unread notifications count for patient ID {$userId}: " . $stmt_unread_notifications->error); }
            $stmt_unread_notifications->close();
        } else { error_log("Error preparing unread notifications count for patient ID {$userId}: " . $conn->error); }
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Escosia Dental Clinic - Patient Profile</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            /* Basic styles from your previous code block */
            .main-nav-icon { font-size: 1.1em; padding: 0 8px; display: inline-block; vertical-align: middle; position: relative; }
            .main-nav-icon:hover { color: var(--old-dark-green); } 
            .main-nav-icon .notification-badge { position: absolute; top: -2px; right: -2px; background-color: var(--danger-red, #dc3545); color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 0.65em; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid var(--old-white, #fff); }
            .patient-summary-card { padding: 20px; margin-bottom: 30px; background-color: var(--card-bg-color, #fff); border-radius: 8px; box-shadow: 0 2px 5px var(--shadow-color, rgba(0,0,0,0.1)); border: 1px solid var(--body-bg-fallback, #eee); }
            .patient-summary-card .summary-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
            .patient-summary-card .detail-item .detail-label { display: block; font-size: 0.85em; color: var(--text-muted, #6c757d); margin-bottom: 4px; }
            .patient-summary-card .detail-item .detail-value { font-size: 1em; font-weight: 500; color: var(--text-color, #333); }
            .profile-page main { display: flex; flex-direction: column; align-items: center; padding-top: 30px; padding-bottom: 30px; }
            .profile-page .profile-content-wrapper { max-width: 750px; width: 100%; padding: 0 15px; }
            .profile-page .page-title{ text-align: center; margin-bottom: 25px; font-size: 1.8em; color: var(--primary-green, #16a085); }
            .personal-info-card { background-color: var(--card-bg-color, #fff); padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px var(--shadow-color, rgba(0,0,0,0.12)); border: 1px solid var(--body-bg-fallback, #e7e7e7); }
            .personal-info-card .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--light-border-color, #eee); }
            .personal-info-card .card-header h3 { margin: 0; font-size: 1.3em; color: var(--dark-green, #004d40);  }
            .personal-info-card .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
            .personal-info-card .info-item > div { margin-bottom: 10px; } /* This might be redundant if info-item is the direct child for grid items */
            .personal-info-card .info-item { /* Added for clarity if each field pair is wrapped */ }
            .personal-info-card .info-label { font-weight: 600; color: var(--text-muted, #555); display: block; margin-bottom: 5px; font-size: 0.9em; }
            .personal-info-card .info-value { color: var(--text-color, #333); font-size: 1em; word-break: break-word; }
            .personal-info-card .full-width { grid-column: 1 / -1; }
            .personal-info-card .edit-info-btn { background: none; border: none; color: var(--primary-green, #16a085); cursor: pointer; font-size: 1.1em; padding: 5px; }
            .personal-info-card .edit-info-btn:hover { color: var(--dark-green, #004d40); }
            .error-messages ul { list-style-position: inside; padding-left: 0;}
            .error-messages li { margin-bottom: 5px;}
        </style>
    </head>
    <body class="patient-area page-patient-profile">

        <div class="container">
            <header>
                <div class="logo">
                    <img src="../images/tooth.png" alt="Tooth Logo" class="logo-icon">
                    <h1>Escosia Dental Clinic</h1>
                </div>
                <nav>
                    <ul>
                        <li><a href="patient_dashboard.php">DASHBOARD</a></li>
                        <li><a href="doctors_patient.php">DOCTORS</a></li>
                        <li><a href="patient_appointments.php">APPOINTMENTS</a></li>
                        <li><a href="patient_profile.php" class="active">PROFILE</a></li>
                        <li>
                            <a href="patient_notifications.php" title="Notifications" class="main-nav-icon">
                                <i class="fas fa-bell"></i>
                                <?php if ($unread_notifications_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_notifications_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                    <div class="header-actions">
                        <a href="logout.php" class="btn btn-secondary">Logout</a>
                    </div>
                </nav>
            </header>

            <main class="profile-page">
                <h2 class="page-title">Patient Profile</h2>

                <?php if (!empty($errors)): ?>
                    <div class="error-messages" style="background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin-bottom:20px; border-radius:8px; max-width: 700px; width: 95%;">
                        <strong>Error!</strong>
                        <ul><?php foreach ($errors as $error) { echo '<li>' . htmlspecialchars($error) . '</li>'; } ?></ul>
                    </div>
                <?php endif; ?>

                <?php if ($patient): ?>
                <div class="profile-content-wrapper">
                    <section class="patient-summary-card">
                        <div class="summary-details">
                            <div class="detail-item">
                                <span class="detail-label">Patient Name</span>
                                <span class="detail-value"><?php echo htmlspecialchars($patient['firstName'] . ' ' . $patient['lastName']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Gender</span>
                                <span class="detail-value"><?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Age</span>
                                <span class="detail-value"><?php echo htmlspecialchars($patient['age']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Next Appointment</span>
                                <span class="detail-value"><?php echo htmlspecialchars($next_appointment_display); ?></span>
                            </div>
                        </div>
                    </section>

                    <section class="personal-info-card" id="personalInfoCard">
                        <div class="card-header">
                            <h3>Personal Information</h3>
                            <button class="edit-info-btn" aria-label="Edit Personal Information" title="Edit Personal Information"><i class="fas fa-pencil-alt"></i></button>
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">First Name</span>
                                <span class="info-value" data-field="firstName"><?php echo htmlspecialchars($patient['firstName']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Last Name</span>
                                <span class="info-value" data-field="lastName"><?php echo htmlspecialchars($patient['lastName']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email Address</span>
                                <span class="info-value" data-field="email"><?php echo htmlspecialchars($patient['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Phone Number</span>
                                <span class="info-value" data-field="phone"><?php echo htmlspecialchars($patient['phoneNumber'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Date of Birth</span>
                                <span class="info-value" data-field="dob">
                                    <?php echo !empty($patient['dob']) ? htmlspecialchars(date("F j, Y", strtotime($patient['dob']))) : 'N/A'; ?>
                                </span>
                            </div>
                            <!-- Address Removed from Display -->
                            <div class="info-item full-width">
                                <span class="info-label">Medical Information</span>
                                <span class="info-value" data-field="medicalInfo"><?php echo nl2br(htmlspecialchars($patient['medicalInfoDecrypted'])); ?></span>
                            </div>
                        </div>
                    </section>
                </div>
                <?php elseif (empty($errors)): ?>
                    <p style="text-align: center;">Could not load patient profile data. If you have just registered, please try logging out and logging back in. If the issue persists, contact support.</p>
                <?php endif; ?>
            </main>
        </div>

        <script src="script.js"></script>
        <?php
            if (isset($conn) && $conn instanceof mysqli) {
                $conn->close();
            }
        ?>
    </body>
    </html>