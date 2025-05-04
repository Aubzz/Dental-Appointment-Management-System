// receptionist_doctors_logic.js

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

    // --- Doctor List Searching Logic ---
    const doctorSearchInput = document.getElementById('doctor-search-input');
    const doctorsTableBody = document.querySelector('#doctors-table tbody');

    // Function to apply search filter
    function applyDoctorSearchFilter() {
        if (!doctorSearchInput || !doctorsTableBody) return;

        const searchTerm = doctorSearchInput.value.toLowerCase().trim();
        const rows = doctorsTableBody.querySelectorAll('tr:not(.no-data-row)');

        rows.forEach(row => {
            // Assuming Doctor Name is 1st cell (index 0), Specialization is 2nd (index 1)
            const doctorNameCell = row.cells[0];
            const specializationCell = row.cells[1];

            const doctorName = doctorNameCell ? doctorNameCell.textContent.trim().toLowerCase() : '';
            const specialization = specializationCell ? specializationCell.textContent.trim().toLowerCase() : '';

            // Check if search term is empty OR included in name OR specialization
            const searchMatch = (searchTerm === '' || doctorName.includes(searchTerm) || specialization.includes(searchTerm));

            if (searchMatch) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        });

        // Update "No data" row visibility after filtering
        updateTableVisibility('#doctors-table', '.no-data-row');
    }

    // Add event listener for the search input if element exists
    if (doctorSearchInput) {
        doctorSearchInput.addEventListener('input', applyDoctorSearchFilter);
    }


    // --- Modal Logic for Sending Message to Doctor ---
    const sendMessageModalOverlay = document.getElementById('sendMessageModalOverlay');
    const sendMessageModalContent = document.getElementById('sendMessageModalContent');
    const sendMessageModalTitle = document.getElementById('sendMessageModalTitle');
    const sendMessageModalBody = document.getElementById('sendMessageModalBody');
    const sendMessageModalFooter = document.getElementById('sendMessageModalFooter'); // This footer is for the message modal
    const sendMessageModalCloseButton = document.getElementById('sendMessageModalCloseButton');


     // Helper to add a button to the send message modal footer
     function addSendMessageModalFooterButton(text, classes, id) {
         if (!sendMessageModalFooter) return;
         const button = document.createElement('button');
         button.textContent = text;
         button.className = 'btn ' + classes;
         button.id = id;
         sendMessageModalFooter.appendChild(button);
         return button;
     }


     // Function to open the send message modal
    function openSendMessageModal(doctorId, doctorData = null) { // doctorData optional for simulation
         if (!sendMessageModalOverlay || !sendMessageModalTitle || !sendMessageModalBody || !sendMessageModalFooter) {
            console.error("Send Message modal elements not found!");
            return;
         }
        sendMessageModalOverlay.classList.add('visible');
        sendMessageModalBody.innerHTML = ''; // Clear previous content
        sendMessageModalFooter.innerHTML = ''; // Clear footer buttons
        sendMessageModalFooter.style.display = 'flex'; // Always show footer for message actions

        // --- Set Modal Content and Footer Buttons ---
        sendMessageModalTitle.textContent = `Send Message to ${doctorData ? doctorData.doctorName : 'Doctor'}`;
        displaySendMessageForm(doctorId, doctorData); // Display message form
        addSendMessageModalFooterButton('Send Message', 'btn-primary', 'sendMessageButton'); // Add Send button
        addSendMessageModalFooterButton('Cancel', 'btn-secondary', 'cancelMessageButton'); // Add Cancel button

         // Re-attach listeners for form elements inside the modal (none needed for this form)
         // addSendMessageModalFormListeners(); // Helper function if needed
    }

    // Function to close the send message modal
    function closeSendMessageModal() {
         if (!sendMessageModalOverlay) return;
        sendMessageModalOverlay.classList.remove('visible');
    }

     // Function to display the send message form
     function displaySendMessageForm(doctorId, doctorData) {
          console.log(`Displaying send message form for doctor ID: ${doctorId}`, doctorData);
          if (!sendMessageModalBody) return;

           // Use doctor data for simulation - get doctor name, etc.
           const doctorName = doctorData ? doctorData.doctorName : 'Selected Doctor';

           sendMessageModalBody.innerHTML = `
               <form id="sendMessageForm" data-doctor-id="${doctorId}">
                   <p>Composing message for:</p>

                   <div class="form-group">
                       <label for="messageRecipient">Recipient:</label>
                       <input type="text" id="messageRecipient" class="form-control" value="${doctorName}" disabled>
                   </div>

                    <div class="form-group">
                       <label for="messageSubject">Subject (Optional):</label>
                       <input type="text" id="messageSubject" name="subject" class="form-control">
                   </div>

                  <div class="form-group">
                      <label for="messageBody">Message:</label>
                      <textarea id="messageBody" name="message" class="form-control" rows="6" required placeholder="Write your message here..."></textarea>
                  </div>
                   <!-- Hidden input for doctor ID -->
                   <input type="hidden" name="doctor_id" value="${doctorId}">
               </form>
           `;
            // No specific listeners needed for this form currently
     }


    // --- Event Listeners for Send Message Modal ---
    if (sendMessageModalCloseButton) { sendMessageModalCloseButton.addEventListener('click', closeSendMessageModal); }
    if (sendMessageModalOverlay) { sendMessageModalOverlay.addEventListener('click', (event) => { if (event.target === sendMessageModalOverlay) { closeSendMessageModal(); } }); }

     if (sendMessageModalFooter) {
         // Use event delegation for the footer buttons
         sendMessageModalFooter.addEventListener('click', (event) => {
              const target = event.target;

              // Handle Send Message buttons
              if (target.id === 'sendMessageButton') {
                   console.log("Send Message button clicked.");
                   const messageForm = sendMessageModalBody ? sendMessageModalBody.querySelector('#sendMessageForm') : null;
                   if (messageForm) { handleSendMessage(messageForm); } else { console.error("Message form not found in modal body."); }
              } else if (target.id === 'cancelMessageButton') {
                   console.log("Cancel Message clicked."); closeSendMessageModal();
              }
         });
     }


     // Function to handle the Send Message Form Submission
     function handleSendMessage(form) {
         const doctorId = form.dataset.doctorId;
          if (!doctorId) { console.error("Doctor ID not found on message form."); alert("Error: Cannot send message."); return; }

          // Basic validation
           const messageBodyTextarea = form.querySelector('#messageBody');
           if (!messageBodyTextarea || messageBodyTextarea.value.trim() === '') {
               alert('Please enter a message body.');
               return;
           }

         const formData = new FormData(form);
         const messageData = { doctor_id: doctorId };
         formData.forEach((value, key) => { messageData[key] = value; });

         console.log("Submitting message for doctor ID:", doctorId, messageData);
         // --- TODO: Add AJAX/Fetch API call here to send the message to PHP ---
         /*
         fetch('/api/send_doctor_message.php', { // Replace with your actual endpoint
             method: 'POST',
             body: JSON.stringify(messageData), // Send doctor_id, subject, message
             headers: { 'Content-Type': 'application/json' }
         })
         .then(response => {
              if (!response.ok) throw new Error('Network response was not ok.');
              return response.json(); // Or response.text()
          })
         .then(data => {
             console.log('Message sent successfully:', data);
             alert('Message sent successfully (simulated)!');
             closeSendMessageModal();
             // Optional: Clear the search/filters or update table if needed
         })
         .catch((error) => {
             console.error('Error sending message:', error);
             alert('Failed to send message. Please try again.');
         });
         */

         // --- Current Simulation (Remove when implementing backend call) ---
         alert(`Message simulated for doctor ID ${doctorId}.\nMessage content: "${messageData.message}"\nCheck console for data.`);
         closeSendMessageModal();
         // --- End Simulation ---
     }


    // --- Modal Logic for Viewing Doctor Details (Optional - if viewDoctorModalOverlay is used) ---
    const viewDoctorModalOverlay = document.getElementById('viewDoctorModalOverlay');
    const viewDoctorModalTitle = document.getElementById('viewDoctorModalTitle');
    const viewDoctorModalBody = document.getElementById('viewDoctorModalBody');
    const viewDoctorModalCloseButton = document.getElementById('viewDoctorModalCloseButton');

     // Function to open the view doctor modal
     function openViewDoctorModal(doctorId, doctorData = null) {
         if (!viewDoctorModalOverlay || !viewDoctorModalTitle || !viewDoctorModalBody) {
             console.error("View Doctor modal elements not found!");
             return;
         }
         viewDoctorModalOverlay.classList.add('visible');
         viewDoctorModalBody.innerHTML = ''; // Clear previous content
         viewDoctorModalTitle.textContent = `Doctor Details`; // Generic title, or fetch name

         displayDoctorDetails(doctorId, doctorData); // Display details
     }

     // Function to close the view doctor modal
     function closeViewDoctorModal() {
         if (!viewDoctorModalOverlay) return;
         viewDoctorModalOverlay.classList.remove('visible');
     }

      // Function to display doctor details (for View mode)
      function displayDoctorDetails(doctorId, data) {
          console.log(`Simulating fetch for details of doctor ID: ${doctorId}`);
          if (!viewDoctorModalBody) return;

          // --- Simulation using data from the table row ---
          if (!data) {
               viewDoctorModalBody.innerHTML = `<p>Simulated View Modal for Doctor ID: ${doctorId}</p><p>Full details would be fetched from backend.</p>`;
               return;
          }
          viewDoctorModalBody.innerHTML = `
             <p><strong>Doctor Name:</strong> ${data.doctorName || 'N/A'}</p>
             <p><strong>Specialization:</strong> ${data.specialization || 'N/A'}</p>
             <p><strong>Contact Info:</strong> ${data.contactInfo || 'N/A'}</p>
              <!-- Add placeholders/fields for other details not in table -->
              <p><strong>License Number:</strong> (Fetch from backend)</p>
              <p><strong>Years of Experience:</strong> (Fetch from backend)</p>
              <p><strong>Assigned Patients:</strong> (Fetch from backend)</p>
              <p><strong>Upcoming Appointments:</strong> (Fetch from backend)</p>
          `;
          // --- End Simulation ---
      }

     // Add listeners to the view doctor modal elements if they exist
     if (viewDoctorModalCloseButton) { viewDoctorModalCloseButton.addEventListener('click', closeViewDoctorModal); }
      if (viewDoctorModalOverlay) {
          viewDoctorModalOverlay.addEventListener('click', (event) => {
             if (event.target === viewDoctorModalOverlay) { closeViewDoctorModal(); }
         });
      }


    // --- Action Link Handlers for #doctors-table ---
    const doctorsTable = document.getElementById('doctors-table');
    if (doctorsTable) {
        doctorsTable.addEventListener('click', (event) => {
            const target = event.target;

            // Check if the clicked element is an <a> tag AND has a doctor action class
            if (target.tagName === 'A' && (target.classList.contains('action-view-doctor') || target.classList.contains('action-send-message'))) { // Add other actions like 'action-edit-doctor' here

                 const actionLink = target;
                 const row = actionLink.closest('tr'); // Find the table row
                 const doctorId = row ? row.dataset.doctorId : null; // Get the Doctor ID

                 if (!row || !doctorId) {
                     console.warn("Could not find doctor ID or row for action:", target);
                     alert("Error: Could not identify the doctor.");
                     return; // Exit if row or ID is missing
                 }

                event.preventDefault(); // Prevent default link navigation

                 // Extract data from the row to pass to modal functions (for simulation)
                 // In a real app, you'd just pass doctorId and let the modal function fetch full data from backend
                 const rowData = {
                     doctorName: row.cells[0] ? row.cells[0].textContent.trim() : 'N/A', // Assuming Name is 1st cell
                     specialization: row.cells[1] ? row.cells[1].textContent.trim() : 'N/A', // Assuming Specialization is 2nd cell
                     contactInfo: row.cells[2] ? row.cells[2].textContent.trim() : 'N/A', // Assuming Contact is 3rd cell
                     // Add other relevant data columns if you display them in the table
                 };

                 // --- Handle Actions ---
                 if (actionLink.classList.contains('action-view-doctor')) {
                     console.log(`Action: VIEW doctor record ID ${doctorId}`);
                     // Check if the view modal exists on this page before opening
                     if (viewDoctorModalOverlay) {
                          openViewDoctorModal(doctorId, rowData); // Open view modal
                     } else {
                         alert(`Viewing details for Doctor ID ${doctorId}.\n(Modal logic not included on this page)`); // Fallback
                     }


                 } else if (actionLink.classList.contains('action-send-message')) {
                     console.log(`Action: SEND MESSAGE to doctor ID ${doctorId}`);
                      openSendMessageModal(doctorId, rowData); // Open message modal
                 }
                 // Optional: Add handler for action-edit-doctor if you added it
                 /*
                 else if (target.classList.contains('action-edit-doctor')) {
                      console.log(`Action: EDIT doctor record ID ${doctorId}`);
                      // Open an edit modal similar to patient/appointment edit if needed
                 }
                 */
            }
        });
    }


    // --- Initial Table Visibility Check ---
    // Apply search filter initially (empty string shows all)
    applyDoctorSearchFilter();

    // Initial Table Visibility Check (Doctors Table)
    // This is called inside applyDoctorSearchFilter, but calling explicitly doesn't hurt.
    // updateTableVisibility('#doctors-table', '.no-data-row');

});