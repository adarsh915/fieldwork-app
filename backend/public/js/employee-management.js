/**
 * Employee Management JavaScript
 * Handles interactions for employee list, create, edit, and delete operations
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initEmployeeManagement();
    });

    function initEmployeeManagement() {
        // Initialize all features
        initDeleteConfirmation();
        initToggleStatusConfirmation();
        initSearchFilters();
        initToastNotifications();
        initTableInteractions();
        initFormValidation();
    }

    /**
     * Delete Confirmation
     */
    function initDeleteConfirmation() {
        const deleteForms = document.querySelectorAll('form[action*="destroy"]');
        
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const confirmed = confirm('Are you sure you want to delete this employee? This action cannot be undone.');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Toggle Status Confirmation
     */
    function initToggleStatusConfirmation() {
        const toggleForms = document.querySelectorAll('form[action*="toggle-status"]');
        
        toggleForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = form.querySelector('button[type="submit"]');
                const action = button.textContent.trim();
                const confirmed = confirm(`Are you sure you want to ${action.toLowerCase()} this employee?`);
                
                if (!confirmed) {
                    e.preventDefault();
                } else {
                    // Show loading state
                    button.disabled = true;
                    button.textContent = 'Processing...';
                }
            });
        });
    }

    /**
     * Search and Filter Functionality
     */
    function initSearchFilters() {
        const searchInput = document.querySelector('input[name="q"]');
        const filterForm = document.querySelector('form[action*="employees"]');
        
        if (searchInput && filterForm) {
            // Auto-submit on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    filterForm.submit();
                }
            });

            // Optional: Real-time search (debounced)
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                
                // Uncomment for auto-search after 500ms of no typing
                // searchTimeout = setTimeout(() => {
                //     filterForm.submit();
                // }, 500);
            });
        }

        // Filter dropdowns auto-submit
        const filterSelects = document.querySelectorAll('select[name="role"], select[name="status"]');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                if (filterForm) {
                    filterForm.submit();
                }
            });
        });
    }

    /**
     * Toast Notifications
     */
    function initToastNotifications() {
        // Auto-hide success messages after 5 seconds
        const successMessages = document.querySelectorAll('.toast-notification, [class*="bg-emerald-50"]');
        
        successMessages.forEach(message => {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 5000);
        });
    }

    /**
     * Table Interactions
     */
    function initTableInteractions() {
        const tableRows = document.querySelectorAll('.employee-table tbody tr');
        
        tableRows.forEach(row => {
            // Add click to edit functionality (optional)
            row.addEventListener('click', function(e) {
                // Don't trigger if clicking on buttons or forms
                if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('form')) {
                    return;
                }
                
                // Optional: Navigate to edit page on row click
                // const editLink = row.querySelector('a[href*="edit"]');
                // if (editLink) {
                //     window.location.href = editLink.href;
                // }
            });
        });

        // Highlight row on hover
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8fafc';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    }

    /**
     * Form Validation
     */
    function initFormValidation() {
        const employeeForms = document.querySelectorAll('form[action*="employees"]');
        
        employeeForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Check required fields
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('border-red-500');
                        
                        // Show error message
                        let errorMsg = field.parentElement.querySelector('.error-message');
                        if (!errorMsg) {
                            errorMsg = document.createElement('p');
                            errorMsg.className = 'error-message text-sm text-red-600 mt-1';
                            errorMsg.textContent = 'This field is required';
                            field.parentElement.appendChild(errorMsg);
                        }
                    } else {
                        field.classList.remove('border-red-500');
                        const errorMsg = field.parentElement.querySelector('.error-message');
                        if (errorMsg) {
                            errorMsg.remove();
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    showToast('Please fill in all required fields', 'error');
                }
            });

            // Remove error styling on input
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('border-red-500');
                    const errorMsg = this.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                });
            });
        });
    }

    /**
     * Show Toast Notification
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="text-sm font-medium">${message}</div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                    ✕
                </button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    /**
     * Show Loading Overlay
     */
    function showLoading() {
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(overlay);
    }

    /**
     * Hide Loading Overlay
     */
    function hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Bulk Selection (for future bulk actions)
     */
    function initBulkSelection() {
        const selectAllCheckbox = document.getElementById('select-all-employees');
        const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                employeeCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkActionButton();
            });
        }
        
        employeeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActionButton);
        });
    }

    /**
     * Update Bulk Action Button
     */
    function updateBulkActionButton() {
        const selectedCount = document.querySelectorAll('.employee-checkbox:checked').length;
        const bulkActionBtn = document.getElementById('bulk-action-btn');
        
        if (bulkActionBtn) {
            if (selectedCount > 0) {
                bulkActionBtn.disabled = false;
                bulkActionBtn.textContent = `Actions (${selectedCount})`;
            } else {
                bulkActionBtn.disabled = true;
                bulkActionBtn.textContent = 'Bulk Actions';
            }
        }
    }

    /**
     * Export to CSV (Future feature)
     */
    function exportToCSV() {
        showLoading();
        
        // Get table data
        const table = document.querySelector('.employee-table');
        let csv = [];
        
        // Headers
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        csv.push(headers.join(','));
        
        // Rows
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td')).map(td => {
                // Clean up and escape CSV data
                return '"' + td.textContent.trim().replace(/"/g, '""') + '"';
            });
            csv.push(cells.join(','));
        });
        
        // Download
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'employees_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        
        hideLoading();
        showToast('Employee data exported successfully!', 'success');
    }

    // Make functions globally available if needed
    window.EmployeeManagement = {
        showToast: showToast,
        showLoading: showLoading,
        hideLoading: hideLoading,
        exportToCSV: exportToCSV
    };

})();
