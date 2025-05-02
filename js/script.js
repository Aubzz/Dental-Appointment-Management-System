/**
 * script.js
 * Handles form submissions for signup, signin, and forgot password requests using asynchronous fetch.
 */
document.addEventListener('DOMContentLoaded', () => {

    // --- Sign Up Form Handling ---
    const signupForm = document.getElementById('signupForm');
    const signupPasswordInput = document.getElementById('password'); // Using more specific name for clarity
    const signupConfirmPasswordInput = document.getElementById('confirmPassword'); // Using more specific name

    // Check if all necessary signup elements exist on the current page
    if (signupForm && signupPasswordInput && signupConfirmPasswordInput) {
        signupForm.addEventListener('submit', async (event) => {
            // Prevent the default form submission behavior which reloads the page
            event.preventDefault();

            // 1. Client-side Password Confirmation Check
            if (signupPasswordInput.value !== signupConfirmPasswordInput.value) {
                alert("Passwords do not match!");
                return; // Stop the function if passwords don't match
            }

            // 2. Get Form Data (using optional chaining ?. for safety)
            const firstName = document.getElementById('firstName')?.value;
            const lastName = document.getElementById('lastName')?.value;
            const email = document.getElementById('email')?.value; // ID 'email' is shared, ensure context is correct
            const password = signupPasswordInput.value;
            const role = document.getElementById('role')?.value; // ID 'role' is shared

            // Basic client-side validation: ensure required fields have values
            if (!firstName || !lastName || !email || !password || !role) {
                alert("Please fill in all required fields.");
                return;
            }

            // Prepare data payload for the server
            const formData = {
                firstName: firstName,
                lastName: lastName,
                email: email,
                password: password,
                role: role
            };

            // 3. Send Data to Server (signup.php) using Fetch API
            try {
                const response = await fetch('signup.php', { // Relative path to the PHP script
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', // Sending JSON
                        'Accept': 'application/json'       // Expecting JSON back
                    },
                    body: JSON.stringify(formData) // Convert JS object to JSON string
                });

                // Parse the JSON response from the server
                // Note: response.json() can fail if the server doesn't send valid JSON (e.g., PHP error)
                const result = await response.json();

                // 4. Handle Server Response
                if (response.ok) { // Status code 200-299 indicates success
                    alert(result.message || 'Sign up successful!'); // Show server message
                    window.location.href = 'signin.html'; // Redirect on success
                } else {
                    // Show error message from server response or a generic fallback
                    alert(result.message || `Sign up failed (Status: ${response.status})`);
                }

            } catch (error) {
                // Handle network errors or issues parsing the response (e.g., PHP error outputting HTML)
                console.error('Error during sign up fetch:', error);
                // Check if the error is likely due to invalid JSON response
                if (error instanceof SyntaxError) {
                     alert('Received an invalid response from the server. Please check server logs.');
                } else {
                    alert('An error occurred during sign up. Please check the console for details.');
                }
            }
        });
    } else {
        // Log if essential signup elements are missing (only when on signup page)
        // This helps debugging if IDs are mistyped in the HTML
        // Check if the relevant form ID exists to determine if we *should* find the inputs
        if (document.getElementById('signupForm')) {
            if (!signupPasswordInput) console.error("Signup page: Password input (ID 'password') not found");
            if (!signupConfirmPasswordInput) console.error("Signup page: Confirm password input (ID 'confirmPassword') not found");
        }
    }


    // --- Sign In Form Handling ---
    const signinForm = document.getElementById('signinForm');
    // Assuming signin form also uses IDs 'email', 'password', 'role'
    const signinEmailInput = document.getElementById('email');
    const signinPasswordInput = document.getElementById('password');
    const signinRoleInput = document.getElementById('role');

    // Check if all necessary signin elements exist on the current page
    if (signinForm && signinEmailInput && signinPasswordInput && signinRoleInput) {
        signinForm.addEventListener('submit', async (event) => {
            // Prevent the default form submission behavior
            event.preventDefault();

            // 1. Get Form Data
            const role = signinRoleInput.value;
            const email = signinEmailInput.value;
            const password = signinPasswordInput.value;

            // Basic client-side validation
            if (!role || !email || !password) {
                alert("Please fill in all fields.");
                return;
            }

            // Prepare data payload
            const formData = {
                role: role,
                email: email,
                password: password
            };

            // 2. Send Data to Server (signin.php) using Fetch API
            try {
                const response = await fetch('signin.php', { // Relative path
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', // Sending JSON
                        'Accept': 'application/json'       // Expecting JSON back
                    },
                    body: JSON.stringify(formData) // Convert JS object to JSON string
                });

                // Parse the JSON response
                const result = await response.json();

                // 3. Handle Server Response
                if (response.ok) { // Status code 200-299
                    // Optional: Display success message briefly
                    // alert(result.message || 'Sign in successful!');

                    // Redirect to the user's dashboard or home page on success
                    window.location.href = 'home.html'; // Target page after login
                } else {
                    // Show error message from server or a generic fallback
                    alert(result.message || `Sign-in failed (Status: ${response.status})`);
                }
            } catch (error) {
                // Handle network errors or issues parsing the response
                console.error('Error during sign-in fetch:', error);
                 if (error instanceof SyntaxError) {
                     alert('Received an invalid response from the server. Please check server logs.');
                } else {
                    alert('An error occurred during sign-in. Please check the console for details.');
                }
            }
        });
    } else {
        // Log if essential signin elements are missing (only when on signin page)
        if (document.getElementById('signinForm')) {
             if (!signinEmailInput) console.error("Signin page: Email input (ID 'email') not found");
             if (!signinPasswordInput) console.error("Signin page: Password input (ID 'password') not found");
             if (!signinRoleInput) console.error("Signin page: Role input (ID 'role') not found");
        }
    }


    // --- Forgot Password Form Handling ---
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    // Assuming forgot password form also uses IDs 'email', 'role'
    const forgotEmailInput = document.getElementById('email');
    const forgotRoleInput = document.getElementById('role');
    const formMessageDiv = document.getElementById('formMessage'); // Div to display messages on forgot password page

    // Check if all necessary forgot password elements exist on the current page
    if (forgotPasswordForm && forgotEmailInput && forgotRoleInput && formMessageDiv) {
        forgotPasswordForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Prevent default form submission
            formMessageDiv.textContent = ''; // Clear previous messages
            formMessageDiv.className = 'message'; // Reset class (removes .success/.error)

            // 1. Get Form Data
            const email = forgotEmailInput.value;
            const role = forgotRoleInput.value;

            // Basic validation
            if (!email || !role) {
                formMessageDiv.textContent = 'Please enter email and select role.';
                formMessageDiv.classList.add('error'); // Add error class for styling
                return;
            }

            // Prepare data payload
            const formData = {
                email: email,
                role: role
            };

            // Show a temporary "processing" message
            formMessageDiv.textContent = 'Processing request...';

            // 2. Send Data to Server (request_reset.php)
            try {
                const response = await fetch('request_reset.php', { // Relative path
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                // 3. Handle Server Response (always show generic success locally for security)
                if (response.ok) {
                    // Display the generic success message provided by the server
                    formMessageDiv.textContent = result.message || 'Request received. Check your email if an account exists.';
                    formMessageDiv.classList.add('success'); // Style as success
                    forgotPasswordForm.reset(); // Clear the form fields
                } else {
                    // Even on server error, show a generic message locally
                    // Log the actual error for developers, but don't expose details
                    console.error(`Forgot password request failed (Status: ${response.status}):`, result.message);
                    formMessageDiv.textContent = 'There was an issue processing your request. Please try again later.';
                    formMessageDiv.classList.add('error'); // Style as error
                }

            } catch (error) {
                // Handle network errors or invalid JSON response
                console.error('Error during forgot password fetch:', error);
                formMessageDiv.textContent = 'An network error occurred. Please try again later.';
                formMessageDiv.classList.add('error'); // Style as error
                 if (error instanceof SyntaxError) {
                     console.error('Server response was not valid JSON.');
                }
            }
        });
    } else {
        // Log if essential forgot password elements are missing (only when on forgot password page)
         if (document.getElementById('forgotPasswordForm')) {
             if (!forgotEmailInput) console.error("Forgot Password page: Email input (ID 'email') not found");
             if (!forgotRoleInput) console.error("Forgot Password page: Role input (ID 'role') not found");
             if (!formMessageDiv) console.error("Forgot Password page: Message div (ID 'formMessage') not found");
         }
    }

}); // End DOMContentLoaded wrapper