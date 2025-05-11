// ReceptionistModule/receptionist_utils.js
function updateTableVisibility(tableId, noDataRowSelector) {
    const tableElement = document.querySelector(tableId);
    if (!tableElement) {
        // console.warn(`updateTableVisibility: Table with ID "${tableId}" not found.`);
        return;
    }
    const tableBody = tableElement.querySelector('tbody');
    if (!tableBody) {
        // console.warn(`updateTableVisibility: tbody not found in table "${tableId}".`);
        return;
    }

    const noDataRow = tableBody.querySelector(noDataRowSelector);
    const dataRows = tableBody.querySelectorAll('tr:not(.no-data-row)');
    let hasVisibleRows = false;
    if (dataRows.length > 0) {
        hasVisibleRows = Array.from(dataRows).some(row => {
            const style = window.getComputedStyle(row);
            return style.display !== 'none';
        });
    }
    
    if (noDataRow) {
        noDataRow.style.display = hasVisibleRows ? 'none' : 'table-row';
    }
}

// ... any other common utility functions for the receptionist module ...