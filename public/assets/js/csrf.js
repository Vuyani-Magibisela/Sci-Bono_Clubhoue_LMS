/**
 * CSRF Token Management for AJAX requests
 * Phase 2 Implementation
 */

class CSRFManager {
    constructor() {
        this.token = this.getTokenFromMeta();
        this.setupAjaxDefaults();
    }
    
    getTokenFromMeta() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    }
    
    getToken() {
        return this.token;
    }
    
    updateToken(newToken) {
        this.token = newToken;
        
        // Update meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }
        
        // Update all hidden form fields
        const hiddenFields = document.querySelectorAll('input[name="_csrf_token"]');
        hiddenFields.forEach(field => field.value = newToken);
    }
    
    setupAjaxDefaults() {
        // jQuery setup if available
        if (typeof $ !== 'undefined') {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': this.token
                }
            });
        }
        
        // Fetch API setup
        const originalFetch = window.fetch;
        window.fetch = (url, options = {}) => {
            if (options.method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method.toUpperCase())) {
                options.headers = options.headers || {};
                options.headers['X-CSRF-TOKEN'] = this.getToken();
            }
            return originalFetch(url, options);
        };
    }
    
    addToForm(form) {
        const existingField = form.querySelector('input[name="_csrf_token"]');
        if (existingField) {
            existingField.value = this.token;
        } else {
            const field = document.createElement('input');
            field.type = 'hidden';
            field.name = '_csrf_token';
            field.value = this.token;
            form.appendChild(field);
        }
    }
    
    addToFormData(formData) {
        formData.append('_csrf_token', this.token);
        return formData;
    }
}

// Initialize CSRF manager
const csrfManager = new CSRFManager();

// Make it globally available
window.CSRFManager = csrfManager;