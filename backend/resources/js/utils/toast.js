/**
 * Toast Notification System
 * Replaces blocking alert() with modern non-blocking notifications
 * 
 * @author AMPL Fieldwork Development Team
 * @version 1.0.0
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.maxToasts = 5;
        this.defaultDuration = 5000; // 5 seconds
        
        // Initialize on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    /**
     * Initialize toast container
     */
    init() {
        if (this.container) return;

        // Create container
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'fixed top-4 right-4 z-[9999] space-y-2 pointer-events-none';
        this.container.setAttribute('aria-live', 'polite');
        this.container.setAttribute('aria-atomic', 'true');
        
        document.body.appendChild(this.container);
    }

    /**
     * Get icon and colors for toast type
     * @param {string} type - Toast type (success, error, warning, info)
     * @returns {Object}
     */
    getTypeConfig(type) {
        const configs = {
            success: {
                icon: `<svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`,
                bgColor: 'bg-white dark:bg-gray-800',
                borderColor: 'border-l-4 border-green-500',
                titleColor: 'text-green-800 dark:text-green-200',
            },
            error: {
                icon: `<svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`,
                bgColor: 'bg-white dark:bg-gray-800',
                borderColor: 'border-l-4 border-red-500',
                titleColor: 'text-red-800 dark:text-red-200',
            },
            warning: {
                icon: `<svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>`,
                bgColor: 'bg-white dark:bg-gray-800',
                borderColor: 'border-l-4 border-yellow-500',
                titleColor: 'text-yellow-800 dark:text-yellow-200',
            },
            info: {
                icon: `<svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>`,
                bgColor: 'bg-white dark:bg-gray-800',
                borderColor: 'border-l-4 border-blue-500',
                titleColor: 'text-blue-800 dark:text-blue-200',
            },
        };

        return configs[type] || configs.info;
    }

    /**
     * Show toast notification
     * @param {string} message - Toast message
     * @param {string} type - Toast type (success, error, warning, info)
     * @param {Object} options - Additional options
     * @returns {HTMLElement}
     */
    show(message, type = 'info', options = {}) {
        this.init(); // Ensure container exists

        const {
            duration = this.defaultDuration,
            dismissible = true,
            title = null,
        } = options;

        // Remove oldest toast if max limit reached
        if (this.toasts.length >= this.maxToasts) {
            this.remove(this.toasts[0]);
        }

        const config = this.getTypeConfig(type);
        const toastId = `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

        // Create toast element
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `${config.bgColor} ${config.borderColor} shadow-lg rounded-lg p-4 max-w-sm w-full pointer-events-auto transform transition-all duration-300 ease-in-out opacity-0 translate-x-full`;
        toast.setAttribute('role', 'alert');

        const titleHtml = title ? `<p class="font-semibold ${config.titleColor}">${this.escapeHtml(title)}</p>` : '';

        toast.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${config.icon}
                </div>
                <div class="ml-3 flex-1">
                    ${titleHtml}
                    <p class="text-sm text-gray-700 dark:text-gray-300 ${title ? 'mt-1' : ''}">
                        ${this.escapeHtml(message)}
                    </p>
                </div>
                ${dismissible ? `
                <button type="button" 
                    class="ml-3 flex-shrink-0 inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-md"
                    onclick="window.toastManager.remove('${toastId}')"
                    aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                ` : ''}
            </div>
        `;

        // Add to container
        this.container.appendChild(toast);
        this.toasts.push(toastId);

        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', 'translate-x-full');
        });

        // Auto-dismiss after duration
        if (duration > 0) {
            setTimeout(() => this.remove(toastId), duration);
        }

        return toast;
    }

    /**
     * Remove toast
     * @param {string|HTMLElement} toastOrId - Toast element or ID
     */
    remove(toastOrId) {
        const toastId = typeof toastOrId === 'string' ? toastOrId : toastOrId;
        const toast = typeof toastOrId === 'string' 
            ? document.getElementById(toastId) 
            : toastOrId;

        if (!toast) return;

        // Animate out
        toast.classList.add('opacity-0', 'translate-x-full');

        // Remove after animation
        setTimeout(() => {
            toast.remove();
            this.toasts = this.toasts.filter(id => id !== toastId && id !== toast.id);
        }, 300);
    }

    /**
     * Convenience methods
     */
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    info(message, options = {}) {
        return this.show(message, 'info', options);
    }

    /**
     * Handle API error responses
     * @param {Error} error - Error object
     * @param {string} fallbackMessage - Default message if error parsing fails
     */
    handleApiError(error, fallbackMessage = 'An error occurred. Please try again.') {
        console.error('API Error:', error);

        let message = fallbackMessage;
        let title = 'Error';

        if (error.response) {
            // HTTP error response
            if (error.status === 403) {
                title = 'Access Denied';
                message = error.message || 'You do not have permission to perform this action.';
            } else if (error.status === 404) {
                title = 'Not Found';
                message = error.message || 'The requested resource was not found.';
            } else if (error.status === 422) {
                title = 'Validation Error';
                message = error.message || 'Please check your input and try again.';
                
                // Display validation errors
                if (error.errors) {
                    const errorList = Object.values(error.errors).flat().join('<br>');
                    message += '<br><br>' + errorList;
                }
            } else if (error.status >= 500) {
                title = 'Server Error';
                message = 'A server error occurred. Our team has been notified.';
            } else {
                message = error.message || fallbackMessage;
            }
        } else if (error.name === 'AbortError') {
            // Request cancelled - don't show error
            return;
        } else if (error.message && error.message.includes('Failed to fetch')) {
            title = 'Network Error';
            message = 'Unable to connect to the server. Please check your internet connection.';
        } else {
            message = error.message || fallbackMessage;
        }

        this.error(message, { title, duration: 7000 });
    }

    /**
     * Clear all toasts
     */
    clear() {
        this.toasts.forEach(toastId => this.remove(toastId));
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string}
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Create and export singleton instance
export const toast = new ToastManager();

// Make globally available
if (typeof window !== 'undefined') {
    window.toast = toast;
    window.toastManager = toast; // For inline onclick handlers
}

export default ToastManager;
