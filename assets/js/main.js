/**
 * CUHK Law E-Mentoring Platform - Main JavaScript
 */

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Table Sorting Function
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
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
        
        // Check if values are numeric
        const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
        const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            aVal = aNum;
            bVal = bNum;
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

// Mobile Accordion Toggle
function toggleAccordion(element) {
    const body = element.nextElementSibling;
    const allBodies = document.querySelectorAll('.accordion-body');
    
    // Close all other accordions
    allBodies.forEach(b => {
        if (b !== body) {
            b.classList.remove('active');
        }
    });
    
    // Toggle current accordion
    body.classList.toggle('active');
}

// Form Validation for "Other" inputs
function validateOtherInput(input) {
    if (input.value.includes('"')) {
        alert('Double quotes (") are not allowed in this field');
        input.value = input.value.replace(/"/g, '');
        return false;
    }
    return true;
}

// Handle "Other" option selection
function setupOtherOptionHandlers() {
    // Programme Level
    const programmeLevelSelect = document.getElementById('programme_level');
    if (programmeLevelSelect) {
        programmeLevelSelect.addEventListener('change', function() {
            const otherDiv = document.getElementById('programme_level_other_div');
            const otherInput = document.getElementById('programme_level_other');
            
            if (this.value === 'Other') {
                otherDiv.style.display = 'block';
                otherInput.required = true;
            } else {
                otherDiv.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        });
        
        // Initialize on page load
        if (programmeLevelSelect.value === 'Other') {
            document.getElementById('programme_level_other_div').style.display = 'block';
            document.getElementById('programme_level_other').required = true;
        }
    }
    
    // Practice Area
    const practiceAreaSelect = document.getElementById('practice_area_preference') || 
                               document.getElementById('practice_area');
    if (practiceAreaSelect) {
        practiceAreaSelect.addEventListener('change', function() {
            const otherDiv = document.getElementById('practice_area_other_div');
            const otherInput = document.getElementById('practice_area_other');
            
            if (this.value === 'Other') {
                otherDiv.style.display = 'block';
                otherInput.required = true;
            } else {
                otherDiv.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        });
        
        // Initialize on page load
        if (practiceAreaSelect.value === 'Other') {
            document.getElementById('practice_area_other_div').style.display = 'block';
            document.getElementById('practice_area_other').required = true;
        }
    }
    
    // Validate "Other" inputs
    const programmeOtherInput = document.getElementById('programme_level_other');
    if (programmeOtherInput) {
        programmeOtherInput.addEventListener('input', function() {
            validateOtherInput(this);
        });
    }
    
    const practiceAreaOtherInput = document.getElementById('practice_area_other');
    if (practiceAreaOtherInput) {
        practiceAreaOtherInput.addEventListener('input', function() {
            validateOtherInput(this);
        });
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    setupOtherOptionHandlers();
});
