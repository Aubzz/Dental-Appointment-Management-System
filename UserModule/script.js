document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('appointmentModal');
    const openModalBtns = document.querySelectorAll('.open-modal-btn');
    const closeModalBtn = modal.querySelector('.modal-close-btn');
    const modalOverlay = modal.querySelector('.modal-overlay');
    const modalContent = modal.querySelector('.modal-content');

    const tabBtns = modal.querySelectorAll('.tab-btn');
    const tabContents = modal.querySelectorAll('.tab-content');

    const newPatientForm = document.getElementById('newPatientForm');
    const existingPatientForm = document.getElementById('existingPatientForm');

    // Create hidden inputs if not present
    let selectedDoctorInput = document.getElementById('selectedDoctor');
    if (!selectedDoctorInput && newPatientForm) {
        selectedDoctorInput = document.createElement('input');
        selectedDoctorInput.type = 'hidden';
        selectedDoctorInput.id = 'selectedDoctor';
        selectedDoctorInput.name = 'selectedDoctor';
        newPatientForm.appendChild(selectedDoctorInput);
    }

    let selectedDoctorExistingInput = document.getElementById('selectedDoctorExisting');
    if (!selectedDoctorExistingInput && existingPatientForm) {
        selectedDoctorExistingInput = document.createElement('input');
        selectedDoctorExistingInput.type = 'hidden';
        selectedDoctorExistingInput.id = 'selectedDoctorExisting';
        selectedDoctorExistingInput.name = 'selectedDoctorExisting';
        existingPatientForm.appendChild(selectedDoctorExistingInput);
    }

    // Open the modal with the selected doctor's name
    const openModal = (doctorName) => {
        if (selectedDoctorInput) selectedDoctorInput.value = doctorName;
        if (selectedDoctorExistingInput) selectedDoctorExistingInput.value = doctorName;
        modal.style.display = 'flex';
        switchTab('new-patient');
    };

    // Close the modal
    const closeModal = () => {
        modal.style.display = 'none';
        // Optionally reset forms
        // newPatientForm.reset();
        // existingPatientForm.reset();
    };

    // Switch between tabs
    const switchTab = (targetTabId) => {
        tabContents.forEach(content => content.classList.remove('active'));
        tabBtns.forEach(btn => btn.classList.remove('active'));

        const targetContent = document.getElementById(targetTabId);
        const targetBtn = modal.querySelector(`.tab-btn[data-tab="${targetTabId}"]`);

        if (targetContent) targetContent.classList.add('active');
        if (targetBtn) targetBtn.classList.add('active');
    };

    // Open modal on button click
    openModalBtns.forEach(btn => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            const doctorName = btn.getAttribute('data-doctor') || 'Unknown Doctor';
            openModal(doctorName);
        });
    });

    // Close modal on close button or overlay click
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (modalOverlay) modalOverlay.addEventListener('click', closeModal);

    // Prevent modal from closing when clicking inside the modal content
    if (modalContent) {
        modalContent.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    }

    // Tab switching logic
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTabId = btn.getAttribute('data-tab');
            switchTab(targetTabId);
        });
    });

    // Handle new patient form submission
    if (newPatientForm) {
        newPatientForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(newPatientForm);
            const data = Object.fromEntries(formData.entries());
            console.log('New Patient Form Data:', data);
            alert(`Appointment submitted for ${data.firstName} ${data.lastName} with ${data.selectedDoctor}. Check console for details.`);
            closeModal();
        });
    }

    // Handle existing patient form submission
    if (existingPatientForm) {
        existingPatientForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(existingPatientForm);
            const data = Object.fromEntries(formData.entries());
            console.log('Existing Patient Form Data:', data);
            alert(`Existing patient lookup & booking for ${data.existingLastName} with ${data.selectedDoctorExisting}. Check console for details.`);
            closeModal();
        });
    }

    // Close modal with Escape key
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            closeModal();
        }
    });
});
// Inside the <script> tag at the bottom of doctors.html
// OR preferably in your linked script.js file

document.addEventListener('DOMContentLoaded', (event) => {
    // --- Existing code for min date and dentist pre-selection ---
    const today = new Date().toISOString().split('T')[0];
    const dateFields = document.querySelectorAll('input[type="date"]');
    dateFields.forEach(field => {
         if (field.id !== 'existingDob') {
             field.setAttribute('min', today);
         }
    });

    const openModalButtons = document.querySelectorAll('.open-modal-btn');
    const modal = document.getElementById('appointmentModal');
    const selectedDoctorInput = document.getElementById('selectedDoctor');
    const dentistSelectNew = document.getElementById('dentistInCharge');
    const selectedDoctorExistingInput = document.getElementById('selectedDoctorExisting');
    const dentistSelectExisting = document.getElementById('dentistInChargeExisting');
    const closeModalButton = modal?.querySelector('.modal-close-btn');
    const tabs = modal?.querySelectorAll('.tab-btn');
    const tabContents = modal?.querySelectorAll('.tab-content');

    // --- Form References --- << NEW
    const newPatientForm = document.getElementById('newPatientForm');
    const existingPatientForm = document.getElementById('existingPatientForm');


    openModalButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const doctorName = this.getAttribute('data-doctor');
            // Pre-select dentist logic... (keep as is)
            if (selectedDoctorInput) selectedDoctorInput.value = doctorName;
            if (dentistSelectNew) { for (let i = 0; i < dentistSelectNew.options.length; i++) { if (dentistSelectNew.options[i].value === doctorName) { dentistSelectNew.selectedIndex = i; break;}}}
            if (selectedDoctorExistingInput) selectedDoctorExistingInput.value = doctorName;
            if (dentistSelectExisting) { for (let i = 0; i < dentistSelectExisting.options.length; i++) { if (dentistSelectExisting.options[i].value === doctorName) { dentistSelectExisting.selectedIndex = i; break;}}}
            // Display modal
            if(modal) modal.style.display = 'block';
        });
    });

     // --- Modal Close Logic --- (keep as is)
     if (closeModalButton) { closeModalButton.addEventListener('click', () => { if(modal) modal.style.display = 'none'; }); }
     window.addEventListener('click', (event) => { if (event.target === modal) { if(modal) modal.style.display = 'none'; }});

     // --- Tab Switching Logic --- (keep as is)
    if (tabs && tabContents) {
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                const targetContent = modal.querySelector(`#${tab.getAttribute('data-tab')}`);
                if(targetContent) targetContent.classList.add('active');
            });
        });
    }

    // --- Function to save appointment --- << NEW
    function saveAppointment(appointmentData) {
        // 1. Get existing appointments from localStorage (or initialize an empty array)
        let appointments = JSON.parse(localStorage.getItem('patientAppointments') || '[]');

        // 2. Add the new appointment
        appointments.push(appointmentData);

        // 3. Save the updated array back to localStorage
        localStorage.setItem('patientAppointments', JSON.stringify(appointments));

        alert('Appointment booked successfully! Check your profile history.'); // User feedback
        if(modal) modal.style.display = 'none'; // Close modal
    }

    // --- Handle New Patient Form Submission --- << NEW
    if (newPatientForm) {
        newPatientForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent actual page reload

            // Get data from the new patient form
            const dentist = this.elements['dentistInCharge'].value;
            const date = this.elements['appointmentDate'].value;
            const startTime = this.elements['startTime'].value;
            // Combine date and time for display (you might format this better)
            const dateTimeString = `${date.replaceAll('-', '.')} / ${formatTime(startTime)}`;

            const appointment = {
                dentist: dentist,
                dateTime: dateTimeString, // Store the combined/formatted string
                status: 'SCHEDULED' // New appointments are scheduled
                // In a real app, you'd also save patient details or link to a patient ID
            };

            saveAppointment(appointment);
            this.reset(); // Clear the form
        });
    }

    // --- Handle Existing Patient Form Submission --- << NEW
     if (existingPatientForm) {
        existingPatientForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent actual page reload

            // !! Add Patient Lookup Logic Here (in a real app) !!
            // For this demo, we assume the patient is found and book the appointment

            // Get data from the existing patient appointment section
            const dentist = this.elements['dentistInChargeExisting'].value;
            const date = this.elements['appointmentDateExisting'].value;
            const startTime = this.elements['startTimeExisting'].value;
            const dateTimeString = `${date.replaceAll('-', '.')} / ${formatTime(startTime)}`;


            const appointment = {
                dentist: dentist,
                dateTime: dateTimeString,
                status: 'SCHEDULED'
                // Link to the looked-up patient ID in a real app
            };

            saveAppointment(appointment);
             this.reset(); // Clear the form
        });
    }

    // Helper function to format time (optional) << NEW
    function formatTime(timeString) {
        if (!timeString) return '';
        const [hourString, minute] = timeString.split(':');
        const hour = +hourString % 24;
        const period = hour < 12 || hour === 24 ? 'AM' : 'PM';
        const hour12 = hour % 12 || 12; // Convert hour to 12-hour format
        return `${hour12}:${minute} ${period}`;
    }

}); // End DOMContentLoaded

document.addEventListener('DOMContentLoaded', () => {
    const dateTimeSpan = document.querySelector('.dashboard-header .date-time');
    if (dateTimeSpan) {
        const now = new Date();
        const optionsDate = { month: 'long', day: 'numeric', year: 'numeric' };
        const optionsTime = { hour: 'numeric', minute: 'numeric', hour12: true };
        const formattedDate = now.toLocaleDateString('en-US', optionsDate);
        const formattedTime = now.toLocaleTimeString('en-US', optionsTime);
        dateTimeSpan.innerHTML = `<i class="far fa-calendar-alt"></i> ${formattedDate} at ${formattedTime}`;
    }
});