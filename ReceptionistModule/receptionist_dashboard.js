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
            if (row.style.display !== 'none') { // Check if the row is not hidden
                hasVisibleRows = true;
            }
        });

        if (hasVisibleRows) {
            noDataRow.style.display = 'none';
        } else {
            noDataRow.style.display = 'table-row';
        }
    }


    // --- Calendar Logic (Lives in common_calendar.js) ---
    // This logic is handled by common_calendar.js. Make sure it's included.


    // --- AM/PM Button Toggle (Specific to forms on this page) ---
    // This handler is needed for the booking form that will appear in the appointment modal
    const allAmpmButtons = document.querySelectorAll('#appointmentModalBody .ampm-btn'); // Target buttons within the appointment modal body
     if (allAmpmButtons.length > 0) {
         allAmpmButtons.forEach(button => {
             button.addEventListener('click', () => {
                 const parentGroup = button.closest('.time-input-group');
                 if (parentGroup) {
                      parentGroup.querySelectorAll('.ampm-btn').forEach(btn => btn.classList.remove('active'));
                      button.classList.add('active');
                 }
             });
         });
     }


    // --- Modal Logic for Request Details (Reuse requestModalOverlay) ---
    const requestModalOverlay = document.getElementById('requestModalOverlay');
    const requestModalContent = document.getElementById('requestModalContent');
    const requestModalTitle = document.getElementById('requestModalTitle');
    const requestModalBody = document.getElementById('requestModalBody');
    const requestModalFooter = document.getElementById('requestModalFooter'); // Footer will be hidden/empty for details view
    const requestModalCloseButton = document.getElementById('requestModalCloseButton'); // Make sure this ID matches HTML


     // Function to open the request details modal
    function openRequestDetailsModal(requestId, requestData) { // requestData for simulation
         if (!requestModalOverlay || !requestModalTitle || !requestModalBody || !requestModalFooter) {
            console.error("Request modal elements not found!");
            return;
         }
        requestModalOverlay.classList.add('visible');
        requestModalBody.innerHTML = ''; // Clear previous content
        requestModalFooter.style.display = 'none'; // Hide footer

        // --- Set Modal Content ---
        requestModalTitle.textContent = 'Appointment Request Details';
        displayRequestDetailsContent(requestId, requestData); // Display details content

        // No form-specific listeners needed for this view
    }

    // Function to close the request modal
    function closeRequestModal() {
         if (!requestModalOverlay) return;
        requestModalOverlay.classList.remove('visible');
    }

     // Function to display the details of a request in the modal body
     // Includes the "Accept and Book" button directly in the body
     function displayRequestDetailsContent(requestId, requestData) {
         if (!requestModalBody) return;
          console.log(`Displaying details for request ID: ${requestId}`, requestData);

          // --- TODO: In a real app, FETCH full request data from backend using requestId ---
          // Include patient's message/notes in the fetched data
          /*
          requestModalBody.innerHTML = '<p>Loading details...</p>'; // Show loading
          fetch(`/api/get_request_details.php?id=${requestId}`) // Replace with your endpoint
          .then(response => {
               if (!response.ok) throw new Error('Failed to fetch details');
               return response.json(); // Assuming backend returns full details including patient message
           })
          .then(fullData => {
               // Render the fetched data into the modal body
                requestModalBody.innerHTML = `
                   <p><strong>Request ID:</strong> ${requestId}</p>
                   <p><strong>Patient Name:</strong> ${fullData.patientName || 'N/A'}</p>
                   <p><strong>Preferred Date & Time:</strong> ${fullData.preferredDateTime || 'N/A'}</p>
                   <p><strong>Purpose:</strong> ${fullData.purpose || 'N/A'}</p>
                   <p><strong>Patient's Message/Notes:</strong><br>${fullData.patientMessage || 'None provided.'}</p>
                   <!-- Add any other relevant request details -->
                   <hr class="content-separator">
                   <div class="form-group" style="margin-bottom: 0;"> <!-- Use form-group styling -->
                        <button class="btn btn-primary" id="modalAcceptAndBookButton" data-request-id="${requestId}">Accept and Book</button>
                   </div>
                `;
                // Add listener to the button within the modal body
                 addRequestDetailsButtonListeners();
            })
           .catch(error => {
               console.error("Error fetching request details:", error);
                requestModalBody.innerHTML = '<p>Error loading request details.</p>';
            });
          */

          // --- Current Simulation (using passed rowData and adding a placeholder message) ---
          if (!requestData) { // Fallback if no row data was passed
               requestModalBody.innerHTML = `<p>Simulated Details Modal for Request ID: ${requestId}</p><p>Full details would be fetched from backend.</p>`;
               requestData = { patientName: 'N/A', preferredDateTime: 'N/A', purpose: 'N/A' }; // Use default placeholder
          }

          // Simulate adding a placeholder message (in a real app, fetch this from backend)
          const simulatedPatientMessage = "Patient prefers morning appointments if possible.";


          requestModalBody.innerHTML = `
             <p><strong>Request ID:</strong> ${requestId}</p>
             <p><strong>Patient Name:</strong> ${requestData.patientName || 'N/A'}</p>
             <p><strong>Preferred Date & Time:</strong> ${requestData.preferredDateTime || 'N/A'}</p>
             <p><strong>Purpose:</strong> ${requestData.purpose || 'N/A'}</p>
             <p><strong>Patient's Message/Notes:</strong><br>${requestData.patientMessage || simulatedPatientMessage}</p> <!-- Use placeholder message -->
             <!-- Add any other relevant request details -->
             <hr class="content-separator">
             <div class="form-group" style="margin-bottom: 0;">
                  <!-- Add the action button directly here -->
                  <button class="btn btn-primary" id="modalAcceptAndBookButton" data-request-id="${requestId}">Accept and Book</button>
                  <!-- The decline button is removed from here -->
             </div>
          `;
           // Add listener to the button within the modal body
           addRequestDetailsButtonListeners();
          // --- End Simulation ---
     }

     // Helper to add listeners to buttons WITHIN the request details modal body
     function addRequestDetailsButtonListeners() {
         if (!requestModalBody) return;
         // Listener for the "Accept and Book" button inside the request details modal
         requestModalBody.querySelector('#modalAcceptAndBookButton').addEventListener('click', handleModalAcceptAndBookClick);
     }

     // Handler for the "Accept and Book" button *INSIDE* the request details modal
     function handleModalAcceptAndBookClick(event) {
          const requestId = event.target.dataset.requestId;
          console.log(`Modal 'Accept and Book' button clicked for request ID: ${requestId}`);

          // --- TODO: Close the request details modal and OPEN the appointment booking modal ---

          // First, close the current request details modal
          closeRequestModal();

          // Then, find the original request row to get data for pre-filling the booking form
           const requestRow = document.querySelector(`#appointment-requests-table tbody tr[data-request-id="${requestId}"]`);
           const requestData = requestRow ? {
               patientName: requestRow.cells[0] ? requestRow.cells[0].textContent.trim() : 'N/A',
               preferredDateTime: requestRow.cells[1] ? requestRow.cells[1].textContent.trim() : 'N/A',
               purpose: requestRow.cells[2] ? requestRow.cells[2].textContent.trim() : 'N/A',
               // Need full patient details (like Patient ID for the select) and dentist list
               // ideally fetched from backend when opening the booking modal
           } : null;

           if (requestData) {
               // Open the separate Appointment Booking Modal ('appointmentModalOverlay')
               // Pass request data so the booking form can be pre-filled
                console.log("Opening Appointment Booking Modal...");
                // Using 'book' type for the appointment modal
                openAppointmentModal('book', requestId, requestData); // Pass request ID and data
           } else {
               console.error("Could not retrieve request data from row for booking.");
               alert("Error: Cannot proceed with booking.");
           }

     }


    // --- Modal Logic for Appointment Booking (Copy/Adapt from receptionist_appointments_logic.js) ---
    // This logic uses the appointmentModalOverlay structure which should be in dashboard.html
    const appointmentModalOverlay = document.getElementById('appointmentModalOverlay');
    const appointmentModalContent = document.getElementById('appointmentModalContent');
    const appointmentModalTitle = document.getElementById('appointmentModalTitle');
    const appointmentModalBody = document.getElementById('appointmentModalBody');
    const appointmentModalFooter = document.getElementById('appointmentModalFooter'); // Footer for this modal
    const appointmentModalCloseButton = document.getElementById('appointmentModalCloseButton'); // Close button for this modal


     // Helper to add a button to the appointment modal footer
     function addAppointmentModalFooterButton(text, classes, id) {
         if (!appointmentModalFooter) return;
         const button = document.createElement('button');
         button.textContent = text;
         button.className = 'btn ' + classes;
         button.id = id;
         appointmentModalFooter.appendChild(button);
         return button;
     }

     // Function to open the appointment modal (used for booking from request)
     // Add a 'book' type to the existing openAppointmentModal logic structure
    function openAppointmentModal(type, id, data = null) { // id is request ID for 'book' type
         if (!appointmentModalOverlay || !appointmentModalTitle || !appointmentModalBody || !appointmentModalFooter) {
            console.error("Appointment modal elements not found!");
            return;
         }
        appointmentModalOverlay.classList.add('visible');
        appointmentModalBody.innerHTML = ''; // Clear previous content
        appointmentModalFooter.innerHTML = ''; // Clear footer buttons
        appointmentModalFooter.style.display = 'flex'; // Default to flex for forms

        // --- Set Modal Content and Footer Buttons Based on Type ---
        if (type === 'book') { // New type for booking from request
             appointmentModalTitle.textContent = 'Book Appointment';
             // Pass request ID and data to the booking form display
             displayBookAppointmentForm(id, data);
             addAppointmentModalFooterButton('Confirm Booking', 'btn-primary', 'confirmBookingButton'); // Add confirm button
             addAppointmentModalFooterButton('Cancel', 'btn-secondary', 'cancelBookingButton'); // Add cancel button
        }
        // Note: If you also added View/Edit actions for Today's Appointments on the dashboard,
        // you would add 'view' and 'edit' cases here, along with their display functions
        // and corresponding footer buttons/listeners.
        /*
        else if (type === 'view') { ... displayAppointmentDetails(id, data); ... }
        else if (type === 'edit') { ... displayEditAppointmentForm(id, data); ... }
        */
        else {
             appointmentModalTitle.textContent = 'Modal Error'; appointmentModalFooter.style.display = 'none';
              appointmentModalBody.innerHTML = '<p>Invalid modal type.</p>';
        }

        // Re-attach listeners for form elements inside this modal (like AM/PM) if they exist
         addAppointmentModalFormListeners();
    }

    // Function to close the appointment modal
    function closeAppointmentModal() {
        if (!appointmentModalOverlay) return;
        appointmentModalOverlay.classList.remove('visible');
    }


    // Function to display the booking form for Accepting/Booking an appointment from a request
    // Adapted from previous displayAcceptRequestForm or displayEditAppointmentForm
    function displayBookAppointmentForm(requestId, requestData) { // Uses requestId here
         if (!appointmentModalBody) return;
         console.log(`Displaying booking form for request ID: ${requestId}`, requestData);

         // --- Parse Preferred Date and Time from requestData ---
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

         appointmentModalBody.innerHTML = `
              <form id="bookAppointmentFromRequestForm" data-request-id="${requestId}">
                  <p>Confirm appointment details for:</p>
                   <div class="form-group">
                      <label for="bookPatientName">Patient Name:</label>
                      <!-- In a real app, this would be a SELECT or search input linked to patient records -->
                      <!-- For now, display from request data -->
                      <input type="text" id="bookPatientName" class="form-control" value="${requestData.patientName || 'N/A'}" disabled>
                      <!-- Hidden input for actual Patient ID needed by backend -->
                       <!-- <input type="hidden" name="patient_id" value="FETCH_PATIENT_ID_FROM_BACKEND"> -->
                   </div>

                   <div class="form-group">
                      <label for="bookPurpose">Purpose of Visit:</label>
                      <textarea id="bookPurpose" name="purpose" class="form-control" rows="2" required>${requestData.purpose || ''}</textarea> <!-- Purpose can often be edited slightly -->
                  </div>

                  <hr class="content-separator"> <!-- Small separator -->

                   <div class="form-group">
                      <label for="bookAssignDentist">Assign Dentist:</label>
                      <select id="bookAssignDentist" name="dentist_id" class="form-control" required>
                          <option value="">-- Select Dentist --</option>
                          <!-- Populate dynamically from backend -->
                          <option value="dentist1">Dr. Smith</option>
                          <option value="dentist2">Dr. Lee</option>
                          <!-- Add more dentist options -->
                      </select>
                       <small class="form-help">Select the dentist for this appointment.</small>
                  </div>

                  <div class="form-group form-group-date-time">
                      <div class="form-group-half">
                          <label for="bookAppointmentDate">Date:</label>
                          <input type="date" id="bookAppointmentDate" name="appointment_date" class="form-control" value="${preferredDate}" required>
                      </div>
                      <div class="form-group-half">
                           <label for="bookAppointmentTime">Time:</label>
                            <div class="time-input-group">
                                <input type="text" id="bookAppointmentTime" name="appointment_time" class="form-control time-input" placeholder="HH:MM" value="${preferredTimeValue}" required pattern="[0-9]{2}:[0-9]{2}">
                                <button type="button" class="ampm-btn ${isAM ? 'active' : ''}">AM</button>
                                <button type="button" class="ampm-btn ${isPM ? 'active' : ''}">PM</button>
                            </div>
                             <small class="form-help">Enter time as HH:MM (e.g., 09:00, 01:30).</small>
                      </div>
                  </div>
                   <!-- Hidden input for the original request ID being processed -->
                   <input type="hidden" name="request_id" value="${requestId}">
              </form>
         `;
         // Re-attach AM/PM button listeners to the newly created buttons
          addAppointmentModalFormListeners();
    }


     // Helper to add listeners to form elements inside the appointment modal body after it's loaded
     function addAppointmentModalFormListeners() {
          if (!appointmentModalBody) return;
          // Re-attach AM/PM button listeners if present in the form
           const modalAmpmButtons = appointmentModalBody.querySelectorAll('.time-input-group .ampm-btn');
            modalAmpmButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const parentGroup = button.closest('.time-input-group');
                    if (parentGroup) {
                         parentGroup.querySelectorAll('.ampm-btn').forEach(btn => btn.classList.remove('active'));
                         button.classList.add('active');
                    }
                });
            });
            // Add other form-specific listeners here if needed (e.g., date picker, select changes)
     }


    // --- Event Listeners for Request Modal ---

    // Close request modal when clicking the close button
    if (requestModalCloseButton) {
         requestModalCloseButton.addEventListener('click', closeRequestModal);
    }

    // Close request modal when clicking outside the modal content
    if (requestModalOverlay) {
        requestModalOverlay.addEventListener('click', (event) => {
            if (event.target === requestModalOverlay) {
                closeRequestModal();
            }
        });
    }

     // No footer buttons for the request details modal now, actions are in the body.
     // Listener for the modal footer is not needed for this specific request modal anymore.
     /*
     if (requestModalFooter) {
          requestModalFooter.addEventListener('click', (event) => { ... });
     }
     */


    // --- Event Listeners for Appointment Booking Modal ---
    // Close appointment modal when clicking the close button
     if (appointmentModalCloseButton) {
         appointmentModalCloseButton.addEventListener('click', closeAppointmentModal);
     }

     // Close appointment modal when clicking outside the modal content
    if (appointmentModalOverlay) {
        appointmentModalOverlay.addEventListener('click', (event) => {
            if (event.target === appointmentModalOverlay) {
                closeAppointmentModal();
            }
        });
    }

     // Handle clicks within the appointment modal footer (Booking form buttons)
     if (appointmentModalFooter) {
         // Use event delegation for the footer buttons as they are added/removed dynamically
         appointmentModalFooter.addEventListener('click', (event) => {
              const target = event.target;

              // Handle Booking form buttons
              if (target.id === 'confirmBookingButton') { // ID for the confirm button in the booking form
                   console.log("Confirm Booking button clicked (Appointment Modal).");
                   const bookingForm = appointmentModalBody ? appointmentModalBody.querySelector('#bookAppointmentFromRequestForm') : null;
                   if (bookingForm) { handleConfirmBookingSubmit(bookingForm); } else { console.error("Booking form not found in modal body."); }
              } else if (target.id === 'cancelBookingButton') { // ID for the cancel button in the booking form
                   console.log("Cancel Booking clicked (Appointment Modal)."); closeAppointmentModal();
              }
         });
     }

     // Function to handle the Booking form submission from the appointment modal
     function handleConfirmBookingSubmit(form) {
         const requestId = form.dataset.requestId; // Get the original request ID
          if (!requestId) { console.error("Request ID not found on booking form."); alert("Error: Cannot confirm booking."); return; }

          // Basic validation for the form inside the modal
          if (!form.checkValidity()) {
              alert('Please fill out all required fields in the booking form.');
              // Optional: Trigger browser's built-in validation messages
              form.reportValidity();
              return;
          }

         const formData = new FormData(form);
         const bookingData = { request_id: requestId }; // Include the original request ID
         formData.forEach((value, key) => { bookingData[key] = value; });

         const selectedAmpmButton = form.querySelector('.ampm-btn.active');
         bookingData['appointment_time_ampm'] = selectedAmpmButton ? selectedAmpmButton.textContent.trim() : 'AM';

         console.log("Submitting booking for request ID:", requestId, bookingData);

         // --- TODO: Add AJAX/Fetch API call here to tell PHP to book the appointment ---
         // This call should:
         // 1. Create a new appointment in the database using the form data (patient, dentist, date, time, purpose).
         // 2. Mark the original request (requestId) as processed (e.g., 'accepted').
         /*
         fetch('/api/confirm_booking_from_request.php', { // Replace with your actual endpoint
             method: 'POST',
             body: JSON.stringify(bookingData), // Send the collected form data + request_id
             headers: { 'Content-Type': 'application/json' }
         })
         .then(response => {
              if (!response.ok) throw new Error('Network response was not ok.');
              return response.json();
          })
         .then(data => {
             console.log('Booking successful:', data);
             alert('Appointment confirmed and booked!');
             closeAppointmentModal(); // Close the booking modal
             // TODO: Remove the original request row from the table on the dashboard page
             const requestRow = document.querySelector(`#appointment-requests-table tbody tr[data-request-id="${requestId}"]`);
             if(requestRow) { requestRow.remove(); updateTableVisibility('#appointment-requests-table', '.no-data-row'); }
             // Optional: Add the newly booked appointment to the Today's Appointments or Upcoming Appointments table dynamically
         })
         .catch((error) => {
             console.error('Error confirming booking:', error);
             alert('Failed to confirm booking. Please try again.');
         });
         */

         // --- Current Simulation (Remove when implementing backend call) ---
         alert(`Booking simulated for request ID ${requestId}.\nAppointment details: ${JSON.stringify(bookingData, null, 2)}\nCheck console for data.`);
         closeAppointmentModal(); // Close the booking modal
         // Simulate removing the request row from the table
         const requestRow = document.querySelector(`#appointment-requests-table tbody tr[data-request-id="${requestId}"]`);
         if(requestRow) { requestRow.remove(); updateTableVisibility('#appointment-requests-table', '.no-data-row'); }
         // --- End Simulation ---
     }


    // --- Action Link Handlers for Tables on Dashboard Page ---
     const requestsTable = document.getElementById('appointment-requests-table');
     const todayAppointmentsTable = document.getElementById('today-appointments-table'); // Get today's appointments table

     // Use a single listener on the main content area and check the target's table
     const dashboardContentArea = document.querySelector('.main-content .content-area');

     if (dashboardContentArea) {
         dashboardContentArea.addEventListener('click', (event) => {
             const target = event.target;

             // --- Handle Clicks on #appointment-requests-table ---
             if (requestsTable && requestsTable.contains(target)) {
                 // Check if the clicked element is the "See More Details" link
                 if (target.tagName === 'A' && target.classList.contains('action-view-request')) {
                     const actionLink = target;
                     const row = actionLink.closest('tr'); // The request row
                     const requestId = row ? row.dataset.requestId : null;

                     if (!row || !requestId) { console.warn("Could not find request ID or row for action:", target); alert("Error: Could not identify the request."); return; }
                     event.preventDefault();

                     // Extract data from the row to pass to modal (for simulation)
                     const requestData = {
                         patientName: row.cells[0] ? row.cells[0].textContent.trim() : 'N/A',
                         preferredDateTime: row.cells[1] ? row.cells[1].textContent.trim() : 'N/A',
                         purpose: row.cells[2] ? row.cells[2].textContent.trim() : 'N/A',
                         // Patient's Message would need to be fetched from backend
                         // For simulation, add a placeholder patientMessage property here
                         patientMessage: "Please confirm morning availability." // Example placeholder message
                     };

                     console.log(`Action: VIEW DETAILS for request ID ${requestId}`);
                     // Open the request details modal
                     openRequestDetailsModal(requestId, requestData);

                 }
             }

             // --- Handle Clicks on #today-appointments-table ---
             // ... (Existing code for Today's Appointments actions if you kept that modal on this page) ...

         });
     }
    // --- End Action Link Handlers ---


    // --- Book New Appointment Form Submission (Keep if on Dashboard) ---
    // Note: This is for the form if you added it directly to the dashboard page.
    // It is DIFFERENT from the booking form logic triggered by accepting a request.
    const bookAppointmentForm = document.getElementById('book-appointment-form');
    if (bookAppointmentForm) { // Check if this form exists on the page
        bookAppointmentForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!bookAppointmentForm.checkValidity()) { alert('Please fill out all required fields.'); return; }
            const formData = new FormData(bookAppointmentForm);
            const appointmentData = {}; formData.forEach((value, key) => { appointmentData[key] = value; });
            const selectedAmpmButton = bookAppointmentForm.querySelector('.ampm-btn.active');
            appointmentData['appointment_time_ampm'] = selectedAmpmButton ? selectedAmpmButton.textContent.trim() : 'AM';
            console.log('Booking new appointment data (from direct form):', appointmentData);
             // --- TODO: Add AJAX/Fetch API call here ---
            alert('Appointment booking (from direct form) simulated.\nCheck console for data.');
            bookAppointmentForm.reset();
             // Optionally, refresh the appointments list or add the new appointment row
        });
    }


    // --- Initial Table Visibility Checks ---
    updateTableVisibility('#today-appointments-table', '.no-data-row');
    updateTableVisibility('#appointment-requests-table', '.no-data-row');
    // Check other tables if they are on the dashboard page
    // updateTableVisibility('#all-appointments-table', '.no-data-row');
    // updateTableVisibility('#doctors-table', '.no-data-row');
    // updateTableVisibility('#patient-records-table', '.no-data-row');


});