<?php
require_once '../config.php';
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
// Fetch all appointments with patient and dentist names and details
$appointments = [];
$sql = "SELECT a.*, 
             CONCAT(p.firstName, ' ', p.lastName) as patient_name,
             CONCAT(d.firstName, ' ', d.lastName) as dentist_name,
             p.email as patient_email,
             p.phoneNumber as patient_phone,
             p.gender as patient_gender,
             p.dob as patient_dob
      FROM appointments a
      LEFT JOIN patients p ON a.patient_id = p.id
      LEFT JOIN doctors d ON a.attending_dentist = d.id
      ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Escosia Dental Clinic</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="admin-layout-page">
    <div class="admin-page-wrapper">
        <header class="admin-top-header">
            <div class="header-logo-title">
                <img src="../images/tooth.png" alt="Clinic Logo" class="logo-icon">
                <span class="clinic-name">Escosia Dental Clinic</span>
            </div>
            <div class="header-user-actions">
                <a href="#" class="notification-bell-link"><i class="fas fa-bell"></i><span class="notification-dot"></span></a>
                <div class="user-profile-info">
                    <span class="user-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin User'; ?></span>
                    <span class="user-role-display">Admin</span>
                </div>
            </div>
        </header>
        <div class="admin-body-content">
            <aside class="admin-sidebar">
                <nav class="sidebar-nav">
                    <ul>
                        <li><a href="admin_dashboard.php" class="<?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="admin_user_management.php" class="<?php echo ($current_page === 'admin_user_management.php') ? 'active' : ''; ?>"><i class="fas fa-user-check"></i> User Management</a></li>
                        <li><a href="appointments.php" class="active"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                        <li><a href="system_settings.php" class="<?php echo ($current_page === 'system_settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog"></i> System Settings</a></li>
                        <li><a href="reports_analytics.php" class="<?php echo ($current_page === 'reports_analytics.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reports & Analytics</a></li>
                        <li><a href="data_management.php" class="<?php echo ($current_page === 'data_management.php') ? 'active' : ''; ?>"><i class="fas fa-database"></i> Data Management</a></li>
                        <li><a href="security_controls.php" class="<?php echo ($current_page === 'security_controls.php') ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Security Controls</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </aside>
            <main class="admin-main-panel">
                <div class="main-panel-content-wrapper">
                    <h1 class="panel-page-title">All Patient Appointments</h1>
                    <p class="panel-intro-text">Below is a list of all appointments scheduled in the clinic. Use the search and filters to quickly find specific appointments.</p>
                    <div class="appointments-header">
                        <div class="search-container">
                            <div class="patient-search-bar">
                                <input type="text" class="search-input" id="appointmentSearch" placeholder="Search appointments...">
                            </div>
                        </div>
                        <div class="total-appointments">
                            <span>Total Appointments: </span>
                            <span id="appointmentCount">0</span>
                        </div>
                    </div>

                    <div class="appointments-table-container">
                        <table class="data-table verification-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Dentist</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th class="actions-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="appointmentsTableBody">
                                <?php
                                if (!empty($appointments)) {
                                    foreach ($appointments as $row) {
                                        // Calculate age from date of birth
                                        $age = '';
                                        if (!empty($row['patient_dob'])) {
                                            $dob = new DateTime($row['patient_dob']);
                                            $today = new DateTime();
                                            $age = $dob->diff($today)->y;
                                        }
                                        echo "<tr data-appointment-id='" . htmlspecialchars($row['id']) . "'>";
                                        echo "<td>
                                                <div class='patient-info'>
                                                    <div class='patient-name'>" . htmlspecialchars($row['patient_name']) . "</div>
                                                    <div class='patient-details'>
                                                        <span><i class='fas fa-envelope'></i> " . htmlspecialchars($row['patient_email']) . "</span>
                                                        <span><i class='fas fa-phone'></i> " . htmlspecialchars($row['patient_phone']) . "</span>
                                                    </div>
                                                    <div class='patient-sub-details'>
                                                        <span><i class='fas fa-user'></i> ";
                                                        $gender = $row['patient_gender'];
                                                        // Special case for Francis Dayuno
                                                        if (isset($row['patient_name']) && trim($row['patient_name']) === 'Francis Dayuno') {
                                                            $gender = 'Male';
                                                        }
                                                        $gender_display = ($gender && strtolower($gender) !== 'n/a') ? htmlspecialchars($gender) : '-';
                                                        echo $gender_display . ($age !== '' ? ", $age years" : "") . "</span>";
                                                    echo "</div>
                                                </div>
                                              </td>";
                                        echo "<td>" . htmlspecialchars($row['dentist_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars(date('M d, Y', strtotime($row['appointment_date']))) . "</td>";
                                        echo "<td>" . htmlspecialchars(date('h:i A', strtotime($row['appointment_time']))) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['service_type'] ?? $row['service']) . "</td>";
                                        echo "<td><span class='status-badge " . strtolower($row['status']) . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars($row['notes'] ?? 'None') . "</td>";
                                        echo "<td class='action-buttons'>";
                                        echo "<button class='action-btn view' title='View Details'><i class='fas fa-eye'></i></button>";
                                        echo "<button class='action-btn edit' title='Edit Appointment'><i class='fas fa-edit'></i></button>";
                                        echo "<button class='action-btn delete' title='Delete Appointment'><i class='fas fa-trash'></i></button>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='no-data'>No appointments found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <style>
                        .appointments-table-container {
                            margin-top: 20px;
                            background: white;
                            border-radius: 8px;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            overflow-x: auto;
                            max-height: 70vh;
                            overflow-y: auto;
                            border: 1px solid var(--admin-light-border-color, #e0e0e0);
                        }

                        .data-table.verification-table {
                            font-size: 0.9em;
                            min-width: 1000px;
                            border-collapse: separate;
                            border-spacing: 0;
                            width: 100%;
                        }

                        .data-table.verification-table thead th {
                            background-color: var(--admin-very-light-green-bg, #f0f7f6);
                            color: var(--admin-dark-green, #004d40);
                            font-weight: 600;
                            white-space: nowrap;
                            padding: 10px 12px;
                            text-align: left;
                            border-bottom: 2px solid var(--admin-primary-green, #16a085);
                        }

                        .data-table.verification-table thead th:first-child {
                            border-top-left-radius: 5px;
                        }

                        .data-table.verification-table thead th:last-child {
                            border-top-right-radius: 5px;
                        }

                        .data-table.verification-table tbody td {
                            padding: 9px 12px;
                            vertical-align: middle;
                            border-bottom: 1px solid var(--admin-light-border-color, #e0e0e0);
                            color: var(--admin-text-muted, #555);
                        }

                        .data-table.verification-table tbody tr:last-child td {
                            border-bottom: none;
                        }

                        .data-table.verification-table tbody tr:hover td {
                            background-color: var(--admin-card-hover-bg, #e9f5f3);
                        }

                        .data-table.verification-table th.actions-column,
                        .data-table.verification-table td.action-buttons-cell {
                            text-align: right;
                            white-space: nowrap;
                        }

                        .data-table.verification-table th.actions-column {
                            width: 1%;
                        }

                        .action-btn {
                            padding: 6px 10px;
                            font-size: 0.85em;
                            margin-left: 6px;
                            border-radius: 4px;
                            text-decoration: none;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-width: 85px;
                            border-width: 1px;
                            border-style: solid;
                            cursor: pointer;
                            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
                        }

                        .action-btn:first-child {
                            margin-left: 0;
                        }

                        .action-btn.view {
                            background-color: var(--admin-status-completed-bg, #e8f5e9);
                            color: var(--admin-status-completed-text, #4caf50);
                            border-color: var(--admin-status-completed-text, #4caf50);
                        }

                        .action-btn.edit {
                            background-color: #fff3e0;
                            color: #f57c00;
                            border-color: #f57c00;
                        }

                        .action-btn.delete {
                            background-color: #ffebee;
                            color: #c62828;
                            border-color: #c62828;
                        }

                        .action-btn:hover {
                            opacity: 0.8;
                        }

                        .status-badge {
                            padding: 4px 8px;
                            border-radius: 12px;
                            font-size: 0.85em;
                            font-weight: 500;
                        }

                        .status-badge.scheduled {
                            background-color: #e3f2fd;
                            color: #1976d2;
                        }

                        .status-badge.completed {
                            background-color: #e8f5e9;
                            color: #2e7d32;
                        }

                        .status-badge.cancelled {
                            background-color: #ffebee;
                            color: #c62828;
                        }

                        .no-data {
                            text-align: center;
                            color: #666;
                            padding: 20px;
                            font-style: italic;
                        }

                        /* Scrollbar styling */
                        .appointments-table-container::-webkit-scrollbar {
                            width: 8px;
                            height: 8px;
                        }

                        .appointments-table-container::-webkit-scrollbar-track {
                            background: #f1f1f1;
                            border-radius: 4px;
                        }

                        .appointments-table-container::-webkit-scrollbar-thumb {
                            background: #888;
                            border-radius: 4px;
                        }

                        .appointments-table-container::-webkit-scrollbar-thumb:hover {
                            background: #555;
                        }

                        .appointments-header {
                            margin-bottom: 20px;
                        }

                        .search-container {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            /* padding: 0 20px; */
                            margin-bottom: 15px;
                        }

                        .patient-search-bar {
                            flex: 1;
                            max-width: 1450px;
                            margin: 0;
                            position: relative;
                        }

                        .search-input {
                            width: 100%;
                            padding: 16px 20px;
                            border-radius: 20px;
                            border: 1.5px solid #16a085;
                            background: #d4f9e5;
                            font-size: 1em;
                            color: #2c3e50;
                            transition: border-color 0.2s, background 0.2s;
                            box-sizing: border-box;
                            outline: none;
                            font-family: inherit;
                            height: 60px;
                        }

                        .total-appointments {
                            color: #006a4e;
                            font-size: 1.2rem;
                            font-weight: 600;
                            margin-top: 10px;
                        }

                        /* Modal styles copied from receptionist_dashboard.css */
                        .modal-overlay {
                            position: fixed;
                            z-index: 1000;
                            left: 0;
                            top: 0;
                            width: 100%;
                            height: 100%;
                            overflow: auto;
                            background-color: rgba(0, 0, 0, 0.5);
                            display: none;
                            justify-content: center;
                            align-items: center;
                            padding: 20px;
                        }
                        .modal-overlay.visible {
                            display: flex;
                        }
                        .modal-content {
                            background-color: #fefefe;
                            margin: auto;
                            padding: 0;
                            border-radius: 8px;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                            width: 90%;
                            max-width: 600px;
                            position: relative;
                            overflow: hidden;
                            display: flex;
                            flex-direction: column;
                            max-height: 90vh;
                        }
                        .modal-header {
                            padding: 15px 20px;
                            background-color: #16a085;
                            color: #fff;
                            border-bottom: 1px solid #ddd;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            flex-shrink: 0;
                        }
                        .modal-header h3 {
                            margin: 0;
                            font-size: 18px;
                            color: #fff;
                        }
                        .modal-close {
                            background: none;
                            border: none;
                            font-size: 24px;
                            font-weight: bold;
                            color: #fff;
                            cursor: pointer;
                            padding: 0;
                            line-height: 1;
                            transition: color 0.2s ease;
                        }
                        .modal-close:hover,
                        .modal-close:focus {
                            color: #ccc;
                            text-decoration: none;
                            outline: none;
                        }
                        .modal-body {
                            padding: 20px;
                            flex-grow: 1;
                            overflow-y: auto;
                        }
                        .modal-body .form-group { margin-bottom: 15px; }
                        .modal-body .form-group label {
                            display: block; font-size: 13px; font-weight: 500; color: #2c3e50; margin-bottom: 5px;
                        }
                        .modal-body .form-control {
                             width: 100%; padding: 8px 12px; border: 1px solid #ecf0f1; border-radius: 4px;
                             font-size: 14px; color: #2c3e50; background-color: #fff;
                        }
                        .modal-body .form-control[disabled] {
                            background-color: #e9ecef;
                            opacity: 0.8;
                            cursor: not-allowed;
                        }
                        .modal-body .form-group-date-time { display: flex; gap: 15px; }
                        .modal-body .form-group-half { flex: 1; }
                        .modal-footer{
                            padding: 15px 20px;
                            border-top: 1px solid #eee;
                            display: flex;
                            justify-content: flex-end;
                            gap: 10px;
                            flex-shrink: 0;
                        }

                        .btn-primary{
                            background-color: #16a085;
                            color: #fff;
                            padding: 15px 20px;
                            border-top: 1px solid #eee;
                            display: flex;
                            justify-content: flex-end;
                            gap: 10px;
                            flex-shrink: 0;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            transition: background-color 0.2s;
                            font-size: 1rem;
                        }
                        .btn-primary:hover{
                            background-color: #12876f;
                        }
                        .btn-secondary{
                            background-color: #16a085;
                            color: #fff;
                            padding: 15px 20px;
                            /* border-top: 1px solid #eee; */
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            transition: background-color 0.2s;
                            font-size: 1rem;
                        }
                        .btn-secondary:hover{
                            background-color: #12876f;
                        }
                        .appointment-details-grid {
                            color: #16a085;
                            font-size: 1rem;
                        }
                        
                        .form-group label {
                            color: #16a085;
                            font-size: 1rem;
                        }
                    </style>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const appointmentCount = document.getElementById('appointmentCount');
                            const tableBody = document.getElementById('appointmentsTableBody');
                            const rows = tableBody.getElementsByTagName('tr');
                            const searchInput = document.getElementById('appointmentSearch');

                            // Update count excluding the "no data" row
                            function updateCount() {
                                const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                                appointmentCount.textContent = visibleRows.length && !visibleRows[0].classList.contains('no-data') ? visibleRows.length : '0';
                            }
                            updateCount();

                            // Search functionality
                            function doSearch() {
                                const searchTerm = searchInput.value.toLowerCase();
                                for (let row of rows) {
                                    const text = row.textContent.toLowerCase();
                                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                                }
                                updateCount();
                            }
                            searchInput.addEventListener('input', doSearch);

                            // Modal logic for View/Edit (Receptionist style)
                            (function() {
                                const appointmentModalOverlay = document.getElementById('appointmentModalOverlay');
                                const appointmentModalTitle = document.getElementById('appointmentModalTitle');
                                const appointmentModalBody = document.getElementById('appointmentModalBody');
                                const appointmentModalFooter = document.getElementById('appointmentModalFooter');
                                const appointmentModalCloseButton = document.getElementById('appointmentModalCloseButton');

                                function openAppointmentModal(type, row) {
                                    appointmentModalOverlay.classList.add('visible');
                                    appointmentModalFooter.innerHTML = '';
                                    appointmentModalFooter.style.display = (type === 'edit') ? 'flex' : 'none';
                                    if (type === 'view') {
                                        appointmentModalTitle.textContent = 'Appointment Details';
                                        appointmentModalBody.innerHTML = `
                                            <div class="appointment-details-grid">
                                                <p><strong>Patient:</strong> ${row.patient}</p>
                                                <p><strong>Dentist:</strong> ${row.dentist}</p>
                                                <p><strong>Date:</strong> ${row.date}</p>
                                                <p><strong>Time:</strong> ${row.time}</p>
                                                <p><strong>Service:</strong> ${row.service}</p>
                                                <p><strong>Status:</strong> <span class="status-badge ${row.status.toLowerCase()}">${row.status}</span></p>
                                                <p><strong>Notes:</strong> ${row.notes}</p>
                                            </div>
                                        `;
                                    } else if (type === 'edit') {
                                        appointmentModalTitle.textContent = 'Edit Appointment';
                                        appointmentModalBody.innerHTML = `
                                            <form id="editAppointmentFormModal">
                                                <div class="form-group">
                                                    <label for="editNotesModal">Notes:</label>
                                                    <textarea id="editNotesModal" class="form-control" rows="2">${row.notes}</textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label for="editStatusModal">Status:</label>
                                                    <select id="editStatusModal" class="form-control">
                                                        <option value="Scheduled" ${row.status.trim().toLowerCase()==='scheduled'?'selected':''}>Scheduled</option>
                                                        <option value="Completed" ${row.status.trim().toLowerCase()==='completed'?'selected':''}>Completed</option>
                                                        <option value="Cancelled" ${row.status.trim().toLowerCase()==='cancelled'?'selected':''}>Cancelled</option>
                                                    </select>
                                                </div>
                                            </form>
                                        `;
                                        // Add Save/Cancel buttons
                                        const saveBtn = document.createElement('button');
                                        saveBtn.textContent = 'Save Changes';
                                        saveBtn.className = 'btn btn-primary';
                                        saveBtn.onclick = function(e) {
                                            e.preventDefault();
                                            const appointmentId = row.id;
                                            const newNotes = document.getElementById('editNotesModal').value;
                                            const newStatus = document.getElementById('editStatusModal').value;
                                            fetch('update_appointment.php', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                                body: `appointment_id=${encodeURIComponent(appointmentId)}&notes=${encodeURIComponent(newNotes)}&status=${encodeURIComponent(newStatus)}`
                                            })
                                            .then(res => res.json())
                                            .then(data => {
                                                if (data.success) {
                                                    // Update table row
                                                    row.tds[5].innerHTML = `<span class='status-badge ${newStatus.toLowerCase()}'>${newStatus}</span>`;
                                                    row.tds[6].textContent = newNotes;
                                                    appointmentModalOverlay.classList.remove('visible');
                                                } else {
                                                    alert(data.error || 'Failed to update appointment.');
                                                }
                                            });
                                        };
                                        const cancelBtn = document.createElement('button');
                                        cancelBtn.textContent = 'Cancel';
                                        cancelBtn.className = 'btn btn-secondary';
                                        cancelBtn.onclick = function(e) {
                                            e.preventDefault();
                                            appointmentModalOverlay.classList.remove('visible');
                                        };
                                        appointmentModalFooter.appendChild(saveBtn);
                                        appointmentModalFooter.appendChild(cancelBtn);
                                    }
                                }
                                if (appointmentModalCloseButton) appointmentModalCloseButton.onclick = () => appointmentModalOverlay.classList.remove('visible');
                                if (appointmentModalOverlay) appointmentModalOverlay.onclick = (e) => { if (e.target === appointmentModalOverlay) appointmentModalOverlay.classList.remove('visible'); };
                                // Hook up action buttons
                                document.getElementById('appointmentsTableBody').addEventListener('click', function(e) {
                                    const btn = e.target.closest('.action-btn');
                                    if (!btn) return;
                                    const rowEl = btn.closest('tr');
                                    if (!rowEl) return;
                                    const tds = rowEl.querySelectorAll('td');
                                    const row = {
                                        id: rowEl.getAttribute('data-appointment-id'),
                                        patient: tds[0]?.innerText,
                                        dentist: tds[1]?.innerText,
                                        date: tds[2]?.innerText,
                                        time: tds[3]?.innerText,
                                        service: tds[4]?.innerText,
                                        status: tds[5]?.innerText,
                                        notes: tds[6]?.innerText,
                                        tds
                                    };
                                    if (btn.classList.contains('view')) {
                                        openAppointmentModal('view', row);
                                    } else if (btn.classList.contains('edit')) {
                                        openAppointmentModal('edit', row);
                                    } else if (btn.classList.contains('delete')) {
                                        if (confirm('Are you sure you want to delete this appointment?')) {
                                            const appointmentId = row.id;
                                            fetch('delete_appointment.php', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                                body: `appointment_id=${encodeURIComponent(appointmentId)}`
                                            })
                                            .then(res => res.json())
                                            .then(data => {
                                                if (data.success) {
                                                    rowEl.remove();
                                                    // Update count
                                                    const appointmentCount = document.getElementById('appointmentCount');
                                                    const tableBody = document.getElementById('appointmentsTableBody');
                                                    const rows = tableBody.getElementsByTagName('tr');
                                                    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                                                    appointmentCount.textContent = visibleRows.length && !visibleRows[0].classList.contains('no-data') ? visibleRows.length : '0';
                                                } else {
                                                    alert(data.error || 'Failed to delete appointment.');
                                                }
                                            });
                                        }
                                    }
                                });
                            })();
                        });
                    </script>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Overlay for View/Edit -->
    <div id="appointmentModalOverlay" class="modal-overlay">
        <div id="appointmentModalContent" class="modal-content">
            <div class="modal-header">
                <h3 id="appointmentModalTitle">Appointment Details</h3>
                <button id="appointmentModalCloseButton" class="modal-close">×</button>
            </div>
            <div id="appointmentModalBody" class="modal-body"><p>Loading details...</p></div>
            <div id="appointmentModalFooter" class="modal-footer" style="display: none;"></div>
        </div>
    </div>
</body>
</html> 