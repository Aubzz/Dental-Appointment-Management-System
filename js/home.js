document.addEventListener('DOMContentLoaded', () => {
    // --- Navigation Highlight (Handles clicks AFTER page load) ---
    // Note: This won't visually change the active state on initial load
    // because the page reloads when clicking the links.
    // The 'active' class set in the HTML determines initial state.
    const navLinks = document.querySelectorAll('nav ul li');
    navLinks.forEach(linkListItem => {
        // Check if the link inside this LI matches the current page URL
        const link = linkListItem.querySelector('a');
        if (link && link.href === window.location.href) {
            // Clear active class from all LIs first (in case HTML was wrong)
            navLinks.forEach(item => item.classList.remove('active'));
            // Add active class to the current page's LI
            linkListItem.classList.add('active');
        }

        // Add click listener for dynamic updates if needed (mostly for SPAs)
        linkListItem.addEventListener('click', (event) => {
            // Remove active class from all LIs
            navLinks.forEach(item => item.classList.remove('active'));
            // Add active class to the clicked LI
            linkListItem.classList.add('active');
            // Logging which link was conceptually clicked
            console.log(`Nav link clicked: ${linkListItem.querySelector('a').textContent}`);
            // Allow the default link behavior (page navigation) to occur
        });
    });


    // --- Button Click Handlers (Example Placeholders) ---
    const loginButton = document.querySelector('.btn-login');
    const registerButton = document.querySelector('.btn-register');
    const bookButtons = document.querySelectorAll('.btn-book'); // Select all booking buttons

    if (loginButton) {
        loginButton.addEventListener('click', () => {
            console.log('Log In button clicked');
            alert('Log In functionality not yet implemented.');
            // Future: Show login modal or redirect
        });
    }

    if (registerButton) {
        registerButton.addEventListener('click', () => {
            console.log('Register button clicked');
            alert('Register functionality not yet implemented.');
            // Future: Show registration form or redirect
        });
    }

    if (bookButtons.length > 0) {
        bookButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                // Find the closest parent doctor card
                const card = event.target.closest('.doctor-card');
                const doctorName = card ? card.querySelector('h3').textContent : 'Unknown Doctor';
                console.log(`Book Appointment clicked for: ${doctorName}`);
                alert(`Booking for ${doctorName} - functionality not yet implemented.`);
                // Future: Redirect to appointment booking page, perhaps pre-filled with doctor info
            });
        });
    }
});