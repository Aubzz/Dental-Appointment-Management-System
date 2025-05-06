// auth_script.js

document.addEventListener('DOMContentLoaded', () => {

    const roleSelect = document.getElementById('roleSelect');
    // Get the container for role-specific fields
    const roleSpecificFieldsContainer = document.getElementById('roleSpecificFieldsContainer');
    // Get the individual role-specific sections
    const receptionistFields = document.getElementById('receptionistFields');
    const dentistFields = document.getElementById('dentistFields');

    const signupForm = document.getElementById('signupForm');

    // Get potentially required inputs in role-specific sections
    const recRequiredInputs = receptionistFields ? receptionistFields.querySelectorAll('[required]') : [];
    const dentistRequiredInputs = dentistFields ? dentistFields.querySelectorAll('[required]') : [];


    // Function to toggle form sections smoothly
    function toggleRoleFields() {
        const selectedRole = roleSelect.value;
        const transitionDuration = 500; // Match CSS max-height transition duration in ms
        const fadeDuration = 300; // Match CSS opacity transition duration in ms

        // --- Manage Required Attributes ---
        // Disable all required inputs in role-specific sections initially
        recRequiredInputs.forEach(input => input.disabled = true);
        dentistRequiredInputs.forEach(input => input.disabled = true);


        // --- Manage Visibility and Transitions ---

        // Hide both role-specific sections first (instantly remove from flow)
        receptionistFields.classList.add('hidden');
        dentistFields.classList.add('hidden');

        // Deactivate the container transition state
        roleSpecificFieldsContainer.classList.remove('active');
        roleSpecificFieldsContainer.style.maxHeight = 0; // Collapse immediately
        roleSpecificFieldsContainer.style.opacity = 0; // Hide opacity instantly on collapse
        roleSpecificFieldsContainer.style.borderTop = ''; // Remove border instantly
        roleSpecificFieldsContainer.style.paddingTop = ''; // Remove padding instantly
        roleSpecificFieldsContainer.style.marginTop = ''; // Remove margin instantly


        let targetSection = null;
        let targetRequiredInputs = [];

        if (selectedRole === 'receptionist') {
            targetSection = receptionistFields;
            targetRequiredInputs = recRequiredInputs;
        } else if (selectedRole === 'dentist') {
            targetSection = dentistFields;
            targetRequiredInputs = dentistRequiredInputs;
        }

        if (targetSection) {
             // Use a small timeout to allow the 'hidden' class to apply before we start showing the target
             setTimeout(() => {
                 targetSection.classList.remove('hidden'); // Make the target section display: block/flex

                 // Set container to active state after a brief moment to allow layout to update
                 setTimeout(() => {
                     roleSpecificFieldsContainer.classList.add('active'); // Add active class for max-height/margin transition
                     roleSpecificFieldsContainer.style.maxHeight = roleSpecificFieldsContainer.scrollHeight + 'px'; // Set max-height to actual content height
                      // Add border/padding/margin back via inline style before fading in
                      roleSpecificFieldsContainer.style.borderTop = '1px solid var(--auth-border-color)';
                      roleSpecificFieldsContainer.style.paddingTop = '20px';
                      roleSpecificFieldsContainer.style.marginTop = '20px';


                     // Use another timeout to start the opacity transition AFTER max-height/layout has started
                     setTimeout(() => {
                         roleSpecificFieldsContainer.style.opacity = 1; // Start fade in
                         targetRequiredInputs.forEach(input => input.disabled = false); // Enable required inputs after fade starts
                     }, 50); // Short delay after layout starts


                 }, 50); // Small delay to allow display: block to register

             }, 10); // Very small initial delay


        }
         // If selectedRole is neither receptionist nor dentist, no section is shown.
         // The container remains collapsed and hidden.

         console.log("Role selected:", selectedRole);
    }

    // Add event listener to the role select dropdown if it exists
    if (roleSelect) {
        roleSelect.addEventListener('change', toggleRoleFields);

         // Optional: Trigger the toggle once on page load if a role is pre-selected
         // This runs the initial show animation if needed
         // if (roleSelect.value && roleSelect.value !== "") {
         //      toggleRoleFields();
         // }
          // No initial call needed if default is '-- Select --' as container is hidden by default CSS/initial JS state.
    }


    // Handle form submission
    if (signupForm) {
        signupForm.addEventListener('submit', (event) => {
            event.preventDefault(); // Prevent default form submission

            const selectedRole = roleSelect.value; // Get the selected role from the dropdown

            if (selectedRole === "") {
                 alert("Please select a role.");
                 return; // Stop if no role is selected
            }

            // Basic frontend validation: Check if all required fields (including the currently visible role-specific ones) are filled
            // The browser's built-in HTML5 validation using 'required' attribute handles this because we disable
            // required inputs in hidden sections and enable them in visible ones.
            if (!signupForm.checkValidity()) {
                 alert('Please fill out all required fields correctly.');
                 // The browser will highlight the invalid fields.
                 return; // Stop the submission if form is invalid
            }


            // Collect data from all enabled inputs.
            // Because we disable required inputs in hidden sections,
            // we only need to collect from inputs that are NOT disabled.
            const signupData = {};

            signupForm.querySelectorAll('input:not([disabled]), select:not([disabled]), textarea:not([disabled])').forEach(element => {
                // Check if element has a name and is not a button or other non-data element
                 if (element.name) {
                     signupData[element.name] = element.value;
                 }
            });

            // The role select input is always enabled, so its value is already in signupData['role']


            console.log(`Attempting Sign Up for Role: ${selectedRole}`);
            console.log('Collected Data:', signupData);

            // Password matching validation
            if (signupData.password !== signupData.confirm_password) {
                alert("Password and Confirm Password do not match.");
                // Clear password fields for security
                signupForm.querySelector('#password').value = '';
                signupForm.querySelector('#confirmPassword').value = '';
                return; // Stop the submission
            }

            // --- TODO: Add AJAX/Fetch API call here to send signupData to PHP backend ---
            /*
            fetch('/api/signup.php', { // Replace with your actual signup endpoint
                method: 'POST',
                body: JSON.stringify(signupData), // Send the collected data
                headers: { 'Content-Type': 'application/json' } // Assuming backend expects JSON
            })
            .then(response => {
                 if (!response.ok) {
                     return response.json().then(err => { throw new Error(err.error || `HTTP error! status: ${response.status}`); });
                 }
                 return response.json(); // Assuming backend returns JSON on success
             })
            .then(data => {
                console.log('Signup Response:', data);
                if (data.success) { // Assuming backend sends { success: true, message: '...' }
                    alert('Sign Up successful! ' + data.message);
                    // Redirect based on role or status from backend response (Flowchart)
                    if (selectedRole === 'receptionist' || selectedRole === 'dentist') {
                         window.location.href = 'auth_signin.html?status=pending_verification'; // Redirect to sign-in with status
                    } else { // Client
                         window.location.href = 'client_dashboard.html'; // Redirect client to dashboard
                    }

                } else { // Assuming backend sends { success: false, error: '...' }
                    alert('Sign Up failed: ' + data.error);
                }
            })
            .catch((error) => {
                console.error('Error during signup fetch:', error);
                alert('An error occurred during sign up. Please try again. Details: ' + error.message);
            });
            */

            // --- Current Simulation (Remove when implementing backend call) ---
            alert(`Sign Up simulated for role: ${selectedRole}\nData: ${JSON.stringify(signupData, null, 2)}\nCheck console for details.`);

            // Simulate success based on role for frontend demo
            if (selectedRole === 'receptionist' || selectedRole === 'dentist') {
                 console.log("Simulation: Redirecting to sign-in with pending verification status.");
                 // Uncomment the actual redirect when ready:
                 // window.location.href = 'auth_signin.html?status=pending_verification';
            } else { // Client
                 console.log("Simulation: Redirecting to client dashboard.");
                 // Uncomment the actual redirect when ready:
                 // window.location.href = 'client_dashboard.html';
            }

            // *** THIS IS THE REDIRECTION SIMULATION LOGIC ***
console.log("Simulation: Redirecting to sign-in after signup.");
window.location.href = 'receptionist_signin.html'; // Redirect to the Sign In page
            // --- End Simulation ---

        });
    }


    // --- Initial state setup ---
    // Get all potentially required inputs in role-specific sections
    const recRequiredInputsInitial = receptionistFields ? receptionistFields.querySelectorAll('[required]') : [];
    const dentistRequiredInputsInitial = dentistFields ? dentistFields.querySelectorAll('[required]') : [];

    // Disable required inputs and hide role-specific sections on initial load
    recRequiredInputsInitial.forEach(input => input.disabled = true);
    dentistRequiredInputsInitial.forEach(input => input.disabled = true);

    if (receptionistFields) receptionistFields.classList.add('hidden');
    if (dentistFields) dentistFields.classList.add('hidden');

    // Ensure the container is collapsed and hidden initially
     if(roleSpecificFieldsContainer) {
         roleSpecificFieldsContainer.classList.remove('active'); // Ensure not active
         roleSpecificFieldsContainer.style.maxHeight = 0;
         roleSpecificFieldsContainer.style.opacity = 0;
         roleSpecificFieldsContainer.style.borderTop = '';
         roleSpecificFieldsContainer.style.paddingTop = '';
         roleSpecificFieldsContainer.style.marginTop = '';
     }


});