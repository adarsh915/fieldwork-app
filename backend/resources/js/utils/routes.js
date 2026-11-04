/**
 * Route Helper Utility
 * Provides safe route URL generation using Laravel route() helper
 * Prevents hardcoded URLs that break on route changes
 * 
 * @author AMPL Fieldwork Development Team
 * @version 1.0.0
 */

class RouteHelper {
    constructor() {
        this.routes = {};
        this.baseUrl = window.location.origin;
    }

    /**
     * Register routes from Laravel
     * Call this from your Blade layout with route definitions
     * @param {Object} routes - Route definitions
     */
    register(routes) {
        this.routes = { ...this.routes, ...routes };
    }

    /**
     * Get route URL with parameter replacement
     * @param {string} name - Route name
     * @param {Object|Array|string|number} params - Route parameters
     * @returns {string}
     */
    get(name, params = null) {
        if (!this.routes[name]) {
            console.error(`Route "${name}" not found. Did you register it?`);
            return '#route-not-found';
        }

        let url = this.routes[name];

        // Handle parameter replacement
        if (params !== null && params !== undefined) {
            // Convert single parameter to object
            if (typeof params === 'string' || typeof params === 'number') {
                // Replace first parameter placeholder
                url = url.replace(/:\w+/, params);
            } 
            // Handle array of parameters
            else if (Array.isArray(params)) {
                params.forEach(param => {
                    url = url.replace(/:\w+/, param);
                });
            }
            // Handle object of named parameters
            else if (typeof params === 'object') {
                Object.entries(params).forEach(([key, value]) => {
                    url = url.replace(`:${key}`, value);
                    // Also handle Laravel's {key} format
                    url = url.replace(`{${key}}`, value);
                });
            }
        }

        // Ensure URL starts with /
        if (!url.startsWith('http') && !url.startsWith('/')) {
            url = '/' + url;
        }

        return url;
    }

    /**
     * Check if route exists
     * @param {string} name - Route name
     * @returns {boolean}
     */
    has(name) {
        return this.routes.hasOwnProperty(name);
    }

    /**
     * Get all registered routes
     * @returns {Object}
     */
    all() {
        return { ...this.routes };
    }

    /**
     * Build query string from object
     * @param {Object} params - Query parameters
     * @returns {string}
     */
    buildQuery(params) {
        const query = new URLSearchParams(params).toString();
        return query ? '?' + query : '';
    }

    /**
     * Get route with query parameters
     * @param {string} name - Route name
     * @param {Object|string|number} pathParams - Path parameters
     * @param {Object} queryParams - Query parameters
     * @returns {string}
     */
    getWithQuery(name, pathParams = null, queryParams = {}) {
        const url = this.get(name, pathParams);
        const query = this.buildQuery(queryParams);
        return url + query;
    }
}

// Create and export singleton instance
export const route = new RouteHelper();

// Make globally available
if (typeof window !== 'undefined') {
    window.route = route;
}

export default RouteHelper;
