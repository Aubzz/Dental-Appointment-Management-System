// AdminModule/admin_dashboard.js
document.addEventListener('DOMContentLoaded', () => {
  // Notification elements
  const notificationBell = document.getElementById('notificationBell');
  const notificationBadge = document.getElementById('notificationBadge');
  const notificationsDropdown = document.getElementById('notificationsDropdown');
  const notificationList = document.getElementById('notificationList');

  // Function to update notification display
  function updateNotificationDisplay(count, notifications = []) {
      if (notificationBadge) {
          notificationBadge.textContent = count > 0 ? count : '';
          notificationBadge.style.display = count > 0 ? 'inline-block' : 'none';
      }
      if (notificationList) {
          notificationList.innerHTML = '';
          if (notifications.length > 0) {
              notifications.forEach(notif => {
                  const item = document.createElement('div');
                  item.classList.add('notification-item');
                  item.innerHTML = `
                      <a href="${notif.link || '#'}" class="notification-link">
                          <div class="notification-item-title">${notif.title}</div>
                          <div class="notification-item-message">${notif.message}</div>
                          <div class="notification-item-time">${notif.time_ago}</div>
                      </a>
                  `;
                  notificationList.appendChild(item);
              });
          } else {
              notificationList.innerHTML = '<p class="no-notifications">No new notifications.</p>';
          }
      }
  }

  // Function to fetch notifications
  async function fetchNotifications() {
      try {
          const response = await fetch('fetch_notifications.php');
          if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
          const data = await response.json();
          if (data.error) {
              console.error('Error fetching notifications:', data.error);
              return;
          }
          updateNotificationDisplay(data.count, data.notifications);
      } catch (error) {
          console.error('Could not fetch notifications:', error);
      }
  }

  // Event listeners for notification bell
  if (notificationBell) {
      notificationBell.addEventListener('click', (event) => {
          event.stopPropagation();
          if (notificationsDropdown) {
              notificationsDropdown.classList.toggle('show');
              if (notificationsDropdown.classList.contains('show')) {
                  fetchNotifications();
              }
          }
      });
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', (event) => {
      if (notificationsDropdown && notificationsDropdown.classList.contains('show')) {
          if (notificationBell && !notificationBell.contains(event.target) && 
              !notificationsDropdown.contains(event.target)) {
              notificationsDropdown.classList.remove('show');
          }
      }
  });

  // Initial fetch of notifications
  fetchNotifications();
});