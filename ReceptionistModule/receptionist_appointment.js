// receptionist_appointments_logic.js

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

    // --- AM/PM Button Toggle (Specific to forms on this page) ---
    // Handle AM/PM toggles for the booking form and the edit modal form
    const allAmpmButtons = document.querySelectorAll('#book-appointment-form .ampm-btn, #appointmentModalBody .ampm-btn');
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


    // --- Appointment Filtering Logic ---
    const dentistFilter = document.getElementById('dentist-filter');
    const appointmentsTableBody = document.querySelector('#all-appointments-table tbody');

    // Function to apply dentist filter
    function applyDentistFilter() {
        if (!dentistFilter || !appointmentsTableBody) return;

        const selectedDentist = dentistFilter.value;
        const rows = appointmentsTableBody.querySelectorAll('tr:not(.no-data-row)');

        rows.forEach(row => {
            const dentistCell = row.cells[3]; // Assuming Dentist name is in the 4th column (index 3)
            if (dentistCell) {
                const rowDentist = dentistCell.textContent.trim();
                // Show row if filter is 'all' OR if row dentist matches filter OR if row is 'Pending' and filter is 'all'
                const isPendingUnassigned = row.cells[5].textContent.trim() === 'Pending' && rowDentist === '-- Not Assigned Yet --';
                if (selectedDentist === 'all' || rowDentist === selectedDentist || (selectedDentist === 'all' && isPendingUnassigned)) {
                    row.style.display = ''; // Show the row
                } else {
                    row.style.display = 'none'; // Hide the row
                }
            }
        });

        // Update "No data" row visibility after filtering
        updateTableVisibility('#all-appointments-table', '.no-data-row');
    }


    // Add event listener for the dentist filter if element exists
    if (dentistFilter) {
        dentistFilter.addEventListener('change', applyDentistFilter);
    }


    // --- Modal Logic for General Appointments View/Edit/Assign ---
    const appointmentModalOverlay = document.getElementById('appointmentModalOverlay');
    const appointmentModalContent = document.getElementById('appointmentModalContent');
    const appointmentModalTitle = document.getElementById('appointmentModalTitle');
    const appointmentModalBody = document.getElementById('appointmentModalBody');
    const appointmentModalFooter = document.getElementById('appointmentModalFooter'); // This footer is for the appointment modal
    const appointmentModalCloseButton = document.getElementById('appointmentModalCloseButton');
    const appointmentSaveChangesButton = document.getElementById('appointmentSaveChangesButton'); // Button specific to Edit form
    const appointmentCancelEditButton = document.getElementById('appointmentCancelEditButton'); // Button specific to Edit form


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


     // Function to open the general appointment modal
    function openAppointmentModal(type, appointmentId, rowData = null) { // rowData optional for simulation
         if (!appointmentModalOverlay || !appointmentModalTitle || !appointmentModalBody || !appointmentModalFooter) {
            console.error("Appointment modal elements not found!");
            return;
         }
        appointmentModalOverlay.classList.add('visible');
        appointmentModalBody.innerHTML = ''; // Clear previous content
        appointmentModalFooter.innerHTML = ''; // Clear footer buttons
        appointmentModalFooter.style.display = 'flex'; // Default to flex for forms

        // --- Set Modal Content and Footer Buttons Based on Type ---
        if (type === 'view') {
            appointmentModalTitle.textContent = 'Appointment Details';
            appointmentModalFooter.style.display = 'none'; // No buttons for view
            displayAppointmentDetails(appointmentId, rowData); // Display details
        } else if (type === 'edit') {
            appointmentModalTitle.textContent = 'Edit Appointment';
             displayEditAppointmentForm(appointmentId, rowData); // Display edit form
             // Add Edit specific buttons to footer
             addAppointmentModalFooterButton('Save Changes', 'btn-primary', 'appointmentSaveChangesButton'); // Re-add Save button
             addAppointmentModalFooterButton('Cancel', 'btn-secondary', 'appointmentCancelEditButton'); // Re-add Cancel button
        } else if (type === 'assign') { // Handle "Assign Dentist" action
             appointmentModalTitle.textContent = 'Assign Dentist';
             displayAssignDentistForm(appointmentId, rowData); // Display assignment form
             // Add Assign specific buttons to footer
             addAppointmentModalFooterButton('Assign Dentist', 'btn-primary', 'confirmAssignButton'); // Add Assign button
             addAppointmentModalFooterButton('Cancel', 'btn-secondary', 'cancelAssignButton'); // Add Cancel button
        } else {
             appointmentModalTitle.textContent = 'Details'; appointmentModalFooter.style.display = 'none';
              appointmentModalBody.innerHTML = '<p>Invalid modal type.</p>';
        }

         // Re-attach listeners for form elements inside the modal (like AM/PM) if they exist
         addAppointmentModalFormListeners();
    }

    // Function to close the general appointment modal
    function closeAppointmentModal() {
        if (!appointmentModalOverlay) return;
        appointmentModalOverlay.classList.remove('visible');
    }

     // Function to display appointment details (for View mode)
     function displayAppointmentDetails(appointmentId, data) {
         console.log(`Simulating fetch for details of appointment ID: ${appointmentId}`);
         if (!appointmentModalBody) return;

         // --- Simulation using data from the table row ---
         if (!data) {
              appointmentModalBody.innerHTML = `<p>Simulated View Modal for Appointment ID: ${appointmentId}</p><p>Details would be fetched from backend.</p>`;
              return;
         }
         appointmentModalBody.innerHTML = `
            <p><strong>Patient Name:</strong> ${data.patientName}</p>
            <p><strong>Date:</strong> ${data.date}</p>
            <p><strong>Time:</strong> ${data.time}</p>
            <p><strong>Dentist:</strong> ${data.dentist}</p>
            <p><strong>Purpose:</strong> ${data.purpose}</p>
            <p><strong>Status:</strong> ${data.status}</p>
         `;
     }

     // Function to display the edit appointment form (for Edit mode)
     function displayEditAppointmentForm(appointmentId, data) {
          console.log(`Simulating fetch for edit form of appointment ID: ${appointmentId}`);
          if (!appointmentModalBody) return;

          if (!data) {
               appointmentModalBody.innerHTML = `<p>Simulated Edit Modal for Appointment ID: ${appointmentId}</p><p>Edit form would be pre-filled after fetching from backend.</p>`;
               return;
           }

          let formattedDate = '';
          if (data.date && data.date.includes('.')) {
              const [month, day, year] = data.date.split('.');
              formattedDate = `${year}-${String(parseInt(month, 10)).padStart(2, '0')}-${String(parseInt(day, 10)).padStart(2, '0')}`;
          } else {
              formattedDate = new Date().toISOString().split('T')[0];
               console.warn(`Unexpected date format: "${data.date}". Defaulting to today.`);
          }

          let timeInputValue = '09:00';
          let isPM = false;
          let isAM = true;
          if (data.time) {
              let [timePart, ampm] = data.time.split(' ');
               let [hours, minutes] = timePart.split(':');
               hours = parseInt(hours, 10);
               minutes = parseInt(minutes, 10);
               if (!isNaN(hours) && !isNaN(minutes)) {
                   timeInputValue = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
                   if (ampm) {
                        isPM = ampm.toUpperCase() === 'PM';
                        isAM = !isPM;
                   }
               } else { console.warn(`Unexpected time format: "${data.time}".`); }
          } else { console.warn(`Time data missing. Defaulting to 09:00 AM.`); }


          appointmentModalBody.innerHTML = `
              <form id="editAppointmentForm" data-appointment-id="${appointmentId}">
                  <div class="form-group">
                      <label for="editPatientName">Patient Name:</label>
                      <input type="text" id="editPatientName" class="form-control" value="${data.patientName || ''}" disabled>
                  </div>

                  <div class="form-group">
                      <label for="editDentist">Assign Dentist:</label>
                      <select id="editDentist" name="dentist_id" class="form-control" required>
                          <option value="">-- Select Dentist --</option>
                          <option value="dentist1" ${data.dentist === 'Dr. Smith' ? 'selected' : ''}>Dr. Smith</option>
                          <option value="dentist2" ${data.dentist === 'Dr. Lee' ? 'selected' : ''}>Dr. Lee</option>
                          <!-- Populate dynamically from backend -->
                           <option value="unassigned" ${data.dentist === '-- Not Assigned Yet --' ? 'selected' : ''}>-- Not Assigned Yet --</option>
                      </select>
                  </div>

                  <div class="form-group form-group-date-time">
                      <div class="form-group-half">
                          <label for="editAppointmentDate">Date:</label>
                          <input type="date" id="editAppointmentDate" name="appointment_date" class="form-control" value="${formattedDate}" required>
                      </div>
                      <div class="form-group-half">
                           <label for="editAppointmentTime">Time:</label>
                            <div class="time-input-group">
                                <input type="text" id="editAppointmentTime" name="appointment_time" class="form-control time-input" placeholder="HH:MM" value="${timeInputValue}" required pattern="[0-9]{2}:[0-9]{2}">
                                <button type="button" class="ampm-btn ${isAM ? 'active' : ''}">AM</button>
                                <button type="button" class="ampm-btn ${isPM ? 'active' : ''}">PM</button>
                            </div>
                            <small class="form-help">Enter time as HH:MM (e.g., 09:00, 01:30).</small>
                      </div>
                  </div>

                  <div class="form-group">
                      <label for="editPurpose">Purpose of Visit:</label>
                      <textarea id="editPurpose" name="purpose" class="form-control" rows="3" required>${data.purpose || ''}</textarea>
                  </div>
                   <input type="hidden" name="appointment_id" value="${appointmentId}">
              </form>
          `;
          // Re-attach AM/PM button listeners to the newly created buttons
           // addAppointmentModalFormListeners(); // This is now called *after* display functions in openAppointmentModal
     }

     // Function to display the Assign Dentist form (for Assign mode)
     function displayAssignDentistForm(appointmentId, data) {
          console.log(`Displaying assign dentist form for appointment ID: ${appointmentId}`, data);
          if (!appointmentModalBody) return;

           // Use row data for simulation - get patient, current dentist (if any)
           appointmentModalBody.innerHTML = `
               <form id="assignDentistForm" data-appointment-id="${appointmentId}">
                    <p>Assign a dentist to the appointment for <strong>${data.patientName || 'N/A'}</strong> for ${data.purpose || 'N/A'} on ${data.date || 'N/A'} at ${data.time || 'N/A'}.</p>

                   <div class="form-group">
                       <label for="assignDentistSelect">Select Dentist:</label>
                       <select id="assignDentistSelect" name="dentist_id" class="form-control" required>
                           <option value="">-- Select Dentist --</option>
                           <!-- Populate dynamically from backend -->
                           <option value="dentist1" ${data.dentist === 'Dr. Smith' ? 'selected' : ''}>Dr. Smith</option>
                           <option value="dentist2" ${data.dentist === 'Dr. Lee' ? 'selected' : ''}>Dr. Lee</option>
                           <!-- Add more dentist options -->
                       </select>
                       <small class="form-help">Current assigned dentist: ${data.dentist || 'None'}</small>
                   </div>
                    <input type="hidden" name="appointment_id" value="${appointmentId}">
               </form>
           `;
            // No special listeners needed for this form currently (no AM/PM etc.)
            // addAppointmentModalFormListeners(); // Only call if form adds complex elements
     }


     // Helper to add listeners to elements inside the appointment modal body after it's loaded (forms)
     // This function now focuses on re-adding listeners like AM/PM buttons if they are present in the loaded form.
     function addAppointmentModalFormListeners() {
          if (!appointmentModalBody) return;
          // Re-attach AM/PM button listeners if present in the form (used in Edit form)
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


    // --- Event Listeners for Appointment Modal ---
    if (appointmentModalCloseButton) { appointmentModalCloseButton.addEventListener('click', closeAppointmentModal); }
    if (appointmentModalOverlay) { appointmentModalOverlay.addEventListener('click', (event) => { if (event.target === appointmentModalOverlay) { closeAppointmentModal(); } }); }

     if (appointmentModalFooter) {
         // Use event delegation for the footer buttons as they are added/removed dynamically
         appointmentModalFooter.addEventListener('click', (event) => {
              const target = event.target;

              // Handle Edit form buttons
              if (target.id === 'appointmentSaveChangesButton') {
                   console.log("Save Changes button clicked (Appointment Modal).");
                   const editForm = appointmentModalBody ? appointmentModalBody.querySelector('#editAppointmentForm') : null;
                   if (editForm) { handleEditAppointmentSubmit(editForm); } else { console.error("Edit form not found in modal body."); }
              } else if (target.id === 'appointmentCancelEditButton') {
                   console.log("Cancel Edit clicked (Appointment Modal)."); closeAppointmentModal();
              }
              // Handle Assign form buttons
               else if (target.id === 'confirmAssignButton') { // Handle Assign button click
                   console.log("Confirm Assign button clicked.");
                    const assignForm = appointmentModalBody ? appointmentModalBody.querySelector('#assignDentistForm') : null;
                   if (assignForm) { handleAssignDentistSubmit(assignForm); } else { console.error("Assign form not found in modal body."); }
              } else if (target.id === 'cancelAssignButton') { // Handle Cancel Assign button click
                   console.log("Cancel Assign clicked."); closeAppointmentModal();
              }
         });
     }


     // Function to handle the Edit form submission
     function handleEditAppointmentSubmit(form) {
         const appointmentId = form.dataset.appointmentId;
          if (!appointmentId) { console.error("Appointment ID not found on edit form."); alert("Error: Cannot save changes."); return; }
          if (!form.checkValidity()) { alert('Please fill out all required fields in the edit form.'); return; }

         const formData = new FormData(form);
         const updatedData = { appointment_id: appointmentId }; formData.forEach((value, key) => { updatedData[key] = value; });
         const selectedAmpmButton = form.querySelector('.ampm-btn.active');
         updatedData['appointment_time_ampm'] = selectedAmpmButton ? selectedAmpmButton.textContent.trim() : 'AM';

         console.log("Submitting updated appointment data:", updatedData);
         // --- TODO: Add AJAX/Fetch API call here to send updatedData to PHP ---
         /*
         fetch('/api/update_appointment.php', { ... })
         .then(response => { ... })
         .then(data => {
             console.log('Update successful:', data);
             alert('Appointment updated successfully!');
             closeAppointmentModal();
             // Update the corresponding row in the main table
              const rowToUpdate = appointmentsTableBody ? appointmentsTableBody.querySelector(`tr[data-appointment-id="${appointmentId}"]`) : null;
             if(rowToUpdate) {
                  const dentistSelect = form.querySelector('#editDentist');
                  const selectedDentistText = dentistSelect ? dentistSelect.options[dentistSelect.selectedIndex].text : updatedData.dentist_id;

                 rowToUpdate.cells[1].textContent = updatedData.appointment_date.replace(/-/g, '.');
                 rowToToUpdate.cells[2].textContent = updatedData.appointment_time + ' ' + updatedData.appointment_time_ampm;
                 rowToUpdate.cells[3].textContent = selectedDentistText; // Update Dentist column
                 rowToUpdate.cells[4].textContent = updatedData.purpose;
                  // Update status visually if it changes based on edit (e.g., assigned dentist)
                 if (rowToUpdate.cells[5].textContent.trim() === 'Pending' && selectedDentistText !== '-- Not Assigned Yet --' && selectedDentistText !== '-- Select Dentist --') {
                       rowToUpdate.cells[5].textContent = 'Confirmed'; // Simulate status change on assignment
                 }

                  // Remove 'Assign Dentist' link if status becomes Confirmed and the link exists
                 if (rowToUpdate.cells[5].textContent.trim() === 'Confirmed') {
                      const assignLink = rowToUpdate.querySelector('.action-assign');
                      if (assignLink) assignLink.remove();
                 }


                 console.log(`Row for appointment ${appointmentId} updated visually.`);
             }
              // Re-apply filters in case dentist changed
             applyDentistFilter();
         })
         .catch((error) => {
             console.error('Error updating appointment:', error);
             alert('Failed to update appointment. Please try again.');
         });
         */

         // --- Current Simulation ---
         alert('Appointment update simulated.\nCheck console for data.');
         closeAppointmentModal();
          const rowToUpdate = appointmentsTableBody ? appointmentsTableBody.querySelector(`tr[data-appointment-id="${appointmentId}"]`) : null;
         if(rowToUpdate) {
              const dentistSelect = form.querySelector('#editDentist');
              const selectedDentistText = dentistSelect ? dentistSelect.options[dentistSelect.selectedIndex].text : updatedData.dentist_id;

             rowToUpdate.cells[1].textContent = updatedData.appointment_date.replace(/-/g, '.');
             rowToUpdate.cells[2].textContent = updatedData.appointment_time + ' ' + updatedData.appointment_time_ampm;
             rowToUpdate.cells[3].textContent = selectedDentistText;
             rowToUpdate.cells[4].textContent = updatedData.purpose;
             // Optionally update status if needed (e.g., from Pending to Confirmed)
             if (rowToUpdate.cells[5].textContent.trim() === 'Pending' && selectedDentistText !== '-- Not Assigned Yet --' && selectedDentistText !== '-- Select Dentist --') {
                  rowToUpdate.cells[5].textContent = 'Confirmed'; // Simulate status change on assignment
             }

              // Remove the 'Assign Dentist' link if a dentist is assigned (in simulation)
              if (rowToUpdate.cells[5].textContent.trim() === 'Confirmed') {
                   const assignLink = rowToUpdate.querySelector('.action-assign');
                   if (assignLink) assignLink.remove();
              }

              console.log(`Row for appointment ${appointmentId} updated visually.`);
         }
         applyDentistFilter(); // Re-apply filters
         // --- End Simulation ---
     }

     // Function to handle the Assign Dentist submission
     function handleAssignDentistSubmit(form) {
         const appointmentId = form.dataset.appointmentId;
          if (!appointmentId) { console.error("Appointment ID not found on assign form."); alert("Error: Cannot assign dentist."); return; }
          const dentistSelect = form.querySelector('#assignDentistSelect');
          if (!dentistSelect || dentistSelect.value === '') {
               alert('Please select a dentist.');
               return;
          }

         const formData = new FormData(form);
         const assignData = { appointment_id: appointmentId }; formData.forEach((value, key) => { assignData[key] = value; });
         const assignedDentistText = dentistSelect.options[dentistSelect.selectedIndex].text; // Get text for visual update

         console.log("Submitting assignment for appointment ID:", appointmentId, assignData);
         // --- TODO: Add AJAX/Fetch API call here to send assignment to PHP ---
         /*
         fetch('/api/assign_dentist.php', { ... })
         .then(response => { ... })
         .then(data => {
             console.log('Assignment successful:', data);
             alert('Dentist assigned successfully!');
             closeAppointmentModal();
             // Update the corresponding row in the main table
             const rowToUpdate = appointmentsTableBody ? appointmentsTableBody.querySelector(`tr[data-appointment-id="${appointmentId}"]`) : null;
             if(rowToUpdate) {
                  rowToUpdate.cells[3].textContent = assignedDentistText; // Update Dentist column
                  rowToUpdate.cells[5].textContent = 'Confirmed'; // Update Status column
                  const assignLink = rowToUpdate.querySelector('.action-assign');
                  if (assignLink) assignLink.remove(); // Remove assign link once assigned
                  console.log(`Row for appointment ${appointmentId} updated visually.`);
             }
             // Re-apply filters in case assignment affects visibility
             applyDentistFilter();
         })
         .catch((error) => {
             console.error('Error assigning dentist:', error);
             alert('Failed to assign dentist. Please try again.');
         });
         */

         // --- Current Simulation ---
         alert(`Dentist assignment simulated for appointment ID ${appointmentId}. Assigned: ${assignedDentistText}.\nCheck console for data.`);
         closeAppointmentModal();
         const rowToUpdate = appointmentsTableBody ? appointmentsTableBody.querySelector(`tr[data-appointment-id="${appointmentId}"]`) : null;
         if(rowToUpdate) {
              rowToUpdate.cells[3].textContent = assignedDentistText;
              rowToUpdate.cells[5].textContent = 'Confirmed'; // Simulate status change
              // Remove the 'Assign Dentist' link once assigned (in simulation)
              const assignLink = rowToUpdate.querySelector('.action-assign');
              if (assignLink) assignLink.remove();
              console.log(`Row for appointment ${appointmentId} updated visually.`);
         }
         applyDentistFilter(); // Re-apply filters
         // --- End Simulation ---
     }


    // --- Action Link Handlers for #all-appointments-table ---
    const appointmentsTable = document.getElementById('all-appointments-table');
    if (appointmentsTable) {
        appointmentsTable.addEventListener('click', (event) => {
            const target = event.target;

            // Check if the clicked element is an <a> tag AND has one of the action classes
            if (target.tagName === 'A' && (target.classList.contains('action-view') || target.classList.contains('action-edit') || target.classList.contains('action-cancel') || target.classList.contains('action-assign'))) {

                 const actionLink = target;
                 const row = actionLink.closest('tr'); // Find the table row
                 const appointmentId = row ? row.dataset.appointmentId : null; // Get the Appointment ID

                 if (!row || !appointmentId) {
                     console.warn("Could not find appointment ID or row for action:", target);
                     alert("Error: Could not identify the appointment.");
                     return;
                 }

                event.preventDefault(); // Prevent default link navigation

                 // Extract data from the row to pass to modal functions (for simulation)
                 // In a real app, you'd just pass appointmentId and let the modal function fetch full data
                 const rowData = {
                     patientName: row.cells[0] ? row.cells[0].textContent.trim() : 'N/A',
                     date: row.cells[1] ? row.cells[1].textContent.trim() : 'N/A',
                     time: row.cells[2] ? row.cells[2].textContent.trim() : 'N/A',
                     dentist: row.cells[3] ? row.cells[3].textContent.trim() : 'N/A',
                     purpose: row.cells[4] ? row.cells[4].textContent.trim() : 'N/A',
                     status: row.cells[5] ? row.cells[5].textContent.trim() : 'N/A'
                 };

                 // --- Handle Actions ---
                 if (actionLink.classList.contains('action-view')) {
                     console.log(`Action: VIEW appointment ID ${appointmentId}`);
                     openAppointmentModal('view', appointmentId, rowData); // Open modal in view mode

                 } else if (actionLink.classList.contains('action-edit')) {
                     // Business logic: Maybe only allow edit if Confirmed and assigned?
                     // For simulation, let's allow edit if not Pending/Unassigned or if dentist assigned
                      if (rowData.status === 'Pending' && rowData.dentist === '-- Not Assigned Yet --') {
                          // If Edit is clicked on a pending, unassigned request, redirect to Assign flow
                          console.log(`Action: EDIT attempted on pending unassigned request ID ${appointmentId}. Redirecting to Assign modal.`);
                           openAppointmentModal('assign', appointmentId, rowData);
                     } else {
                         console.log(`Action: EDIT appointment ID ${appointmentId}`);
                         openAppointmentModal('edit', appointmentId, rowData); // Open modal in edit mode
                     }

                 } else if (actionLink.classList.contains('action-cancel')) {
                     console.log(`Action: CANCEL appointment ID ${appointmentId}`);
                      if (confirm(`Are you sure you want to cancel the appointment for ${rowData.patientName} (ID: ${appointmentId})?`)) {
                           // --- TODO: Add AJAX/Fetch API call here to tell PHP to cancel ---
                           alert(`Cancelling appointment (ID: ${appointmentId}) for ${rowData.patientName}.\n(Frontend simulation)`);
                           row.remove();
                           console.log(`Row for appointment ${appointmentId} removed.`);
                           updateTableVisibility('#all-appointments-table', '.no-data-row');
                           // Re-apply filter after removal
                           applyDentistFilter();
                      } else { console.log(`Cancellation for appointment ${appointmentId} aborted.`); }
                 } else if (actionLink.classList.contains('action-assign')) { // Handle Assign Dentist action
                      // This action is typically for rows with Status 'Pending' and Dentist '-- Not Assigned Yet --'
                     console.log(`Action: ASSIGN DENTIST for appointment ID ${appointmentId}`);
                      openAppointmentModal('assign', appointmentId, rowData); // Open modal for assignment
                 }
            }
        });
    }


    // --- Book New Appointment Form Submission ---
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
             // Optionally, refresh the appointments list or add the new appointment row
        });
    }


    // --- Initial Render Calls ---
    // Apply filter initially to show records based on the default filter value ('all')
     applyDentistFilter();

    // Initial Table Visibility Check (Appointments Table)
    // This is called inside applyDentistFilter, but calling explicitly doesn't hurt.
    // updateTableVisibility('#all-appointments-table', '.no-data-row');


});