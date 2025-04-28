document.addEventListener('DOMContentLoaded', () => {
    // --- Sign Up Form Handling ---
    const signupForm = document.getElementById('signupForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    if (signupForm) {
        signupForm.addEventListener('submit', async (event) => {
            if (passwordInput.value !== confirmPasswordInput.value) {
                alert("Passwords do not match!");
                event.preventDefault(); // Prevent form submission
                return;
            }

            event.preventDefault(); // Prevent default form submission

            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const password = passwordInput.value;
            const role = document.getElementById('role').value;

            const formData = {
                firstName: firstName,
                lastName: lastName,
                email: email,
                password: password,
                role: role
            };

            try {
                const response = await fetch('/signup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                if (response.ok) {
                    const result = await response.text();
                    alert(result || 'Sign up successful!');
                    window.location.href = 'signin.html'; // Redirect to sign-in page after successful signup
                } else {
                    const errorMessage = await response.text();
                    alert(errorMessage || 'Sign up failed');
                }
            } catch (error) {
                console.error('Error during sign up:', error);
                alert('An error occurred during sign up');
            }
        });
    }

    // --- Sign In Form Handling ---
    const signinForm = document.getElementById('signinForm');

    if (signinForm) {
        signinForm.addEventListener('submit', async (event) => {
            event.preventDefault(); // Prevent the default form submission

            const role = document.getElementById('role').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            const formData = {
                role: role,
                email: email,
                password: password
            };

            try {
                const response = await fetch('/signin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                if (response.ok) {
                    // Sign-in was successful, redirect to home.html
                    window.location.href = 'home.html';
                } else {
                    const errorMessage = await response.text();
                    alert(errorMessage || 'Sign-in failed');
                }
            } catch (error) {
                console.error('Error during sign-in:', error);
                alert('An error occurred during sign-in');
            }
        });
    }
});