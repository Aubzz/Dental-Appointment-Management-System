document.addEventListener('DOMContentLoaded', function() {
  const fileInput = document.getElementById('profilePictureInput');
const fileChosenText = document.getElementById('fileChosenText');

if (fileInput && fileChosenText) {
    fileInput.addEventListener('change', function() {
        if (fileInput.files && fileInput.files[0]) {
            const file = fileInput.files[0];
            fileChosenText.textContent = file.name;
            fileChosenText.classList.remove('no-file');
            fileChosenText.style.cursor = 'pointer';
            fileChosenText.onclick = function() {
                const fileURL = URL.createObjectURL(file);
                window.open(fileURL, '_blank');
            };
        } else {
            fileChosenText.textContent = 'No file chosen';
            fileChosenText.classList.add('no-file');
            fileChosenText.style.cursor = 'default';
            fileChosenText.onclick = null;
        }
    });

    // Initial state
    fileChosenText.classList.add('no-file');
    fileChosenText.style.cursor = 'default';
  }
});
// doctor_signup.js

document.addEventListener('DOMContentLoaded', () => {
  const signupForm = document.getElementById('signupForm');
  const serverMessagesDiv = document.getElementById('serverMessages');

  // Doctor-specific fields
  const emailInput = signupForm.querySelector('input[name="email"]');
  const passwordInput = signupForm.querySelector('input[name="password"]');
  const confirmPasswordInput = signupForm.querySelector('input[name="confirm_password"]');

  // Password requirements (optional, add IDs if you want to show requirements)
  // const passwordRequirementsDiv = document.getElementById('passwordRequirements');
  // const lengthReq = document.getElementById('length');
  // const uppercaseReq = document.getElementById('uppercase');
  // const lowercaseReq = document.getElementById('lowercase');
  // const numberReq = document.getElementById('number');

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

  function validateEmail() {
      const email = emailInput.value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (email !== "" && !emailRegex.test(email)) {
          emailInput.classList.add('input-error');
          return false;
      }
      emailInput.classList.remove('input-error');
      return true;
  }

  function validatePassword() {
      const password = passwordInput.value;
      let isValid = true;
      if (password.length < 8) isValid = false;
      if (!/[A-Z]/.test(password)) isValid = false;
      if (!/[a-z]/.test(password)) isValid = false;
      if (!/[0-9]/.test(password)) isValid = false;
      if (isValid) {
          passwordInput.classList.remove('input-error');
      } else {
          passwordInput.classList.add('input-error');
      }
      return isValid;
  }

  function validateConfirmPassword() {
      if (passwordInput.value !== confirmPasswordInput.value && confirmPasswordInput.value !== "") {
          confirmPasswordInput.classList.add('input-error');
          return false;
      }
      confirmPasswordInput.classList.remove('input-error');
      return true;
  }

  if (signupForm) {
      signupForm.addEventListener('submit', (event) => {
          event.preventDefault();
          const signupButton = signupForm.querySelector('button[type="submit"]');

          if (serverMessagesDiv) {
              serverMessagesDiv.innerHTML = '';
              serverMessagesDiv.style.display = 'none';
          }
          signupForm.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

          let formIsValid = true;
          signupForm.querySelectorAll('input[required], textarea[required]').forEach(input => {
              if (!input.value.trim()) {
                  input.classList.add('input-error');
                  formIsValid = false;
              }
          });

          if (!validateEmail()) formIsValid = false;
          if (!validatePassword()) formIsValid = false;
          if (!validateConfirmPassword()) formIsValid = false;

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

          fetch('doctor_signup_process.php', {
              method: 'POST',
              body: formData
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  displayClientMessage(data.message || 'Doctor account created successfully! Please wait for admin verification.', 'success');
                  setTimeout(() => {
                      window.location.href = 'doctor_signin.html?status=pending_verification';
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
});

document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profilePictureInput');
    const fileChosenText = document.getElementById('fileChosenText');
    const filePlaceholder = document.querySelector('.custom-file-placeholder');

    if (fileInput && fileChosenText) {
        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0];
                fileChosenText.textContent = file.name;
                fileChosenText.classList.remove('no-file');
                fileChosenText.style.cursor = 'pointer';
                filePlaceholder.style.display = 'none';

                // Make the file name clickable to open the image in a new tab
                fileChosenText.onclick = function() {
                    const fileURL = URL.createObjectURL(file);
                    window.open(fileURL, '_blank');
                };
            } else {
                fileChosenText.textContent = 'No file chosen';
                fileChosenText.classList.add('no-file');
                fileChosenText.style.cursor = 'default';
                filePlaceholder.style.display = '';
                fileChosenText.onclick = null;
            }
        });

        // Initial state
        fileChosenText.classList.add('no-file');
        fileChosenText.style.cursor = 'default';
    }
});