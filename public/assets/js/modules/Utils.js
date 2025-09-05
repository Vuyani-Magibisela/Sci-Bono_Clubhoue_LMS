/**
 * Core Utilities JavaScript Module
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

// Define the Utils module
ModuleLoader.define('Utils', [], function() {
    'use strict';
    
    /**
     * Core utility functions and helpers
     */
    const Utils = {
        
        /**
         * DOM Utilities
         */
        DOM: {
            /**
             * Query selector with optional context
             * @param {string} selector - CSS selector
             * @param {Element} context - Optional context element
             * @returns {Element|null} Found element or null
             */
            $(selector, context = document) {
                return context.querySelector(selector);
            },
            
            /**
             * Query selector all with optional context
             * @param {string} selector - CSS selector
             * @param {Element} context - Optional context element
             * @returns {NodeList} NodeList of found elements
             */
            $$(selector, context = document) {
                return context.querySelectorAll(selector);
            },
            
            /**
             * Create element with attributes and content
             * @param {string} tag - HTML tag name
             * @param {Object} attributes - Element attributes
             * @param {string|Element|Array} content - Element content
             * @returns {Element} Created element
             */
            createElement(tag, attributes = {}, content = '') {
                const element = document.createElement(tag);
                
                // Set attributes
                Object.keys(attributes).forEach(key => {
                    if (key === 'className') {
                        element.className = attributes[key];
                    } else if (key === 'dataset') {
                        Object.keys(attributes[key]).forEach(dataKey => {
                            element.dataset[dataKey] = attributes[key][dataKey];
                        });
                    } else {
                        element.setAttribute(key, attributes[key]);
                    }
                });
                
                // Set content
                if (typeof content === 'string') {
                    element.textContent = content;
                } else if (content instanceof Element) {
                    element.appendChild(content);
                } else if (Array.isArray(content)) {
                    content.forEach(item => {
                        if (typeof item === 'string') {
                            element.appendChild(document.createTextNode(item));
                        } else if (item instanceof Element) {
                            element.appendChild(item);
                        }
                    });
                }
                
                return element;
            },
            
            /**
             * Check if element matches selector
             * @param {Element} element - Element to check
             * @param {string} selector - CSS selector
             * @returns {boolean} True if element matches
             */
            matches(element, selector) {
                return element.matches(selector);
            },
            
            /**
             * Find closest ancestor matching selector
             * @param {Element} element - Starting element
             * @param {string} selector - CSS selector
             * @returns {Element|null} Matching ancestor or null
             */
            closest(element, selector) {
                return element.closest(selector);
            },
            
            /**
             * Add class to element(s)
             * @param {Element|NodeList|Array} elements - Element(s) to modify
             * @param {string|Array} classes - Class name(s) to add
             */
            addClass(elements, classes) {
                this._applyToElements(elements, el => {
                    if (Array.isArray(classes)) {
                        el.classList.add(...classes);
                    } else {
                        el.classList.add(classes);
                    }
                });
            },
            
            /**
             * Remove class from element(s)
             * @param {Element|NodeList|Array} elements - Element(s) to modify
             * @param {string|Array} classes - Class name(s) to remove
             */
            removeClass(elements, classes) {
                this._applyToElements(elements, el => {
                    if (Array.isArray(classes)) {
                        el.classList.remove(...classes);
                    } else {
                        el.classList.remove(classes);
                    }
                });
            },
            
            /**
             * Toggle class on element(s)
             * @param {Element|NodeList|Array} elements - Element(s) to modify
             * @param {string} className - Class name to toggle
             * @param {boolean} force - Force add (true) or remove (false)
             */
            toggleClass(elements, className, force) {
                this._applyToElements(elements, el => {
                    el.classList.toggle(className, force);
                });
            },
            
            /**
             * Check if element has class
             * @param {Element} element - Element to check
             * @param {string} className - Class name to check
             * @returns {boolean} True if element has class
             */
            hasClass(element, className) {
                return element.classList.contains(className);
            },
            
            /**
             * Apply function to element(s)
             * @private
             * @param {Element|NodeList|Array} elements - Element(s)
             * @param {Function} fn - Function to apply
             */
            _applyToElements(elements, fn) {
                if (elements instanceof Element) {
                    fn(elements);
                } else if (elements instanceof NodeList || Array.isArray(elements)) {
                    Array.from(elements).forEach(fn);
                }
            }
        },
        
        /**
         * Event Utilities
         */
        Events: {
            /**
             * Add event listener with optional delegation
             * @param {Element|string} target - Element or selector
             * @param {string} event - Event type
             * @param {Function} handler - Event handler
             * @param {Object} options - Event options
             */
            on(target, event, handler, options = {}) {
                if (typeof target === 'string') {
                    // Event delegation
                    document.addEventListener(event, (e) => {
                        const delegateTarget = e.target.closest(target);
                        if (delegateTarget) {
                            handler.call(delegateTarget, e);
                        }
                    }, options);
                } else {
                    target.addEventListener(event, handler, options);
                }
            },
            
            /**
             * Remove event listener
             * @param {Element} target - Target element
             * @param {string} event - Event type
             * @param {Function} handler - Event handler
             * @param {Object} options - Event options
             */
            off(target, event, handler, options = {}) {
                target.removeEventListener(event, handler, options);
            },
            
            /**
             * Add one-time event listener
             * @param {Element} target - Target element
             * @param {string} event - Event type
             * @param {Function} handler - Event handler
             * @param {Object} options - Event options
             */
            once(target, event, handler, options = {}) {
                target.addEventListener(event, handler, { ...options, once: true });
            },
            
            /**
             * Trigger custom event
             * @param {Element} target - Target element
             * @param {string} event - Event type
             * @param {*} detail - Event detail data
             * @param {Object} options - Event options
             */
            trigger(target, event, detail = null, options = {}) {
                const customEvent = new CustomEvent(event, {
                    detail,
                    bubbles: true,
                    cancelable: true,
                    ...options
                });
                
                target.dispatchEvent(customEvent);
            },
            
            /**
             * Debounce function calls
             * @param {Function} func - Function to debounce
             * @param {number} wait - Wait time in milliseconds
             * @param {boolean} immediate - Execute immediately
             * @returns {Function} Debounced function
             */
            debounce(func, wait, immediate = false) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        timeout = null;
                        if (!immediate) func(...args);
                    };
                    const callNow = immediate && !timeout;
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                    if (callNow) func(...args);
                };
            },
            
            /**
             * Throttle function calls
             * @param {Function} func - Function to throttle
             * @param {number} limit - Time limit in milliseconds
             * @returns {Function} Throttled function
             */
            throttle(func, limit) {
                let inThrottle;
                return function(...args) {
                    if (!inThrottle) {
                        func.apply(this, args);
                        inThrottle = true;
                        setTimeout(() => inThrottle = false, limit);
                    }
                };
            }
        },
        
        /**
         * AJAX Utilities
         */
        AJAX: {
            /**
             * Make HTTP request
             * @param {string} url - Request URL
             * @param {Object} options - Request options
             * @returns {Promise} Promise resolving to response data
             */
            request(url, options = {}) {
                const defaults = {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                };
                
                const config = { ...defaults, ...options };
                
                // Add CSRF token if available
                const csrfToken = this.getCSRFToken();
                if (csrfToken) {
                    config.headers['X-CSRF-Token'] = csrfToken;
                }
                
                return fetch(url, config)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        }
                        return response.text();
                    })
                    .catch(error => {
                        console.error('AJAX request failed:', error);
                        throw error;
                    });
            },
            
            /**
             * GET request
             * @param {string} url - Request URL
             * @param {Object} params - URL parameters
             * @param {Object} options - Request options
             * @returns {Promise} Promise resolving to response data
             */
            get(url, params = {}, options = {}) {
                const urlWithParams = this.buildURL(url, params);
                return this.request(urlWithParams, { ...options, method: 'GET' });
            },
            
            /**
             * POST request
             * @param {string} url - Request URL
             * @param {*} data - Request body data
             * @param {Object} options - Request options
             * @returns {Promise} Promise resolving to response data
             */
            post(url, data = {}, options = {}) {
                return this.request(url, {
                    ...options,
                    method: 'POST',
                    body: JSON.stringify(data)
                });
            },
            
            /**
             * PUT request
             * @param {string} url - Request URL
             * @param {*} data - Request body data
             * @param {Object} options - Request options
             * @returns {Promise} Promise resolving to response data
             */
            put(url, data = {}, options = {}) {
                return this.request(url, {
                    ...options,
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
            },
            
            /**
             * DELETE request
             * @param {string} url - Request URL
             * @param {Object} options - Request options
             * @returns {Promise} Promise resolving to response data
             */
            delete(url, options = {}) {
                return this.request(url, { ...options, method: 'DELETE' });
            },
            
            /**
             * Build URL with parameters
             * @param {string} url - Base URL
             * @param {Object} params - Parameters object
             * @returns {string} URL with parameters
             */
            buildURL(url, params) {
                if (Object.keys(params).length === 0) {
                    return url;
                }
                
                const searchParams = new URLSearchParams();
                Object.keys(params).forEach(key => {
                    if (params[key] !== null && params[key] !== undefined) {
                        searchParams.append(key, params[key]);
                    }
                });
                
                return url + (url.includes('?') ? '&' : '?') + searchParams.toString();
            },
            
            /**
             * Get CSRF token from meta tag or cookie
             * @returns {string|null} CSRF token or null
             */
            getCSRFToken() {
                // Try meta tag first
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    return metaTag.getAttribute('content');
                }
                
                // Try cookie as fallback
                const matches = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
                return matches ? decodeURIComponent(matches[1]) : null;
            }
        },
        
        /**
         * Validation Utilities
         */
        Validation: {
            /**
             * Validate email address
             * @param {string} email - Email to validate
             * @returns {boolean} True if valid
             */
            email(email) {
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return regex.test(email);
            },
            
            /**
             * Validate phone number
             * @param {string} phone - Phone number to validate
             * @returns {boolean} True if valid
             */
            phone(phone) {
                const regex = /^[\+]?[1-9][\d]{0,15}$/;
                return regex.test(phone.replace(/\s/g, ''));
            },
            
            /**
             * Validate URL
             * @param {string} url - URL to validate
             * @returns {boolean} True if valid
             */
            url(url) {
                try {
                    new URL(url);
                    return true;
                } catch {
                    return false;
                }
            },
            
            /**
             * Check if string is not empty
             * @param {string} value - Value to check
             * @returns {boolean} True if not empty
             */
            required(value) {
                return typeof value === 'string' && value.trim().length > 0;
            },
            
            /**
             * Check minimum length
             * @param {string} value - Value to check
             * @param {number} min - Minimum length
             * @returns {boolean} True if meets minimum
             */
            minLength(value, min) {
                return typeof value === 'string' && value.length >= min;
            },
            
            /**
             * Check maximum length
             * @param {string} value - Value to check
             * @param {number} max - Maximum length
             * @returns {boolean} True if within maximum
             */
            maxLength(value, max) {
                return typeof value === 'string' && value.length <= max;
            },
            
            /**
             * Validate against pattern
             * @param {string} value - Value to validate
             * @param {RegExp|string} pattern - Pattern to match
             * @returns {boolean} True if matches pattern
             */
            pattern(value, pattern) {
                const regex = pattern instanceof RegExp ? pattern : new RegExp(pattern);
                return regex.test(value);
            }
        },
        
        /**
         * String Utilities
         */
        String: {
            /**
             * Escape HTML characters
             * @param {string} str - String to escape
             * @returns {string} Escaped string
             */
            escapeHTML(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            },
            
            /**
             * Capitalize first letter
             * @param {string} str - String to capitalize
             * @returns {string} Capitalized string
             */
            capitalize(str) {
                return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
            },
            
            /**
             * Convert to camelCase
             * @param {string} str - String to convert
             * @returns {string} camelCase string
             */
            camelCase(str) {
                return str.replace(/[-_\s]+(.)?/g, (_, c) => c ? c.toUpperCase() : '');
            },
            
            /**
             * Convert to kebab-case
             * @param {string} str - String to convert
             * @returns {string} kebab-case string
             */
            kebabCase(str) {
                return str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
            },
            
            /**
             * Truncate string with ellipsis
             * @param {string} str - String to truncate
             * @param {number} length - Maximum length
             * @param {string} suffix - Suffix for truncated string
             * @returns {string} Truncated string
             */
            truncate(str, length, suffix = '...') {
                if (str.length <= length) return str;
                return str.slice(0, length - suffix.length) + suffix;
            }
        },
        
        /**
         * Storage Utilities
         */
        Storage: {
            /**
             * Set localStorage item with JSON serialization
             * @param {string} key - Storage key
             * @param {*} value - Value to store
             */
            setLocal(key, value) {
                try {
                    localStorage.setItem(key, JSON.stringify(value));
                } catch (error) {
                    console.error('Failed to set localStorage:', error);
                }
            },
            
            /**
             * Get localStorage item with JSON parsing
             * @param {string} key - Storage key
             * @param {*} defaultValue - Default value if key not found
             * @returns {*} Retrieved value or default
             */
            getLocal(key, defaultValue = null) {
                try {
                    const item = localStorage.getItem(key);
                    return item !== null ? JSON.parse(item) : defaultValue;
                } catch (error) {
                    console.error('Failed to get localStorage:', error);
                    return defaultValue;
                }
            },
            
            /**
             * Remove localStorage item
             * @param {string} key - Storage key
             */
            removeLocal(key) {
                try {
                    localStorage.removeItem(key);
                } catch (error) {
                    console.error('Failed to remove localStorage:', error);
                }
            },
            
            /**
             * Set sessionStorage item with JSON serialization
             * @param {string} key - Storage key
             * @param {*} value - Value to store
             */
            setSession(key, value) {
                try {
                    sessionStorage.setItem(key, JSON.stringify(value));
                } catch (error) {
                    console.error('Failed to set sessionStorage:', error);
                }
            },
            
            /**
             * Get sessionStorage item with JSON parsing
             * @param {string} key - Storage key
             * @param {*} defaultValue - Default value if key not found
             * @returns {*} Retrieved value or default
             */
            getSession(key, defaultValue = null) {
                try {
                    const item = sessionStorage.getItem(key);
                    return item !== null ? JSON.parse(item) : defaultValue;
                } catch (error) {
                    console.error('Failed to get sessionStorage:', error);
                    return defaultValue;
                }
            },
            
            /**
             * Remove sessionStorage item
             * @param {string} key - Storage key
             */
            removeSession(key) {
                try {
                    sessionStorage.removeItem(key);
                } catch (error) {
                    console.error('Failed to remove sessionStorage:', error);
                }
            }
        },
        
        /**
         * General Utilities
         */
        General: {
            /**
             * Generate unique ID
             * @param {string} prefix - Optional prefix
             * @returns {string} Unique ID
             */
            uniqueId(prefix = 'id') {
                return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            },
            
            /**
             * Deep clone object
             * @param {*} obj - Object to clone
             * @returns {*} Cloned object
             */
            deepClone(obj) {
                if (obj === null || typeof obj !== 'object') return obj;
                if (obj instanceof Date) return new Date(obj.getTime());
                if (obj instanceof Array) return obj.map(item => this.deepClone(item));
                if (typeof obj === 'object') {
                    const clonedObj = {};
                    Object.keys(obj).forEach(key => {
                        clonedObj[key] = this.deepClone(obj[key]);
                    });
                    return clonedObj;
                }
            },
            
            /**
             * Check if value is empty
             * @param {*} value - Value to check
             * @returns {boolean} True if empty
             */
            isEmpty(value) {
                if (value === null || value === undefined) return true;
                if (typeof value === 'string') return value.trim() === '';
                if (Array.isArray(value)) return value.length === 0;
                if (typeof value === 'object') return Object.keys(value).length === 0;
                return false;
            },
            
            /**
             * Format number with thousands separator
             * @param {number} num - Number to format
             * @param {string} separator - Thousands separator
             * @returns {string} Formatted number
             */
            formatNumber(num, separator = ',') {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, separator);
            },
            
            /**
             * Format file size
             * @param {number} bytes - Size in bytes
             * @param {number} decimals - Number of decimal places
             * @returns {string} Formatted file size
             */
            formatFileSize(bytes, decimals = 2) {
                if (bytes === 0) return '0 Bytes';
                
                const k = 1024;
                const dm = decimals < 0 ? 0 : decimals;
                const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            }
        }
    };
    
    return Utils;
});