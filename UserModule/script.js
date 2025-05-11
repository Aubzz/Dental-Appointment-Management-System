document.addEventListener('DOMContentLoaded', (event) => {

    // --- START: PROFILE PAGE EDIT FUNCTIONALITY ---
    const personalInfoCard = document.getElementById('personalInfoCard'); // Get the card by its ID

    // Check if we are on the profile page and the card exists
    if (personalInfoCard && document.body.classList.contains('page-patient-profile')) {
        const editInfoBtn = personalInfoCard.querySelector('.edit-info-btn');
        // These might be null if not defined in your HTML structure for edit-actions
        const editActionsDiv = personalInfoCard.querySelector('.edit-actions'); 
        const saveInfoBtn = personalInfoCard.querySelector('.btn-save-info');   
        const cancelEditBtn = personalInfoCard.querySelector('.btn-cancel-edit'); 
        
        const infoValues = personalInfoCard.querySelectorAll('.info-grid .info-value[data-field]');
        let originalValues = {}; // To store original values for cancellation

        const toggleEditMode = (isEditing) => {
            infoValues.forEach(span => {
                const fieldName = span.dataset.field;
                const currentParent = span.parentNode; // Get parent once

                // Skip making 'address' editable as it's removed
                if (fieldName === 'address') { 
                    if (isEditing === 'cancel' || isEditing === false) { // If exiting edit mode or cancelling/saving
                        const input = currentParent.querySelector(`[data-field="${fieldName}"].info-input`);
                        if (input) input.remove(); // Remove input if it somehow exists
                    }
                    span.style.display = ''; // Ensure span is visible (it shows N/A from PHP if no address column)
                    return; // Skip making 'address' editable
                }

                if (isEditing === true) { // Entering edit mode
                    // Only create input if one doesn't already exist for this field
                    if (!currentParent.querySelector(`[data-field="${fieldName}"].info-input`)) {
                        originalValues[fieldName] = span.textContent.trim(); // Store original value (formatted for DOB)
                        let input;

                        if (fieldName === 'dob') {
                            input = document.createElement('input');
                            input.type = 'date';
                            // Convert "Month Day, Year" from span to "YYYY-MM-DD" for date input
                            try {
                                // Check if originalValues[fieldName] is not "N/A" before parsing
                                if (originalValues[fieldName] && originalValues[fieldName].toLowerCase() !== 'n/a') {
                                    const dateObj = new Date(originalValues[fieldName]); // This can parse "Month Day, Year"
                                    if (!isNaN(dateObj)) {
                                       input.value = dateObj.toISOString().split('T')[0];
                                    } else {
                                        input.value = ''; // Fallback if parsing fails
                                    }
                                } else {
                                    input.value = ''; // If original value was N/A
                                }
                            } catch(e) { input.value = ''; console.error("Error parsing DOB for input:", e); }
                        } else if (fieldName === 'email') {
                            input = document.createElement('input');
                            input.type = 'email';
                            input.value = originalValues[fieldName];
                        } else if (fieldName === 'phone') {
                            input = document.createElement('input');
                            input.type = 'tel';
                            input.value = originalValues[fieldName];
                        } else if (fieldName === 'medicalInfo') { 
                            input = document.createElement('textarea');
                            input.rows = 3; 
                            input.value = originalValues[fieldName] === "[Encrypted data - retrieval issue or no data]" || originalValues[fieldName] === "Not provided" ? "" : originalValues[fieldName];
                        } else { // Default for firstName, lastName
                            input = document.createElement('input');
                            input.type = 'text';
                            input.value = originalValues[fieldName];
                        }
                        input.className = 'info-input form-control'; // Add form-control for potential global styling
                        input.dataset.field = fieldName;
                        span.style.display = 'none';
                        // Insert input after the span's parent if span is deeply nested, or directly into parent
                        currentParent.insertBefore(input, span.nextSibling); 
                    }
                } else { // Reverting from edit mode (after save or on cancel)
                    const input = currentParent.querySelector(`[data-field="${fieldName}"].info-input`);
                    if (input) {
                        if (isEditing === 'cancel') { // if 'cancel' was passed
                           span.textContent = originalValues[fieldName]; // Restore original text
                        } else { // if false (save was clicked and successful)
                           // Update span with new value for fields 
                           if(fieldName === 'dob' && input.value) { // Format DOB back to "Month Day, Year"
                                try {
                                    // Create date object assuming input.value is YYYY-MM-DD
                                    // Add 'T00:00:00' to avoid timezone issues where new Date('YYYY-MM-DD') might be previous day in UTC
                                    const dateObj = new Date(input.value + 'T00:00:00'); 
                                    span.textContent = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                                } catch(e) { span.textContent = "N/A"; console.error("Error formatting DOB for display:", e); }
                           } else if (fieldName === 'medicalInfo') {
                                span.innerHTML = input.value.replace(/\n/g, '<br>'); // Preserve newlines
                           } else {
                               span.textContent = input.value;
                           }
                        }
                        input.remove();
                    }
                    span.style.display = ''; // Show the span
                }
            });

            // Toggle button visibility
            if (editInfoBtn) editInfoBtn.style.display = (isEditing === true) ? 'none' : 'inline-block'; // or 'block'
            if (editActionsDiv) editActionsDiv.style.display = (isEditing === true) ? 'flex' : 'none';
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
                const updatedData = new FormData(); // Using FormData for easier handling if file uploads are added later
                let hasChanges = false;

                personalInfoCard.querySelectorAll('.info-input[data-field]').forEach(input => {
                    const fieldName = input.dataset.field;
                    let originalComparable = originalValues[fieldName];

                    // For DOB, originalValues stores the formatted "Month Day, Year" string.
                    // The input.value is "YYYY-MM-DD". We need to compare them appropriately or just check if input.value is different.
                    if (fieldName === 'dob') {
                        let originalInputFormat = '';
                        if (originalValues[fieldName] && originalValues[fieldName].toLowerCase() !== 'n/a') {
                            try {
                                originalInputFormat = new Date(originalValues[fieldName]).toISOString().split('T')[0];
                            } catch(e) { /* ignore parse error, will be treated as different */ }
                        }
                        if (input.value !== originalInputFormat) {
                            hasChanges = true;
                        }
                    } else if (input.value.trim() !== originalValues[fieldName]) { // Compare trimmed input with original
                        hasChanges = true;
                    }
                    updatedData.append(fieldName, input.value);
                });

                if (!hasChanges) {
                    alert("No changes were made.");
                    toggleEditMode(false); // Still exit edit mode
                    originalValues = {};
                    return;
                }
                
                // --- AJAX call to a PHP script to save the data ---
                fetch('update_patient_profile.php', { // MAKE SURE THIS PHP SCRIPT EXISTS!
                    method: 'POST',
                    body: updatedData
                })
                .then(response => {
                    if (!response.ok) {
                        // Try to get error message from server if it's JSON
                        return response.json().then(errData => {
                            throw new Error(errData.message || `Server responded with ${response.status}`);
                        }).catch(() => {
                            // If not JSON, throw generic error
                            throw new Error(`Server responded with ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert("Information updated successfully!");
                        toggleEditMode(false); // Exit edit mode, values are already updated in spans by toggleEditMode
                        originalValues = {}; 

                        // Update the summary card dynamically
                        if (updatedData.has('firstName') || updatedData.has('lastName')) {
                            const currentFirstName = updatedData.get('firstName') || personalInfoCard.querySelector('.info-value[data-field="firstName"]')?.textContent;
                            const currentLastName = updatedData.get('lastName') || personalInfoCard.querySelector('.info-value[data-field="lastName"]')?.textContent;
                            const fullNameDisplay = document.querySelector('.patient-summary-card .detail-value[data-field="fullNameDisplay"]');
                            if (fullNameDisplay) {
                                fullNameDisplay.textContent = `${currentFirstName} ${currentLastName}`;
                            }
                        }
                        if (updatedData.has('dob')) {
                            const ageDisplay = document.querySelector('.patient-summary-card .detail-value[data-field="ageDisplay"]');
                            const dobValue = updatedData.get('dob');
                            if (ageDisplay && dobValue) {
                                try {
                                    const birthDate = new Date(dobValue  + 'T00:00:00');
                                    const today = new Date();
                                    let age = today.getFullYear() - birthDate.getFullYear();
                                    const m = today.getMonth() - birthDate.getMonth();
                                    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                                        age--;
                                    }
                                    ageDisplay.textContent = age;
                                } catch(e) { ageDisplay.textContent = "N/A"; }
                            } else if (ageDisplay && !dobValue) {
                                ageDisplay.textContent = "N/A";
                            }
                        }
                    } else {
                        alert("Error updating profile: " + (data.message || "Unknown error from server."));
                        // Optionally, revert to original values if save failed and you want to offer that UX
                        // toggleEditMode('cancel'); 
                    }
                })
                .catch(error => {
                    console.error('Error saving profile:', error);
                    alert("An AJAX error occurred while saving: " + error.message + ". Please check console and try again.");
                    // toggleEditMode('cancel'); // Revert changes on AJAX failure
                });
            });
        }
    }
    // --- END: PROFILE PAGE EDIT FUNCTIONALITY ---


    // --- START: COMMON MODAL JAVASCRIPT LOGIC (for appointment booking modal, if present) ---
    // This section assumes you have a modal with id 'appointmentModal' and related elements.
    // If not used on this page, these querySelectors will just return null and not error.
    const today = new Date().toISOString().split('T')[0];
    const dateFields = document.querySelectorAll('input[type="date"]');
    dateFields.forEach(field => {
        // Ensure we don't set 'min' for date inputs used for editing (like profile DOB)
        if (field.id !== 'existingDob' && !field.closest('#personalInfoCard')) { // Check if not profile DOB
            field.setAttribute('min', today);
        }
    });

    const openModalButtons = document.querySelectorAll('.open-modal-btn'); // Button to open appointment modal
    const modal = document.getElementById('appointmentModal'); // The appointment modal itself

    if (modal) {
        // Elements within the appointment modal
        const selectedDoctorInput = document.getElementById('selectedDoctor');
        const dentistSelectNew = document.getElementById('dentistInCharge'); // In "new patient" tab
        const selectedDoctorExistingInput = document.getElementById('selectedDoctorExisting');
        const dentistSelectExisting = document.getElementById('dentistInChargeExisting'); // In "existing patient" tab
        const closeModalButton = modal.querySelector('.modal-close-btn');
        const tabs = modal.querySelectorAll('.tab-btn');
        const tabContents = modal.querySelectorAll('.tab-content');
        const newPatientForm = document.getElementById('newPatientForm');
        const existingPatientForm = document.getElementById('existingPatientForm');

        openModalButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const doctorName = this.getAttribute('data-doctor'); // Get doctor from button if available

                // Pre-select doctor in both tabs of the modal if doctorName is provided
                if (doctorName) {
                    if (selectedDoctorInput) selectedDoctorInput.value = doctorName;
                    if (dentistSelectNew) { 
                        for (let i = 0; i < dentistSelectNew.options.length; i++) { 
                            if (dentistSelectNew.options[i].text.includes(doctorName) || dentistSelectNew.options[i].value === doctorName) { // Check by text or value
                                dentistSelectNew.selectedIndex = i; break;
                            }
                        }
                    }
                    if (selectedDoctorExistingInput) selectedDoctorExistingInput.value = doctorName;
                    if (dentistSelectExisting) { 
                        for (let i = 0; i < dentistSelectExisting.options.length; i++) { 
                            if (dentistSelectExisting.options[i].text.includes(doctorName) || dentistSelectExisting.options[i].value === doctorName) {
                                dentistSelectExisting.selectedIndex = i; break;
                            }
                        }
                    }
                } else { // If no doctor pre-selected, reset dropdowns
                    if (selectedDoctorInput) selectedDoctorInput.value = '';
                    if (dentistSelectNew) dentistSelectNew.selectedIndex = 0;
                    if (selectedDoctorExistingInput) selectedDoctorExistingInput.value = '';
                    if (dentistSelectExisting) dentistSelectExisting.selectedIndex = 0;
                }

                modal.style.display = 'block'; // Or 'flex' depending on your CSS

                // Logic for pre-filling existing patient tab if on profile page
                const existingPatientTab = modal.querySelector('.tab-btn[data-tab="existing-patient"]');
                const newPatientTab = modal.querySelector('.tab-btn[data-tab="new-patient"]');
                const existingPatientContent = modal.querySelector('#existing-patient');
                const newPatientContent = modal.querySelector('#new-patient');
                
                const isProfileContext = document.body.classList.contains('page-patient-profile');

                if (existingPatientTab && newPatientTab && existingPatientContent && newPatientContent) {
                    if (isProfileContext && personalInfoCard) { // If on profile page and profile card exists
                        // Default to "Existing Patient" tab
                        newPatientTab.classList.remove('active');
                        newPatientContent.classList.remove('active');
                        existingPatientTab.classList.add('active');
                        existingPatientContent.classList.add('active');

                        // Pre-fill existing patient form with profile data
                        const profileLastName = personalInfoCard.querySelector('.info-value[data-field="lastName"]')?.textContent;
                        const profileDobValue = personalInfoCard.querySelector('.info-value[data-field="dob"]')?.textContent; // This is "Month Day, Year"

                        if (document.getElementById('existingLastName') && profileLastName) {
                            document.getElementById('existingLastName').value = profileLastName;
                        }
                        if (document.getElementById('existingDob') && profileDobValue && profileDobValue.toLowerCase() !== 'n/a') {
                            try { // Convert "Month Day, Year" to "YYYY-MM-DD"
                                const dateObj = new Date(profileDobValue);
                                document.getElementById('existingDob').value = dateObj.toISOString().split('T')[0];
                            } catch(e) { document.getElementById('existingDob').value = ''; }
                        } else if (document.getElementById('existingDob')) {
                             document.getElementById('existingDob').value = '';
                        }
                    } else { // Not profile context, or personalInfoCard not found: default to "New Patient" tab
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
            if (event.key === 'Escape' && modal && modal.style.display !== 'none') {
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

        function formatTimeForDisplay(timeString) { // Renamed for clarity
            if (!timeString) return '';
            const [hourString, minute] = timeString.split(':');
            let hour = parseInt(hourString, 10);
            const period = hour < 12 || hour === 24 ? 'AM' : 'PM'; // 24 is midnight, so AM
            hour = hour % 12 || 12; // Convert 0 or 12 to 12 for 12hr format
            return `${hour}:${minute} ${period}`;
        }

        // AJAX submission for newPatientForm and existingPatientForm
        // This replaces the localStorage demo
        function handleAppointmentFormSubmit(form, event) {
            event.preventDefault();
            const formData = new FormData(form);
            // Add any extra data if needed, e.g., if patient_id is handled separately for existing patients
            
            fetch(form.action, { // Assumes form.action points to the correct PHP script
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Appointment booked successfully!');
                    form.reset();
                    if(modal) modal.style.display = 'none';
                    // Optionally, redirect or update UI
                    // window.location.href = 'patient_appointments.php';
                } else {
                    alert('Error: ' + (data.message || 'Could not book appointment.'));
                }
            })
            .catch(error => {
                console.error('Error submitting appointment form:', error);
                alert('An error occurred. Please try again.');
            });
        }

        if (newPatientForm) {
            newPatientForm.addEventListener('submit', function(e) {
                handleAppointmentFormSubmit(this, e);
            });
        }

        if (existingPatientForm) {
            existingPatientForm.addEventListener('submit', function(e) {
                handleAppointmentFormSubmit(this, e);
            });
        }
    }
    // --- END: COMMON MODAL JAVASCRIPT LOGIC ---


    // --- START: LOGIC FOR appointments.html specific elements ---
    // (This section can remain as is, or be adapted if localStorage is replaced by server data)
    const scheduledTableBody = document.querySelector('.scheduled-appointments tbody');
    const completedTableBody = document.querySelector('.completed-appointments tbody');

    if ((scheduledTableBody || completedTableBody) && document.body.classList.contains('page-patient-appointments')) {
        // ... (Your existing reschedule, cancel, checkAppointmentTableEmptyState logic) ...
        // The part that populates from localStorage would ideally be replaced by PHP generating the table
        // or an AJAX call to fetch appointments. For now, if you keep localStorage for demo:
        // ... (Your existing localStorage population logic, ensuring it's only for demo) ...
        console.log("Note: Appointment list on appointments.html is using localStorage for demo purposes in this script version.");
    }
    // --- END: LOGIC FOR appointments.html specific elements ---


    // --- START: LOGIC FOR notifications.html specific elements ---
    // (This section can remain as is)
    const notificationsListContainer = document.querySelector('.notifications-section .notifications-list');
    if (notificationsListContainer && document.body.classList.contains('page-patient-notifications')) {
        // ... (Your existing notification list interaction logic) ...
    }
    // --- END: LOGIC FOR notifications.html specific elements ---

    // --- START: LOGIC FOR patient_dashboard.html (Date/Time) ---
    const dateTimeSpan = document.querySelector('.dashboard-header .date-time');
    if (dateTimeSpan && document.body.classList.contains('page-patient-dashboard')) {
        const now = new Date();
        const optionsDate = { month: 'long', day: 'numeric', year: 'numeric' };
        const optionsTime = { hour: 'numeric', minute: 'numeric', hour12: true };
        const formattedDate = now.toLocaleDateString('en-US', optionsDate);
        const formattedTime = now.toLocaleTimeString('en-US', optionsTime);
        dateTimeSpan.innerHTML = `<i class="far fa-calendar-alt"></i> ${formattedDate} at ${formattedTime}`;
    }
    // --- END: LOGIC FOR patient_dashboard.html ---

});