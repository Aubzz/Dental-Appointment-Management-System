<?php
// doctors.php
// Start the session at the very beginning to access user login status and details
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors - Escosia Dental Clinic</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Link CSS Files -->
    <link rel="stylesheet" href="css/base.css">      <!-- Base styles -->
    <link rel="stylesheet" href="css/doctors.css">   <!-- Doctors specific styles -->
    <link rel="stylesheet" href="css/modal.css">     <!-- Modal styles -->

    <!-- Favicon -->
    <link rel="icon" href="images/favicon.png">
</head>
<body>

    <!-- Inject currentUser object into JavaScript -->
    <!-- This object allows the script.js file to know user status and role -->
    <script>
        const currentUser = {
            isLoggedIn: <?php echo json_encode(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true); ?>,
            role: <?php echo json_encode($_SESSION['role'] ?? null); ?>,
            userId: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
            firstName: <?php echo json_encode($_SESSION['firstName'] ?? null); ?>
        };
    </script>

    <header>
        <div class="logo-area">
             <!-- Link the logo and clinic name back to the homepage -->
             <!-- Ensure 'home.php' is the correct filename for your homepage -->
            <a href="home.php" class="logo-link">
                <div class="logo-icon">🦷</div>
                <h1>Escosia Dental Clinic</h1>
            </a>
        </div>
    </header>

    <nav>
        <ul>
             <!-- Link to homepage -->
             <!-- Ensure 'home.php' is the correct filename -->
            <li><a href="home.php" id="nav-home">Home</a></li>
             <!-- This is the active page -->
            <li class="active"><a href="doctors.php" id="nav-doctors">Doctors</a></li>
             <!-- Update hrefs when pages are created -->
            <li><a href="#" id="nav-notifications">Notifications</a></li>
             <!-- Ensure 'appointments.php' is the correct filename if it exists -->
            <li><a href="appointments.php" id="nav-appointments">Appointments</a></li>
             <!-- Ensure 'profile.php' is the correct filename if it exists -->
            <li><a href="profile.php" id="nav-profile">Profile</a></li>
        </ul>
    </nav>

    <main>
        <section class="doctors-section">
            <h2>OUR DOCTORS</h2>
            <div class="doctors-grid">

                <!-- Doctor Card 1 -->
                <!-- Add a unique data-doctor-id from your database -->
                <div class="doctor-card" data-doctor-id="1">
                    <img src="images/doctor-ashford.png" alt="Dr. Heart D. Ashford" class="doctor-photo">
                    <h3>Dr. Heart D. Ashford</h3>
                    <p class="description">Dr. Heart Ashford is a dedicated and experienced general dentist committed to providing dental care. His practice emphasizes preventive care, restorative treatments, and patient education.</p>
                    <p class="experience">12 years of experience in general dentistry, treating patients of all ages.</p>
                    <p class="doctor-specialty">General Dentist</p>
                    <p class="doctor-schedule"><strong>Monday | Tuesday | Thursday</strong><br>10:00 AM - 7:00 PM</p>
                    <!-- Button also has the doctor ID for easier access in JS -->
                    <button class="btn btn-book" data-doctor-id="1">BOOK APPOINTMENT</button>
                </div>

                <!-- Doctor Card 2 -->
                <!-- Add a unique data-doctor-id from your database -->
                <div class="doctor-card" data-doctor-id="2">
                    <img src="images/doctor-monte.png" alt="Dr. Andrue A. Monte" class="doctor-photo">
                    <h3>Dr. Andrue A. Monte</h3>
                    <p class="description">Dr. Andrue Monte is a highly skilled orthodontist. He specializes in correcting misaligned teeth and jaws using techniques such as braces, clear aligners, and retainers.</p>
                    <p class="experience">Over 10 years of experience in orthodontics, treating patients of all ages.</p>
                    <p class="doctor-specialty">Orthodontist</p>
                    <p class="doctor-schedule"><strong>Wednesday | Friday | Saturday</strong><br>10:00 AM - 7:00 PM</p>
                    <button class="btn btn-book" data-doctor-id="2">BOOK APPOINTMENT</button>
                </div>

                <!-- Doctor Card 3 -->
                <!-- Add a unique data-doctor-id from your database -->
                <div class="doctor-card" data-doctor-id="3">
                     <img src="images/doctor-khan.png" alt="Dr. Aubrey R. Khan" class="doctor-photo">
                    <h3>Dr. Aubrey R. Khan</h3>
                    <p class="description">Dr. Aubrey Khan is an experienced endodontist. His expertise in root canal therapy, retreatments, and procedures aimed at saving teeth.</p>
                    <p class="experience">14 years of experience in endodontics, with a focus on complex cases and patient-centered care.</p>
                    <p class="doctor-specialty">Endodontist</p>
                    <p class="doctor-schedule"><strong>Tuesday | Thursday | Saturday</strong><br>10:00 AM - 7:00 PM</p>
                    <button class="btn btn-book" data-doctor-id="3">BOOK APPOINTMENT</button>
                </div>

            </div> <!-- end doctors-grid -->
        </section>

         <!-- MODAL PLACEHOLDERS (Hidden by default via CSS) -->

         <!-- Staff Booking Modal -->
         <!-- This modal is shown if a receptionist/admin clicks book -->
        <div id="staffBookingModal" class="modal">
            <div class="modal-content">
                 <!-- Close button -->
                 <span class="close-button" onclick="closeModal('staffBookingModal')">×</span>
                 <h2>Book Appointment (Staff)</h2>
                 <!-- TODO: Implement Staff Booking Form -->
                 <!-- Needs elements for: -->
                 <!-- 1. Searching/Selecting Existing Patient OR Entering New Patient Details -->
                 <!-- 2. Selecting Date/Time Slot -->
                 <!-- 3. Confirming Doctor (might be pre-filled) -->
                 <p style="text-align:center; padding: 30px; color: grey;">Staff booking form interface to be built here.</p>
                 <button type="button" onclick="submitStaffBooking()">Save Appointment</button> <!-- Changed to type="button" -->
            </div>
        </div>

         <!-- Patient Booking Modal (Simplified) -->
         <!-- This modal is shown if a patient clicks book -->
         <div id="patientBookingModal" class="modal">
            <div class="modal-content">
                 <!-- Close button -->
                 <span class="close-button" onclick="closeModal('patientBookingModal')">×</span>
                 <h2>Book Appointment</h2>
                 <!-- Display who the booking is for and with whom -->
                 <p>Booking for: <strong id="patientBookingName">[Your Name]</strong></p>
                 <p style="margin-bottom: 20px;">With: <strong id="patientBookingDoctor">[Doctor Name]</strong></p>
                 <!-- Hidden input to store the doctor ID for submission -->
                 <input type="hidden" id="patientBookingDoctorId" value="">

                 <!-- Form Elements for Date/Time -->
                  <div>
                     <label for="appointmentDate">Select Date:</label>
                     <input type="date" id="appointmentDate" name="appointmentDate" required>
                 </div>
                 <div>
                     <label for="appointmentTime">Select Available Time Slot:</label>
                     <select id="appointmentTime" name="appointmentTime" required>
                         <option value="">Select a time...</option>
                         <!-- TODO: This dropdown should be populated dynamically -->
                         <!-- based on selected date and doctor availability -->
                         <!-- Example static options: -->
                         <option value="09:00">9:00 AM</option>
                         <option value="09:30">9:30 AM</option>
                         <option value="10:00">10:00 AM</option>
                         <option value="10:30">10:30 AM</option>
                         <option value="11:00">11:00 AM</option>
                         <!-- ... more slots ... -->
                         <option value="14:00">2:00 PM</option>
                         <option value="14:30">2:30 PM</option>
                         <!-- etc. -->
                     </select>
                 </div>
                 <!-- Confirmation button triggers JS function -->
                 <button type="button" onclick="submitPatientBooking()">Confirm Booking</button> <!-- Changed to type="button" -->
            </div>
        </div>
        <!-- End Modals -->

    </main>

     <!-- Link Correct JS File -->
    <script src="js/script.js"></script>

</body>
</html>