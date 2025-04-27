document.addEventListener('DOMContentLoaded', () => {
    const signupForm = document.getElementById('signupForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');

    signupForm.addEventListener('submit', (event) => {
        if (passwordInput.value !== confirmPasswordInput.value) {
            alert("Passwords do not match!");
            event.preventDefault();
        }
    });
});