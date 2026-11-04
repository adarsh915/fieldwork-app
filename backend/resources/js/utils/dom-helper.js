/**
 * DOM Helper Utility
 * Provides XSS-safe DOM manipulation methods
 * Replaces dangerous innerHTML with secure alternatives
 * 
 * @author AMPL Fieldwork Development Team
 * @version 1.0.0
 */

class DOMHelper {
    /**
     * Safely set HTML content with automatic XSS protection
     * @param {HTMLElement|string} element - Element or element ID
     * @param {string} html - HTML content to set
     * @param {Object} options - Options
     * @returns {HTMLElement}
     */
    setHTML(element, html, options = {}) {
        const {
            sanitize = true,
            allowedTags = ['span', 'div', 'p', 'a', 'strong', 'em', 'br', 'ul', 'ol', 'li', 'table', 'tr', 'td', 'th'],
            allowedAttributes = ['class', 'href', 'title', 'data-*'],
        } = options;

        const el = typeof element === 'string' ? document.getElementById(element) : element;
        if (!el) {
            console.error('Element not found:', element);
            return null;
        }

        if (sanitize) {
            // Use browser's built-in sanitizer if available (modern browsers)
            if (window.Sanitizer) {
                const sanitizer = new Sanitizer({
                    allowElements: allowedTags,
                    allowAttributes: allowedAttributes.reduce((acc, attr) => {
                        acc[attr] = ['*'];
                        return acc;
                    }, {}),
                });
                el.setHTML(html, { sanitizer });
            } else {
                // Fallback: Basic sanitization
                el.innerHTML = this.sanitize(html, allowedTags, allowedAttributes);
            }
        } else {
            el.innerHTML = html;
        }

        return el;
    }

    /**
     * Basic HTML sanitization (fallback for browsers without Sanitizer API)
     * @param {string} html - HTML to sanitize
     * @param {Array} allowedTags - Allowed HTML tags
     * @param {Array} allowedAttributes - Allowed attributes
     * @returns {string}
     */
    sanitize(html, allowedTags, allowedAttributes) {
        const temp = document.createElement('div');
        temp.textContent = html; // This escapes everything
        
        // For now, just return escaped content
        // In production, use DOMPurify for proper sanitization
        return temp.innerHTML;
    }

    /**
     * Safely set text content (always XSS-safe)
     * @param {HTMLElement|string} element - Element or element ID
     * @param {string} text - Text content to set
     * @returns {HTMLElement}
     */
    setText(element, text) {
        const el = typeof element === 'string' ? document.getElementById(element) : element;
        if (!el) {
            console.error('Element not found:', element);
            return null;
        }

        el.textContent = text;
        return el;
    }

    /**
     * Create element with attributes (XSS-safe)
     * @param {string} tagName - Tag name
     * @param {Object} attributes - Element attributes
     * @param {string} content - Text content (will be escaped)
     * @returns {HTMLElement}
     */
    createElement(tagName, attributes = {}, content = '') {
        const element = document.createElement(tagName);

        // Set attributes safely
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'style' && typeof value === 'object') {
                Object.assign(element.style, value);
            } else if (key.startsWith('data-')) {
                element.setAttribute(key, value);
            } else {
                element[key] = value;
            }
        });

        // Set content safely
        if (content) {
            element.textContent = content;
        }

        return element;
    }

    /**
     * Create multiple elements from array (XSS-safe)
     * @param {string} tagName - Tag name for all elements
     * @param {Array} items - Array of {attributes, content} objects
     * @returns {Array<HTMLElement>}
     */
    createElements(tagName, items) {
        return items.map(item => {
            return this.createElement(tagName, item.attributes || {}, item.content || '');
        });
    }

    /**
     * Safely append HTML badges/chips (common use case)
     * @param {HTMLElement|string} container - Container element
     * @param {Array<string>} items - Badge texts
     * @param {string} badgeClass - CSS class for badges
     * @returns {HTMLElement}
     */
    appendBadges(container, items, badgeClass = 'badge badge-primary') {
        const el = typeof container === 'string' ? document.getElementById(container) : container;
        if (!el) {
            console.error('Container not found:', container);
            return null;
        }

        // Clear container
        el.innerHTML = '';

        // Create badges safely
        items.forEach(item => {
            const badge = this.createElement('span', {
                className: badgeClass,
            }, item);
            el.appendChild(badge);
        });

        return el;
    }

    /**
     * Safely create a table row (common use case)
     * @param {Array} cells - Array of cell contents
     * @param {Object} options - Options
     * @returns {HTMLElement}
     */
    createTableRow(cells, options = {}) {
        const {
            cellType = 'td',
            rowClass = '',
            cellClasses = [],
        } = options;

        const tr = document.createElement('tr');
        if (rowClass) tr.className = rowClass;

        cells.forEach((cellContent, index) => {
            const cell = document.createElement(cellType);
            if (cellClasses[index]) cell.className = cellClasses[index];
            
            // If content is object with html property, treat as trusted HTML
            if (typeof cellContent === 'object' && cellContent.html) {
                cell.innerHTML = cellContent.html;
            } else {
                cell.textContent = cellContent;
            }
            
            tr.appendChild(cell);
        });

        return tr;
    }

    /**
     * Safely update button text with loading state
     * @param {HTMLElement} button - Button element
     * @param {string} text - New button text
     * @param {boolean} loading - Show loading spinner
     * @returns {Function} - Function to restore original state
     */
    updateButton(button, text, loading = false) {
        const originalHTML = button.innerHTML;
        const originalDisabled = button.disabled;

        if (loading) {
            button.disabled = true;
            const spinner = this.createElement('span', {
                className: 'inline-block animate-spin mr-2',
            }, '↻');
            button.innerHTML = '';
            button.appendChild(spinner);
            button.appendChild(document.createTextNode(text));
        } else {
            button.textContent = text;
        }

        // Return restore function
        return () => {
            button.innerHTML = originalHTML;
            button.disabled = originalDisabled;
        };
    }

    /**
     * Safely clear an element's content
     * @param {HTMLElement|string} element - Element to clear
     * @returns {HTMLElement}
     */
    clear(element) {
        const el = typeof element === 'string' ? document.getElementById(element) : element;
        if (!el) {
            console.error('Element not found:', element);
            return null;
        }

        while (el.firstChild) {
            el.removeChild(el.firstChild);
        }

        return el;
    }

    /**
     * Safely append child elements
     * @param {HTMLElement|string} parent - Parent element
     * @param {HTMLElement|Array<HTMLElement>} children - Child element(s)
     * @returns {HTMLElement}
     */
    append(parent, children) {
        const el = typeof parent === 'string' ? document.getElementById(parent) : parent;
        if (!el) {
            console.error('Parent element not found:', parent);
            return null;
        }

        const childArray = Array.isArray(children) ? children : [children];
        childArray.forEach(child => {
            if (child instanceof HTMLElement) {
                el.appendChild(child);
            }
        });

        return el;
    }

    /**
     * Safely prepend child elements
     * @param {HTMLElement|string} parent - Parent element
     * @param {HTMLElement|Array<HTMLElement>} children - Child element(s)
     * @returns {HTMLElement}
     */
    prepend(parent, children) {
        const el = typeof parent === 'string' ? document.getElementById(parent) : parent;
        if (!el) {
            console.error('Parent element not found:', parent);
            return null;
        }

        const childArray = Array.isArray(children) ? children : [children];
        childArray.reverse().forEach(child => {
            if (child instanceof HTMLElement) {
                el.insertBefore(child, el.firstChild);
            }
        });

        return el;
    }

    /**
     * Escape HTML to prevent XSS (utility method)
     * @param {string} text - Text to escape
     * @returns {string}
     */
    escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Create icon element (SVG or text)
     * @param {string} type - Icon type or SVG path
     * @param {Object} options - Options
     * @returns {HTMLElement}
     */
    createIcon(type, options = {}) {
        const { className = 'w-5 h-5', color = 'currentColor' } = options;

        // Common icon SVGs
        const icons = {
            spinner: `<svg class="${className} animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="${color}" stroke-width="4"></circle>
                <path class="opacity-75" fill="${color}" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>`,
            check: `<svg class="${className}" fill="none" stroke="${color}" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>`,
            close: `<svg class="${className}" fill="none" stroke="${color}" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>`,
        };

        const svg = icons[type] || type;
        const temp = document.createElement('div');
        temp.innerHTML = svg;
        return temp.firstElementChild;
    }
}

// Create and export singleton instance
export const dom = new DOMHelper();

// Make globally available
if (typeof window !== 'undefined') {
    window.dom = dom;
}

export default DOMHelper;
