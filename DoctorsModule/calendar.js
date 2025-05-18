// DoctorsModule/calendar.js

document.addEventListener('DOMContentLoaded', () => {
  const monthYearElement = document.getElementById('month-year');
  const daysElement = document.getElementById('calendar-days');
  const prevMonthButton = document.getElementById('prev-month');
  const nextMonthButton = document.getElementById('next-month');
  const scheduleListElement = document.querySelector('.schedule-list');

  let currentDate = new Date();
  let currentSelectedDayElement = null;

  function renderCalendar(year, month) {
      if (!monthYearElement || !daysElement) return;

      const firstDayOfMonth = new Date(year, month, 1).getDay();
      const lastDateOfMonth = new Date(year, month + 1, 0).getDate();
      const lastDateOfPrevMonth = new Date(year, month, 0).getDate();
      const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

      monthYearElement.innerText = `${months[month]} ${year}`;
      let daysHTML = "";

      for (let i = firstDayOfMonth; i > 0; i--) {
          daysHTML += `<div class="other-month">${lastDateOfPrevMonth - i + 1}</div>`;
      }

  const today = new Date();
      for (let i = 1; i <= lastDateOfMonth; i++) {
          let classes = "";
          if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
              classes += " current-day";
          }
          if (currentSelectedDayElement && 
              parseInt(currentSelectedDayElement.dataset.date) === i &&
              parseInt(currentSelectedDayElement.dataset.month) === month &&
              parseInt(currentSelectedDayElement.dataset.year) === year) {
              classes += " selected-day";
          }
          daysHTML += `<div class="${classes.trim()}" data-date="${i}" data-month="${month}" data-year="${year}">${i}</div>`;
      }

      const totalCellsFilledByMonth = firstDayOfMonth + lastDateOfMonth;
      const cellsNeeded = (Math.ceil(totalCellsFilledByMonth / 7) * 7) - totalCellsFilledByMonth;
      for (let i = 1; i <= cellsNeeded; i++) {
          daysHTML += `<div class="other-month">${i}</div>`;
      }
      
      daysElement.innerHTML = daysHTML;
      addDayClickListeners();

      let dayToSelectElement = null;
      if (currentSelectedDayElement && 
          parseInt(currentSelectedDayElement.dataset.month) === month &&
          parseInt(currentSelectedDayElement.dataset.year) === year) {
          dayToSelectElement = daysElement.querySelector(`.selected-day`);
      }
      
      if (!dayToSelectElement) {
          dayToSelectElement = daysElement.querySelector('.current-day');
      }

      if (dayToSelectElement) {
          const currentlySelected = daysElement.querySelector('.selected-day');
          if (currentlySelected && currentlySelected !== dayToSelectElement) {
              currentlySelected.classList.remove('selected-day');
          }
          dayToSelectElement.classList.add('selected-day');
          currentSelectedDayElement = dayToSelectElement;

          const { date, month: selMonth, year: selYear } = dayToSelectElement.dataset;
          fetchAndDisplaySchedule(parseInt(selYear), parseInt(selMonth), parseInt(date));
      } else {
          currentSelectedDayElement = null; 
          displayEmptySchedule("Select a date to see schedule.");
      }
  }

  function addDayClickListeners() {
      if (!daysElement) return;
      const dayElements = daysElement.querySelectorAll('div:not(.other-month)');
      dayElements.forEach(day => {
          day.addEventListener('click', () => {
              if (currentSelectedDayElement) {
                  currentSelectedDayElement.classList.remove('selected-day');
              }
              day.classList.add('selected-day');
              currentSelectedDayElement = day;

              const selectedDate = day.dataset.date;
              const selectedMonth = day.dataset.month;
              const selectedYear = day.dataset.year;
              fetchAndDisplaySchedule(parseInt(selectedYear), parseInt(selectedMonth), parseInt(selectedDate));
          });
      });
  }

  async function fetchAndDisplaySchedule(year, month, day) {
      if (!scheduleListElement) return;
      displayEmptySchedule("Loading schedule...");

      // Construct date in YYYY-MM-DD format
      const queryDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

      try {
          // --- AJAX Call to PHP backend for doctor ---
          const response = await fetch(`get_daily_schedule_doctor.php?date=${queryDate}`);
          
          if (!response.ok) {
              const errorText = await response.text();
              throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
          }
          
          const scheduleData = await response.json();

          if (scheduleData.error) {
              displayEmptySchedule(scheduleData.error);
          } else {
              displaySchedule(scheduleData);
          }

      } catch (error) {
          displayEmptySchedule("Could not load schedule. Please try again.");
      }
  }

  function displaySchedule(scheduleItems) {
      if (!scheduleListElement) return;
      scheduleListElement.innerHTML = '';

      if (scheduleItems && scheduleItems.length > 0) {
          scheduleItems.forEach(item => {
              const itemDiv = document.createElement('div');
              itemDiv.classList.add('schedule-item');
              let description = `${item.service_type || 'Appointment'} with ${item.patient_name || 'Patient'}`;
              itemDiv.innerHTML = `
                  <span class="schedule-time">${item.time_formatted || item.time}</span>
                  <span class="schedule-desc">
                      <i class="fas fa-circle" style="color: ${item.color || '#7f8c8d'};"></i> 
                      ${description}
                  </span>
              `;
              scheduleListElement.appendChild(itemDiv);
          });
      } else {
          displayEmptySchedule("No appointments scheduled for this day.");
      }
  }

  function displayEmptySchedule(message) {
      if (!scheduleListElement) return;
      scheduleListElement.innerHTML = `<div class="schedule-item"><span class="schedule-desc">${message}</span></div>`;
  }

  if (prevMonthButton) {
      prevMonthButton.addEventListener('click', () => {
          currentDate.setMonth(currentDate.getMonth() - 1);
          renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      });
  }
  if (nextMonthButton) {
      nextMonthButton.addEventListener('click', () => {
          currentDate.setMonth(currentDate.getMonth() + 1);
          renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      });
  }

  if (monthYearElement && daysElement) {
      renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
  }
});