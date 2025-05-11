// receptionist_patient_records.js
// Assumes `availableDentistsForPatientModal` is globally available from PHP

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
    
    function addPatientModalFormListeners() { /* Placeholder */ }

    const dentistFilterElement = document.getElementById('patient-dentist-filter'); // Might be null if HTML removed
    const patientSearchInput = document.getElementById('patient-search-input');
    const patientRecordsTableBody = document.querySelector('#patient-records-table tbody');

    function applyPatientFilters() {
        if (!patientRecordsTableBody) return;
        const searchTerm = patientSearchInput ? patientSearchInput.value.toLowerCase().trim() : '';
        const rows = patientRecordsTableBody.querySelectorAll('tr:not(.no-data-row)');

        rows.forEach(row => {
            const patientIdText = row.cells[0]?.textContent.trim().toLowerCase() || '';
            const patientNameText = row.cells[1]?.textContent.trim().toLowerCase() || '';
            
            // Dentist filter is effectively disabled if the UI and data source for it are removed
            const dentistMatch = true; 
            
            const searchMatch = (searchTerm === '' || 
                                 patientNameText.includes(searchTerm) || 
                                 patientIdText.includes(searchTerm.replace('#','')));

            row.style.display = (dentistMatch && searchMatch) ? '' : 'none';
        });
        updateTableVisibility('#patient-records-table', '.no-data-row');
    }

    if (dentistFilterElement) dentistFilterElement.addEventListener('change', applyPatientFilters);
    if (patientSearchInput) patientSearchInput.addEventListener('input', applyPatientFilters);

    const patientModalOverlay = document.getElementById('patientModalOverlay');
    const patientModalTitle = document.getElementById('patientModalTitle');
    const patientModalBody = document.getElementById('patientModalBody');
    const patientModalFooter = document.getElementById('patientModalFooter');
    const patientModalCloseButton = document.getElementById('patientModalCloseButton');

    function addPatientModalFooterButton(text, classes, id) {
        if (!patientModalFooter) return null;
        const button = document.createElement('button');
        button.textContent = text;
        button.className = 'btn ' + classes;
        if (id) button.id = id;
        patientModalFooter.appendChild(button);
        return button;
    }

    function openPatientModal(type, patientIdFromCell, fullRowData = null) {
        if (!patientModalOverlay || !patientModalTitle || !patientModalBody || !patientModalFooter) return;
        patientModalOverlay.classList.add('visible');
        patientModalBody.innerHTML = '<p>Loading...</p>';
        patientModalFooter.innerHTML = '';
        patientModalFooter.style.display = 'none';

        if (type === 'view') {
            patientModalTitle.textContent = 'Patient Details';
            displayPatientDetails(patientIdFromCell, fullRowData);
        } else if (type === 'edit') {
            patientModalTitle.textContent = 'Edit Patient Record';
            displayEditPatientForm(patientIdFromCell, fullRowData);
            addPatientModalFooterButton('Save Changes', 'btn-primary', 'patientSaveChangesButton');
            addPatientModalFooterButton('Cancel', 'btn-secondary', 'patientCancelEditButton');
            patientModalFooter.style.display = 'flex';
        } else if (type === 'add') {
            patientModalTitle.textContent = 'Add New Patient';
            displayAddPatientForm(); // No rowData needed for add
            addPatientModalFooterButton('Add Patient', 'btn-primary', 'patientAddButton');
            addPatientModalFooterButton('Cancel', 'btn-secondary', 'patientCancelAddButton');
            patientModalFooter.style.display = 'flex';
        }
        addPatientModalFormListeners();
    }

    function closePatientModal() {
        if (patientModalOverlay) patientModalOverlay.classList.remove('visible');
    }

    function displayPatientDetails(patientIdFromCell, data) { // data is { ...row.dataset }
        if (!patientModalBody || !data) return;
        
        const patientName = data.firstName && data.lastName ? `${data.firstName} ${data.lastName}` : 'N/A';
        const contactInfo = (data.phone || data.email) 
            ? `${data.phone || 'No phone available'}<br>${data.email || 'No email available'}` 
            : 'N/A';
        const dobFormatted = data.dob ? new Date(data.dob + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
        
        // Assigned Dentist and Medical Info are no longer directly in rowData from main table attributes
        // If needed, they would be fetched via a separate AJAX call or included if p.assigned_doctor_id was selected
        let assignedDentistDisplay = '<span class="placeholder-data">(Info not directly listed)</span>';
        if (data.assignedDoctorId && typeof availableDentistsForPatientModal !== 'undefined') { // Check if assignedDoctorId was passed (e.g. if you re-add it to data attributes)
            const dentist = availableDentistsForPatientModal.find(d => d.id == data.assignedDoctorId);
            if (dentist) {
                assignedDentistDisplay = `Dr. ${dentist.firstName} ${dentist.lastName}`;
            }
        }


        patientModalBody.innerHTML = `
            <div class="patient-details-grid">
                <p><strong>Patient ID:</strong> ${patientIdFromCell || 'N/A'}</p>
                <p><strong>Name:</strong> ${patientName}</p>
                <p><strong>Contact Info:</strong><br>${contactInfo}</p>
                <p><strong>Date of Birth:</strong> ${dobFormatted}</p>
                <p><strong>Assigned Dentist:</strong> ${assignedDentistDisplay}</p>
                <!-- Medical Info removed -->
            </div>
        `;
    }

    function displayEditPatientForm(patientIdFromCell, data) { // data is { ...row.dataset }
        if (!patientModalBody || !data) return;
        
        let dentistsOptionsHtml = '<option value="">-- Not Assigned --</option>';
        if (typeof availableDentistsForPatientModal !== 'undefined' && Array.isArray(availableDentistsForPatientModal)) {
            availableDentistsForPatientModal.forEach(dentist => {
                // data.assignedDoctorId will be undefined if not in data attributes
                const isSelected = (data.assignedDoctorId && data.assignedDoctorId == dentist.id); 
                dentistsOptionsHtml += `<option value="${dentist.id}" ${isSelected ? 'selected' : ''}>Dr. ${dentist.firstName} ${dentist.lastName}</option>`;
            });
        }

        patientModalBody.innerHTML = `
            <form id="editPatientFormModal" action="process_edit_patient.php" method="POST">
                <input type="hidden" name="patient_id" value="${patientIdFromCell.replace('#', '')}">
                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="editPatientFirstNameModal">First Name:</label>
                        <input type="text" id="editPatientFirstNameModal" name="first_name" class="form-control" value="${data.firstName || ''}" required>
                    </div>
                    <div class="form-group form-group-half">
                        <label for="editPatientLastNameModal">Last Name:</label>
                        <input type="text" id="editPatientLastNameModal" name="last_name" class="form-control" value="${data.lastName || ''}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="editPatientPhoneModal">Phone Number:</label>
                        <input type="tel" id="editPatientPhoneModal" name="phone_number" class="form-control" value="${data.phone || ''}" pattern="[0-9()\\-\\s+]{7,}" placeholder="(123) 456-7890">
                    </div>
                    <div class="form-group form-group-half">
                        <label for="editPatientEmailModal">Email Address:</label>
                        <input type="email" id="editPatientEmailModal" name="email" class="form-control" value="${data.email || ''}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="editPatientDOBModal">Date of Birth:</label>
                    <input type="date" id="editPatientDOBModal" name="dob" class="form-control" value="${data.dob || ''}">
                </div>
                <div class="form-group">
                    <label for="editAssignedDentistModal">Assigned Dentist (Optional):</label>
                    <select id="editAssignedDentistModal" name="assigned_doctor_id" class="form-control">
                        ${dentistsOptionsHtml}
                    </select>
                </div>
                <!-- Medical Info and Address removed -->
            </form>
        `;
    }
    
    function displayAddPatientForm() {
        if (!patientModalBody) return;
        let dentistsOptionsHtml = '<option value="">-- Assign Later --</option>';
        if (typeof availableDentistsForPatientModal !== 'undefined' && Array.isArray(availableDentistsForPatientModal)) {
            availableDentistsForPatientModal.forEach(dentist => {
                dentistsOptionsHtml += `<option value="${dentist.id}">Dr. ${dentist.firstName} ${dentist.lastName}</option>`;
            });
        }

        patientModalBody.innerHTML = `
            <form id="addPatientFormModal" action="process_add_patient.php" method="POST">
                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="addPatientFirstNameModal">First Name:</label>
                        <input type="text" id="addPatientFirstNameModal" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group form-group-half">
                        <label for="addPatientLastNameModal">Last Name:</label>
                        <input type="text" id="addPatientLastNameModal" name="last_name" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="addPatientPhoneModal">Phone Number:</label>
                        <input type="tel" id="addPatientPhoneModal" name="phone_number" class="form-control" pattern="[0-9()\\-\\s+]{7,}" placeholder="(123) 456-7890">
                    </div>
                    <div class="form-group form-group-half">
                        <label for="addPatientEmailModal">Email Address:</label>
                        <input type="email" id="addPatientEmailModal" name="email" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label for="addPatientDOBModal">Date of Birth:</label>
                    <input type="date" id="addPatientDOBModal" name="dob" class="form-control">
                </div>
                <div class="form-group">
                    <label for="addAssignedDentistModal">Assign Dentist (Optional):</label>
                    <select id="addAssignedDentistModal" name="assigned_doctor_id" class="form-control">
                        ${dentistsOptionsHtml}
                    </select>
                </div>
                <!-- Medical Info and Address removed -->
            </form>
        `;
    }

    if (patientModalCloseButton) patientModalCloseButton.addEventListener('click', closePatientModal);
    if (patientModalOverlay) patientModalOverlay.addEventListener('click', (e) => { if (e.target === patientModalOverlay) closePatientModal(); });

    if (patientModalFooter) {
        patientModalFooter.addEventListener('click', (event) => {
            const target = event.target;
            let form;
            if (target.id === 'patientSaveChangesButton') {
                form = patientModalBody.querySelector('#editPatientFormModal');
                if (form) { if(!form.checkValidity()) { form.reportValidity(); return; } form.submit(); }
            } else if (target.id === 'patientCancelEditButton') {
                closePatientModal();
            } else if (target.id === 'patientAddButton') {
                form = patientModalBody.querySelector('#addPatientFormModal');
                if (form) { if(!form.checkValidity()) { form.reportValidity(); return; } form.submit(); }
            } else if (target.id === 'patientCancelAddButton') {
                closePatientModal();
            }
        });
    }

    const patientRecordsTable = document.getElementById('patient-records-table');
    if (patientRecordsTable) {
        patientRecordsTable.addEventListener('click', (event) => {
            const targetLink = event.target.closest('a.btn-view-patient, a.btn-edit-patient');
            if (!targetLink) return;

            event.preventDefault();
            const row = targetLink.closest('tr');
            const patientIdFromCell = row?.cells[0]?.textContent.trim(); 
            
            if (!row || !patientIdFromCell) return;
            
            const rowDataFromAttributes = { ...row.dataset }; 
            rowDataFromAttributes.patientIdDisplay = patientIdFromCell; 
            rowDataFromAttributes.patientFullNameDisplay = row.cells[1]?.textContent.trim() || (rowDataFromAttributes.firstName + ' ' + rowDataFromAttributes.lastName);
            rowDataFromAttributes.contactInfoDisplay = row.cells[2]?.textContent.trim() || '';
            rowDataFromAttributes.dobDisplayFromCell = row.cells[3]?.textContent.trim() || '';

            if (targetLink.classList.contains('btn-view-patient')) {
                openPatientModal('view', patientIdFromCell, rowDataFromAttributes);
            } else if (targetLink.classList.contains('btn-edit-patient')) {
                openPatientModal('edit', patientIdFromCell, rowDataFromAttributes);
            }
        });
    }

    const addNewPatientBtn = document.getElementById('add-new-patient-btn');
    if(addNewPatientBtn) {
        addNewPatientBtn.addEventListener('click', () => {
            openPatientModal('add', null, null);
        });
    }

    if(patientRecordsTableBody) {
        applyPatientFilters();
    } else {
        console.warn("#patient-records-table tbody not found for initial filter.");
    }
});