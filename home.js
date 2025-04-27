document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('nav ul li');
    const loginButton = document.querySelector('.btn-login');
    const registerButton = document.querySelector('.btn-register');

    // --- Navigation Active State ---
    navLinks.forEach(link => {
        link.addEventListener('click', (event) => {
            // Optional: Prevent default if links are just #
            // event.preventDefault();

            // Remove active class from all links
            navLinks.forEach(item => item.classList.remove('active'));

            // Add active class to the clicked link's parent LI
            link.classList.add('active');

            // You might want to load content dynamically here based on
            // which link was clicked in a real application.
            console.log(`Navigated to: ${link.querySelector('a').textContent}`);
        });
    });

    // --- Button Click Handlers (Example) ---
    if (loginButton) {
        loginButton.addEventListener('click', () => {
            console.log('Log In button clicked');
            // Add logic here: show login modal, redirect, etc.
            alert('Log In functionality not yet implemented.');
        });
    }

    if (registerButton) {
        registerButton.addEventListener('click', () => {
            console.log('Register button clicked');
            // Add logic here: show registration form, redirect, etc.
            alert('Register functionality not yet implemented.');
        });
    }
});