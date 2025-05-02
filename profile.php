<?php
// profile.php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // If not logged in, redirect to the login page
    header("Location: login.php"); // Replace 'login.php' with your actual login page
    exit; // Stop further execution of the script
}

// If the user is logged in, you can access their information from the session
$firstName = $_SESSION['firstName'] ?? 'Guest'; // Use a default if not set
$lastName = $_SESSION['lastName'] ?? '';
$email = $_SESSION['email'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$address = $_SESSION['address'] ?? '';
$birthdate = $_SESSION['birthdate'] ?? '';
$gender = $_SESSION['gender'] ?? '';
$age = $_SESSION['age'] ?? '';

// TODO: Fetch Appointment Date from Database
// You will need to connect to your database and fetch the appointment date
// based on the user's ID ($_SESSION['user_id']).
// This is just a placeholder - replace with your actual database query.
$appointmentDate = "No Schedule"; // Default Value
$attendingDentist = "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Patient Profile</title>
  <link rel="stylesheet" href="css/profile.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

  <!-- Inject currentUser object into JavaScript -->
  <script>
      const currentUser = {
          isLoggedIn: <?php echo json_encode(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true); ?>,
          role: <?php echo json_encode($_SESSION['role'] ?? null); ?>,
          userId: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
          firstName: <?php echo json_encode($_SESSION['firstName'] ?? null); ?>
      };
  </script>

  <!-- Header -->
  <header>
    <div class="logo-area">
      <div class="logo-icon">🦷</div>
      <h1>Escosia Dental Clinic</h1>
    </div>
  </header>

  <!-- Navigation -->
  <nav>
    <ul>
      <li><a href="home.php" id="nav-home">Home</a></li>
      <li><a href="doctors.php" id="nav-doctors">Doctors</a></li>
      <li><a href="#" id="nav-notifications">Notifications</a></li>
      <li><a href="appointments.php" id="nav-appointments">Appointments</a></li>
      <li class="active"><a href="profile.php" id="nav-profile">Profile</a></li>
    </ul>
  </nav>

  <!-- Profile Page Layout -->
  <main class="profile-container">
    <div class="left-panel">
      <h2>Patient Profile</h2>

      <div class="info-box">
        <img src="images/jen_hernandez.png" alt="Patient Photo" class="profile-pic" />
        <div class="info-details">
          <div class="info-row top-row">
            <div class="patient-name">
              <div class="info-label">Patient Name</div>
              <div class="info-value bold"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></div>
            </div>
            <div class="gender">
              <div class="info-label">Gender</div>
              <div class="info-value"><?php echo htmlspecialchars($gender); ?></div>
            </div>
            <div class="age">
              <div class="info-label">Age</div>
              <div class="info-value"><?php echo htmlspecialchars($age); ?></div>
            </div>
          </div>
          <div class="info-row">
            <div class="appointment">
              <div class="info-label">Appointment Date</div>
              <div class="info-value bold"><?php echo htmlspecialchars($appointmentDate); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="personal-info">
        <div class="section-header">
          <strong>Personal Information</strong>
          <span class="edit-icon">✏️</span>
        </div>
        <div class="info-grid">
          <div><strong>First Name</strong><br><?php echo htmlspecialchars($firstName); ?></div>
          <div><strong>Last Name</strong><br><?php echo htmlspecialchars($lastName); ?></div>
          <div><strong>Email address</strong><br><?php echo htmlspecialchars($email); ?></div>
          <div><strong>Phone number</strong><br><?php echo htmlspecialchars($phone); ?></div>
          <div class="address" style="grid-column: span 2;">
            <strong>Home address</strong><br><?php echo htmlspecialchars($address); ?>
          </div>
          <div><strong>Date of Birth</strong><br><?php echo htmlspecialchars($birthdate); ?></div>
        </div>
      </div>
    </div>

    <div class="right-panel">
      <button class="book-btn">+ Book an Appointment</button>
      <h3>Appointment History</h3>
      <table class="history-table">
        <thead>
          <tr>
            <th>Attending Dentist</th>
            <th>Date and Time</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr><td><?php echo htmlspecialchars($attendingDentist); ?></td><td><?php echo htmlspecialchars($appointmentDate); ?></td><td></td>
          <!-- <tr><td>Dr. Rebecca Stone</td><td>11.27.2024 / 12:00 PM</td><td>SCHEDULED</td></tr>
          <tr><td>Dr. Valerie Wayne</td><td>10.12.2024</td><td>COMPLETED</td></tr>
          <tr><td>Dr. Kevin Larson</td><td>8.21.2024 / 9:00 AM</td><td>COMPLETED</td></tr>
          <tr><td>Dr. Jaime Lamister</td><td>5.01.2024 / 3:30 PM</td><td>COMPLETED</td></tr>
          <tr><td>Dr. Doves Seaworth</td><td>3.28.2024 / 2:00 PM</td><td>COMPLETED</td></tr> -->
        </tbody>
      </table>
    </div>
  </main>
  <!-- Link Correct JS File -->
  <script src="js/script.js"></script>
</body>
</html>