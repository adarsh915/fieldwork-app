/**
 * Secure AJAX Helper Utility
 * Provides request cancellation, error handling, and CSRF protection
 * 
 * @author AMPL Fieldwork Development Team
 * @version 1.0.0
 */

class AjaxHelper {
    constructor() {
        // Store active abort controllers for each endpoint
        this.abortControllers = new Map();
        
        // Default configuration
        this.config = {
            timeout: 30000, // 30 seconds
            retryAttempts: 2,
            retryDelay: 1000,
        };
    }

    /**
     * Get CSRF token from meta tag
     * @returns {string}
     */
    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            console.error('CSRF token not found. Ensure <meta name="csrf-token"> is in your layout.');
        }
        return token || '';
    }

    /**
     * Cancel previous request for a specific key
     * @param {string} key - Unique identifier for the request
     */
    cancelPrevious(key) {
        if (this.abortControllers.has(key)) {
            this.abortControllers.get(key).abort();
            this.abortControllers.delete(key);
        }
    }

    /**
     * Create new abort controller for a request
     * @param {string} key - Unique identifier for the request
     * @returns {AbortController}
     */
    createAbortController(key) {
        this.cancelPrevious(key);
        const controller = new AbortController();
        this.abortControllers.set(key, controller);
        return controller;
    }

    /**
     * Build fetch options with security headers
     * @param {string} method - HTTP method
     * @param {Object} data - Request body data
     * @param {Object} customHeaders - Additional headers
     * @param {AbortSignal} signal - Abort signal
     * @returns {Object}
     */
    buildOptions(method, data = null, customHeaders = {}, signal = null) {
        const options = {
            method: method.toUpperCase(),
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...customHeaders,
            },
            credentials: 'same-origin', // Include cookies for session auth
        };

        // Add CSRF token for state-changing requests
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method)) {
            options.headers['X-CSRF-TOKEN'] = this.getCsrfToken();
        }

        // Add request body
        if (data && options.method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        // Add abort signal
        if (signal) {
            options.signal = signal;
        }

        return options;
    }

    /**
     * Secure fetch with automatic retry and error handling
     * @param {string} url - Request URL
     * @param {Object} options - Fetch options
     * @param {number} attempt - Current retry attempt
     * @returns {Promise<Response>}
     */
    async fetchWithRetry(url, options, attempt = 1) {
        try {
            const response = await fetch(url, options);

            // Check for HTTP errors
            if (!response.ok) {
                const error = new Error(`HTTP ${response.status}: ${response.statusText}`);
                error.response = response;
                error.status = response.status;
                
                // Try to parse error message from JSON
                try {
                    const errorData = await response.json();
                    error.message = errorData.message || error.message;
                    error.errors = errorData.errors || null;
                } catch (e) {
                    // Response is not JSON, use status text
                }
                
                throw error;
            }

            return response;

        } catch (error) {
            // Don't retry if request was aborted
            if (error.name === 'AbortError') {
                console.log(`Request cancelled: ${url}`);
                throw error;
            }

            // Don't retry client errors (4xx)
            if (error.status && error.status >= 400 && error.status < 500) {
                throw error;
            }

            // Retry on network errors or server errors (5xx)
            if (attempt < this.config.retryAttempts) {
                console.warn(`Retry attempt ${attempt} for ${url}`);
                await new Promise(resolve => setTimeout(resolve, this.config.retryDelay * attempt));
                return this.fetchWithRetry(url, options, attempt + 1);
            }

            // Max retries reached
            throw error;
        }
    }

    /**
     * Main request method
     * @param {string} url - Request URL
     * @param {Object} config - Request configuration
     * @returns {Promise<any>}
     */
    async request(url, {
        method = 'GET',
        data = null,
        headers = {},
        cancelKey = null,
        parseJson = true,
        timeout = null,
    } = {}) {
        
        // Create abort controller if cancelKey provided
        let controller = null;
        let timeoutId = null;

        if (cancelKey) {
            controller = this.createAbortController(cancelKey);
        }

        // Set timeout
        const requestTimeout = timeout || this.config.timeout;
        if (requestTimeout && controller) {
            timeoutId = setTimeout(() => {
                controller.abort();
                console.warn(`Request timeout: ${url}`);
            }, requestTimeout);
        }

        try {
            const options = this.buildOptions(method, data, headers, controller?.signal);
            const response = await this.fetchWithRetry(url, options);

            // Clear timeout
            if (timeoutId) {
                clearTimeout(timeoutId);
            }

            // Parse response
            if (parseJson) {
                const json = await response.json();
                return json;
            }

            return response;

        } catch (error) {
            // Clear timeout
            if (timeoutId) {
                clearTimeout(timeoutId);
            }

            // Clean up abort controller
            if (cancelKey) {
                this.abortControllers.delete(cancelKey);
            }

            throw error;

        } finally {
            // Clean up abort controller
            if (cancelKey && this.abortControllers.has(cancelKey)) {
                this.abortControllers.delete(cancelKey);
            }
        }
    }

    /**
     * Convenience methods
     */
    get(url, config = {}) {
        return this.request(url, { ...config, method: 'GET' });
    }

    post(url, data = null, config = {}) {
        return this.request(url, { ...config, method: 'POST', data });
    }

    patch(url, data = null, config = {}) {
        return this.request(url, { ...config, method: 'PATCH', data });
    }

    put(url, data = null, config = {}) {
        return this.request(url, { ...config, method: 'PUT', data });
    }

    delete(url, config = {}) {
        return this.request(url, { ...config, method: 'DELETE' });
    }

    /**
     * Cancel all pending requests
     */
    cancelAll() {
        this.abortControllers.forEach(controller => controller.abort());
        this.abortControllers.clear();
    }
}

// Export singleton instance
export const ajax = new AjaxHelper();

// Also export class for custom instances
export default AjaxHelper;
