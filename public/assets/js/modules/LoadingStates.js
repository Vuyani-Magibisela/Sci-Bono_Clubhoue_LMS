/**
 * Loading States and Feedback System
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

// Define the LoadingStates module with Utils dependency
ModuleLoader.define('LoadingStates', ['Utils'], function(Utils) {
    'use strict';
    
    /**
     * Loading Spinner Component
     */
    class LoadingSpinner {
        constructor(element, options = {}) {
            this.element = typeof element === 'string' ? Utils.DOM.$(element) : element;
            
            this.options = {
                size: 'md', // sm, md, lg
                color: 'primary',
                text: null,
                overlay: false,
                classes: {
                    spinner: 'loading-spinner',
                    text: 'loading-spinner__text',
                    overlay: 'loading-overlay'
                },
                ...options
            };
            
            this.isVisible = false;
            this.spinnerElement = null;
            this.overlayElement = null;
            
            this.init();
        }
        
        init() {
            this.createSpinner();
            
            if (this.options.overlay) {
                this.createOverlay();
            }
        }
        
        createSpinner() {
            this.spinnerElement = Utils.DOM.createElement('div', {
                className: `${this.options.classes.spinner} loading-spinner--${this.options.size} loading-spinner--${this.options.color}`
            });
            
            // Create spinner animation
            const spinner = Utils.DOM.createElement('div', {
                className: 'loading-spinner__animation'
            });
            
            this.spinnerElement.appendChild(spinner);
            
            // Add text if provided
            if (this.options.text) {
                const textElement = Utils.DOM.createElement('div', {
                    className: this.options.classes.text
                }, this.options.text);
                
                this.spinnerElement.appendChild(textElement);
            }
        }
        
        createOverlay() {
            this.overlayElement = Utils.DOM.createElement('div', {
                className: this.options.classes.overlay
            });
            
            this.overlayElement.appendChild(this.spinnerElement);
        }
        
        show() {
            if (this.isVisible) return;
            
            const targetElement = this.overlayElement || this.spinnerElement;
            
            if (this.element) {
                this.element.appendChild(targetElement);
            } else {
                document.body.appendChild(targetElement);
            }
            
            this.isVisible = true;
            Utils.Events.trigger(targetElement, 'loading:show');
        }
        
        hide() {
            if (!this.isVisible) return;
            
            const targetElement = this.overlayElement || this.spinnerElement;
            
            if (targetElement.parentNode) {
                targetElement.parentNode.removeChild(targetElement);
            }
            
            this.isVisible = false;
            Utils.Events.trigger(targetElement, 'loading:hide');
        }
        
        updateText(text) {
            const textElement = Utils.DOM.$('.loading-spinner__text', this.spinnerElement);
            if (textElement) {
                textElement.textContent = text;
            }
        }
        
        destroy() {
            this.hide();
            this.spinnerElement = null;
            this.overlayElement = null;
        }
    }
    
    /**
     * Progress Bar Component
     */
    class ProgressBar {
        constructor(element, options = {}) {
            this.element = typeof element === 'string' ? Utils.DOM.$(element) : element;
            
            if (!this.element) {
                throw new Error('Progress bar element not found');
            }
            
            this.options = {
                min: 0,
                max: 100,
                value: 0,
                animated: true,
                striped: false,
                label: true,
                color: 'primary',
                height: 'md', // sm, md, lg
                classes: {
                    progress: 'progress',
                    bar: 'progress__bar',
                    label: 'progress__label',
                    animated: 'progress--animated',
                    striped: 'progress--striped'
                },
                ...options
            };
            
            this.currentValue = this.options.value;
            this.progressBar = null;
            this.labelElement = null;
            
            this.init();
        }
        
        init() {
            this.setupStructure();
            this.setValue(this.options.value);
        }
        
        setupStructure() {
            Utils.DOM.addClass(this.element, this.options.classes.progress);
            Utils.DOM.addClass(this.element, `progress--${this.options.height}`);
            
            if (this.options.animated) {
                Utils.DOM.addClass(this.element, this.options.classes.animated);
            }
            
            if (this.options.striped) {
                Utils.DOM.addClass(this.element, this.options.classes.striped);
            }
            
            // Create progress bar
            this.progressBar = Utils.DOM.createElement('div', {
                className: `${this.options.classes.bar} progress__bar--${this.options.color}`,
                role: 'progressbar',
                'aria-valuemin': this.options.min,
                'aria-valuemax': this.options.max,
                'aria-valuenow': this.currentValue
            });
            
            // Create label if enabled
            if (this.options.label) {
                this.labelElement = Utils.DOM.createElement('span', {
                    className: this.options.classes.label
                });
                
                this.progressBar.appendChild(this.labelElement);
            }
            
            this.element.appendChild(this.progressBar);
        }
        
        setValue(value, animate = true) {
            value = Math.max(this.options.min, Math.min(this.options.max, value));
            
            const percentage = ((value - this.options.min) / (this.options.max - this.options.min)) * 100;
            
            if (animate && this.options.animated) {
                // Animate the progress change
                const startWidth = parseFloat(this.progressBar.style.width) || 0;
                const targetWidth = percentage;
                const duration = 300;
                const startTime = performance.now();
                
                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Easing function (ease-out)
                    const easeOut = 1 - Math.pow(1 - progress, 3);
                    const currentWidth = startWidth + (targetWidth - startWidth) * easeOut;
                    
                    this.progressBar.style.width = `${currentWidth}%`;
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                };
                
                requestAnimationFrame(animate);
            } else {
                this.progressBar.style.width = `${percentage}%`;
            }
            
            // Update ARIA attributes
            this.progressBar.setAttribute('aria-valuenow', value);
            
            // Update label
            if (this.labelElement) {
                this.labelElement.textContent = `${Math.round(percentage)}%`;
            }
            
            this.currentValue = value;
            
            Utils.Events.trigger(this.element, 'progress:change', {
                value: value,
                percentage: percentage
            });
        }
        
        increment(amount = 1) {
            this.setValue(this.currentValue + amount);
        }
        
        decrement(amount = 1) {
            this.setValue(this.currentValue - amount);
        }
        
        reset() {
            this.setValue(this.options.min, false);
        }
        
        complete() {
            this.setValue(this.options.max);
        }
        
        getValue() {
            return this.currentValue;
        }
        
        getPercentage() {
            return ((this.currentValue - this.options.min) / (this.options.max - this.options.min)) * 100;
        }
    }
    
    /**
     * Skeleton Loader Component
     */
    class SkeletonLoader {
        constructor(element, options = {}) {
            this.element = typeof element === 'string' ? Utils.DOM.$(element) : element;
            
            if (!this.element) {
                throw new Error('Skeleton loader element not found');
            }
            
            this.options = {
                lines: 3,
                height: '1rem',
                spacing: '0.5rem',
                width: ['100%', '75%', '50%'],
                animation: 'pulse', // pulse, wave, none
                classes: {
                    skeleton: 'skeleton',
                    line: 'skeleton__line',
                    animated: 'skeleton--animated'
                },
                ...options
            };
            
            this.isVisible = false;
            this.originalContent = null;
            
            this.init();
        }
        
        init() {
            Utils.DOM.addClass(this.element, this.options.classes.skeleton);
            
            if (this.options.animation !== 'none') {
                Utils.DOM.addClass(this.element, this.options.classes.animated);
                Utils.DOM.addClass(this.element, `skeleton--${this.options.animation}`);
            }
        }
        
        show() {
            if (this.isVisible) return;
            
            // Store original content
            this.originalContent = this.element.innerHTML;
            
            // Clear element and add skeleton lines
            this.element.innerHTML = '';
            
            for (let i = 0; i < this.options.lines; i++) {
                const line = Utils.DOM.createElement('div', {
                    className: this.options.classes.line
                });
                
                line.style.height = this.options.height;
                line.style.marginBottom = i < this.options.lines - 1 ? this.options.spacing : '0';
                
                // Set width
                if (Array.isArray(this.options.width)) {
                    line.style.width = this.options.width[i] || this.options.width[0];
                } else {
                    line.style.width = this.options.width;
                }
                
                this.element.appendChild(line);
            }
            
            this.isVisible = true;
            Utils.Events.trigger(this.element, 'skeleton:show');
        }
        
        hide() {
            if (!this.isVisible) return;
            
            // Restore original content
            this.element.innerHTML = this.originalContent || '';
            
            this.isVisible = false;
            Utils.Events.trigger(this.element, 'skeleton:hide');
        }
        
        destroy() {
            this.hide();
            Utils.DOM.removeClass(this.element, [
                this.options.classes.skeleton,
                this.options.classes.animated,
                `skeleton--${this.options.animation}`
            ]);
        }
    }
    
    /**
     * Toast Notification Component
     */
    class Toast {
        constructor(options = {}) {
            this.options = {
                message: '',
                type: 'info', // info, success, warning, error
                duration: 5000,
                position: 'top-right', // top-left, top-right, bottom-left, bottom-right, top-center, bottom-center
                closable: true,
                actions: [],
                classes: {
                    container: 'toast-container',
                    toast: 'toast',
                    content: 'toast__content',
                    message: 'toast__message',
                    actions: 'toast__actions',
                    close: 'toast__close'
                },
                ...options
            };
            
            this.element = null;
            this.container = null;
            this.timeout = null;
            this.isVisible = false;
            
            this.init();
        }
        
        init() {
            this.createContainer();
            this.createElement();
        }
        
        createContainer() {
            const containerId = `toast-container-${this.options.position}`;
            this.container = Utils.DOM.$(`#${containerId}`);
            
            if (!this.container) {
                this.container = Utils.DOM.createElement('div', {
                    id: containerId,
                    className: `${this.options.classes.container} toast-container--${this.options.position}`
                });
                
                document.body.appendChild(this.container);
            }
        }
        
        createElement() {
            this.element = Utils.DOM.createElement('div', {
                className: `${this.options.classes.toast} toast--${this.options.type}`,
                role: 'alert',
                'aria-live': 'polite'
            });
            
            const content = Utils.DOM.createElement('div', {
                className: this.options.classes.content
            });
            
            const message = Utils.DOM.createElement('div', {
                className: this.options.classes.message
            }, this.options.message);
            
            content.appendChild(message);
            
            // Add actions if provided
            if (this.options.actions.length > 0) {
                const actionsContainer = Utils.DOM.createElement('div', {
                    className: this.options.classes.actions
                });
                
                this.options.actions.forEach(action => {
                    const button = Utils.DOM.createElement('button', {
                        type: 'button',
                        className: action.className || 'button button--sm button--ghost'
                    }, action.text);
                    
                    Utils.Events.on(button, 'click', (e) => {
                        e.preventDefault();
                        if (action.handler) {
                            action.handler(this);
                        }
                        this.hide();
                    });
                    
                    actionsContainer.appendChild(button);
                });
                
                content.appendChild(actionsContainer);
            }
            
            // Add close button if closable
            if (this.options.closable) {
                const closeButton = Utils.DOM.createElement('button', {
                    type: 'button',
                    className: this.options.classes.close,
                    'aria-label': 'Close notification'
                }, '×');
                
                Utils.Events.on(closeButton, 'click', () => {
                    this.hide();
                });
                
                content.appendChild(closeButton);
            }
            
            this.element.appendChild(content);
        }
        
        show() {
            if (this.isVisible) return;
            
            this.container.appendChild(this.element);
            
            // Trigger reflow then add show class for animation
            this.element.offsetHeight;
            Utils.DOM.addClass(this.element, 'toast--show');
            
            this.isVisible = true;
            
            // Auto hide if duration is set
            if (this.options.duration > 0) {
                this.timeout = setTimeout(() => {
                    this.hide();
                }, this.options.duration);
            }
            
            Utils.Events.trigger(this.element, 'toast:show');
        }
        
        hide() {
            if (!this.isVisible) return;
            
            if (this.timeout) {
                clearTimeout(this.timeout);
                this.timeout = null;
            }
            
            Utils.DOM.removeClass(this.element, 'toast--show');
            
            // Remove from DOM after animation
            setTimeout(() => {
                if (this.element.parentNode) {
                    this.element.parentNode.removeChild(this.element);
                }
                this.isVisible = false;
                Utils.Events.trigger(this.element, 'toast:hide');
            }, 300);
        }
        
        destroy() {
            this.hide();
            this.element = null;
            this.container = null;
        }
    }
    
    /**
     * Global Loading Manager
     */
    class LoadingManager {
        constructor() {
            this.activeLoaders = new Map();
            this.globalSpinner = null;
        }
        
        /**
         * Show loading spinner on element
         */
        show(element, options = {}) {
            const key = typeof element === 'string' ? element : element.id || Utils.General.uniqueId();
            
            if (this.activeLoaders.has(key)) {
                return this.activeLoaders.get(key);
            }
            
            const spinner = new LoadingSpinner(element, options);
            spinner.show();
            
            this.activeLoaders.set(key, spinner);
            return spinner;
        }
        
        /**
         * Hide loading spinner
         */
        hide(element) {
            const key = typeof element === 'string' ? element : element.id || Utils.General.uniqueId();
            
            const spinner = this.activeLoaders.get(key);
            if (spinner) {
                spinner.hide();
                this.activeLoaders.delete(key);
            }
        }
        
        /**
         * Show global loading overlay
         */
        showGlobal(options = {}) {
            if (this.globalSpinner) {
                return;
            }
            
            this.globalSpinner = new LoadingSpinner(null, {
                overlay: true,
                text: 'Loading...',
                ...options
            });
            
            this.globalSpinner.show();
        }
        
        /**
         * Hide global loading overlay
         */
        hideGlobal() {
            if (this.globalSpinner) {
                this.globalSpinner.hide();
                this.globalSpinner.destroy();
                this.globalSpinner = null;
            }
        }
        
        /**
         * Hide all active loaders
         */
        hideAll() {
            this.activeLoaders.forEach(spinner => {
                spinner.hide();
                spinner.destroy();
            });
            
            this.activeLoaders.clear();
            this.hideGlobal();
        }
    }
    
    // Create global instance
    const loadingManager = new LoadingManager();
    
    // Static methods for easy access
    const LoadingStates = {
        LoadingSpinner,
        ProgressBar,
        SkeletonLoader,
        Toast,
        LoadingManager,
        
        // Convenience methods
        show: (element, options) => loadingManager.show(element, options),
        hide: (element) => loadingManager.hide(element),
        showGlobal: (options) => loadingManager.showGlobal(options),
        hideGlobal: () => loadingManager.hideGlobal(),
        hideAll: () => loadingManager.hideAll(),
        
        // Toast shortcuts
        toast: (message, options = {}) => {
            const toast = new Toast({ message, ...options });
            toast.show();
            return toast;
        },
        
        toastSuccess: (message, options = {}) => {
            return LoadingStates.toast(message, { type: 'success', ...options });
        },
        
        toastError: (message, options = {}) => {
            return LoadingStates.toast(message, { type: 'error', ...options });
        },
        
        toastWarning: (message, options = {}) => {
            return LoadingStates.toast(message, { type: 'warning', ...options });
        },
        
        toastInfo: (message, options = {}) => {
            return LoadingStates.toast(message, { type: 'info', ...options });
        }
    };
    
    return LoadingStates;
});