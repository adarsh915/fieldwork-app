/**
 * AMPL Fieldwork - Main Application JavaScript
 * Entry point for all JavaScript utilities
 */

import './bootstrap';
import Alpine from 'alpinejs';

// Import utilities
import { ajax } from './utils/ajax-helper';
import { toast } from './utils/toast';
import { route } from './utils/routes';
import { dom } from './utils/dom-helper';

// Make utilities globally available
window.Alpine = Alpine;
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
