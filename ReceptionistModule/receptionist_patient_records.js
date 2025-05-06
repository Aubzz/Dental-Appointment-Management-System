// receptionist_patient_records_logic.js

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

    // --- AM/PM Button Toggle (If needed in patient edit form - unlikely, but kept for completeness) ---
     // This re-attaches AM/PM listeners *within* the patient modal body if they exist there.
     function addPatientModalFormListeners() {
          if (!patientModalBody) return;
          // Example: If you had a date picker input in the form, add its listener here
          // const dateInput = patientModalBody.querySelector('#editDateOfBirth');
          // if(dateInput) { dateInput.addEventListener(...) }
           const modalAmpmButtons = patientModalBody.querySelectorAll('.time-input-group .ampm-btn'); // Check for AM/PM buttons
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


    // --- Patient Records Filtering and Searching Logic ---
    const dentistFilter = document.getElementById('patient-dentist-filter');
    const patientSearchInput = document.getElementById('patient-search-input');
    const patientRecordsTableBody = document.querySelector('#patient-records-table tbody');

    // Function to apply filters
    function applyPatientFilters() {
        if (!patientRecordsTableBody) return;

        const selectedDentist = dentistFilter ? dentistFilter.value : 'all';
        const searchTerm = patientSearchInput ? patientSearchInput.value.toLowerCase().trim() : '';

        const rows = patientRecordsTableBody.querySelectorAll('tr:not(.no-data-row)');

        rows.forEach(row => {
            const dentistCell = row.cells[3]; // Assuming Assigned Dentist is the 4th cell (index 3)
            const patientNameCell = row.cells[1]; // Assuming Patient Name is the 2nd cell (index 1)
            const patientIdCell = row.cells[0]; // Assuming Patient ID is the 1st cell (index 0)

            const rowDentist = dentistCell ? dentistCell.textContent.trim() : '';
            const patientName = patientNameCell ? patientNameCell.textContent.trim().toLowerCase() : '';
            const patientId = patientIdCell ? patientIdCell.textContent.trim().toLowerCase() : '';


            // Dentist filtering: show row if 'all' is selected OR if the row's dentist matches the selected dentist value.
            // Include '-- Not Assigned Yet --' when 'all' is selected.
            const dentistMatch = (selectedDentist === 'all' || rowDentist === selectedDentist);

            // Search filtering: show row if search term is empty OR if search term is included in name OR patient ID.
            const searchMatch = (searchTerm === '' || patientName.includes(searchTerm) || patientId.includes(searchTerm));

            if (dentistMatch && searchMatch) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        });

        // Update "No data" row visibility after filtering
        updateTableVisibility('#patient-records-table', '.no-data-row');
    }


    // Add event listeners for the filters if elements exist
    if (dentistFilter) {
        dentistFilter.addEventListener('change', applyPatientFilters);
    }
     if (patientSearchInput) {
         patientSearchInput.addEventListener('input', applyPatientFilters);
     }


    // --- Modal Logic for Patient Details/Edit ---
    // This modal structure is specific to this page
    const patientModalOverlay = document.getElementById('patientModalOverlay');
    const patientModalContent = document.getElementById('patientModalContent');
    const patientModalTitle = document.getElementById('patientModalTitle');
    const patientModalBody = document.getElementById('patientModalBody');
    const patientModalFooter = document.getElementById('patientModalFooter');
    const patientModalCloseButton = document.getElementById('patientModalCloseButton');
    const patientSaveChangesButton = document.getElementById('patientSaveChangesButton'); // Button specific to Edit form
    const patientCancelEditButton = document.getElementById('patientCancelEditButton'); // Button specific to Edit form


    // Function to open the patient modal
    function openPatientModal(type, patientId, rowData = null) { // rowData optional for simulation
        if (!patientModalOverlay || !patientModalTitle || !patientModalBody || !patientModalFooter) {
            console.error("Patient modal elements not found!");
            return;
        }
        patientModalOverlay.classList.add('visible');
        patientModalBody.innerHTML = ''; // Clear previous content
        patientModalFooter.innerHTML = ''; // Clear footer buttons (important for re-adding)
        patientModalFooter.style.display = 'none'; // Default hide footer

        // --- Set Modal Content and Footer Buttons Based on Type ---
        if (type === 'view') {
            patientModalTitle.textContent = 'Patient Details';
            displayPatientDetails(patientId, rowData); // Display details
        } else if (type === 'edit') {
            patientModalTitle.textContent = 'Edit Patient Record';
             displayEditPatientForm(patientId, rowData); // Display edit form
             // Add Edit specific buttons to footer
             addPatientModalFooterButton('Save Changes', 'btn-primary', 'patientSaveChangesButton'); // Re-add Save button
             addPatientModalFooterButton('Cancel', 'btn-secondary', 'patientCancelEditButton'); // Re-add Cancel button
              patientModalFooter.style.display = 'flex'; // Show footer
        } else {
             patientModalTitle.textContent = 'Details';
              patientModalBody.innerHTML = '<p>Invalid modal type.</p>';
        }

        // Re-attach listeners for form elements inside the modal (like AM/PM) if they exist
         addPatientModalFormListeners(); // This helper checks for elements itself
    }

    // Function to close the patient modal
    function closePatientModal() {
         if (!patientModalOverlay) return;
        patientModalOverlay.classList.remove('visible');
    }

     // Helper to add a button to the patient modal footer
     function addPatientModalFooterButton(text, classes, id) {
         if (!patientModalFooter) return;
         const button = document.createElement('button');
         button.textContent = text;
         button.className = 'btn ' + classes;
         button.id = id;
         patientModalFooter.appendChild(button);
         return button;
     }


     // Function to display patient details (for View mode)
     function displayPatientDetails(patientId, data) {
         console.log(`Simulating fetch for details of patient ID: ${patientId}`);
         if (!patientModalBody) return;

         // --- Simulation using data from the table row ---
         if (!data) {
              patientModalBody.innerHTML = `<p>Simulated View Modal for Patient ID: ${patientId}</p><p>Full details would be fetched from backend.</p>`;
              return;
         }
         // Using row data for simulation - expand with more fields if available in rowData
         patientModalBody.innerHTML = `
            <p><strong>Patient ID:</strong> ${data.patientId || 'N/A'}</p>
            <p><strong>Name:</strong> ${data.patientName || 'N/A'}</p>
            <p><strong>Contact Info:</strong> ${data.contactInfo || 'N/A'}</p>
            <p><strong>Assigned Dentist:</strong> ${data.assignedDentist || 'N/A'}</p>
            <!-- Add placeholder for other details not in table -->
             <p><strong>Date of Birth:</strong> (Fetch from backend)</p>
         `;
         // --- End Simulation ---
     }

     // Function to display the edit patient form (for Edit mode)
     function displayEditPatientForm(patientId, data) {
          console.log(`Simulating fetch for edit form of patient ID: ${patientId}`);
          if (!patientModalBody) return;

          // --- Current Simulation (using passed rowData - limited fields) ---
          if (!data) {
               patientModalBody.innerHTML = `<p>Simulated Edit Modal for Patient ID: ${patientId}</p><p>Full edit form would be pre-filled after fetching from backend.</p>`;
               return;
           }
           // Using row data for simulation - only include fields available in rowData + patient ID
           patientModalBody.innerHTML = `
               <form id="editPatientForm" data-patient-id="${patientId}">
                    <div class="form-group">
                        <label for="editPatientId">Patient ID:</label>
                        <input type="text" id="editPatientId" class="form-control" value="${data.patientId || ''}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="editPatientName">Patient Name:</label>
                        <input type="text" id="editPatientName" class="form-control" value="${data.patientName || ''}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="editContactInfo">Contact Info:</label>
                         <input type="text" id="editContactInfo" name="contact_info" class="form-control" value="${data.contactInfo || ''}" required>
                    </div>
                     <div class="form-group">
                         <label for="editAssignedDentist">Assigned Dentist:</label>
                         <select id="editAssignedDentist" name="assigned_dentist_id" class="form-control">
                              <option value="">-- Select Dentist --</option>
                              <!-- Populate dentist options dynamically -->
                              <option value="dentist1" ${data.assignedDentist === 'Dr. Smith' ? 'selected' : ''}>Dr. Smith</option>
                              <option value="dentist2" ${data.assignedDentist === 'Dr. Lee' ? 'selected' : ''}>Dr. Lee</option>
                              <option value="unassigned" ${data.assignedDentist === '-- Not Assigned Yet --' ? 'selected' : ''}>-- Not Assigned Yet --</option>
                         </select>
                     </div>
                     <!-- Add placeholders/fields for other details -->
                      <div class="form-group">
                           <label for="editDateOfBirth">Date of Birth:</label>
                           <input type="date" id="editDateOfBirth" name="date_of_birth" class="form-control" value=""> <!-- Needs backend fetch -->
                       </div>
                       <div class="form-group">
                           <label for="editGender">Gender:</label>
                            <select id="editGender" name="gender" class="form-control">
                                 <option value="">-- Select Gender --</option>
                                 <option value="Male">Male</option>
                                 <option value="Female">Female</option>
                                 <option value="Other">Other</option>
                            </select> <!-- Needs backend fetch -->
                       </div>
                    

                     <input type="hidden" name="patient_id" value="${patientId}">
               </form>
           `;
           // addPatientModalFormListeners(); // Call this if you add inputs needing specific JS listeners like date pickers or AM/PM
          // --- End Simulation ---
     }


    // --- Event Listeners for Patient Modal ---
    if (patientModalCloseButton) { patientModalCloseButton.addEventListener('click', closePatientModal); }
    if (patientModalOverlay) { patientModalOverlay.addEventListener('click', (event) => { if (event.target === patientModalOverlay) { closePatientModal(); } }); }

     if (patientModalFooter) {
         // Use event delegation for the footer buttons
         patientModalFooter.addEventListener('click', (event) => {
              const target = event.target;
              if (target.id === 'patientSaveChangesButton') {
                   console.log("Save Changes button clicked (Patient Modal).");
                   const editForm = patientModalBody ? patientModalBody.querySelector('#editPatientForm') : null;
                   if (editForm) { handleEditPatientSubmit(editForm); } else { console.error("Edit form not found in modal body."); }
              } else if (target.id === 'patientCancelEditButton') {
                   console.log("Cancel Edit clicked (Patient Modal)."); closePatientModal();
              }
         });
     }


     // Function to handle the Patient Edit Form Submission
     function handleEditPatientSubmit(form) {
         const patientId = form.dataset.patientId;
          if (!patientId) { console.error("Patient ID not found on edit form."); alert("Error: Cannot save changes."); return; }
          // Check validity of the form (only required fields that are NOT disabled)
          let isFormValid = true;
          form.querySelectorAll('[required]:not([disabled])').forEach(input => {
              if (!input.checkValidity()) {
                  isFormValid = false;
                  // You might want to add visual feedback here
              }
          });
           if (!isFormValid) {
               alert('Please fill out all required fields in the edit form.');
               // Optional: Trigger browser's built-in validation messages
               form.reportValidity();
               return;
           }


         const formData = new FormData(form);
         const updatedData = { patient_id: patientId };
         // Only collect data from fields that are NOT disabled
         formData.forEach((value, key) => {
             const element = form.elements[key];
             if (element && !element.disabled) {
                 updatedData[key] = value;
             }
         });

         console.log("Submitting updated patient data:", updatedData);
         // --- TODO: Add AJAX/Fetch API call here to send updatedData to PHP ---
         /*
         fetch('/api/update_patient.php', { // Replace with your actual endpoint
             method: 'POST', // Or 'PUT'
             body: JSON.stringify(updatedData), // Send the data as JSON
             headers: { 'Content-Type': 'application/json' } // Tell backend it's JSON
         })
         .then(response => {
              if (!response.ok) throw new Error('Network response was not ok.');
              return response.json(); // Or response.text() depending on backend response
          })
         .then(data => {
             console.log('Update successful:', data);
             alert('Patient record updated successfully!');
             closePatientModal();
             // TODO: Update the corresponding row in the main table on the page with the new data
             // The simulation below updates contact info and assigned dentist text.
             const rowToUpdate = patientRecordsTableBody ? patientRecordsTableBody.querySelector(`tr[data-patient-id="${patientId}"]`) : null;
             if(rowToUpdate) {
                  if(updatedData.contact_info !== undefined) rowToUpdate.cells[2].textContent = updatedData.contact_info; // Update Contact Info (index 2)
                   if(updatedData.assigned_dentist_id !== undefined) { // Update Assigned Dentist text (index 3)
                        const dentistSelect = form.querySelector('#editAssignedDentist');
                        const selectedDentistText = dentistSelect ? dentistSelect.options[dentistSelect.selectedIndex].text : updatedData.assigned_dentist_id;
                       rowToUpdate.cells[3].textContent = selectedDentistText;
                   }
                  // If updating other fields like Name, update row.cells[1]
                  console.log(`Row for patient ${patientId} updated visually.`);
             }
             // After updating row, re-apply filters in case assigned dentist changed affecting visibility
             applyPatientFilters();

         })
         .catch((error) => {
             console.error('Error updating patient:', error);
             alert('Failed to update patient record. Please try again.');
         });
         */

         // --- Current Simulation (Remove when implementing backend call) ---
         alert('Patient record update simulated.\nCheck console for data.');
         closePatientModal();
          // --- Simulate updating the table row ---
          const rowToUpdate = patientRecordsTableBody ? patientRecordsTableBody.querySelector(`tr[data-patient-id="${patientId}"]`) : null;
         if(rowToUpdate) {
              if(updatedData.contact_info !== undefined) rowToUpdate.cells[2].textContent = updatedData.contact_info; // Update Contact Info (index 2)
               if(updatedData.assigned_dentist_id !== undefined) { // Update Assigned Dentist text (index 3)
                    const dentistSelect = form.querySelector('#editAssignedDentist');
                    const selectedDentistText = dentistSelect ? dentistSelect.options[dentistSelect.selectedIndex].text : updatedData.assigned_dentist_id;
                   rowToUpdate.cells[3].textContent = selectedDentistText;
               }
              // If updating other fields like Name, update row.cells[1]
              console.log(`Row for patient ${patientId} updated visually.`);
         }
         // After updating row in simulation, re-apply filters
         applyPatientFilters();
         // --- End Simulation ---
     }


    // --- Action Link Handlers for #patient-records-table ---
    const patientRecordsTable = document.getElementById('patient-records-table');
    if (patientRecordsTable) {
        patientRecordsTable.addEventListener('click', (event) => {
            const target = event.target;

            // Check if the clicked element is an <a> tag AND has a patient action class
            if (target.tagName === 'A' && (target.classList.contains('action-view-patient') || target.classList.contains('action-edit-patient'))) {

                 const actionLink = target;
                 const row = actionLink.closest('tr'); // Find the table row
                 const patientId = row ? row.dataset.patientId : null; // Get the Patient ID

                 if (!row || !patientId) {
                     console.warn("Could not find patient ID or row for action:", target);
                     alert("Error: Could not identify the patient record.");
                     return; // Exit if row or ID is missing
                 }

                event.preventDefault(); // Prevent default link navigation

                 // Extract data from the row to pass to modal functions (for simulation)
                 // In a real app, you'd just pass patientId and let the modal function fetch full data from backend
                 const rowData = {
                     patientId: row.cells[0] ? row.cells[0].textContent.trim() : 'N/A', // Assuming ID is 1st cell
                     patientName: row.cells[1] ? row.cells[1].textContent.trim() : 'N/A', // Assuming Name is 2nd cell
                     contactInfo: row.cells[2] ? row.cells[2].textContent.trim() : 'N/A', // Assuming Contact is 3rd cell
                     assignedDentist: row.cells[3] ? row.cells[3].textContent.trim() : 'N/A', // Assuming Dentist is 4th cell
                     // Add other relevant data columns if you display them in the table
                 };

                 // --- Handle Actions ---
                 if (actionLink.classList.contains('action-view-patient')) {
                     console.log(`Action: VIEW patient record ID ${patientId}`);
                     openPatientModal('view', patientId, rowData); // Open modal in view mode

                 } else if (actionLink.classList.contains('action-edit-patient')) {
                     console.log(`Action: EDIT patient record ID ${patientId}`);
                      openPatientModal('edit', patientId, rowData); // Open modal in edit mode
                 }
                 // No cancel action specified for patient records in the flowchart/request
                 // Optional: Add Delete action similarly using a new class like action-delete-patient
                 /*
                 else if (target.classList.contains('action-delete-patient')) {
                      console.log(`Action: DELETE patient record ID ${patientId}`);
                      if (confirm(`Are you sure you want to delete the record for Patient ID ${patientId}? This action cannot be undone.`)) {
                           // TODO: Add AJAX/Fetch call to backend to delete
                           alert(`Deleting record for Patient ID ${patientId}.\n(Frontend simulation)`);
                           row.remove(); // Simulate removal
                           updateTableVisibility('#patient-records-table', '.no-data-row');
                           applyPatientFilters(); // Re-apply filters
                      } else { console.log(`Deletion for Patient ID ${patientId} aborted.`); }
                 }
                 */
            }
        });
    }


    // --- Initial Load Logic ---
    // Apply filters initially to show records based on the default filter value ('all')
    // This also handles the initial visibility check for the table's no-data row.
    applyPatientFilters();

});