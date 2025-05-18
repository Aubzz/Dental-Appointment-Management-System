// doctor_dashboard.js
document.addEventListener('DOMContentLoaded', function() {
  const editBtn = document.getElementById('editProfileBtn');
  const editForm = document.getElementById('editProfileForm');
  const profileInfoView = document.getElementById('profileInfoView');
  const cancelBtn = document.getElementById('cancelEditProfileBtn');

  if (editBtn && editForm && profileInfoView) {
      editBtn.addEventListener('click', function() {
          editForm.style.display = 'block';
          profileInfoView.style.display = 'none';
      });
  }
  if (cancelBtn && editForm && profileInfoView) {
      cancelBtn.addEventListener('click', function() {
          editForm.style.display = 'none';
          profileInfoView.style.display = 'block';
      });
  }

  // Notification handling
  const bell = document.getElementById('notificationBell');
  const dropdown = document.getElementById('notificationsDropdown');
  const list = document.getElementById('notificationList');
  const badge = document.getElementById('notificationBadge');

  // Initialize notification elements
  if (dropdown) {
      dropdown.style.display = 'none'; // Set initial state
  }

  // Show badge if there are notifications
  if (badge) {
      const notificationCount = parseInt(badge.textContent) || 0;
      if (notificationCount > 0) {
          badge.style.display = "inline-block";
      } else {
          badge.style.display = "none";
      }
  }

  // Render notifications
  function renderNotifications() {
      if (!list) return;
      
      if (!doctorNotifications || doctorNotifications.length === 0) {
          list.innerHTML = '<p class="no-notifications">No new notifications.</p>';
          return;
      }
      list.innerHTML = doctorNotifications.map(n => `
          <div class="notification-item">
              <p>
                  <strong>New Appointment:</strong> 
                  ${n.patient_firstName} ${n.patient_lastName} on 
                  ${n.appointment_date} at ${n.appointment_time}
              </p>
              <small>Status: ${n.status} | Set by Receptionist</small>
          </div>
      `).join('');
  }

  // Toggle dropdown on bell click
  if (bell && dropdown) {
      bell.addEventListener('click', function(e) {
          e.stopPropagation();
          const currentDisplay = dropdown.style.display;
          dropdown.style.display = currentDisplay === "block" ? "none" : "block";
          if (dropdown.style.display === "block") {
              renderNotifications();
          }
      });
  }

  // Hide dropdown when clicking outside
  if (dropdown && bell) {
      document.addEventListener('click', function(e) {
          if (dropdown.style.display === "block" && 
              !dropdown.contains(e.target) && 
              e.target !== bell) {
              dropdown.style.display = "none";
          }
      });
  }

  if (editForm) {
      editForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(editForm);

          fetch('update_doctor_profile.php', {
              method: 'POST',
              body: formData
          })
          .then(res => res.json())
          .then(data => {
              if (data.success) {
                  location.reload();
              } else if (data.errors) {
                  alert(data.errors.join('\n'));
              } else if (data.error) {
                  alert(data.error);
              }
          })
          .catch(() => alert('An error occurred while saving your profile.'));
      });
  }
});