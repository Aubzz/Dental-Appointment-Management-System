// common_calendar.js

document.addEventListener('DOMContentLoaded', () => {

    // --- Calendar Logic ---
    const monthYearElement = document.getElementById('month-year');
    const daysElement = document.getElementById('calendar-days');
    const prevMonthButton = document.getElementById('prev-month');
    const nextMonthButton = document.getElementById('next-month');
    const scheduleListElement = document.querySelector('.schedule-list'); // Get the schedule list element

    let currentDate = new Date();
    let currentSelectedDayElement = null;

    function renderCalendar(year, month) {
        if (!monthYearElement || !daysElement) return; // Check if calendar elements exist

        const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 = Sunday, 1 = Monday...
        const lastDateOfMonth = new Date(year, month + 1, 0).getDate();
        const lastDateOfPrevMonth = new Date(year, month, 0).getDate();
        const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        monthYearElement.innerText = `${months[month]} ${year}`;
        let daysHTML = "";

        // Previous month's days
        for (let i = firstDayOfMonth; i > 0; i--) { daysHTML += `<div class="other-month">${lastDateOfPrevMonth - i + 1}</div>`; }

        // Current month's days
        const today = new Date();
        for (let i = 1; i <= lastDateOfMonth; i++) {
            let classes = "";
             // Add 'current-day' class if it's today's date
            if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) { classes += " current-day"; }
             // Add 'selected-day' class if it was the previously selected day in this month view
             if (currentSelectedDayElement && parseInt(currentSelectedDayElement.dataset.date) === i && parseInt(currentSelectedDayElement.dataset.month) === month && parseInt(currentSelectedDayElement.dataset.year) === year) {
                 classes += " selected-day";
            }
            daysHTML += `<div class="${classes.trim()}" data-date="${i}" data-month="${month}" data-year="${year}">${i}</div>`;
        }

        // Next month's days (to fill the grid up to 6 rows)
        const totalCellsFilledByMonth = firstDayOfMonth + lastDateOfMonth;
        const cellsNeeded = 42 - totalCellsFilledByMonth; // Aim for 6 rows (42 cells)
        for (let i = 1; i <= cellsNeeded; i++) { daysHTML += `<div class="other-month">${i}</div>`; }

        daysElement.innerHTML = daysHTML;
        addDayClickListeners(); // Add listeners after rendering

        // After rendering, re-select the previously selected day if it's in the current view.
        // If no day was selected or the selected day is not in this month, select today.
        let dayToSelectElement = null;
        if (currentSelectedDayElement && parseInt(currentSelectedDayElement.dataset.month) === month && parseInt(currentSelectedDayElement.dataset.year) === year) {
            dayToSelectElement = daysElement.querySelector(`[data-date="${currentSelectedDayElement.dataset.date}"][data-month="${month}"][data-year="${year}"]`);
        } else {
             dayToSelectElement = daysElement.querySelector('.current-day');
        }

        // Apply selection and fetch schedule
        if (dayToSelectElement) {
             // Remove selection from the old element if it was tracking one from a different month
             if(currentSelectedDayElement && currentSelectedDayElement !== dayToSelectElement) {
                 // Check if the old element is still in the DOM before trying to remove class (prevents errors on re-renders)
                 if(document.body.contains(currentSelectedDayElement)) {
                    currentSelectedDayElement.classList.remove('selected-day');
                 }
             }
             dayToSelectElement.classList.add('selected-day');
             currentSelectedDayElement = dayToSelectElement; // Update the tracking variable
             const { date, month: selMonth, year: selYear } = currentSelectedDayElement.dataset;
             fetchAndDisplaySchedule(parseInt(selYear), parseInt(selMonth), parseInt(date));
         } else {
             // If no day to select (e.g., today is not in the current month view and no day was previously selected)
             currentSelectedDayElement = null; // Ensure tracker is null
             displayEmptySchedule("Select a date to see schedule.");
         }
    }

     function addDayClickListeners() {
        if (!daysElement) return; // Ensure element exists
        const dayElements = daysElement.querySelectorAll('div:not(.other-month)');
        dayElements.forEach(day => {
            day.addEventListener('click', () => {
                // Remove selection from the previously selected day
                if (currentSelectedDayElement) {
                    currentSelectedDayElement.classList.remove('selected-day');
                }

                // Add selection to the clicked day
                day.classList.add('selected-day');
                currentSelectedDayElement = day; // Update the tracking variable

                const selectedDate = day.dataset.date;
                const selectedMonth = day.dataset.month;
                const selectedYear = day.dataset.year;
                 fetchAndDisplaySchedule(parseInt(selectedYear), parseInt(selectedMonth), parseInt(selectedDate));
            });
        });
     }

     // Placeholder function to fetch and display schedule in the right sidebar
     function fetchAndDisplaySchedule(year, month, day) {
         console.log(`Fetching schedule for ${month + 1}/${day}/${year}`);
         // In a real application, this would be an AJAX/Fetch call to your PHP backend
         // e.g., fetch('/api/get_daily_schedule.php?year=${year}&month=${month}&day=${day}')
         // .then(response => response.json())
         // .then(scheduleData => { /* Render scheduleData */ })
         // .catch(error => { /* Handle error */ });

         // --- Simulation ---
         // Simulate fetching data. Replace with actual backend call.
         // This simulation currently only shows data for the actual current date.
         const simulatedSchedule = [
             { time: "10:00 AM", description: "Consultation with Ms. Took", color: "#4e73df" },
             { time: "11:00 AM", description: "Consultation with Mr. Bash", color: "#1cc88a" },
             { time: "1:00 PM", description: "Teeth Cleaning with Mr. Lee", color: "#e74a3b" },
             { time: "2:00 PM", description: "Digital X-Ray of Mrs. Bash teeth", color: "#f6c23e" },
         ];

         const today = new Date();
         // Compare with actual today's date for simulation purposes
         const isToday = year === today.getFullYear() && month === today.getMonth() && day === today.getDate();

         if (isToday) {
              displaySchedule(simulatedSchedule);
         } else {
             displayEmptySchedule("No appointments scheduled for this day.");
         }
         // --- End Simulation ---
     }

     // Helper function to display schedule items
     function displaySchedule(scheduleItems) {
         if (!scheduleListElement) return; scheduleListElement.innerHTML = ''; // Clear current schedule

         if (scheduleItems && scheduleItems.length > 0) {
             scheduleItems.forEach(item => {
                 const itemDiv = document.createElement('div'); itemDiv.classList.add('schedule-item');
                 itemDiv.innerHTML = `
                    <span class="schedule-time">${item.time}</span>
                    <span class="schedule-desc">${item.description.includes('<i class="fas fa-circle') ? item.description : '<i class="fas fa-circle" style="color: ' + (item.color || '#7f8c8d') + ';"></i> ' + item.description}</span>
                 `; // Add circle icon if not already present
                 scheduleListElement.appendChild(itemDiv);
             });
         } else { displayEmptySchedule("No appointments scheduled for this day."); }
     }

     // Helper function to display empty schedule message
     function displayEmptySchedule(message) {
          if (!scheduleListElement) return; scheduleListElement.innerHTML = `<div class="schedule-item"><span class="schedule-time"></span><span class="schedule-desc">${message}</span></div>`;
     }

    // Add event listeners to calendar navigation buttons if they exist
    if (prevMonthButton) { prevMonthButton.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(currentDate.getFullYear(), currentDate.getMonth()); }); }
    if (nextMonthButton) { nextMonthButton.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(currentDate.getFullYear(), currentDate.getMonth()); }); }

    // Initial Render if calendar elements are present
    if (monthYearElement && daysElement) {
         renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
    }

     // Note: AM/PM toggle logic and Modal logic are page-specific and should be in their respective files.
     // Keeping a general AM/PM handler here that works IF the elements exist on the page,
     // but specific page logic might add more targeted listeners.
    const allAmpmButtons = document.querySelectorAll('.time-input-group .ampm-btn');
    allAmpmButtons.forEach(button => {
        button.addEventListener('click', () => {
            const parentGroup = button.closest('.time-input-group');
            if (parentGroup) {
                 parentGroup.querySelectorAll('.ampm-btn').forEach(btn => btn.classList.remove('active'));
                 button.classList.add('active');
            }
        });
    });

});