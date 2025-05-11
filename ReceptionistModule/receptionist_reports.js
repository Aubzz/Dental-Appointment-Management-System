// receptionist_reports.js
// Assumes `phpChartData` is globally available from a <script> tag in the PHP file.

document.addEventListener('DOMContentLoaded', () => {

    // Helper function (keep if you add other tables to this page later)
    function updateTableVisibility(tableId, noDataRowSelector) {
        const tableBody = document.querySelector(`${tableId} tbody`);
        if (!tableBody) {
             console.warn(`Table body for visibility check not found: ${tableId}`);
             return;
        }
        const noDataRow = tableBody.querySelector(noDataRowSelector);
        if (!noDataRow) {
            console.warn(`No data row selector not found: ${noDataRowSelector} in table: ${tableId}`);
            return;
        }
        const dataRows = tableBody.querySelectorAll('tr:not(.no-data-row)');
        let hasVisibleRows = Array.from(dataRows).some(row => row.style.display !== 'none');
        noDataRow.style.display = hasVisibleRows ? 'none' : 'table-row';
    }

    // --- Monthly Engagement Rate Chart Logic ---
    const engagementChartCanvas = document.getElementById('engagementChart');

    if (engagementChartCanvas && typeof phpChartData !== 'undefined' && phpChartData && phpChartData.labels && phpChartData.data) {
        console.log("Chart canvas and PHP data found. Initializing chart with backend data.");

        const primaryGreen = getComputedStyle(document.documentElement).getPropertyValue('--primary-green').trim() || '#16a085';

        const ctx = engagementChartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: phpChartData.labels,
                datasets: [{
                    label: 'Patient Engagement',
                    data: phpChartData.data,
                    backgroundColor: primaryGreen,
                    borderColor: primaryGreen,
                    borderWidth: 1,
                    borderRadius: 4,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Appointments'
                        },
                        grid: {
                            borderColor: 'rgba(0,0,0,0.1)',
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                     x: {
                         grid: {
                             display: false
                         }
                     }
                },
                 plugins: {
                     legend: {
                         display: false
                     },
                     title: {
                         display: false,
                     },
                     tooltip: {
                         backgroundColor: 'rgba(0,0,0,0.7)',
                         titleFont: { size: 14 },
                         bodyFont: { size: 12 },
                         padding: 10,
                         callbacks: {
                             label: function(context) {
                                 let label = context.dataset.label || '';
                                 if (label) {
                                     label += ': ';
                                 }
                                 label += context.raw + ' appointments';
                                 return label;
                             }
                         }
                     }
                 },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    } else {
        if (!engagementChartCanvas) {
            console.warn("Chart canvas element #engagementChart not found on this page.");
        }
        if (typeof phpChartData === 'undefined' || !phpChartData || !phpChartData.labels || !phpChartData.data) {
            console.warn("phpChartData is not defined or is incomplete. Chart will not render with backend data.");
            if (engagementChartCanvas) {
                const ctx = engagementChartCanvas.getContext('2d');
                ctx.font = "16px Poppins, sans-serif"; // Ensure font is loaded
                ctx.fillStyle = "#888";
                ctx.textAlign = "center";
                ctx.fillText("Chart data not available.", engagementChartCanvas.width / 2, engagementChartCanvas.height / 2);
            }
        }
    }

    // The key metrics are directly output by PHP into the HTML,
    // so no specific JavaScript is needed to update them unless you
    // implement a feature to fetch/refresh them dynamically via AJAX.

    // Calendar script (calendar.js) will handle its own initialization.
});