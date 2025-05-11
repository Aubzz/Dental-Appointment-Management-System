// receptionist_signup.js

document.addEventListener('DOMContentLoaded', () => {
    const signupForm = document.getElementById('signupForm');
    const serverMessagesDiv = document.getElementById('serverMessages');

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const receptionistIdInput = document.getElementById('receptionistIdInput'); // CHANGED ID

    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    const receptionistIdError = document.getElementById('receptionistIdError'); // CHANGED ID

    const passwordRequirementsDiv = document.getElementById('passwordRequirements');
    const lengthReq = document.getElementById('length');
    const uppercaseReq = document.getElementById('uppercase');
    const lowercaseReq = document.getElementById('lowercase');
    const numberReq = document.getElementById('number');
    // const specialReq = document.getElementById('special'); // Assuming removed from display list

    function displayClientMessage(message, type = 'error') {
        if (serverMessagesDiv) {
            serverMessagesDiv.innerHTML = `<p class="${type}-message" style="padding:10px; border-radius:4px;">${message}</p>`;
            serverMessagesDiv.style.display = 'block';
            const msgElement = serverMessagesDiv.firstElementChild;
            if (type === 'error') {
                msgElement.style.backgroundColor = '#FFD2D2';
                msgElement.style.color = '#D8000C';
                msgElement.style.border = '1px solid #D8000C';
            } else {
                msgElement.style.backgroundColor = '#DFF2BF';
                msgElement.style.color = '#4F8A10';
                msgElement.style.border = '1px solid #4F8A10';
            }
        } else {
            alert(message);
        }
    }

    function displayFieldError(element, message) {
        if (element) {
            element.textContent = message;
            element.style.display = 'block';
        }
    }

    function clearFieldError(element) {
        if (element) {
            element.textContent = '';
            element.style.display = 'none';
        }
    }

    function validateEmail() {
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email !== "" && !emailRegex.test(email)) {
            displayFieldError(emailError, 'Please enter a valid email address.');
            emailInput.classList.add('input-error');
            return false;
        }
        clearFieldError(emailError);
        emailInput.classList.remove('input-error');
        return true;
    }

    function validatePassword() {
        const password = passwordInput.value;
        let isValid = true;

        if (lengthReq) {
            if (password.length >= 8) { lengthReq.className = 'valid'; }
            else { lengthReq.className = 'invalid'; isValid = false; }
        }
        if (uppercaseReq) {
            if (/[A-Z]/.test(password)) { uppercaseReq.className = 'valid'; }
            else { uppercaseReq.className = 'invalid'; isValid = false; }
        }
        if (lowercaseReq) {
            if (/[a-z]/.test(password)) { lowercaseReq.className = 'valid'; }
            else { lowercaseReq.className = 'invalid'; isValid = false; }
        }
        if (numberReq) {
            if (/[0-9]/.test(password)) { numberReq.className = 'valid'; }
            else { numberReq.className = 'invalid'; isValid = false; }
        }
        // if (specialReq) { // If you re-add special char to the list
        //     if (/[\^$*.[\]{}()?\-!"@#%&/\\,><':;|_~`]/.test(password)) { specialReq.className = 'valid'; }
        //     else { specialReq.className = 'invalid'; isValid = false; }
        // }


        if (isValid) {
            clearFieldError(passwordError);
            passwordInput.classList.remove('input-error');
        } else {
            passwordInput.classList.add('input-error');
        }
        return isValid;
    }

    function validateConfirmPassword() {
        if (passwordInput.value !== confirmPasswordInput.value && confirmPasswordInput.value !== "") {
            displayFieldError(confirmPasswordError, 'Passwords do not match.');
            confirmPasswordInput.classList.add('input-error');
            return false;
        }
        clearFieldError(confirmPasswordError);
        confirmPasswordInput.classList.remove('input-error');
        return true;
    }

    function validateReceptionistId() { // CHANGED function name
        const repId = receptionistIdInput.value.trim();
        const repIdRegex = /^REP-\d{4}$/; // Format REP-XXXX
        if (repId !== "" && !repIdRegex.test(repId)) {
            displayFieldError(receptionistIdError, 'Receptionist ID must be in the format REP-XXXX (e.g., REP-1234).'); // CHANGED message
            receptionistIdInput.classList.add('input-error');
            return false;
        }
        clearFieldError(receptionistIdError);
        receptionistIdInput.classList.remove('input-error');
        return true;
    }

    if (emailInput) emailInput.addEventListener('input', validateEmail);
    if (passwordInput) {
        passwordInput.addEventListener('focus', () => {
            if (passwordRequirementsDiv) passwordRequirementsDiv.style.display = 'block';
        });
        passwordInput.addEventListener('input', validatePassword);
    }
    if (confirmPasswordInput) confirmPasswordInput.addEventListener('input', validateConfirmPassword);
    if (receptionistIdInput) receptionistIdInput.addEventListener('input', validateReceptionistId); // CHANGED


    if (signupForm) {
        signupForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const signupButton = document.getElementById('signupButton');

            if (serverMessagesDiv) {
                serverMessagesDiv.innerHTML = '';
                serverMessagesDiv.style.display = 'none';
            }
            clearFieldError(emailError);
            clearFieldError(passwordError);
            clearFieldError(confirmPasswordError);
            clearFieldError(receptionistIdError); // CHANGED
            signupForm.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

            let formIsValid = true;
            signupForm.querySelectorAll('input[required]').forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('input-error');
                    formIsValid = false;
                }
            });

            if (!validateEmail()) formIsValid = false;
            if (!validatePassword()) formIsValid = false;
            if (!validateConfirmPassword()) formIsValid = false;
            if (!validateReceptionistId()) formIsValid = false; // CHANGED


            if (!formIsValid) {
                displayClientMessage('Please correct the errors in the form.', 'error');
                const firstErrorField = signupForm.querySelector('.input-error, input:invalid');
                if (firstErrorField) {
                    firstErrorField.focus();
                }
                return;
            }

            signupButton.disabled = true;
            signupButton.textContent = 'Creating Account...';
            const formData = new FormData(signupForm);

            fetch('receptionist_signup_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayClientMessage(data.message || 'Receptionist account created successfully! Please wait for admin verification.', 'success');
                    setTimeout(() => {
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.href = 'receptionist_signin.html?status=pending_verification';
                        }
                    }, 2000);
                } else {
                    if (data.errors && Array.isArray(data.errors)) {
                        displayClientMessage(data.errors.join('<br>'), 'error');
                    } else if (data.error) {
                        displayClientMessage(data.error, 'error');
                    } else {
                        displayClientMessage('An unknown error occurred. Please try again.', 'error');
                    }
                }
            })
            .catch((error) => {
                console.error('Error during signup fetch:', error);
                displayClientMessage('A network error occurred. Please try again.', 'error');
            })
            .finally(() => {
                signupButton.disabled = false;
                signupButton.textContent = 'Create Account';
            });
        });
    }

    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetPasswordInput = this.previousElementSibling;
            const icon = this.querySelector('i');
            if (targetPasswordInput.type === 'password') {
                targetPasswordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                targetPasswordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('aria-label', 'Show password');
            }
        });
    });
});