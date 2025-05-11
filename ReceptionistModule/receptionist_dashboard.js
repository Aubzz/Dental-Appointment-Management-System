// receptionist_dashboard.js
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM fully loaded and parsed. receptionist_dashboard.js executing.');

    // --- UTILITY FUNCTIONS (like updateTableVisibility) ---
    function updateTableVisibility(tableId, noDataRowSelector) {
        const tableElement = document.querySelector(tableId); // Get the table itself
        if (!tableElement) {
            // console.warn(`updateTableVisibility: Table with ID "${tableId}" not found.`);
            return;
        }
        const tableBody = tableElement.querySelector('tbody');
        if (!tableBody) {
            // console.warn(`updateTableVisibility: tbody not found in table "${tableId}".`);
            return;
        }

        const noDataRow = tableBody.querySelector(noDataRowSelector);
        // It's okay if noDataRow doesn't exist, we might not always have one.
        // if (!noDataRow) {
        //     console.warn(`updateTableVisibility: No data row with selector "${noDataRowSelector}" found in table "${tableId}".`);
        // }

        const dataRows = tableBody.querySelectorAll('tr:not(.no-data-row)'); // Select only actual data rows
        let hasVisibleRows = false;
        if (dataRows.length > 0) {
            hasVisibleRows = Array.from(dataRows).some(row => {
                // Check the computed style for display none, as inline style might not be the only way it's hidden
                const style = window.getComputedStyle(row);
                return style.display !== 'none';
            });
        }
        
        if (noDataRow) {
            noDataRow.style.display = hasVisibleRows ? 'none' : 'table-row';
        }
    }


    // --- Notification Bell Logic ---
    const notificationBell = document.getElementById('notificationBell');
    const notificationBadge = document.getElementById('notificationBadge'); // May be null initially
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationList = document.getElementById('notificationList');

    function updateNotificationDisplay(count, notifications = []) {
        let currentBadge = document.getElementById('notificationBadge'); 
        if (!currentBadge && count > 0 && notificationBell) {
             currentBadge = document.createElement('span');
             currentBadge.id = 'notificationBadge';
             currentBadge.className = 'notification-badge';
             notificationBell.appendChild(currentBadge);
        }
        if (currentBadge) {
            if (count > 0) {
                currentBadge.textContent = count;
                currentBadge.style.display = 'inline-block'; 
            } else {
                currentBadge.style.display = 'none';
            }
        }

        if (notificationList) {
            notificationList.innerHTML = ''; 
            if (notifications.length > 0) {
                notifications.forEach(notif => {
                    const item = document.createElement('div'); item.classList.add('notification-item');
                    item.innerHTML = `<a href="${notif.link || '#'}" data-request-id="${notif.request_id || ''}" class="notification-link"><div class="notification-item-title">${notif.title || 'Notification'}</div><div class="notification-item-message">${notif.message || 'New update.'}</div><div class="notification-item-time">${notif.time_ago || ''}</div></a>`;
                    if (notif.type === 'new_request' && notif.request_id) { item.querySelector('.notification-link').addEventListener('click', function(e) { e.preventDefault(); if(notificationsDropdown) notificationsDropdown.classList.remove('show'); const reqData = allAppointmentRequests.find(r => r.request_id == notif.request_id); if (reqData) { openRequestDetailsModal(notif.request_id, { requestId: notif.request_id, patientName: reqData.patient_firstName + ' ' + reqData.patient_lastName, preferredDateTime: new Date(reqData.preferred_date + 'T' + reqData.preferred_time).toLocaleString(), serviceType: reqData.service_type, patientMessage: reqData.patient_message }); } else { window.location.href = this.href; } }); }
                    notificationList.appendChild(item);
                });
            } else { notificationList.innerHTML = '<p class="no-notifications">No new notifications.</p>'; }
        }
    }
    async function fetchNotifications() { 
        try { const response = await fetch('fetch_notifications.php'); if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`); const data = await response.json(); if (data.error) { console.error('Error fetching notifications:', data.error); return; } updateNotificationDisplay(data.count, data.notifications); } catch (error) { console.error('Could not fetch notifications:', error); }
    }
    if (notificationBell) { notificationBell.addEventListener('click', (event) => { event.stopPropagation(); if(notificationsDropdown) { notificationsDropdown.classList.toggle('show'); if (notificationsDropdown.classList.contains('show')) fetchNotifications(); } }); }
    document.addEventListener('click', (event) => { if (notificationsDropdown && notificationsDropdown.classList.contains('show')) { if (notificationBell && !notificationBell.contains(event.target) && !notificationsDropdown.contains(event.target)) { notificationsDropdown.classList.remove('show'); } } });
    if (typeof initialNotificationCount !== 'undefined' && typeof initialNotifications !== 'undefined') { updateNotificationDisplay(initialNotificationCount, initialNotifications); } else { if(notificationBell) fetchNotifications(); }


    // --- Request Details and Booking Modals ---
    const requestModalOverlay = document.getElementById('requestModalOverlay'); 
    const requestModalTitle = document.getElementById('requestModalTitle'); 
    const requestModalBody = document.getElementById('requestModalBody'); 
    const requestModalFooter = document.getElementById('requestModalFooter'); 
    const requestModalCloseButton = document.getElementById('requestModalCloseButton'); 
    const appointmentBookingModalOverlay = document.getElementById('appointmentModalOverlay'); 
    const appointmentBookingModalTitle = document.getElementById('appointmentModalTitle'); 
    const appointmentBookingModalBody = document.getElementById('appointmentModalBody'); 
    const appointmentBookingModalFooter = document.getElementById('appointmentModalFooter'); 
    const appointmentBookingModalCloseButton = document.getElementById('appointmentModalCloseButton'); 
    function openRequestDetailsModal(requestId, requestData) { if (!requestModalOverlay || !requestModalTitle || !requestModalBody) return; requestModalOverlay.classList.add('visible'); requestModalBody.innerHTML = ''; if(requestModalFooter) requestModalFooter.style.display = 'none';  requestModalTitle.textContent = 'Appointment Request Details'; displayRequestDetailsContent(requestId, requestData); }
    function closeRequestModal() { if (requestModalOverlay) requestModalOverlay.classList.remove('visible'); }
    function displayRequestDetailsContent(requestId, requestData) { if (!requestModalBody) return; const patientMessageDisplay = requestData.patientMessage || "No specific message provided by patient."; requestModalBody.innerHTML = `<p><strong>Request ID:</strong> ${requestId}</p><p><strong>Patient Name:</strong> ${requestData.patientName || 'N/A'}</p><p><strong>Preferred Date & Time:</strong> ${requestData.preferredDateTime || 'N/A'}</p><p><strong>Service Request:</strong> ${requestData.serviceType || 'N/A'}</p><p><strong>Patient's Message:</strong><br>${patientMessageDisplay}</p><hr class="content-separator"><div class="form-group" style="margin-bottom: 0;"><button class="btn btn-primary" id="modalAcceptAndBookButton" data-request-id="${requestId}">Accept and Book</button></div>`; const acceptButton = requestModalBody.querySelector('#modalAcceptAndBookButton'); if (acceptButton) acceptButton.addEventListener('click', handleModalAcceptAndBookClick); }
    function handleModalAcceptAndBookClick(event) { const requestId = event.target.dataset.requestId; closeRequestModal();  let bookingDataForModal = null; if (typeof allAppointmentRequests !== 'undefined' && Array.isArray(allAppointmentRequests)) { const fullReqData = allAppointmentRequests.find(r => r.request_id == requestId); if (fullReqData) { bookingDataForModal = { requestId: requestId, patientName: `${fullReqData.patient_firstName} ${fullReqData.patient_lastName}`, patient_id_for_booking: fullReqData.patient_id, preferredDateTime: `${fullReqData.preferred_date} / ${fullReqData.preferred_time}`, serviceType: fullReqData.service_type, patientMessage: fullReqData.patient_message }; } } if (bookingDataForModal) { openAppointmentBookingModal('bookFromRequest', bookingDataForModal); } else {  const requestRow = document.querySelector(`#appointment-requests-table tbody tr[data-request-id="${requestId}"]`); if(requestRow){ bookingDataForModal = { requestId: requestId, patientName: requestRow.cells[0]?.textContent.trim() || '', preferredDateTime: requestRow.cells[1]?.textContent.trim() || '', serviceType: requestRow.cells[2]?.textContent.trim() || '' }; openAppointmentBookingModal('bookFromRequest', bookingDataForModal); } else { alert("Error: Could not retrieve request data for booking."); } } }
    function addModalFooterButton(targetFooter, text, classes, id) { if (!targetFooter) return null; const button = document.createElement('button'); button.textContent = text; button.className = 'btn ' + classes; if (id) button.id = id; targetFooter.appendChild(button); return button; }
    function openAppointmentBookingModal(type, data = null) { if (!appointmentBookingModalOverlay || !appointmentBookingModalTitle || !appointmentBookingModalBody || !appointmentBookingModalFooter) return; appointmentBookingModalOverlay.classList.add('visible'); appointmentBookingModalBody.innerHTML = ''; appointmentBookingModalFooter.innerHTML = ''; appointmentBookingModalFooter.style.display = 'flex'; if (type === 'bookFromRequest') { appointmentBookingModalTitle.textContent = 'Confirm & Book Appointment'; displayBookAppointmentFormFromRequest(data); addModalFooterButton(appointmentBookingModalFooter, 'Confirm Booking', 'btn-primary', 'confirmBookingFromRequestButton'); addModalFooterButton(appointmentBookingModalFooter, 'Cancel', 'btn-secondary', 'cancelBookingFromRequestButton'); } else { appointmentBookingModalBody.innerHTML = '<p>Error: Invalid booking modal usage.</p>'; appointmentBookingModalFooter.style.display = 'none'; } addAppointmentModalFormElementListeners(); }
    function closeAppointmentBookingModal() { if (appointmentBookingModalOverlay) appointmentBookingModalOverlay.classList.remove('visible'); }
    function displayBookAppointmentFormFromRequest(requestData) { if (!appointmentBookingModalBody || !requestData) return; let preferredDate = ''; let preferredTime24h = '09:00'; if (requestData.preferredDateTime && requestData.preferredDateTime.includes(' / ')) { const [datePartStr, timePartStr] = requestData.preferredDateTime.split(' / '); if (datePartStr.includes('.')) { const [month, day, year] = datePartStr.split('.'); preferredDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`; } else { preferredDate = datePartStr; } try { const tempDate = new Date(`1970-01-01T${timePartStr}`); if (!isNaN(tempDate)) { preferredTime24h = tempDate.toTimeString().substring(0,5); } else { const timeMatch = timePartStr.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i); if (timeMatch) { let hours = parseInt(timeMatch[1], 10); const minutes = timeMatch[2]; const ampm = timeMatch[3].toUpperCase(); if (ampm === 'PM' && hours < 12) hours += 12; if (ampm === 'AM' && hours === 12) hours = 0; preferredTime24h = `${String(hours).padStart(2, '0')}:${minutes}`; } } } catch (e) { console.warn("Could not parse preferred time:", timePartStr); } } let dentistsOptionsHtml = '<option value="">-- Select Dentist --</option>'; if (typeof availableDentists !== 'undefined' && Array.isArray(availableDentists)) { availableDentists.forEach(dentist => { dentistsOptionsHtml += `<option value="${dentist.id || ''}">Dr. ${dentist.firstName || ''} ${dentist.lastName || ''}</option>`; }); } const patientIdHiddenInput = requestData.patient_id_for_booking ? `<input type="hidden" name="patient_id" value="${requestData.patient_id_for_booking}">` : ''; appointmentBookingModalBody.innerHTML = `<form id="confirmBookingFormModal" action="process_confirm_booking.php" method="POST"><p style="margin-bottom:15px;">Booking for: <strong>${requestData.patientName || 'N/A'}</strong></p>${patientIdHiddenInput}<div class="form-group"><label for="bookAttendingDentistModal">Assign Dentist:</label><select id="bookAttendingDentistModal" name="attending_dentist_id" class="form-control" required>${dentistsOptionsHtml}</select></div><div class="form-group form-group-date-time"><div class="form-group-half"><label for="bookAppointmentDateModal">Date:</label><input type="date" id="bookAppointmentDateModal" name="appointment_date" class="form-control" value="${preferredDate}" required></div><div class="form-group-half"><label for="bookAppointmentTimeModal">Time (24-hour):</label><input type="time" id="bookAppointmentTimeModal" name="appointment_time" class="form-control" value="${preferredTime24h}" required></div></div><div class="form-group"><label for="bookServiceTypeModal">Service Type:</label><input type="text" id="bookServiceTypeModal" name="service_type" class="form-control" value="${requestData.serviceType || ''}" required></div><div class="form-group"><label for="bookNotesModal">Notes (From Request):</label><textarea id="bookNotesModal" name="notes" class="form-control" rows="2">${requestData.patientMessage || ''}</textarea></div><input type="hidden" name="original_request_id" value="${requestData.requestId || ''}"></form>`; const dateInputBookingModal = document.getElementById('bookAppointmentDateModal'); if (dateInputBookingModal) { const today = new Date().toISOString().split('T')[0]; dateInputBookingModal.setAttribute('min', today); } }
    function addAppointmentModalFormElementListeners() { /* ... */ }
    if (requestModalCloseButton) requestModalCloseButton.addEventListener('click', closeRequestModal);
    if (requestModalOverlay) requestModalOverlay.addEventListener('click', (e) => { if (e.target === requestModalOverlay) closeRequestModal(); });
    if (appointmentBookingModalCloseButton) appointmentBookingModalCloseButton.addEventListener('click', closeAppointmentBookingModal);
    if (appointmentBookingModalOverlay) appointmentBookingModalOverlay.addEventListener('click', (e) => { if (e.target === appointmentBookingModalOverlay) closeAppointmentBookingModal(); });
    if (appointmentBookingModalFooter) { appointmentBookingModalFooter.addEventListener('click', (event) => { const target = event.target; if (target.id === 'confirmBookingFromRequestButton') { const form = appointmentBookingModalBody.querySelector('#confirmBookingFormModal'); if (form) { if (!form.checkValidity()) { alert('Please fill out all required fields.'); form.reportValidity(); return; } form.submit(); }} else if (target.id === 'cancelBookingFromRequestButton') { closeAppointmentBookingModal(); } }); }
    const dashboardContentArea = document.querySelector('.main-content .content-area');
    if (dashboardContentArea) { dashboardContentArea.addEventListener('click', (event) => { const targetLink = event.target.closest('a.action-view-request'); if (!targetLink) return; event.preventDefault(); const row = targetLink.closest('tr'); const requestId = row ? row.dataset.requestId : null; if (!requestId) return; let requestDataForModal = { requestId: requestId, patientName: row.cells[0]?.textContent.trim() || '', preferredDateTime: row.cells[1]?.textContent.trim() || '', serviceType: row.cells[2]?.textContent.trim() || '', patientMessage: "Loading..." }; if (typeof allAppointmentRequests !== 'undefined' && Array.isArray(allAppointmentRequests)) { const fullReqData = allAppointmentRequests.find(r => r.request_id == requestId); if (fullReqData) requestDataForModal.patientMessage = fullReqData.patient_message || "No message."; } openRequestDetailsModal(requestId, requestDataForModal); }); }
    
    // Initial call to update table visibility for both tables on this page
    updateTableVisibility('#today-appointments-table', '.no-data-row');
    updateTableVisibility('#appointment-requests-table', '.no-data-row');


    // --- RECEPTIONIST PROFILE EDIT LOGIC ---
    console.log('PROFILE EDIT: Attempting to set up profile edit listeners...');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const cancelEditProfileBtn = document.getElementById('cancelEditProfileBtn');
    const profileInfoView = document.getElementById('profileInfoView');
    const editProfileFormElement = document.getElementById('editProfileForm'); 

    const receptionistFullNameDisplay = document.getElementById('receptionistFullNameDisplay');
    const receptionistEmailDisplay = document.getElementById('receptionistEmailDisplay');
    const receptionistPhoneDisplay = document.getElementById('receptionistPhoneDisplay');
    const headerProfileCardName = document.getElementById('profileCardName'); 

    if (!editProfileBtn) { console.error('PROFILE EDIT ERROR: editProfileBtn not found!'); }
    if (!cancelEditProfileBtn) { console.error('PROFILE EDIT ERROR: cancelEditProfileBtn not found!'); }
    if (!profileInfoView) { console.error('PROFILE EDIT ERROR: profileInfoView not found!'); }
    if (!editProfileFormElement) { console.error('PROFILE EDIT ERROR: editProfileFormElement not found!'); }

    if (editProfileBtn && profileInfoView && editProfileFormElement && cancelEditProfileBtn) {
        console.log('PROFILE EDIT: All required elements found. Attaching listeners.');
        
        editProfileBtn.addEventListener('click', (event) => {
            event.preventDefault(); 
            event.stopPropagation(); 
            console.log('PROFILE EDIT: Edit Profile button was clicked.');
            
            editProfileFormElement.querySelectorAll('.info-input').forEach(input => input.style.borderColor = '');

            profileInfoView.style.display = 'none';
            editProfileFormElement.style.display = 'block';
            editProfileBtn.style.display = 'none'; 
            console.log('PROFILE EDIT: Switched to edit view.');
        });

        cancelEditProfileBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            console.log('PROFILE EDIT: Cancel Edit Profile button clicked.');
            profileInfoView.style.display = 'block';
            editProfileFormElement.style.display = 'none';
            editProfileBtn.style.display = 'inline-block'; 
            editProfileFormElement.reset(); 
            editProfileFormElement.querySelectorAll('.info-input').forEach(input => input.style.borderColor = '');
            console.log('PROFILE EDIT: Switched back to view mode, form reset.');
        });

        editProfileFormElement.addEventListener('submit', async (event) => {
            event.preventDefault();
            console.log('PROFILE EDIT: Edit Profile form submitted.');
            
            editProfileFormElement.querySelectorAll('.info-input').forEach(input => input.style.borderColor = '');

            const formData = new FormData(editProfileFormElement);
            const saveButton = editProfileFormElement.querySelector('button[type="submit"]');
            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';

            console.log("PROFILE EDIT: Form data being sent:");
            for (let [key, value] of formData.entries()) {
                console.log(`PROFILE EDIT: ${key} = ${value}`);
            }

            try {
                const response = await fetch('update_receptionist_profile.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const errorText = await response.text(); 
                    console.error('PROFILE EDIT: Server responded with an error:', response.status, errorText);
                    alert(`Error: ${response.status} - ${errorText || 'Server error processing the request.'}`);
                } else {
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        const errorText = await response.text();
                        console.error("PROFILE EDIT: Expected JSON, but got:", contentType, errorText);
                        alert("Received an unexpected response format from the server.");
                    } else {
                        const result = await response.json();
                        console.log('PROFILE EDIT: Profile update response:', result);

                        if (result.success) {
                            if (result.updated_data) {
                                if(receptionistFullNameDisplay) receptionistFullNameDisplay.textContent = `${result.updated_data.firstName} ${result.updated_data.lastName}`;
                                if(receptionistEmailDisplay) receptionistEmailDisplay.textContent = result.updated_data.email;
                                if(receptionistPhoneDisplay) receptionistPhoneDisplay.textContent = result.updated_data.phoneNumber || 'N/A';
                                if(headerProfileCardName) headerProfileCardName.textContent = `${result.updated_data.firstName} ${result.updated_data.lastName}`;

                                document.getElementById('profileFirstName').value = result.updated_data.firstName;
                                document.getElementById('profileLastName').value = result.updated_data.lastName;
                                document.getElementById('profileEmail').value = result.updated_data.email;
                                document.getElementById('profilePhoneNumber').value = result.updated_data.phoneNumber || '';
                            }
                            profileInfoView.style.display = 'block';
                            editProfileFormElement.style.display = 'none';
                            editProfileBtn.style.display = 'inline-block';
                            
                            if (result.message && result.message !== 'No changes were made to the profile.') {
                                 alert(result.message); 
                                 window.location.reload(); 
                            } else if (result.message) { 
                                alert(result.message);
                            }

                        } else {
                            let errorMsg = result.message || 'Failed to update profile.';
                            if (result.errors) {
                                let firstErrorField = null;
                                for (const key in result.errors) {
                                    const fieldWithError = document.getElementById(`profile${key.charAt(0).toUpperCase() + key.slice(1)}`);
                                    if(fieldWithError) {
                                        fieldWithError.style.borderColor = 'red';
                                        fieldWithError.title = result.errors[key];
                                        if (!firstErrorField) firstErrorField = fieldWithError;
                                    }
                                }
                                if (firstErrorField) firstErrorField.focus();
                                alert(errorMsg + " Please check the highlighted fields.");
                            } else {
                               alert(errorMsg); 
                            }
                        }
                    } 
                } 
            } catch (error) {
                console.error('PROFILE EDIT: Error in fetch/submit profile:', error);
                alert('A network error occurred or the server response was unreadable. Please check console and try again.');
            } finally {
                saveButton.disabled = false;
                saveButton.textContent = 'Save Changes';
                console.log('PROFILE EDIT: Form submission process finished.');
            }
        });
    } else {
        console.error('PROFILE EDIT CRITICAL: One or more profile elements for editing are missing from the DOM. Edit functionality will not work.');
    }
    // --- END RECEPTIONIST PROFILE EDIT LOGIC ---

}); // End DOMContentLoaded