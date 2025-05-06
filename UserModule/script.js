document.addEventListener('DOMContentLoaded', (event) => {

    // --- START: PROFILE PAGE EDIT FUNCTIONALITY ---
    const personalInfoCard = document.getElementById('personalInfoCard'); // Get the card by its ID

    // Check if we are on the profile page and the card exists
    if (personalInfoCard && document.body.classList.contains('page-patient-profile')) {
        const editInfoBtn = personalInfoCard.querySelector('.edit-info-btn');
        const editActionsDiv = personalInfoCard.querySelector('.edit-actions');
        const saveInfoBtn = personalInfoCard.querySelector('.btn-save-info');
        const cancelEditBtn = personalInfoCard.querySelector('.btn-cancel-edit');
        const infoValues = personalInfoCard.querySelectorAll('.info-grid .info-value[data-field]');
        let originalValues = {}; // To store original values for cancellation

        const toggleEditMode = (isEditing) => {
            infoValues.forEach(span => {
                const fieldName = span.dataset.field;
                const currentParent = span.parentNode; // Get parent once

                if (isEditing) {
                    // Only create input if one doesn't already exist for this field
                    if (!currentParent.querySelector(`[data-field="${fieldName}"].info-input`)) {
                        originalValues[fieldName] = span.textContent; // Store original value
                        let input;

                        if (fieldName === 'dob') {
                            input = document.createElement('input');
                            input.type = 'date';
                        } else if (fieldName === 'email') {
                            input = document.createElement('input');
                            input.type = 'email';
                        } else if (fieldName === 'phone') {
                            input = document.createElement('input');
                            input.type = 'tel';
                        } else if (fieldName === 'address') {
                            input = document.createElement('textarea');
                            input.rows = 2;
                        } else {
                            input = document.createElement('input');
                            input.type = 'text';
                        }
                        input.className = 'info-input';
                        input.value = originalValues[fieldName];
                        input.dataset.field = fieldName;
                        span.style.display = 'none';
                        currentParent.insertBefore(input, span.nextSibling);
                    }
                } else { // Reverting from edit mode (save or cancel)
                    const input = currentParent.querySelector(`[data-field="${fieldName}"].info-input`);
                    if (input) {
                        // On 'cancel', restore original. On save (isEditing === false), use input's new value.
                        span.textContent = (isEditing === 'cancel') ? originalValues[fieldName] : input.value;
                        input.remove();
                    }
                    span.style.display = ''; // Show the span
                }
            });

            // Toggle button visibility only if buttons exist
            if (editInfoBtn) editInfoBtn.style.display = isEditing ? 'none' : '';
            if (editActionsDiv) editActionsDiv.style.display = isEditing ? 'flex' : 'none';
        };

        if (editInfoBtn) {
            editInfoBtn.addEventListener('click', () => {
                toggleEditMode(true); // Enter edit mode
            });
        }

        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', () => {
                toggleEditMode('cancel'); // Revert changes and exit edit mode
                originalValues = {}; // Clear stored values
            });
        }

        if (saveInfoBtn) {
            saveInfoBtn.addEventListener('click', () => {
                const updatedData = {};
                personalInfoCard.querySelectorAll('.info-input[data-field]').forEach(input => {
                    updatedData[input.dataset.field] = input.value;
                });

                console.log("Saving data:", updatedData);
                // TODO: Add AJAX call here to send `updatedData` to the server
                // On successful save from server:
                toggleEditMode(false); // Exit edit mode, applying new values
                alert("Information updated successfully! (Backend integration needed)");
                originalValues = {}; // Clear stored values

                // Update the summary card if fields match
                const fullNameDisplay = document.querySelector('.patient-summary-card .detail-value[data-field="fullNameDisplay"]');
                if (fullNameDisplay && updatedData.firstName && updatedData.lastName) {
                    fullNameDisplay.textContent = `${updatedData.firstName} ${updatedData.lastName}`;
                }
            });
        }
    }
    // --- END: PROFILE PAGE EDIT FUNCTIONALITY ---


    // --- START: COMMON MODAL JAVASCRIPT LOGIC ---
    const today = new Date().toISOString().split('T')[0];
    const dateFields = document.querySelectorAll('input[type="date"]');
    dateFields.forEach(field => {
        // Ensure we don't set 'min' for date inputs used for editing (like profile DOB)
        if (field.id !== 'existingDob' && !field.closest('#personalInfoCard')) {
            field.setAttribute('min', today);
        }
    });

    const openModalButtons = document.querySelectorAll('.open-modal-btn');
    const modal = document.getElementById('appointmentModal');

    if (modal) {
        const selectedDoctorInput = document.getElementById('selectedDoctor');
        const dentistSelectNew = document.getElementById('dentistInCharge');
        const selectedDoctorExistingInput = document.getElementById('selectedDoctorExisting');
        const dentistSelectExisting = document.getElementById('dentistInChargeExisting');
        const closeModalButton = modal.querySelector('.modal-close-btn');
        const tabs = modal.querySelectorAll('.tab-btn');
        const tabContents = modal.querySelectorAll('.tab-content');
        const newPatientForm = document.getElementById('newPatientForm');
        const existingPatientForm = document.getElementById('existingPatientForm');


        openModalButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const doctorName = this.getAttribute('data-doctor');

                if (doctorName) {
                    if (selectedDoctorInput) selectedDoctorInput.value = doctorName;
                    if (dentistSelectNew) { for (let i = 0; i < dentistSelectNew.options.length; i++) { if (dentistSelectNew.options[i].value === doctorName) { dentistSelectNew.selectedIndex = i; break;}}}
                    if (selectedDoctorExistingInput) selectedDoctorExistingInput.value = doctorName;
                    if (dentistSelectExisting) { for (let i = 0; i < dentistSelectExisting.options.length; i++) { if (dentistSelectExisting.options[i].value === doctorName) { dentistSelectExisting.selectedIndex = i; break;}}}
                } else {
                    if (selectedDoctorInput) selectedDoctorInput.value = '';
                    if (dentistSelectNew) dentistSelectNew.selectedIndex = 0;
                    if (selectedDoctorExistingInput) selectedDoctorExistingInput.value = '';
                    if (dentistSelectExisting) dentistSelectExisting.selectedIndex = 0;
                }

                modal.style.display = 'block'; // Use 'block' or 'flex' based on your CSS for .modal

                const existingPatientTab = modal.querySelector('.tab-btn[data-tab="existing-patient"]');
                const newPatientTab = modal.querySelector('.tab-btn[data-tab="new-patient"]');
                const existingPatientContent = modal.querySelector('#existing-patient');
                const newPatientContent = modal.querySelector('#new-patient');
                const isProfileContext = !this.hasAttribute('data-doctor') || document.body.classList.contains('page-patient-profile');

                if (existingPatientTab && newPatientTab && existingPatientContent && newPatientContent) {
                    if (isProfileContext) {
                        newPatientTab.classList.remove('active');
                        newPatientContent.classList.remove('active');
                        existingPatientTab.classList.add('active');
                        existingPatientContent.classList.add('active');

                        // Pre-fill from profile if data is available and personalInfoCard exists
                        if (personalInfoCard) {
                            const profileLastName = personalInfoCard.querySelector('.info-value[data-field="lastName"]')?.textContent;
                            const profileDob = personalInfoCard.querySelector('.info-value[data-field="dob"]')?.textContent;

                            if (document.getElementById('existingLastName') && profileLastName) {
                                document.getElementById('existingLastName').value = profileLastName;
                            }
                            if (document.getElementById('existingDob') && profileDob) {
                                document.getElementById('existingDob').value = profileDob;
                            }
                        } else { // Fallback if personalInfoCard not found (e.g. on doctors page)
                             if (document.getElementById('existingLastName')) document.getElementById('existingLastName').value = "";
                             if (document.getElementById('existingDob')) document.getElementById('existingDob').value = "";
                        }

                    } else { // Not profile context (e.g., booking from doctor card)
                        existingPatientTab.classList.remove('active');
                        existingPatientContent.classList.remove('active');
                        newPatientTab.classList.add('active');
                        newPatientContent.classList.add('active');
                    }
                }
            });
        });

        if (closeModalButton) {
            closeModalButton.addEventListener('click', () => { modal.style.display = 'none'; });
        }
        window.addEventListener('click', (event) => {
            if (event.target === modal) { modal.style.display = 'none'; }
        });
        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.style.display !== 'none') { // Check display style
                modal.style.display = 'none';
            }
        });


        if (tabs && tabContents) {
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    tab.classList.add('active');
                    const targetContentId = tab.getAttribute('data-tab');
                    const targetContent = modal.querySelector(`#${targetContentId}`);
                    if (targetContent) targetContent.classList.add('active');
                });
            });
        }

        // Helper function to format time (optional)
        function formatTime(timeString) {
            if (!timeString) return '';
            const [hourString, minute] = timeString.split(':');
            const hour = +hourString % 24;
            const period = hour < 12 || hour === 24 ? 'AM' : 'PM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minute} ${period}`;
        }

        function saveAppointmentToLocalStorage(appointmentData) {
            let appointments = JSON.parse(localStorage.getItem('patientAppointments') || '[]');
            appointments.push(appointmentData);
            localStorage.setItem('patientAppointments', JSON.stringify(appointments));
            alert('Appointment booked successfully! (Data saved to LocalStorage for demo)');
            if(modal) modal.style.display = 'none';
        }


        if (newPatientForm) {
            newPatientForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const dentist = this.elements['dentistInCharge'].value;
                const date = this.elements['appointmentDate'].value;
                const startTime = this.elements['startTime'].value;
                const dateTimeString = `${date.replaceAll('-', '.')} / ${formatTime(startTime)}`;
                const appointment = { dentist: dentist, dateTime: dateTimeString, status: 'SCHEDULED' };
                saveAppointmentToLocalStorage(appointment); // Using localStorage for demo
                this.reset();
            });
        }

        if (existingPatientForm) {
            existingPatientForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Add Patient Lookup Logic Here for real app
                const dentist = this.elements['dentistInChargeExisting'].value;
                const date = this.elements['appointmentDateExisting'].value;
                const startTime = this.elements['startTimeExisting'].value;
                const dateTimeString = `${date.replaceAll('-', '.')} / ${formatTime(startTime)}`;
                const appointment = { dentist: dentist, dateTime: dateTimeString, status: 'SCHEDULED' };
                saveAppointmentToLocalStorage(appointment); // Using localStorage for demo
                this.reset();
            });
        }
    }
    // --- END: COMMON MODAL JAVASCRIPT LOGIC ---


    // --- START: LOGIC FOR appointments.html specific elements ---
    const scheduledTableBody = document.querySelector('.scheduled-appointments tbody');
    const completedTableBody = document.querySelector('.completed-appointments tbody');

    if (scheduledTableBody || completedTableBody) {
        const rescheduleButtons = document.querySelectorAll('.btn-action.reschedule');
        const cancelButtons = document.querySelectorAll('.btn-action.cancel');

        rescheduleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                alert('Reschedule functionality not implemented yet.');
                console.log('Reschedule clicked for appointment:', e.target.closest('tr'));
            });
        });

        cancelButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                if (confirm('Are you sure you want to cancel this appointment?')) {
                    alert('Cancellation functionality not implemented yet.');
                    console.log('Cancel confirmed for appointment:', e.target.closest('tr'));
                    // e.target.closest('tr').remove(); // Example: remove row on cancel
                    // checkAppointmentTableEmptyState(scheduledTableBody, 5); // Re-check
                }
            });
        });

        function checkAppointmentTableEmptyState(tableBody, colspan, messageType) {
            if (tableBody && tableBody.querySelectorAll('tr:not(.no-appointments-row)').length === 0) {
                let message = messageType === 'scheduled' ?
                              'No upcoming appointments scheduled.' :
                              'No past appointment history found.';
                // Remove old message if exists
                const oldMessageRow = tableBody.querySelector('.no-appointments-row');
                if(oldMessageRow) oldMessageRow.remove();
                // Add new message
                tableBody.innerHTML = `<tr class="no-appointments-row"><td colspan="${colspan}" class="no-appointments">${message}</td></tr>`;
            } else {
                 const oldMessageRow = tableBody.querySelector('.no-appointments-row');
                 if(oldMessageRow) oldMessageRow.remove();
            }
        }
        if(scheduledTableBody) checkAppointmentTableEmptyState(scheduledTableBody, 5, 'scheduled');
        if(completedTableBody) checkAppointmentTableEmptyState(completedTableBody, 4, 'completed');

        // Example: Populate from localStorage if on appointments.html
        if (document.body.classList.contains('page-patient-appointments')) { // Add this class to appointments.html body
            const appointments = JSON.parse(localStorage.getItem('patientAppointments') || '[]');
            appointments.forEach(app => {
                const row = document.createElement('tr');
                let targetTableBody = null;

                if (app.status === 'SCHEDULED') {
                    targetTableBody = scheduledTableBody;
                    row.innerHTML = `
                        <td>${app.dateTime.split(' / ')[0]}</td>
                        <td>${app.dateTime.split(' / ')[1]}</td>
                        <td>${app.dentist}</td>
                        <td><span class="status status-scheduled">${app.status}</span></td>
                        <td>
                            <button class="btn-action reschedule" aria-label="Reschedule Appointment">Reschedule</button>
                            <button class="btn-action cancel" aria-label="Cancel Appointment">Cancel</button>
                        </td>
                    `;
                } else if (app.status === 'COMPLETED') { // Assuming you might add completed status later
                    targetTableBody = completedTableBody;
                     row.innerHTML = `
                        <td>${app.dateTime.split(' / ')[0]}</td>
                        <td>${app.dateTime.split(' / ')[1]}</td>
                        <td>${app.dentist}</td>
                        <td><span class="status status-completed">${app.status}</span></td>
                    `;
                }
                if(targetTableBody) targetTableBody.appendChild(row);
            });
            if(scheduledTableBody) checkAppointmentTableEmptyState(scheduledTableBody, 5, 'scheduled');
            if(completedTableBody) checkAppointmentTableEmptyState(completedTableBody, 4, 'completed');
        }
    }
    // --- END: LOGIC FOR appointments.html specific elements ---


    // --- START: LOGIC FOR notifications.html specific elements ---
    const notificationsListContainer = document.querySelector('.notifications-section .notifications-list');
    if (notificationsListContainer) {
        function checkNotificationsEmptyState(listContainer) {
            const remainingItems = listContainer.querySelectorAll('.notification-item');
            let noNotificationsDiv = listContainer.querySelector('.no-notifications');

            if (remainingItems.length === 0 && !noNotificationsDiv) {
                noNotificationsDiv = document.createElement('div');
                noNotificationsDiv.classList.add('no-notifications');
                noNotificationsDiv.textContent = 'You have no notifications.';
                listContainer.appendChild(noNotificationsDiv);
            } else if (remainingItems.length > 0 && noNotificationsDiv) {
                noNotificationsDiv.remove();
            }
        }

        notificationsListContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('mark-read') || e.target.closest('.mark-read')) {
                const button = e.target.classList.contains('mark-read') ? e.target : e.target.closest('.mark-read');
                const notificationItem = button.closest('.notification-item');
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');
                    button.remove();
                    console.log('Marked as read:', notificationItem);
                    checkNotificationsEmptyState(notificationsListContainer);
                }
            }

            if (e.target.classList.contains('delete') || e.target.closest('.delete')) {
                const button = e.target.classList.contains('delete') ? e.target : e.target.closest('.delete');
                const notificationItem = button.closest('.notification-item');
                if (notificationItem && confirm('Are you sure you want to delete this notification?')) {
                    console.log('Deleting notification:', notificationItem);
                    notificationItem.style.opacity = '0';
                    notificationItem.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        notificationItem.remove();
                        checkNotificationsEmptyState(notificationsListContainer);
                    }, 300);
                }
            }
        });
        checkNotificationsEmptyState(notificationsListContainer); // Initial check
    }
    // --- END: LOGIC FOR notifications.html specific elements ---

    // --- START: LOGIC FOR patient_dashboard.html (Date/Time) ---
    const dateTimeSpan = document.querySelector('.dashboard-header .date-time'); // Assuming this element exists on dashboard
    if (dateTimeSpan && document.body.classList.contains('page-patient-dashboard')) { // Add class to dashboard body
        const now = new Date();
        const optionsDate = { month: 'long', day: 'numeric', year: 'numeric' };
        const optionsTime = { hour: 'numeric', minute: 'numeric', hour12: true };
        const formattedDate = now.toLocaleDateString('en-US', optionsDate);
        const formattedTime = now.toLocaleTimeString('en-US', optionsTime);
        dateTimeSpan.innerHTML = `<i class="far fa-calendar-alt"></i> ${formattedDate} at ${formattedTime}`;
    }
    // --- END: LOGIC FOR patient_dashboard.html ---

});