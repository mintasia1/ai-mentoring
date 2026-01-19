/**
 * Browse Mentors Page - JavaScript
 * Handles modal for Send Request and table sorting
 */

// Send Request Modal Functions
function openSendRequestModal(mentorId, mentorName) {
    document.getElementById('modal_mentor_id').value = mentorId;
    document.getElementById('mentorNameDisplay').textContent = mentorName;
    document.getElementById('modal_message').value = '';
    document.getElementById('sendRequestModal').style.display = 'block';
}

function closeSendRequestModal() {
    document.getElementById('sendRequestModal').style.display = 'none';
}

// Close modal when clicking outside of it
window.addEventListener('click', function(event) {
    const modal = document.getElementById('sendRequestModal');
    if (event.target == modal) {
        closeSendRequestModal();
    }
});

// Table sorting specific to browse mentors
function sortTable(columnIndex) {
    const table = document.getElementById("mentorsTable");
    const tbody = table.getElementsByTagName("tbody")[0];
    const rows = Array.from(tbody.getElementsByTagName("tr"));
    
    // Determine sort direction
    const currentSort = table.getAttribute("data-sort-col");
    const currentDir = table.getAttribute("data-sort-dir");
    let sortDir = "asc";
    
    if (currentSort == columnIndex && currentDir == "asc") {
        sortDir = "desc";
    }
    
    // Sort rows
    rows.sort(function(a, b) {
        let aVal = a.getElementsByTagName("td")[columnIndex].textContent.trim();
        let bVal = b.getElementsByTagName("td")[columnIndex].textContent.trim();
        
        // Handle numeric values (Match Score and Availability)
        if (columnIndex === 0) { // Match Score
            aVal = parseFloat(aVal);
            bVal = parseFloat(bVal);
        } else if (columnIndex === 6) { // Availability
            aVal = parseInt(aVal.split('/')[0]);
            bVal = parseInt(aVal.split('/')[0]);
        }
        
        if (aVal < bVal) return sortDir === "asc" ? -1 : 1;
        if (aVal > bVal) return sortDir === "asc" ? 1 : -1;
        return 0;
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
    
    // Store sort state
    table.setAttribute("data-sort-col", columnIndex);
    table.setAttribute("data-sort-dir", sortDir);
}
