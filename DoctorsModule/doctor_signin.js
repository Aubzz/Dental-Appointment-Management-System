// doctor_signin.js

document.addEventListener('DOMContentLoaded', () => {
  const signinForm = document.getElementById('signinForm');
  const emailInput = signinForm.querySelector('input[name="email"]');
  const passwordInput = signinForm.querySelector('input[name="password"]');
  const signinButton = document.getElementById('signinButton');
  const serverMessagesDiv = document.getElementById('serverMessagesSignin');

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

  if (signinForm) {
      signinForm.addEventListener('submit', (event) => {
          event.preventDefault();

          if (serverMessagesDiv) {
              serverMessagesDiv.innerHTML = '';
              serverMessagesDiv.style.display = 'none';
          }
          emailInput.classList.remove('input-error');
          passwordInput.classList.remove('input-error');

          let formIsValid = true;
          if (!emailInput.value.trim()) {
              displayClientMessage('Email is required.', 'error');
              emailInput.classList.add('input-error');
              formIsValid = false;
          }
          if (!passwordInput.value) {
              if(formIsValid) displayClientMessage('Password is required.', 'error');
              passwordInput.classList.add('input-error');
              formIsValid = false;
          }

          if (!formIsValid) {
              const firstErrorField = signinForm.querySelector('.input-error');
              if (firstErrorField) {
                  firstErrorField.focus();
              }
              return;
          }

          const email = emailInput.value.trim();
          const password = passwordInput.value;

          signinButton.disabled = true;
          signinButton.textContent = 'Signing In...';

          const formData = new FormData();
          formData.append('email', email);
          formData.append('password', password);

          fetch('doctor_signin_process.php', {
              method: 'POST',
              body: formData
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  displayClientMessage('Sign In successful! Redirecting...', 'success');
                  if (data.redirect_url) {
                      setTimeout(() => {
                          window.location.href = data.redirect_url;
                      }, 1000);
                  } else {
                      window.location.href = '../index.html';
                  }
              } else {
                  displayClientMessage(data.error || 'Sign In failed. Please check your credentials.', 'error');
                  passwordInput.value = '';
              }
          })
          .catch((error) => {
              console.error('Error during signin fetch:', error);
              displayClientMessage('An error occurred during sign in. Please try again.', 'error');
              passwordInput.value = '';
          })
          .finally(() => {
              signinButton.disabled = false;
              signinButton.textContent = 'Sign In';
          });
      });
  } else {
      console.error("Sign In form element not found!");
  }

  // Password show/hide toggle
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

  // Show status messages from URL
  const urlParams = new URLSearchParams(window.location.search);
  const status = urlParams.get('status');
  if (status === 'pending_verification') {
      displayClientMessage('Account created. Please wait for admin verification before signing in.', 'success');
  } else if (status === 'verified') {
      displayClientMessage('Your account has been verified! You can now sign in.', 'success');
  }
});