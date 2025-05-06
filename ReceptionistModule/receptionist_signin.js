// auth_script.js (This file contains logic for auth_signin.html)

document.addEventListener('DOMContentLoaded', () => {

    const signinForm = document.getElementById('signinForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const roleSelect = document.getElementById('roleSelect');
    const signinButton = document.getElementById('signinButton'); // Get the sign-in button

    // Check if the form exists on this page before adding listener
    if (signinForm) {
        signinForm.addEventListener('submit', (event) => {
            event.preventDefault(); // Prevent default form submission

            // Basic frontend validation
            if (!signinForm.checkValidity()) {
                 alert('Please fill out all required fields.');
                 // The browser will typically show validation messages automatically
                 return; // Stop if form is not valid
            }

            const email = emailInput.value.trim();
            const password = passwordInput.value; // Get password value
            const selectedRole = roleSelect.value; // Get selected role

            // Perform basic checks
            if (!email || !password || !selectedRole) {
                alert("Please enter your email, password, and select your role.");
                return; // Stop if any field is empty (redundant with 'required' but good practice)
            }

            console.log(`Attempting Sign In as ${selectedRole}`);
            console.log('Email:', email);
            console.log('Password:', password); // Note: Avoid logging passwords in real applications
            console.log('Selected Role:', selectedRole);


            // --- TODO: Add AJAX/Fetch API call here to send credentials to PHP backend ---
            // The backend should:
            // 1. Verify email and password against the database.
            // 2. Verify that the user's actual role matches the selectedRole (important!).
            //    Or, the backend could just return the user's actual role after verification.
            // 3. Return success status and the user's role/dashboard path.

            /*
            fetch('/api/signin.php', { // Replace with your actual signin endpoint
                method: 'POST',
                body: JSON.stringify({ email: email, password: password, role: selectedRole }), // Send credentials
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => {
                 if (!response.ok) {
                      // Handle HTTP errors
                     return response.json().then(err => { throw new Error(err.error || `HTTP error! status: ${response.status}`); });
                 }
                 return response.json(); // Assuming backend returns { success: true, user_role: '...', redirect_url: '...' }
             })
            .then(data => {
                console.log('Signin Response:', data);
                if (data.success) {
                    alert('Sign In successful!');
                    // Redirect based on the URL provided by the backend
                     if (data.redirect_url) {
                         window.location.href = data.redirect_url; // Backend tells frontend where to go
                     } else {
                          // Fallback redirect if backend doesn't provide URL, based on selected role
                          console.warn("Backend did not provide redirect_url, using frontend redirect fallback.");
                          switch (selectedRole) {
                              case 'receptionist':
                                  window.location.href = 'receptionist_dashboard.html';
                                  break;
                              case 'dentist':
                                   window.location.href = 'dentist_dashboard.html'; // Replace with actual dentist dashboard page
                                   break;
                              case 'client':
                                   window.location.href = 'client_dashboard.html'; // Replace with actual client dashboard page
                                   break;
                              default:
                                  alert("Unknown role, cannot redirect."); // Handle unexpected roles
                                   // Maybe redirect to a generic logged-in page or back to signin
                                   window.location.href = 'index.html'; // Example: redirect to homepage
                          }
                     }

                } else { // Assuming backend sends { success: false, error: '...' }
                    alert('Sign In failed: ' + data.error);
                    // Clear password field for security on failed attempt
                    passwordInput.value = '';
                }
            })
            .catch((error) => {
                console.error('Error during signin fetch:', error);
                alert('An error occurred during sign in. Please try again. Details: ' + error.message);
                 passwordInput.value = ''; // Clear password on fetch error
            });
            */

            // --- Current Simulation (Remove when implementing backend call) ---
            console.log("Sign In simulated.");

            // Simulate successful login based on selected role for frontend demo
            alert(`Sign In simulated for ${selectedRole}.\nRedirecting to dashboard (simulated).`);

            // Simulate redirection based on the selected role
            switch (selectedRole) {
                case 'receptionist':
                    console.log("Simulation: Redirecting to receptionist dashboard.");
                    window.location.href = 'receptionist_dashboard.html'; // Redirect to Receptionist Dashboard HTML
                    break;
                case 'dentist':
                    console.log("Simulation: Redirecting to dentist dashboard.");
                    window.location.href = 'dentist_dashboard.html'; // Replace with actual Dentist Dashboard page
                    break;
                case 'client':
                    console.log("Simulation: Redirecting to client dashboard.");
                    window.location.href = 'client_dashboard.html'; // Replace with actual Client Dashboard page
                    break;
                default:
                    alert("Please select a valid role.");
                    // Stay on the sign-in page or redirect to a generic error page
                    // window.location.href = 'index.html'; // Example: redirect to homepage
            }

            // --- End Simulation ---
        });
    } else {
        console.error("Sign In form element not found!");
    }

});