/**
 * Advanced Form Validation Module
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

// Define the FormValidator module with Utils dependency
ModuleLoader.define('FormValidator', ['Utils'], function(Utils) {
    'use strict';
    
    /**
     * Advanced Form Validator
     * 
     * Features:
     * - Real-time validation
     * - Custom validation rules
     * - Field dependencies
     * - Async validation
     * - Accessibility support
     * - Custom error messages
     * - Form state management
     */
    class FormValidator {
        constructor(form, options = {}) {
            this.form = typeof form === 'string' ? Utils.DOM.$(form) : form;
            
            if (!this.form) {
                throw new Error('Form element not found');
            }
            
            this.options = {
                // Validation timing
                validateOnBlur: true,
                validateOnInput: true,
                validateOnSubmit: true,
                
                // UI options
                showErrors: true,
                showSuccessState: true,
                focusFirstError: true,
                
                // CSS classes
                classes: {
                    valid: 'form-group--valid',
                    invalid: 'form-group--invalid',
                    validating: 'form-group--validating',
                    errorMessage: 'form-group__help form-group__help--invalid',
                    successMessage: 'form-group__help form-group__help--valid'
                },
                
                // Error messages
                messages: {
                    required: 'This field is required',
                    email: 'Please enter a valid email address',
                    phone: 'Please enter a valid phone number',
                    url: 'Please enter a valid URL',
                    minLength: 'Please enter at least {min} characters',
                    maxLength: 'Please enter no more than {max} characters',
                    min: 'Please enter a value greater than or equal to {min}',
                    max: 'Please enter a value less than or equal to {max}',
                    pattern: 'Please match the requested format',
                    confirm: 'This field must match the {field} field'
                },
                
                // Custom validation rules
                rules: {},
                
                // Event callbacks
                onFieldValidate: null,
                onFormValidate: null,
                onSubmit: null,
                onError: null,
                
                ...options
            };
            
            this.fields = new Map();
            this.validationPromises = new Map();
            this.isValid = false;
            this.isValidating = false;
            
            this.init();
        }
        
        /**
         * Initialize the validator
         */
        init() {
            this.setupFields();
            this.bindEvents();
            
            // Add ARIA attributes for accessibility
            this.form.setAttribute('novalidate', 'true');
            this.form.setAttribute('aria-live', 'polite');
        }
        
        /**
         * Setup form fields for validation
         */
        setupFields() {
            const fields = Utils.DOM.$$('input, select, textarea', this.form);
            
            fields.forEach(field => {
                if (field.type === 'submit' || field.type === 'button' || field.type === 'reset') {
                    return;
                }
                
                const fieldConfig = this.parseFieldConfig(field);
                this.fields.set(field, fieldConfig);
                
                // Add ARIA attributes
                field.setAttribute('aria-describedby', fieldConfig.errorId);
            });
        }
        
        /**
         * Parse field validation configuration
         */
        parseFieldConfig(field) {
            const config = {
                element: field,
                name: field.name || field.id,
                rules: [],
                messages: {},
                errorId: `${field.name || field.id}_error`,
                isValid: null,
                value: '',
                errors: []
            };
            
            // Parse HTML5 validation attributes
            if (field.hasAttribute('required')) {
                config.rules.push({ type: 'required' });
            }
            
            if (field.type === 'email') {
                config.rules.push({ type: 'email' });
            }
            
            if (field.type === 'url') {
                config.rules.push({ type: 'url' });
            }
            
            if (field.type === 'tel') {
                config.rules.push({ type: 'phone' });
            }
            
            if (field.hasAttribute('pattern')) {
                config.rules.push({
                    type: 'pattern',
                    pattern: field.getAttribute('pattern')
                });
            }
            
            if (field.hasAttribute('minlength')) {
                config.rules.push({
                    type: 'minLength',
                    min: parseInt(field.getAttribute('minlength'), 10)
                });
            }
            
            if (field.hasAttribute('maxlength')) {
                config.rules.push({
                    type: 'maxLength',
                    max: parseInt(field.getAttribute('maxlength'), 10)
                });
            }
            
            if (field.hasAttribute('min')) {
                config.rules.push({
                    type: 'min',
                    min: parseFloat(field.getAttribute('min'))
                });
            }
            
            if (field.hasAttribute('max')) {
                config.rules.push({
                    type: 'max',
                    max: parseFloat(field.getAttribute('max'))
                });
            }
            
            // Parse data attributes for custom rules
            const dataRules = field.getAttribute('data-validate');
            if (dataRules) {
                try {
                    const rules = JSON.parse(dataRules);
                    config.rules = config.rules.concat(rules);
                } catch (e) {
                    console.warn('Invalid validation rules for field:', field.name, e);
                }
            }
            
            // Parse custom messages
            const dataMessages = field.getAttribute('data-messages');
            if (dataMessages) {
                try {
                    config.messages = JSON.parse(dataMessages);
                } catch (e) {
                    console.warn('Invalid validation messages for field:', field.name, e);
                }
            }
            
            return config;
        }
        
        /**
         * Bind form and field events
         */
        bindEvents() {
            // Form submit event
            Utils.Events.on(this.form, 'submit', (e) => {
                if (this.options.validateOnSubmit) {
                    e.preventDefault();
                    this.validateForm().then(isValid => {
                        if (isValid && this.options.onSubmit) {
                            this.options.onSubmit(e, this.getFormData());
                        }
                    });
                }
            });
            
            // Field events
            this.fields.forEach((config, field) => {
                if (this.options.validateOnBlur) {
                    Utils.Events.on(field, 'blur', () => {
                        this.validateField(field);
                    });
                }
                
                if (this.options.validateOnInput) {
                    const eventType = field.type === 'checkbox' || field.type === 'radio' ? 'change' : 'input';
                    Utils.Events.on(field, eventType, Utils.Events.debounce(() => {
                        this.validateField(field);
                    }, 300));
                }
            });
        }
        
        /**
         * Validate entire form
         * @returns {Promise<boolean>} Promise resolving to validation result
         */
        async validateForm() {
            this.isValidating = true;
            Utils.DOM.addClass(this.form, 'form--validating');
            
            const validationPromises = [];
            
            this.fields.forEach((config, field) => {
                validationPromises.push(this.validateField(field));
            });
            
            const results = await Promise.all(validationPromises);
            this.isValid = results.every(result => result);
            this.isValidating = false;
            
            Utils.DOM.removeClass(this.form, 'form--validating');
            Utils.DOM.toggleClass(this.form, 'form--valid', this.isValid);
            Utils.DOM.toggleClass(this.form, 'form--invalid', !this.isValid);
            
            if (this.options.onFormValidate) {
                this.options.onFormValidate(this.isValid, this.getErrors());
            }
            
            if (!this.isValid && this.options.focusFirstError) {
                this.focusFirstError();
            }
            
            return this.isValid;
        }
        
        /**
         * Validate single field
         * @param {Element} field - Field to validate
         * @returns {Promise<boolean>} Promise resolving to validation result
         */
        async validateField(field) {
            const config = this.fields.get(field);
            if (!config) return true;
            
            const value = this.getFieldValue(field);
            config.value = value;
            config.errors = [];
            
            // Cancel previous validation if still running
            if (this.validationPromises.has(field)) {
                // Note: Can't actually cancel fetch, but we can ignore the result
                this.validationPromises.delete(field);
            }
            
            // Show validating state
            this.setFieldState(field, 'validating');
            
            try {
                // Run all validation rules
                for (const rule of config.rules) {
                    const isValid = await this.runValidationRule(field, value, rule);
                    if (!isValid) {
                        const message = this.getErrorMessage(rule, config.messages);
                        config.errors.push(message);
                        break; // Stop at first error
                    }
                }
                
                config.isValid = config.errors.length === 0;
                
                // Update UI
                if (this.options.showErrors || this.options.showSuccessState) {
                    this.updateFieldUI(field, config);
                }
                
                // Trigger callback
                if (this.options.onFieldValidate) {
                    this.options.onFieldValidate(field, config.isValid, config.errors);
                }
                
                return config.isValid;
                
            } catch (error) {
                console.error('Validation error for field:', field.name, error);
                config.isValid = false;
                config.errors = ['Validation error occurred'];
                
                this.updateFieldUI(field, config);
                return false;
            }
        }
        
        /**
         * Run single validation rule
         * @param {Element} field - Field being validated
         * @param {*} value - Field value
         * @param {Object} rule - Validation rule
         * @returns {Promise<boolean>} Promise resolving to validation result
         */
        async runValidationRule(field, value, rule) {
            // Custom rule function
            if (typeof rule === 'function') {
                return await rule(value, field, this);
            }
            
            // Custom rule by name
            if (typeof rule === 'string' && this.options.rules[rule]) {
                return await this.options.rules[rule](value, field, this);
            }
            
            // Rule object with type
            if (typeof rule === 'object' && rule.type) {
                // Check for custom rule
                if (this.options.rules[rule.type]) {
                    return await this.options.rules[rule.type](value, field, this, rule);
                }
                
                // Built-in rules
                switch (rule.type) {
                    case 'required':
                        return Utils.Validation.required(value);
                    
                    case 'email':
                        return !value || Utils.Validation.email(value);
                    
                    case 'phone':
                        return !value || Utils.Validation.phone(value);
                    
                    case 'url':
                        return !value || Utils.Validation.url(value);
                    
                    case 'minLength':
                        return Utils.Validation.minLength(value, rule.min);
                    
                    case 'maxLength':
                        return Utils.Validation.maxLength(value, rule.max);
                    
                    case 'min':
                        return !value || parseFloat(value) >= rule.min;
                    
                    case 'max':
                        return !value || parseFloat(value) <= rule.max;
                    
                    case 'pattern':
                        return Utils.Validation.pattern(value, rule.pattern);
                    
                    case 'confirm':
                        const targetField = Utils.DOM.$(`[name="${rule.field}"]`, this.form);
                        return targetField ? value === this.getFieldValue(targetField) : false;
                    
                    case 'async':
                        // Handle async validation (e.g., server-side checks)
                        if (rule.url) {
                            const promise = Utils.AJAX.post(rule.url, { 
                                field: field.name, 
                                value: value 
                            }).then(response => response.valid === true);
                            
                            this.validationPromises.set(field, promise);
                            return promise;
                        }
                        return true;
                    
                    default:
                        console.warn('Unknown validation rule:', rule.type);
                        return true;
                }
            }
            
            return true;
        }
        
        /**
         * Get field value
         * @param {Element} field - Field element
         * @returns {*} Field value
         */
        getFieldValue(field) {
            if (field.type === 'checkbox') {
                return field.checked;
            }
            
            if (field.type === 'radio') {
                const checkedRadio = Utils.DOM.$(`[name="${field.name}"]:checked`, this.form);
                return checkedRadio ? checkedRadio.value : '';
            }
            
            if (field.type === 'file') {
                return field.files;
            }
            
            return field.value;
        }
        
        /**
         * Set field validation state
         * @param {Element} field - Field element
         * @param {string} state - State: 'valid', 'invalid', 'validating'
         */
        setFieldState(field, state) {
            const formGroup = Utils.DOM.closest(field, '.form-group');
            if (!formGroup) return;
            
            // Remove all state classes
            Utils.DOM.removeClass(formGroup, [
                this.options.classes.valid,
                this.options.classes.invalid,
                this.options.classes.validating
            ]);
            
            // Add appropriate state class
            if (state && this.options.classes[state]) {
                Utils.DOM.addClass(formGroup, this.options.classes[state]);
            }
        }
        
        /**
         * Update field UI with validation results
         * @param {Element} field - Field element
         * @param {Object} config - Field configuration
         */
        updateFieldUI(field, config) {
            const formGroup = Utils.DOM.closest(field, '.form-group');
            if (!formGroup) return;
            
            // Update validation state
            this.setFieldState(field, config.isValid ? 'valid' : 'invalid');
            
            // Update error/success messages
            this.updateFieldMessages(field, config);
            
            // Update ARIA attributes
            field.setAttribute('aria-invalid', !config.isValid);
            
            if (config.errors.length > 0) {
                field.setAttribute('aria-describedby', config.errorId);
            } else {
                field.removeAttribute('aria-describedby');
            }
        }
        
        /**
         * Update field error/success messages
         * @param {Element} field - Field element
         * @param {Object} config - Field configuration
         */
        updateFieldMessages(field, config) {
            const formGroup = Utils.DOM.closest(field, '.form-group');
            if (!formGroup) return;
            
            // Remove existing messages
            const existingMessages = Utils.DOM.$$('.form-group__help', formGroup);
            existingMessages.forEach(msg => msg.remove());
            
            // Add new messages
            if (config.errors.length > 0 && this.options.showErrors) {
                config.errors.forEach(error => {
                    const errorElement = Utils.DOM.createElement('div', {
                        className: this.options.classes.errorMessage,
                        id: config.errorId
                    }, error);
                    
                    formGroup.appendChild(errorElement);
                });
            } else if (config.isValid && this.options.showSuccessState) {
                const successElement = Utils.DOM.createElement('div', {
                    className: this.options.classes.successMessage
                }, '✓ Valid');
                
                formGroup.appendChild(successElement);
            }
        }
        
        /**
         * Get error message for validation rule
         * @param {Object} rule - Validation rule
         * @param {Object} customMessages - Custom messages for field
         * @returns {string} Error message
         */
        getErrorMessage(rule, customMessages = {}) {
            const ruleType = typeof rule === 'string' ? rule : rule.type;
            
            // Use custom message if available
            if (customMessages[ruleType]) {
                return this.interpolateMessage(customMessages[ruleType], rule);
            }
            
            // Use default message
            if (this.options.messages[ruleType]) {
                return this.interpolateMessage(this.options.messages[ruleType], rule);
            }
            
            return 'Invalid value';
        }
        
        /**
         * Interpolate message with rule parameters
         * @param {string} message - Message template
         * @param {Object} rule - Validation rule
         * @returns {string} Interpolated message
         */
        interpolateMessage(message, rule) {
            if (typeof rule !== 'object') return message;
            
            return message.replace(/\{(\w+)\}/g, (match, key) => {
                return rule[key] !== undefined ? rule[key] : match;
            });
        }
        
        /**
         * Focus first field with error
         */
        focusFirstError() {
            for (const [field, config] of this.fields) {
                if (!config.isValid) {
                    field.focus();
                    break;
                }
            }
        }
        
        /**
         * Get all form errors
         * @returns {Object} Object with field errors
         */
        getErrors() {
            const errors = {};
            
            this.fields.forEach((config, field) => {
                if (config.errors.length > 0) {
                    errors[config.name] = config.errors;
                }
            });
            
            return errors;
        }
        
        /**
         * Get form data
         * @returns {Object} Form data object
         */
        getFormData() {
            const data = {};
            
            this.fields.forEach((config, field) => {
                data[config.name] = config.value;
            });
            
            return data;
        }
        
        /**
         * Reset form validation
         */
        reset() {
            this.fields.forEach((config, field) => {
                config.isValid = null;
                config.errors = [];
                this.setFieldState(field, null);
                
                const formGroup = Utils.DOM.closest(field, '.form-group');
                if (formGroup) {
                    const messages = Utils.DOM.$$('.form-group__help', formGroup);
                    messages.forEach(msg => msg.remove());
                }
            });
            
            this.isValid = false;
            Utils.DOM.removeClass(this.form, ['form--valid', 'form--invalid']);
        }
        
        /**
         * Add validation rule
         * @param {string} name - Rule name
         * @param {Function} validator - Validator function
         */
        addRule(name, validator) {
            this.options.rules[name] = validator;
        }
        
        /**
         * Remove validation rule
         * @param {string} name - Rule name
         */
        removeRule(name) {
            delete this.options.rules[name];
        }
        
        /**
         * Destroy validator
         */
        destroy() {
            // Remove event listeners
            this.form.removeAttribute('novalidate');
            this.form.removeAttribute('aria-live');
            
            // Clear field attributes
            this.fields.forEach((config, field) => {
                field.removeAttribute('aria-invalid');
                field.removeAttribute('aria-describedby');
            });
            
            // Clear data
            this.fields.clear();
            this.validationPromises.clear();
        }
    }
    
    /**
     * Static method to create validator instance
     * @param {Element|string} form - Form element or selector
     * @param {Object} options - Validation options
     * @returns {FormValidator} Validator instance
     */
    FormValidator.create = function(form, options) {
        return new FormValidator(form, options);
    };
    
    return FormValidator;
});