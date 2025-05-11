<?php
// doctors_patient.php
require_once '../config.php';

$is_logged_in = (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true);

// Only fetch counts/user info if logged in and potentially needed (e.g., for main nav badge)
$unread_notifications_count = 0;
if ($is_logged_in && isset($_SESSION['user_id'])) {
    $userId = $_SESSION["user_id"]; // Define userId here if needed for other logic on this page
    $sql_unread_notifications = "SELECT COUNT(*) as count FROM notifications WHERE patient_id = ? AND is_read = 0";
    if ($stmt_unread_notifications = $conn->prepare($sql_unread_notifications)) {
        $stmt_unread_notifications->bind_param("i", $userId);
        if ($stmt_unread_notifications->execute()) {
            $result_unread = $stmt_unread_notifications->get_result();
            $unread_notifications_count = $result_unread->fetch_assoc()['count'];
        }
        $stmt_unread_notifications->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escosia Dental Clinic - Our Doctors</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Styles copied from previous version for consistency */
        .main-nav-icon {
            font-size: 1.1em; padding: 0 8px; display: inline-block; vertical-align: middle;
            position: relative; /* For potential badge */
        }
        .main-nav-icon:hover { color: var(--old-dark-green); }
        .main-nav-icon .notification-badge {
            position: absolute; top: -2px; right: -2px; background-color: var(--danger-red);
            color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 0.65em;
            display: flex; align-items: center; justify-content: center; font-weight: bold;
            border: 1px solid var(--old-white);
        }
    </style>
</head>
<body class="patient-area page-doctors">

    <div class="container">
        <header>
            <div class="logo">
                <img src="../images/tooth.png" alt="Tooth Logo" class="logo-icon"> <!-- Adjusted path -->
                <h1>Escosia Dental Clinic</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="patient_dashboard.php">DASHBOARD</a></li>
                    <li><a href="doctors_patient.php" class="active">DOCTORS</a></li>
                    <li><a href="patient_appointments.php">APPOINTMENTS</a></li>
                    <!-- PROFILE NAVIGATION LINK REMOVED -->
                    <li>
                        <a href="patient_notifications.php" title="Notifications" class="main-nav-icon">
                            <i class="fas fa-bell"></i>
                            <?php if ($is_logged_in && $unread_notifications_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_notifications_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <div class="header-actions">
                    <!-- Content removed -->
                </div>
            </nav>
        </header>

        <main>
             <section class="doctors-section">
                 <h2>OUR DOCTORS</h2>
                 <div class="doctors-scroll-container">
                     <div class="doctors-grid">
                         <div class="doctor-card">
                             <div class="doctor-image-placeholder">
                                 <img src="../images/doctor1.jpeg" alt="Photo of Dr. Ashford">
                             </div>
                             <h3>Dr. Ashford</h3>
                             <div class="doctor-details">
                                 <p>Experience: 12 years</p>
                                 <p>Specialty: General Dentist</p>
                                 <p>Fee per consultation: PHP 650.00</p>
                             </div>
                         </div>
                         <div class="doctor-card">
                             <div class="doctor-image-placeholder">
                                 <img src="../images/doctor2.jpeg" alt="Photo of Dr. Monte">
                             </div>
                             <h3>Dr. Monte</h3>
                             <div class="doctor-details">
                                 <p>Experience: 8 years</p>
                                 <p>Specialty: Orthodontist</p>
                                 <p>Fee per consultation: PHP 700.00</p>
                             </div>
                         </div>
                         <div class="doctor-card">
                             <div class="doctor-image-placeholder">
                                 <img src="../images/doctor3.jpeg" alt="Photo of Dr. Khan">
                             </div>
                             <h3>Dr. Khan</h3>
                             <div class="doctor-details">
                                 <p>Experience: 15 years</p>
                                 <p>Specialty: Endodontist</p>
                                 <p>Fee per consultation: PHP 800.00</p>
                             </div>
                         </div>
                         <div class="doctor-card">
                             <div class="doctor-image-placeholder">
                                 <img src="../images/doctor4.jpeg" alt="Photo of Dr. Emily Carter">
                             </div>
                             <h3>Dr. Emily Carter</h3>
                             <div class="doctor-details">
                                 <p>Experience: 10 years</p>
                                 <p>Specialty: Pediatric Dentist</p>
                                 <p>Fee per consultation: PHP 750.00</p>
                             </div>
                         </div>
                         <div class="doctor-card">
                             <div class="doctor-image-placeholder">
                                 <img src="../images/doctor5.jpeg" alt="Photo of Dr. Ben Chang">
                             </div>
                             <h3>Dr. Ben Chang</h3>
                             <div class="doctor-details">
                                 <p>Experience: 7 years</p>
                                 <p>Specialty: Periodontist</p>
                                 <p>Fee per consultation: PHP 850.00</p>
                             </div>
                         </div>
                         <div class="doctor-card">
                             <div class="doctor-image-placeholder">
                                 <img src="../images/doctor6.jpeg" alt="Photo of Dr. Sarah Willow">
                             </div>
                             <h3>Dr. Sarah Willow</h3>
                             <div class="doctor-details">
                                 <p>Experience: 14 years</p>
                                 <p>Specialty: Prosthodontist</p>
                                 <p>Fee per consultation: PHP 900.00</p>
                             </div>
                         </div>
                     </div>
                 </div>
             </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            console.log("Doctors page loaded.");
            // Any specific JS for this page would go here
        });
    </script>
    <?php
        // Close connection only if it was successfully opened and user is logged in (or if $conn is an object)
        if (isset($conn) && $conn instanceof mysqli) {
            if ($is_logged_in || !$is_logged_in) { // Simplified: close if $conn exists
                 $conn->close();
            }
        }
    ?>
</body>
</html>