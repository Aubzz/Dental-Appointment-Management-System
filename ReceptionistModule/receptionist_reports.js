// receptionist_reports_analytics_logic.js

document.addEventListener('DOMContentLoaded', () => {

    // --- Helper function to update table visibility (Include if any tables are added to this page) ---
    // Used for optional tables like 'Appointments by Service Type'
     function updateTableVisibility(tableId, noDataRowSelector) {
        const tableBody = document.querySelector(`${tableId} tbody`);
        if (!tableBody) {
             console.warn(`Table body not found for ID: ${tableId}`);
             return;
        }
        const noDataRow = tableBody.querySelector(noDataRowSelector);
        if (!noDataRow) {
            console.warn(`No data row not found for selector: ${noDataRowSelector} in table ID: ${tableId}`);
            return;
        }

        const dataRows = tableBody.querySelectorAll('tr:not(.no-data-row)');

        let hasVisibleRows = false;
        dataRows.forEach(row => {
            if (row.style.display !== 'none') { // Check if the row is not hidden
                hasVisibleRows = true;
            }
        });

        if (hasVisibleRows) {
            noDataRow.style.display = 'none';
        } else {
            noDataRow.style.display = 'table-row';
        }
    }


    // --- Monthly Engagement Rate Chart Logic ---
    const engagementChartCanvas = document.getElementById('engagementChart');

    if (engagementChartCanvas) {
        console.log("Chart canvas found. Initializing chart.");

        // --- TODO: Fetch chart data from PHP backend here ---
        // Example: fetch('/api/get_monthly_engagement.php')
        // .then(response => response.json())
        // .then(chartData => { /* Use chartData.labels and chartData.data */ })
        // .catch(error => console.error('Error fetching chart data:', error));

        // --- Using Sample Data for Simulation ---
        const monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const engagementData = [30, 55, 20, 35, 45, 58, 38, 15, 52, 60, 48, 42]; // Sample values from the image

        // Get the primary green color from CSS variable if possible, default otherwise
        const primaryGreen = getComputedStyle(document.documentElement).getPropertyValue('--primary-green').trim() || '#16a085';
        const secondaryGreen = getComputedStyle(document.documentElement).getPropertyValue('--secondary-green').trim() || '#1abc9c';


        const ctx = engagementChartCanvas.getContext('2d');

        new Chart(ctx, {
            type: 'bar', // Based on the image
            data: {
                labels: monthlyLabels, // Months
                datasets: [{
                    label: 'Patient Engagement', // Label for the data set
                    data: engagementData, // The engagement values
                    backgroundColor: primaryGreen, // Use your theme color for bars
                    borderColor: primaryGreen,
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { // Add a title to the Y-axis like in the image
                            display: true,
                            text: 'Customer' // Or 'Engaged Patients' or 'Appointments'
                        }
                    },
                     x: {
                         title: { // Add a title to the X-axis (optional, but good practice)
                             display: false, // Set to true if you want an X-axis title
                             text: 'Month'
                         }
                     }
                },
                 plugins: {
                     legend: {
                         display: false // Hide the legend if it's just one dataset and the title is clear
                     },
                     title: {
                         display: false, // Title is already in the card header
                         text: 'Monthly Engagement Rate'
                     },
                     tooltip: { // Customize tooltips if needed
                         callbacks: {
                             label: function(context) {
                                 let label = context.dataset.label || '';
                                 if (label) {
                                     label += ': ';
                                 }
                                 label += context.raw;
                                 return label;
                             }
                         }
                     }
                 },
                responsive: true, // Make chart responsive
                maintainAspectRatio: true // Maintain aspect ratio (default behavior)
                 // Or set to false and control size with CSS if needed
            }
        });

    } else {
        console.warn("Chart canvas element #engagementChart not found on this page.");
    }


    // --- Optional: Logic to update metric values dynamically (Fetch from backend) ---
    // Add IDs to the metric value divs in HTML (e.g., <div class="metric-value" id="metric-booked">150</div>)
     function fetchAndDisplayMetrics() {
          console.log("Fetching key metrics...");
         // TODO: Fetch data from PHP backend (e.g., /api/get_metrics.php)
         /*
          fetch('/api/get_metrics.php')
          .then(response => {
               if (!response.ok) throw new Error('Network response was not ok.');
               return response.json();
           })
          .then(metrics => {
               console.log("Fetched metrics:", metrics);
               // Update the metric values in the HTML using their IDs
               const bookedEl = document.getElementById('metric-booked');
               const rescheduledEl = document.getElementById('metric-rescheduled');
               const cancellationsEl = document.getElementById('metric-cancellations');
               const newPatientsEl = document.getElementById('metric-new-patients');
               const seenTodayEl = document.getElementById('metric-seen-today');
               const showRateEl = document.getElementById('metric-show-rate'); // Assuming you add IDs to these

               if (bookedEl && metrics.appointmentsBooked !== undefined) bookedEl.textContent = metrics.appointmentsBooked;
               if (rescheduledEl && metrics.appointmentsRescheduled !== undefined) rescheduledEl.textContent = metrics.appointmentsRescheduled;
               if (cancellationsEl && metrics.cancellations !== undefined) cancellationsEl.textContent = metrics.cancellations;
               if (newPatientsEl && metrics.newRegisteredPatients !== undefined) newPatientsEl.textContent = metrics.newRegisteredPatients < 10 ? '0' + metrics.newRegisteredPatients : metrics.newRegisteredPatients; // Format with leading zero if needed
                if (seenTodayEl && metrics.patientsSeenToday !== undefined) seenTodayEl.textContent = metrics.patientsSeenToday;
                if (showRateEl && metrics.showUpRate !== undefined) showRateEl.textContent = metrics.showUpRate + '%'; // Add percentage sign

          })
          .catch(error => console.error('Error fetching metrics:', error));
         */
     }
     // Call the function on page load if needed
     // fetchAndDisplayMetrics(); // Uncomment this if you implement the backend fetch


    // --- Initial Load Logic ---
    // Initial Table Visibility Check (Only if any tables are on this page, e.g., Service Type)
    // Example if you added the 'Appointments by Service Type' table from the optional section
    // updateTableVisibility('#service-appointments-table', '.no-data-row');


}); // End DOMContentLoaded listener