// receptionist_dashboard_logic.js

document.addEventListener('DOMContentLoaded', () => {

    // --- Helper function to update table visibility ---
    function updateTableVisibility(tableId, noDataRowSelector) {
        const tableBody = document.querySelector(`${tableId} tbody`);
        if (!tableBody) {
             console.warn(`Table body not found for ID: ${tableId}`);
             return;
        }
        const noDataRow = tableBody.querySelector(noDataRowSelector);
        if (!noDataRow) {
            console.warn(`No data row not found for selector: ${noDataRowSelector} in table ID: ${tableId}`);
            return;
        }

        const dataRows = tableBody.querySelectorAll('tr:not(.no-data-row)');

        let hasVisibleRows = false;
        dataRows.forEach(row => {
             // Check if the row is visible (not explicitly hidden by JS display: none)
            if (row.style.display !== 'none') {
                hasVisibleRows = true;
            }
        });

        if (hasVisibleRows) {
            noDataRow.style.display = 'none';
        } else {
            noDataRow.style.display = 'table-row';
        }
    }

    // --- AM/PM Button Toggle (Specific to elements on this page) ---
    // This needs to be included if time input groups appear outside modals on this page,
    // or if modal logic doesn't re-attach listeners itself.
    // In our case, the request modal logic re-attaches, so we don't need a global one here.

    // --- Modal Logic for Request Actions (Accept/Decline) ---
    const requestModalOverlay = document.getElementById('requestModalOverlay');
    const requestModalContent = document.getElementById('requestModalContent');
    const requestModalTitle = document.getElementById('requestModalTitle');
    const requestModalBody = document.getElementById('requestModalBody');
    const requestModalFooter = document.getElementById('requestModalFooter');
    const requestModalCloseButton = document.getElementById('requestModalCloseButton');

    // Function to open the request action modal
    function openRequestModal(type, requestId, requestData) {
         if (!requestModalOverlay || !requestModalTitle || !requestModalBody || !requestModalFooter) {
            console.error("Request modal elements not found!");
            return;
         }
        requestModalOverlay.classList.add('visible');
        requestModalBody.innerHTML = ''; // Clear previous content
        requestModalFooter.innerHTML = ''; // Clear footer buttons
        requestModalFooter.style.display = 'flex'; // Assume footer needed for actions

        // --- Set Modal Content Based on Type ---
        if (type === 'accept') {
            requestModalTitle.textContent = 'Accept Appointment Request';
            displayAcceptRequestForm(requestId, requestData); // Show booking form
            addModalFooterButton(requestModalFooter, 'Confirm Booking', 'btn-primary', 'confirmBookingButton');
        } else if (type === 'decline') {
            requestModalTitle.textContent = 'Decline Appointment Request';
            displayDeclineRequestForm(requestId, requestData); // Show decline message form
             addModalFooterButton(requestModalFooter, 'Send Decline Message', 'btn-primary', 'sendDeclineButton');
             addModalFooterButton(requestModalFooter, 'Cancel', 'btn-secondary', 'cancelDeclineButton');
        } else {
            requestModalTitle.textContent = 'Request Details';
            requestModalBody.innerHTML = '<p>Invalid request action.</p>';
             requestModalFooter.style.display = 'none';
        }

         // Add event listeners specific to the content if needed (e.g., AM/PM buttons in accept form)
         addRequestModalContentListeners();
    }

    // Function to close the request action modal
    function closeRequestModal() {
         if (!requestModalOverlay) return;
        requestModalOverlay.classList.remove('visible');
    }

     // Helper to add a button to the modal footer
     function addModalFooterButton(footerElement, text, classes, id) {
         const button = document.createElement('button');
         button.textContent = text;
         button.className = 'btn ' + classes;
         button.id = id;
         footerElement.appendChild(button);
         return button;
     }


    // Function to display the booking form for Accepting a request
    function displayAcceptRequestForm(requestId, requestData) {
         if (!requestModalBody) return;
         console.log(`Displaying accept form for request ID: ${requestId}`, requestData);

         let preferredDate = '';
         let preferredTimeValue = '09:00';
         let isPM = false;
         let isAM = true;

         if (requestData.preferredDateTime && requestData.preferredDateTime.includes('/')) {
              const [datePart, timePartWithAmpm] = requestData.preferredDateTime.split(' / ');
              if (datePart && datePart.includes('.')) {
                   const [month, day, year] = datePart.split('.');
                   preferredDate = `${year}-${String(parseInt(month, 10)).padStart(2, '0')}-${String(parseInt(day, 10)).padStart(2, '0')}`;
              }
              if (timePartWithAmpm) {
                  let [timeOnly, ampm] = timePartWithAmpm.split(' ');
                  let [hours, minutes] = timeOnly.split(':');
                  hours = parseInt(hours, 10);
                  minutes = parseInt(minutes, 10);
                   if (!isNaN(hours) && !isNaN(minutes)) {
                       preferredTimeValue = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                       if (ampm) {
                            isPM = ampm.toUpperCase() === 'PM';
                            isAM = !isPM;
                       }
                   }
              }
         }

         requestModalBody.innerHTML = `
              <form id="acceptRequestForm" data-request-id="${requestId}">
                  <p>Review and confirm appointment details for:</p>
                   <div class="form-group">
                      <label for="acceptPatientName">Patient Name:</label>
                      <input type="text" id="acceptPatientName" class="form-control" value="${requestData.patientName || ''}" disabled>
                  </div>

                   <div class="form-group">
                      <label for="acceptPurpose">Purpose of Visit:</label>
                      <textarea id="acceptPurpose" class="form-control" rows="2" disabled>${requestData.purpose || ''}</textarea>
                  </div>

                  <hr class="content-separator">

                   <div class="form-group">
                      <label for="assignDentist">Assign Dentist:</label>
                      <select id="assignDentist" name="dentist_id" class="form-control" required>
                          <option value="">-- Select Dentist --</option>
                          <!-- Populate dynamically from backend -->
                          <option value="dentist1">Dr. Smith</option>
                          <option value="dentist2">Dr. Lee</option>
                          <!-- Add more dentist options -->
                      </select>
                  </div>

                  <div class="form-group form-group-date-time">
                      <div class="form-group-half">
                          <label for="acceptAppointmentDate">Date:</label>
                          <input type="date" id="acceptAppointmentDate" name="appointment_date" class="form-control" value="${preferredDate}" required>
                      </div>
                      <div class="form-group-half">
                           <label for="acceptAppointmentTime">Time:</label>
                            <div class="time-input-group">
                                <input type="text" id="acceptAppointmentTime" name="appointment_time" class="form-control time-input" placeholder="HH:MM" value="${preferredTimeValue}" required pattern="[0-9]{2}:[0-9]{2}">
                                <button type="button" class="ampm-btn ${isAM ? 'active' : ''}">AM</button>
                                <button type="button" class="ampm-btn ${isPM ? 'active' : ''}">PM</button>
                            </div>
                             <small class="form-help">Enter time as HH:MM (e.g., 09:00, 01:30).</small>
                      </div>
                  </div>
                   <input type="hidden" name="request_id" value="${requestId}">
              </form>
         `;
    }

     // Function to display the form for Declining a request
     function displayDeclineRequestForm(requestId, requestData) {
         if (!requestModalBody) return;
          console.log(`Displaying decline form for request ID: ${requestId}`, requestData);

          requestModalBody.innerHTML = `
              <form id="declineRequestForm" data-request-id="${requestId}">
                   <p>Send a message to ${requestData.patientName || 'the patient'} regarding their request for ${requestData.preferredDateTime || 'the requested date/time'}.</p>

                   <div class="form-group">
                       <label for="declineReason">Reason for Decline:</label>
                       <select id="declineReason" name="decline_reason_template" class="form-control">
                           <option value="">-- Select a reason or write below --</option>
                           <option value="dentist_unavailable">Dentist unavailable at requested time.</option>
                           <option value="fully_booked">Practice is fully booked at that time.</option>
                           <option value="service_not_offered">Requested service not offered.</option>
                            <option value="patient_details_missing">Missing required patient details.</option>
                           <option value="other">Other (specify below)</option>
                       </select>
                   </div>

                  <div class="form-group">
                      <label for="declineMessage">Message to Patient:</label>
                      <textarea id="declineMessage" name="decline_message" class="form-control" rows="6" required placeholder="e.g., Dear [Patient Name], Unfortunately, Dr. [Dentist Name] is not available at your requested time of [Time] on [Date]..."></textarea>
                       <small class="form-help">Customize the message based on the reason and suggest alternatives if possible.</small>
                  </div>
                   <input type="hidden" name="request_id" value="${requestId}">
              </form>
          `;

          const declineReasonSelect = requestModalBody.querySelector('#declineReason');
          const declineMessageTextarea = requestModalBody.querySelector('#declineMessage');
          if(declineReasonSelect && declineMessageTextarea) {
              declineReasonSelect.addEventListener('change', (event) => {
                   const selectedTemplate = event.target.value;
                   const patientName = requestData.patientName || 'the patient';
                   const dateTime = requestData.preferredDateTime || 'the requested time';
                   const purpose = requestData.purpose || 'the requested service';
                   let messageTemplate = '';
                   switch(selectedTemplate) {
                       case 'dentist_unavailable': messageTemplate = `Dear ${patientName},\n\nUnfortunately, the dentist you requested is not available at your requested time of ${dateTime} for your ${purpose}. We apologize for the inconvenience.\n\nPlease contact us to discuss alternative times or other available dentists.\n\nSincerely,\nEscosia Dental Clinic`; break;
                       case 'fully_booked': messageTemplate = `Dear ${patientName},\n\nThank you for your appointment request for ${dateTime} for your ${purpose}. Unfortunately, we are fully booked at that specific time.\n\nWe could offer you an appointment on [Alternative Date] at [Alternative Time]. Please contact us to reschedule.\n\nSincerely,\nEscosia Dental Clinic`; break;
                       case 'service_not_offered': messageTemplate = `Dear ${patientName},\n\nThank you for your request regarding ${purpose}. We currently do not offer this specific service at our clinic.\n\nWe recommend you consult with a specialist in [Suggested Field]. You can find more information on common dental services on our website.\n\nSincerely,\nEscosia Dental Clinic`; break;
                       case 'patient_details_missing': messageTemplate = `Dear ${patientName},\n\nThank you for your appointment request. We need some additional information to process your request for ${dateTime} (${purpose}).\n\nPlease contact us by phone or reply to this message with the required details.\n\nSincerely,\nEscosia Dental Clinic`; break;
                       case 'other': default: messageTemplate = `Dear ${patientName},\n\nRegarding your appointment request for ${dateTime} (${purpose})...\n\nSincerely,\nEscosia Dental Clinic`; break;
                   }
                   declineMessageTextarea.value = messageTemplate;
              });
          }
     }

     // Helper to add listeners to elements inside the request modal body after it's loaded
     function addRequestModalContentListeners() {
          if (!requestModalBody) return;
           const modalAmpmButtons = requestModalBody.querySelectorAll('.time-input-group .ampm-btn');
            modalAmpmButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const parentGroup = button.closest('.time-input-group');
                    if (parentGroup) {
                         parentGroup.querySelectorAll('.ampm-btn').forEach(btn => btn.classList.remove('active'));
                         button.classList.add('active');
                    }
                });
            });
     }


    // --- Event Listeners for Request Modal ---
    if (requestModalCloseButton) { requestModalCloseButton.addEventListener('click', closeRequestModal); }
    if (requestModalOverlay) { requestModalOverlay.addEventListener('click', (event) => { if (event.target === requestModalOverlay) { closeRequestModal(); } }); }

     if (requestModalFooter) {
         requestModalFooter.addEventListener('click', (event) => {
              const target = event.target;
              if (target.id === 'confirmBookingButton') {
                   console.log("Confirm Booking button clicked.");
                   const acceptForm = requestModalBody ? requestModalBody.querySelector('#acceptRequestForm') : null;
                   if (acceptForm) { handleAcceptRequestBooking(acceptForm); } else { console.error("Accept request form not found."); }
              } else if (target.id === 'sendDeclineButton') {
                  console.log("Send Decline Message button clicked.");
                   const declineForm = requestModalBody ? requestModalBody.querySelector('#declineRequestForm') : null;
                   if (declineForm) { handleDeclineMessage(declineForm); } else { console.error("Decline form not found."); }
              } else if (target.id === 'cancelDeclineButton') {
                   console.log("Cancel Decline clicked."); closeRequestModal();
              }
         });
     }


     // Function to handle the Accept Request Booking Form Submission
     function handleAcceptRequestBooking(form) {
         const requestId = form.dataset.requestId;
          if (!requestId) { console.error("Request ID not found on accept form."); alert("Error: Cannot confirm booking."); return; }
          if (!form.checkValidity()) { alert('Please fill out all required fields in the booking form.'); return; }

         const formData = new FormData(form);
         const bookingData = {}; formData.forEach((value, key) => { bookingData[key] = value; });
         const selectedAmpmButton = form.querySelector('.ampm-btn.active');
         bookingData['appointment_time_ampm'] = selectedAmpmButton ? selectedAmpmButton.textContent.trim() : 'AM';

         console.log("Submitting booking for request ID:", requestId, bookingData);
         // --- TODO: Add AJAX/Fetch API call here to tell PHP to book the appointment and mark request as accepted ---

         alert(`Booking simulated for request ID ${requestId}.\nAppointment details: ${JSON.stringify(bookingData, null, 2)}\nCheck console for data.`);
         closeRequestModal();
         const requestRow = document.querySelector(`#appointment-requests-table tbody tr[data-request-id="${requestId}"]`);
         if(requestRow) { requestRow.remove(); updateTableVisibility('#appointment-requests-table', '.no-data-row'); }
     }

     // Function to handle the Decline Message Submission
     function handleDeclineMessage(form) {
         const requestId = form.dataset.requestId;
          if (!requestId) { console.error("Request ID not found on decline form."); alert("Error: Cannot send decline message."); return; }
           const declineMessageTextarea = form.querySelector('#declineMessage');
           if (!declineMessageTextarea || declineMessageTextarea.value.trim() === '') {
               alert('Please enter a message to the patient.');
               return;
           }

         const formData = new FormData(form);
         const declineData = { request_id: requestId };
         formData.forEach((value, key) => { declineData[key] = value; });

         console.log("Submitting decline for request ID:", requestId, declineData);
         // --- TODO: Add AJAX/Fetch API call here to tell PHP to decline the request and potentially send message ---

         alert(`Decline simulated for request ID ${requestId}.\nMessage content: "${declineData.decline_message}"\nCheck console for data.`);
         closeRequestModal();
         const requestRow = document.querySelector(`#appointment-requests-table tbody tr[data-request-id="${requestId}"]`);
         if(requestRow) { requestRow.remove(); updateTableVisibility('#appointment-requests-table', '.no-data-row'); }
     }


    // --- Action Link Handlers for Tables on Dashboard Page ---
    // This handles Accept/Decline on the Requests list and potentially View/Edit/Cancel on Today's Appointments
     const requestsTable = document.getElementById('appointment-requests-table');
     const todayAppointmentsTable = document.getElementById('today-appointments-table'); // Get today's appointments table

     // Use a single listener on the main content area and check the target's table
     const dashboardContentArea = document.querySelector('.main-content .content-area'); // Assuming .main-content > .content-area wraps tables

     if (dashboardContentArea) {
         dashboardContentArea.addEventListener('click', (event) => {
             const target = event.target;

             // --- Handle Clicks on #appointment-requests-table ---
             if (requestsTable && requestsTable.contains(target)) { // Check if click is inside the requests table
                 if (target.tagName === 'A' && (target.classList.contains('action-accept') || target.classList.contains('action-decline'))) {
                     const actionLink = target;
                     const row = actionLink.closest('tr');
                     const requestId = row ? row.dataset.requestId : null;

                     if (!row || !requestId) { console.warn("Could not find request ID or row for action:", target); alert("Error: Could not identify the request."); return; }
                     event.preventDefault();
                     const requestData = {
                         patientName: row.cells[0] ? row.cells[0].textContent.trim() : 'N/A',
                         preferredDateTime: row.cells[1] ? row.cells[1].textContent.trim() : 'N/A',
                         purpose: row.cells[2] ? row.cells[2].textContent.trim() : 'N/A',
                     };

                     if (actionLink.classList.contains('action-accept')) {
                         console.log(`Action: ACCEPT request ID ${requestId}`);
                         openRequestModal('accept', requestId, requestData);
                     } else if (actionLink.classList.contains('action-decline')) {
                          console.log(`Action: DECLINE request ID ${requestId}`);
                          openRequestModal('decline', requestId, requestData);
                     }
                 }
             }

             // --- Handle Clicks on #today-appointments-table ---
             if (todayAppointmentsTable && todayAppointmentsTable.contains(target)) { // Check if click is inside today's appointments table
                  // Add logic here if you want View/Edit/Cancel on Today's Appointments table on the Dashboard
                  // This would likely use the appointmentModalOverlay and its associated functions
                  // You'd need to add data-appointment-id to rows in #today-appointments-table
                  /*
                   if (target.tagName === 'A' && (target.classList.contains('action-view') || target.classList.contains('action-edit') || target.classList.contains('action-cancel'))) {
                        const actionLink = target;
                         const row = actionLink.closest('tr');
                         const appointmentId = row ? row.dataset.appointmentId : null; // Need data-appointment-id here

                         if (!row || !appointmentId) { console.warn("Could not find today's appointment ID or row:", target); alert("Error: Could not identify the appointment."); return; }
                         event.preventDefault();
                         // Extract row data for this table
                         const rowData = { // Needs correct cell indices
                              patientName: row.cells[0] ? row.cells[0].textContent.trim() : 'N/A',
                              dateTime: row.cells[1] ? row.cells[1].textContent.trim() : 'N/A', // Date and Time combined
                              purpose: row.cells[2] ? row.cells[2].textContent.trim() : 'N/A',
                              // Dentist/Status likely not here
                         };
                         // Parse date/time if needed by openAppointmentModal
                         let date = 'N/A', time = 'N/A';
                         if(rowData.dateTime.includes(' / ')) { [date, time] = rowData.dateTime.split(' / '); } else { date = rowData.dateTime; }

                         if (actionLink.classList.contains('action-view')) {
                             // Call the appointment modal function (if appointmentModalOverlay is on this page)
                              if (typeof openAppointmentModal === 'function') { // Check if the function exists
                                 openAppointmentModal('view', appointmentId, { ...rowData, date: date, time: time, dentist: 'N/A', status: 'Confirmed' });
                              } else { alert(`Viewing details for appointment ID ${appointmentId}.\n(Modal logic not included on this page)`); } // Fallback
                         } else if (actionLink.classList.contains('action-edit')) {
                             // Similar call to openAppointmentModal('edit', ...) if edit needed here
                         } else if (actionLink.classList.contains('action-cancel')) {
                              // Similar confirm and simulation/backend call for cancellation
                         }
                    }
                  */
             }

         });
     }
    // --- End Action Link Handlers ---


    // --- Book New Appointment Form Submission (Keep if form is on Dashboard too) ---
    // Note: The original dashboard HTML didn't have the booking form.
    // If you added it, keep this block. If not, you can remove it.
    const bookAppointmentForm = document.getElementById('book-appointment-form');
    if (bookAppointmentForm) {
        bookAppointmentForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!bookAppointmentForm.checkValidity()) { alert('Please fill out all required fields.'); return; }
            const formData = new FormData(bookAppointmentForm);
            const appointmentData = {}; formData.forEach((value, key) => { appointmentData[key] = value; });
            const selectedAmpmButton = bookAppointmentForm.querySelector('.ampm-btn.active');
            appointmentData['appointment_time_ampm'] = selectedAmpmButton ? selectedAmpmButton.textContent.trim() : 'AM';
            console.log('Booking new appointment data:', appointmentData);
            // --- TODO: Add AJAX/Fetch API call here ---
            alert('Appointment booking simulated.\nCheck console for data.');
            bookAppointmentForm.reset();
        });
    }


    // --- Initial Table Visibility Checks ---
    updateTableVisibility('#today-appointments-table', '.no-data-row'); // Dashboard table
    updateTableVisibility('#appointment-requests-table', '.no-data-row'); // Dashboard table
    // If you have the #all-appointments-table on the dashboard, also check it:
    // updateTableVisibility('#all-appointments-table', '.no-data-row');


});

