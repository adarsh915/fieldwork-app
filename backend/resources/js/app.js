/**
 * AMPL Fieldwork - Main Application JavaScript
 * Entry point for all JavaScript utilities
 */

import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import DataTable from 'datatables.net-dt';

// Set global DataTables defaults for consistent pagination & language
if (DataTable && DataTable.defaults) {
    Object.assign(DataTable.defaults, {
        pagingType: 'simple_numbers',
        language: {
            paginate: {
                previous: '‹ Prev',
                next: 'Next ›'
            }
        },
        infoCallback: function (settings, start, end, max, total, pre) {
            if (!total || total === 0) {
                return 'Showing <span class="font-semibold text-ink-900">0</span> to <span class="font-semibold text-ink-900">0</span> of <span class="font-semibold text-ink-900">0</span> results';
            }
            if (pre) {
                // Standardize 'entries' to 'results' and wrap all numbers in <span class="font-semibold text-ink-900">
                return pre
                    .replace(/\bentries\b/gi, 'results')
                    .replace(/\b(\d[\d,.]*)\b/g, '<span class="font-semibold text-ink-900">$1</span>');
            }
            return `Showing <span class="font-semibold text-ink-900">${start}</span> to <span class="font-semibold text-ink-900">${end}</span> of <span class="font-semibold text-ink-900">${total}</span> results`;
        }
    });
}

Alpine.plugin(persist);

// Import utilities
import { ajax } from './utils/ajax-helper';
import { toast } from './utils/toast';
import { route } from './utils/routes';
import { dom } from './utils/dom-helper';

// Make utilities globally available
window.Alpine = Alpine;
window.DataTable = DataTable;
window.ajax = ajax;
window.toast = toast;
window.route = route;
window.dom = dom;

// Initialize Alpine.js
Alpine.start();

// Initialize application on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('AMPL Fieldwork Application Initialized');
    
    // Cancel all pending AJAX requests when leaving page
    window.addEventListener('beforeunload', () => {
        ajax.cancelAll();
    });
});

// Export for module usage
export { ajax, toast, route, dom, Alpine };
