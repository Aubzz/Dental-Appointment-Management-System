// receptionist_appointment.js
// Assumes `availableDentists` is globally available from a <script> tag in the PHP file.

document.addEventListener('DOMContentLoaded', () => {

    function updateTableVisibility(tableId, noDataRowSelector) {
        const tableBody = document.querySelector(`${tableId} tbody`);
        if (!tableBody) return;
        const noDataRow = tableBody.querySelector(noDataRowSelector);
        if (!noDataRow) return;
        const dataRows = tableBody.querySelectorAll('tr:not(.no-data-row)');
        let hasVisibleRows = Array.from(dataRows).some(row => row.style.display !== 'none');
        noDataRow.style.display = hasVisibleRows ? 'none' : 'table-row';
    }

    const dentistFilter = document.getElementById('dentist-filter');
    const appointmentsTableBody = document.querySelector('#all-appointments-table tbody');

    function applyDentistFilter() {
        if (!dentistFilter || !appointmentsTableBody) return;
        const selectedDentistValue = dentistFilter.value;
        const rows = appointmentsTableBody.querySelectorAll('tr:not(.no-data-row)');
        rows.forEach(row => {
            const dentistCell = row.cells[3];
            if (dentistCell) {
                const rowDentist = dentistCell.textContent.trim();
                const isPendingUnassigned = row.cells[5].textContent.trim().toUpperCase() === 'PENDING' && rowDentist === '-- Not Assigned Yet --';
                row.style.display = (selectedDentistValue === 'all' || rowDentist === selectedDentistValue || (selectedDentistValue === 'all' && isPendingUnassigned)) ? '' : 'none';
            }
        });
        updateTableVisibility('#all-appointments-table', '.no-data-row');
    }

    if (dentistFilter) dentistFilter.addEventListener('change', applyDentistFilter);

    const appointmentModalOverlay = document.getElementById('appointmentModalOverlay');
    const appointmentModalTitle = document.getElementById('appointmentModalTitle');
    const appointmentModalBody = document.getElementById('appointmentModalBody');
    const appointmentModalFooter = document.getElementById('appointmentModalFooter');
    const appointmentModalCloseButton = document.getElementById('appointmentModalCloseButton');

    function addModalFooterButton(text, classes, id) {
        if (!appointmentModalFooter) return null;
        const button = document.createElement('button');
        button.textContent = text;
        button.className = 'btn ' + classes;
        if (id) button.id = id;
        appointmentModalFooter.appendChild(button);
        return button;
    }

    function openAppointmentModal(type, appointmentId, rowData = {}) { // Default rowData to empty object
        if (!appointmentModalOverlay || !appointmentModalTitle || !appointmentModalBody || !appointmentModalFooter) return;
        
        appointmentModalOverlay.classList.add('visible');
        appointmentModalBody.innerHTML = '<p>Loading...</p>';
        appointmentModalFooter.innerHTML = '';
        appointmentModalFooter.style.display = 'flex'; // Show footer for forms

        if (type === 'view') {
            appointmentModalTitle.textContent = 'Appointment Details';
            appointmentModalFooter.style.display = 'none';
            displayAppointmentDetails(appointmentId, rowData);
        } else if (type === 'edit') {
            appointmentModalTitle.textContent = 'Edit Appointment';
            displayEditAppointmentForm(appointmentId, rowData);
            addModalFooterButton('Save Changes', 'btn-primary', 'appointmentSaveChangesButton');
            addModalFooterButton('Cancel', 'btn-secondary', 'appointmentCancelEditButton');
        } else if (type === 'assign') {
            appointmentModalTitle.textContent = 'Assign Dentist';
            displayAssignDentistForm(appointmentId, rowData);
            addModalFooterButton('Assign Dentist', 'btn-primary', 'confirmAssignButton');
            addModalFooterButton('Cancel', 'btn-secondary', 'cancelAssignButton');
        } else {
            appointmentModalTitle.textContent = 'Error';
            appointmentModalBody.innerHTML = '<p>Invalid modal type requested.</p>';
            appointmentModalFooter.style.display = 'none';
        }
        // No complex form listeners like AM/PM buttons in these modal forms current design
    }

    function closeAppointmentModal() {
        if (appointmentModalOverlay) appointmentModalOverlay.classList.remove('visible');
    }

    function displayAppointmentDetails(appointmentId, data) {
        if (!appointmentModalBody) return;
        const patientName = data.patientFullName || `${data.patientFirstName} ${data.patientLastName}`;
        const dentistName = data.doctorFullName || (data.doctorFirstName ? `Dr. ${data.doctorFirstName} ${data.doctorLastName}` : '-- Not Assigned Yet --');
        const timeDisplay = data.appointmentTimeFormatted || (data.appointmentTime ? new Date(`1970-01-01T${data.appointmentTime}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }) : 'N/A');
        const dateDisplay = data.appointmentDateFormatted || (data.appointmentDate ? new Date(data.appointmentDate + 'T00:00:00').toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric'}) : 'N/A');

        appointmentModalBody.innerHTML = `
            <div class="appointment-details-grid">
                <p><strong>Appointment ID:</strong> ${appointmentId}</p>
                <p><strong>Patient:</strong> ${patientName}</p>
                <p><strong>Date:</strong> ${dateDisplay}</p>
                <p><strong>Time:</strong> ${timeDisplay}</p>
                <p><strong>Dentist:</strong> ${dentistName}</p>
                <p><strong>Service:</strong> ${data.serviceType || 'N/A'}</p>
                <p><strong>Notes:</strong> ${data.notes || 'None'}</p>
                <p><strong>Status:</strong> <span class="status-cell status-${(data.status || '').toLowerCase()}">${data.status || 'N/A'}</span></p>
            </div>
        `;
    }

    function generateDentistOptions(selectedDoctorId = null) {
        let optionsHtml = '<option value="">-- Select Dentist --</option>';
        if (typeof availableDentists !== 'undefined' && Array.isArray(availableDentists)) {
            availableDentists.forEach(dentist => {
                optionsHtml += `<option value="${dentist.id}" ${selectedDoctorId == dentist.id ? 'selected' : ''}>Dr. ${dentist.firstName} ${dentist.lastName}</option>`;
            });
        }
        if (selectedDoctorId === null || selectedDoctorId === '') { // Add "Not Assigned" if no doctor is currently selected or if it's an option
             optionsHtml += `<option value="" ${selectedDoctorId === null || selectedDoctorId === '' ? 'selected' : ''}>-- Not Assigned Yet --</option>`;
        }
        return optionsHtml;
    }
    
    function displayEditAppointmentForm(appointmentId, data) {
        if (!appointmentModalBody) return;
        const patientName = data.patientFullName || `${data.patientFirstName} ${data.patientLastName}`;
        const time24h = data.appointmentTime ? data.appointmentTime.substring(0, 5) : '09:00'; // Expects HH:MM:SS from data-*, take HH:MM
        const dentistsOptionsHtml = generateDentistOptions(data.doctorId);

        appointmentModalBody.innerHTML = `
            <form id="editAppointmentFormModal" action="process_edit_appointment.php" method="POST">
                <input type="hidden" name="appointment_id" value="${appointmentId}">
                <div class="form-group">
                    <label for="editPatientNameModal">Patient Name:</label>
                    <input type="text" id="editPatientNameModal" class="form-control" value="${patientName}" disabled>
                    <input type="hidden" name="patient_id" value="${data.patientId}">
                </div>
                <div class="form-group">
                    <label for="editAttendingDentistModal">Assign Dentist:</label>
                    <select id="editAttendingDentistModal" name="attending_dentist_id" class="form-control" required>
                        ${dentistsOptionsHtml}
                    </select>
                </div>
                <div class="form-group form-group-date-time">
                    <div class="form-group-half">
                        <label for="editAppointmentDateModal">Date:</label>
                        <input type="date" id="editAppointmentDateModal" name="appointment_date" class="form-control" value="${data.appointmentDate || ''}" required>
                    </div>
                    <div class="form-group-half">
                        <label for="editAppointmentTimeModal">Time (24-hour):</label>
                        <input type="time" id="editAppointmentTimeModal" name="appointment_time" class="form-control" value="${time24h}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="editServiceTypeModal">Service Type:</label>
                    <input type="text" id="editServiceTypeModal" name="service_type" class="form-control" value="${data.serviceType || ''}" required>
                </div>
                <div class="form-group">
                    <label for="editNotesModal">Notes (Optional):</label>
                    <textarea id="editNotesModal" name="notes" class="form-control" rows="2">${data.notes || ''}</textarea>
                </div>
                <div class="form-group">
                    <label for="editStatusModal">Status:</label>
                    <select id="editStatusModal" name="status" class="form-control" required>
                        <option value="SCHEDULED" ${data.status?.toUpperCase() === 'SCHEDULED' ? 'selected' : ''}>Scheduled</option>
                        <option value="CONFIRMED" ${data.status?.toUpperCase() === 'CONFIRMED' ? 'selected' : ''}>Confirmed</option>
                        <option value="PENDING" ${data.status?.toUpperCase() === 'PENDING' ? 'selected' : ''}>Pending</option>
                        <option value="CANCELLED" ${data.status?.toUpperCase() === 'CANCELLED' ? 'selected' : ''}>Cancelled</option>
                        <option value="COMPLETED" ${data.status?.toUpperCase() === 'COMPLETED' ? 'selected' : ''}>Completed</option>
                        <option value="NO SHOW" ${data.status?.toUpperCase() === 'NO SHOW' ? 'selected' : ''}>No Show</option>
                    </select>
                </div>
            </form>
        `;
    }

    function displayAssignDentistForm(appointmentId, data) {
        if (!appointmentModalBody) return;
        const patientName = data.patientFullName || `${data.patientFirstName} ${data.patientLastName}`;
        const dentistsOptionsHtml = generateDentistOptions(data.doctorId);

        appointmentModalBody.innerHTML = `
            <form id="assignDentistFormModal" action="process_assign_dentist.php" method="POST">
                <input type="hidden" name="patient_id" value="${data.patientId || ''}">
                <input type="hidden" name="doctor_id" value="">
                <input type="hidden" name="appointment_date" value="${data.appointmentDate || ''}">
                <input type="hidden" name="appointment_time" value="${data.appointmentTime || ''}">
                <input type="hidden" name="service_type" value="${data.serviceType || ''}">
                <p>Assign a dentist to the appointment for <strong>${patientName}</strong> (Service: ${data.serviceType || 'N/A'}).</p>
                <div class="form-group">
                    <label for="assignDentistSelectModal">Select Dentist:</label>
                    <select id="assignDentistSelectModal" name="doctor_id" class="form-control" required>
                        ${dentistsOptionsHtml}
                    </select>
                </div>
            </form>
        `;
    }

    if (appointmentModalCloseButton) appointmentModalCloseButton.addEventListener('click', closeAppointmentModal);
    if (appointmentModalOverlay) {
        appointmentModalOverlay.addEventListener('click', (event) => {
            if (event.target === appointmentModalOverlay) closeAppointmentModal();
        });
    }

    if (appointmentModalFooter) {
        appointmentModalFooter.addEventListener('click', (event) => {
            const target = event.target;
            let form;
            if (target.id === 'appointmentSaveChangesButton') {
                form = appointmentModalBody.querySelector('#editAppointmentFormModal');
                if (form) {
                    if (!form.checkValidity()) { form.reportValidity(); return; }
                    form.submit(); // Standard POST for now
                }
            } else if (target.id === 'appointmentCancelEditButton') {
                closeAppointmentModal();
            } else if (target.id === 'confirmAssignButton') {
                form = appointmentModalBody.querySelector('#assignDentistFormModal');
                if (form) {
                    if (!form.checkValidity()) { form.reportValidity(); return; }
                    form.submit(); // Standard POST for now
                }
            } else if (target.id === 'cancelAssignButton') {
                closeAppointmentModal();
            }
        });
    }

    const appointmentsTable = document.getElementById('all-appointments-table');
    if (appointmentsTable) {
        appointmentsTable.addEventListener('click', (event) => {
            const targetLink = event.target.closest('a.btn-action'); // Target any button with .btn-action
            if (!targetLink) return;

            event.preventDefault();
            const row = targetLink.closest('tr');
            const appointmentId = row ? row.dataset.appointmentId : null;
            if (!appointmentId) return;

            // Extract all data attributes from the row
            const rowData = { ...row.dataset }; // Spread dataset into a new object
            // Convert text from cells for display if data attributes are not comprehensive
            rowData.patientFullName = row.cells[0]?.textContent.trim() || '';
            rowData.appointmentDateFormatted = row.cells[1]?.textContent.trim() || '';
            rowData.appointmentTimeFormatted = row.cells[2]?.textContent.trim() || '';
            rowData.doctorFullName = row.cells[3]?.textContent.trim() || '';
            // serviceType, status, notes, patientId, doctorId should be in row.dataset

            if (targetLink.classList.contains('action-view')) {
                openAppointmentModal('view', appointmentId, rowData);
            } else if (targetLink.classList.contains('action-edit')) {
                openAppointmentModal('edit', appointmentId, rowData);
            } else if (targetLink.classList.contains('action-assign')) {
                openAppointmentModal('assign', appointmentId, rowData);
            } else if (targetLink.classList.contains('action-cancel')) {
                if (confirm(`Are you sure you want to cancel this appointment for ${rowData.patientFullName}?`)) {
                    // Simulate cancellation or make AJAX call to process_cancel_appointment.php
                    console.log(`Simulating Cancel - Appointment ID: ${appointmentId}`);
                    // TODO: AJAX call
                    // For now, just reload to reflect potential backend changes (if you implement cancellation)
                    // Or visually remove/update row:
                    // row.cells[5].textContent = 'Cancelled'; // Update status cell
                    // targetLink.remove(); // Remove cancel button
                    alert(`Appointment cancellation simulated for ID ${appointmentId}.\nImplement backend and AJAX.`);
                    // location.reload(); 
                }
            }
        });
    }

    // --- Booking Form: Fetch Available Time Slots (Code from previous response) ---
    const bookingForm = document.getElementById('book-appointment-form');
    const dentistBookingSelect = document.getElementById('dentist-for-booking');
    const dateBookingInput = document.getElementById('appointment-date-booking');
    const timeSlotsSelect = document.getElementById('appointment-time-slots');
    const timeSlotLoader = document.getElementById('time-slot-loader');

    async function fetchAvailableTimeSlots() {
        if (!dentistBookingSelect || !dateBookingInput || !timeSlotsSelect || !timeSlotLoader) return;
        const selectedDentistId = dentistBookingSelect.value;
        const selectedDate = dateBookingInput.value;
        timeSlotsSelect.innerHTML = '<option value="">-- Select Date & Dentist First --</option>';
        timeSlotsSelect.disabled = true;

        if (selectedDentistId && selectedDate) {
            timeSlotLoader.style.display = 'inline';
            timeSlotsSelect.innerHTML = '<option value="">Loading...</option>';
            try {
                console.log(`Fetching slots for Dentist ID: ${selectedDentistId}, Date: ${selectedDate}`);
                const response = await fetch(`get_available_slots.php?dentist_id=${selectedDentistId}&date=${selectedDate}`);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
                }
                const availableSlots = await response.json();

                timeSlotsSelect.innerHTML = '';
                if (availableSlots && availableSlots.error) {
                    console.error("Error from server (get_available_slots):", availableSlots.error);
                    timeSlotsSelect.innerHTML = `<option value="">-- ${availableSlots.error} --</option>`;
                    timeSlotsSelect.disabled = true;
                } else if (availableSlots && availableSlots.length > 0) {
                    availableSlots.forEach(slot => {
                        const option = document.createElement('option');
                        const dateObj = new Date(`1970-01-01T${slot}`);
                        option.value = slot;
                        option.textContent = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
                        timeSlotsSelect.appendChild(option);
                    });
                    timeSlotsSelect.disabled = false;
                } else {
                    timeSlotsSelect.innerHTML = '<option value="">-- No slots available --</option>';
                    timeSlotsSelect.disabled = true;
                }
            } catch (error) {
                console.error('Error fetching available time slots:', error);
                timeSlotsSelect.innerHTML = '<option value="">-- Error loading slots --</option>';
                timeSlotsSelect.disabled = true;
            } finally {
                timeSlotLoader.style.display = 'none';
            }
        } else {
            timeSlotLoader.style.display = 'none';
        }
    }

    if (dentistBookingSelect) dentistBookingSelect.addEventListener('change', fetchAvailableTimeSlots);
    if (dateBookingInput) {
        dateBookingInput.addEventListener('change', fetchAvailableTimeSlots);
        const today = new Date().toISOString().split('T')[0];
        dateBookingInput.setAttribute('min', today);
    }

    if (bookingForm) {
        bookingForm.addEventListener('submit', (event) => {
            if (!bookingForm.checkValidity()) {
                event.preventDefault();
                alert('Please fill out all required fields correctly, including selecting an available time slot.');
                bookingForm.reportValidity();
            } else if (timeSlotsSelect && timeSlotsSelect.value === "") {
                event.preventDefault();
                alert('Please select an available time slot.');
                timeSlotsSelect.focus();
            }
        });
    }

    if (dentistFilter) {
        applyDentistFilter();
    } else {
        updateTableVisibility('#all-appointments-table', '.no-data-row');
    }
});