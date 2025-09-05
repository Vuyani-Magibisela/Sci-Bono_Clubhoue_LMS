/**
 * Modal and Dialog Components
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

// Define the Modal module with Utils dependency
ModuleLoader.define('Modal', ['Utils'], function(Utils) {
    'use strict';
    
    /**
     * Modal Component
     * Manages modal dialogs with accessibility support
     */
    class Modal {
        constructor(element, options = {}) {
            this.element = typeof element === 'string' ? Utils.DOM.$(element) : element;
            
            if (!this.element) {
                throw new Error('Modal element not found');
            }
            
            this.options = {
                backdrop: true,
                keyboard: true,
                focus: true,
                show: false,
                scrollable: true,
                size: 'md', // sm, md, lg, xl
                animation: true,
                closeOnBackdrop: true,
                closeOnEscape: true,
                autoFocus: true,
                restoreFocus: true,
                appendTo: 'body',
                classes: {
                    modal: 'modal',
                    backdrop: 'modal__backdrop',
                    dialog: 'modal__dialog',
                    content: 'modal__content',
                    header: 'modal__header',
                    title: 'modal__title',
                    body: 'modal__body',
                    footer: 'modal__footer',
                    close: 'modal__close',
                    show: 'modal--show',
                    fade: 'modal--fade',
                    scrollable: 'modal--scrollable',
                    centered: 'modal--centered',
                    fullscreen: 'modal--fullscreen'
                },
                ...options
            };
            
            this.isOpen = false;
            this.isAnimating = false;
            this.backdrop = null;
            this.dialog = null;
            this.previousActiveElement = null;
            
            this.init();
        }
        
        /**
         * Initialize modal
         */
        init() {
            this.setupStructure();
            this.setupAccessibility();
            this.bindEvents();
            
            if (this.options.show) {
                this.show();
            }
            
            Utils.Events.trigger(this.element, 'modal:init');
        }
        
        /**
         * Setup modal structure
         */
        setupStructure() {
            // Ensure proper modal structure
            this.dialog = Utils.DOM.$('.modal__dialog', this.element);
            if (!this.dialog) {
                // Wrap existing content in dialog structure
                const content = this.element.innerHTML;
                this.element.innerHTML = '';
                
                this.dialog = Utils.DOM.createElement('div', {
                    className: this.options.classes.dialog
                });
                
                const modalContent = Utils.DOM.createElement('div', {
                    className: this.options.classes.content
                });
                
                modalContent.innerHTML = content;
                this.dialog.appendChild(modalContent);
                this.element.appendChild(this.dialog);
            }
            
            // Add modal classes
            Utils.DOM.addClass(this.element, this.options.classes.modal);
            
            if (this.options.animation) {
                Utils.DOM.addClass(this.element, this.options.classes.fade);
            }
            
            if (this.options.scrollable) {
                Utils.DOM.addClass(this.element, this.options.classes.scrollable);
            }
            
            // Size modifier
            if (this.options.size !== 'md') {
                Utils.DOM.addClass(this.dialog, `modal__dialog--${this.options.size}`);
            }
            
            // Create backdrop if needed
            if (this.options.backdrop) {
                this.createBackdrop();
            }
            
            // Setup close button
            this.setupCloseButton();
        }
        
        /**
         * Create backdrop element
         */
        createBackdrop() {
            this.backdrop = Utils.DOM.createElement('div', {
                className: this.options.classes.backdrop
            });
            
            if (this.options.animation) {
                Utils.DOM.addClass(this.backdrop, this.options.classes.fade);
            }
        }
        
        /**
         * Setup close button
         */
        setupCloseButton() {
            let closeButton = Utils.DOM.$('.modal__close', this.element);
            
            if (!closeButton) {
                const header = Utils.DOM.$('.modal__header', this.element);
                if (header) {
                    closeButton = Utils.DOM.createElement('button', {
                        type: 'button',
                        className: this.options.classes.close,
                        'aria-label': 'Close modal'
                    }, '×');
                    
                    header.appendChild(closeButton);
                }
            }
            
            this.closeButton = closeButton;
        }
        
        /**
         * Setup accessibility attributes
         */
        setupAccessibility() {
            // Modal attributes
            this.element.setAttribute('role', 'dialog');
            this.element.setAttribute('aria-modal', 'true');
            this.element.setAttribute('aria-hidden', 'true');
            this.element.setAttribute('tabindex', '-1');
            
            // Title association
            const title = Utils.DOM.$('.modal__title', this.element);
            if (title) {
                const titleId = title.id || `modal-title-${Utils.General.uniqueId()}`;
                title.id = titleId;
                this.element.setAttribute('aria-labelledby', titleId);
            }
            
            // Description association
            const body = Utils.DOM.$('.modal__body', this.element);
            if (body) {
                const bodyId = body.id || `modal-body-${Utils.General.uniqueId()}`;
                body.id = bodyId;
                this.element.setAttribute('aria-describedby', bodyId);
            }
        }
        
        /**
         * Bind event listeners
         */
        bindEvents() {
            // Close button click
            if (this.closeButton) {
                Utils.Events.on(this.closeButton, 'click', (e) => {
                    e.preventDefault();
                    this.hide();
                });
            }
            
            // Close on backdrop click
            if (this.options.closeOnBackdrop && this.backdrop) {
                Utils.Events.on(this.backdrop, 'click', () => {
                    this.hide();
                });
            }
            
            // Close on modal background click (not dialog)
            if (this.options.closeOnBackdrop) {
                Utils.Events.on(this.element, 'click', (e) => {
                    if (e.target === this.element) {
                        this.hide();
                    }
                });
            }
            
            // Keyboard events
            if (this.options.keyboard) {
                Utils.Events.on(this.element, 'keydown', (e) => {
                    this.handleKeydown(e);
                });
            }
            
            // Prevent scroll on body when modal is open
            Utils.Events.on(this.element, 'modal:show', () => {
                Utils.DOM.addClass(document.body, 'modal-open');
            });
            
            Utils.Events.on(this.element, 'modal:hide', () => {
                Utils.DOM.removeClass(document.body, 'modal-open');
            });
        }
        
        /**
         * Handle keyboard events
         */
        handleKeydown(e) {
            if (e.key === 'Escape' && this.options.closeOnEscape) {
                this.hide();
                return;
            }
            
            // Trap focus within modal
            if (e.key === 'Tab') {
                this.trapFocus(e);
            }
        }
        
        /**
         * Trap focus within modal
         */
        trapFocus(e) {
            const focusableElements = this.getFocusableElements();
            const firstFocusable = focusableElements[0];
            const lastFocusable = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        }
        
        /**
         * Get focusable elements within modal
         */
        getFocusableElements() {
            const selector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
            return Array.from(Utils.DOM.$$(selector, this.element))
                .filter(el => !el.disabled && !el.hidden);
        }
        
        /**
         * Show modal
         */
        show() {
            if (this.isOpen || this.isAnimating) return;
            
            this.isAnimating = true;
            this.previousActiveElement = document.activeElement;
            
            Utils.Events.trigger(this.element, 'modal:show');
            
            // Add to DOM
            const appendTarget = document.querySelector(this.options.appendTo) || document.body;
            appendTarget.appendChild(this.element);
            
            if (this.backdrop) {
                appendTarget.appendChild(this.backdrop);
            }
            
            // Show backdrop first
            if (this.backdrop) {
                this.backdrop.style.display = 'block';
                if (this.options.animation) {
                    // Force reflow then add show class
                    this.backdrop.offsetHeight;
                    Utils.DOM.addClass(this.backdrop, this.options.classes.show);
                }
            }
            
            // Show modal
            this.element.style.display = 'block';
            this.element.setAttribute('aria-hidden', 'false');
            
            if (this.options.animation) {
                // Force reflow then add show class
                this.element.offsetHeight;
                Utils.DOM.addClass(this.element, this.options.classes.show);
                
                // Wait for animation to complete
                setTimeout(() => {
                    this.isAnimating = false;
                    this.isOpen = true;
                    
                    if (this.options.autoFocus) {
                        this.setInitialFocus();
                    }
                    
                    Utils.Events.trigger(this.element, 'modal:shown');
                }, 300);
            } else {
                this.isAnimating = false;
                this.isOpen = true;
                
                if (this.options.autoFocus) {
                    this.setInitialFocus();
                }
                
                Utils.Events.trigger(this.element, 'modal:shown');
            }
        }
        
        /**
         * Hide modal
         */
        hide() {
            if (!this.isOpen || this.isAnimating) return;
            
            this.isAnimating = true;
            
            Utils.Events.trigger(this.element, 'modal:hide');
            
            if (this.options.animation) {
                Utils.DOM.removeClass(this.element, this.options.classes.show);
                
                if (this.backdrop) {
                    Utils.DOM.removeClass(this.backdrop, this.options.classes.show);
                }
                
                // Wait for animation to complete
                setTimeout(() => {
                    this.completeHide();
                }, 300);
            } else {
                this.completeHide();
            }
        }
        
        /**
         * Complete modal hiding
         */
        completeHide() {
            this.element.style.display = 'none';
            this.element.setAttribute('aria-hidden', 'true');
            
            if (this.backdrop) {
                this.backdrop.style.display = 'none';
                if (this.backdrop.parentNode) {
                    this.backdrop.parentNode.removeChild(this.backdrop);
                }
            }
            
            if (this.element.parentNode) {
                this.element.parentNode.removeChild(this.element);
            }
            
            // Restore focus
            if (this.options.restoreFocus && this.previousActiveElement) {
                this.previousActiveElement.focus();
            }
            
            this.isAnimating = false;
            this.isOpen = false;
            
            Utils.Events.trigger(this.element, 'modal:hidden');
        }
        
        /**
         * Toggle modal visibility
         */
        toggle() {
            if (this.isOpen) {
                this.hide();
            } else {
                this.show();
            }
        }
        
        /**
         * Set initial focus
         */
        setInitialFocus() {
            const focusableElements = this.getFocusableElements();
            
            // Try to focus first input, then first button, then first focusable
            let focusTarget = Utils.DOM.$('input:not([type="hidden"])', this.element) ||
                             Utils.DOM.$('button:not([aria-label*="Close"])', this.element) ||
                             focusableElements[0] ||
                             this.element;
            
            focusTarget.focus();
        }
        
        /**
         * Update modal content
         */
        setContent(content) {
            const body = Utils.DOM.$('.modal__body', this.element);
            if (body) {
                if (typeof content === 'string') {
                    body.innerHTML = content;
                } else if (content instanceof Element) {
                    body.innerHTML = '';
                    body.appendChild(content);
                }
            }
        }
        
        /**
         * Set modal title
         */
        setTitle(title) {
            const titleElement = Utils.DOM.$('.modal__title', this.element);
            if (titleElement) {
                titleElement.textContent = title;
            }
        }
        
        /**
         * Destroy modal
         */
        destroy() {
            if (this.isOpen) {
                this.hide();
            }
            
            if (this.backdrop && this.backdrop.parentNode) {
                this.backdrop.parentNode.removeChild(this.backdrop);
            }
            
            if (this.element.parentNode) {
                this.element.parentNode.removeChild(this.element);
            }
            
            Utils.Events.trigger(this.element, 'modal:destroy');
        }
    }
    
    /**
     * Confirm Dialog
     * Simple confirmation dialog
     */
    class ConfirmDialog {
        constructor(options = {}) {
            this.options = {
                title: 'Confirm',
                message: 'Are you sure?',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                confirmClass: 'button button--primary',
                cancelClass: 'button button--outline',
                ...options
            };
            
            this.modal = null;
            this.resolve = null;
        }
        
        /**
         * Show confirmation dialog
         */
        show() {
            return new Promise((resolve) => {
                this.resolve = resolve;
                this.createModal();
                this.modal.show();
            });
        }
        
        /**
         * Create modal structure
         */
        createModal() {
            const modalElement = Utils.DOM.createElement('div', {
                className: 'modal',
                role: 'dialog',
                'aria-modal': 'true'
            });
            
            const dialog = Utils.DOM.createElement('div', {
                className: 'modal__dialog modal__dialog--sm'
            });
            
            const content = Utils.DOM.createElement('div', {
                className: 'modal__content'
            });
            
            const header = Utils.DOM.createElement('div', {
                className: 'modal__header'
            });
            
            const title = Utils.DOM.createElement('h5', {
                className: 'modal__title'
            }, this.options.title);
            
            const body = Utils.DOM.createElement('div', {
                className: 'modal__body'
            });
            
            const message = Utils.DOM.createElement('p', {}, this.options.message);
            
            const footer = Utils.DOM.createElement('div', {
                className: 'modal__footer'
            });
            
            const confirmButton = Utils.DOM.createElement('button', {
                type: 'button',
                className: this.options.confirmClass
            }, this.options.confirmText);
            
            const cancelButton = Utils.DOM.createElement('button', {
                type: 'button',
                className: this.options.cancelClass
            }, this.options.cancelText);
            
            // Assemble structure
            header.appendChild(title);
            body.appendChild(message);
            footer.appendChild(cancelButton);
            footer.appendChild(confirmButton);
            
            content.appendChild(header);
            content.appendChild(body);
            content.appendChild(footer);
            
            dialog.appendChild(content);
            modalElement.appendChild(dialog);
            
            // Create modal instance
            this.modal = new Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                closeOnBackdrop: false,
                closeOnEscape: true
            });
            
            // Bind button events
            Utils.Events.on(confirmButton, 'click', () => {
                this.modal.hide();
                if (this.resolve) this.resolve(true);
            });
            
            Utils.Events.on(cancelButton, 'click', () => {
                this.modal.hide();
                if (this.resolve) this.resolve(false);
            });
            
            Utils.Events.on(modalElement, 'modal:hidden', () => {
                this.modal.destroy();
                if (this.resolve) this.resolve(false);
            });
        }
    }
    
    /**
     * Alert Dialog
     * Simple alert dialog
     */
    class AlertDialog {
        constructor(options = {}) {
            this.options = {
                title: 'Alert',
                message: 'Something happened!',
                buttonText: 'OK',
                buttonClass: 'button button--primary',
                type: 'info', // info, success, warning, error
                ...options
            };
            
            this.modal = null;
            this.resolve = null;
        }
        
        /**
         * Show alert dialog
         */
        show() {
            return new Promise((resolve) => {
                this.resolve = resolve;
                this.createModal();
                this.modal.show();
            });
        }
        
        /**
         * Create modal structure
         */
        createModal() {
            const modalElement = Utils.DOM.createElement('div', {
                className: 'modal',
                role: 'alertdialog',
                'aria-modal': 'true'
            });
            
            const dialog = Utils.DOM.createElement('div', {
                className: 'modal__dialog modal__dialog--sm'
            });
            
            const content = Utils.DOM.createElement('div', {
                className: 'modal__content'
            });
            
            const header = Utils.DOM.createElement('div', {
                className: 'modal__header'
            });
            
            const title = Utils.DOM.createElement('h5', {
                className: 'modal__title'
            }, this.options.title);
            
            const body = Utils.DOM.createElement('div', {
                className: 'modal__body'
            });
            
            // Add icon based on type
            const icon = this.getIcon(this.options.type);
            if (icon) {
                body.appendChild(icon);
            }
            
            const message = Utils.DOM.createElement('p', {}, this.options.message);
            
            const footer = Utils.DOM.createElement('div', {
                className: 'modal__footer'
            });
            
            const button = Utils.DOM.createElement('button', {
                type: 'button',
                className: this.options.buttonClass
            }, this.options.buttonText);
            
            // Assemble structure
            header.appendChild(title);
            body.appendChild(message);
            footer.appendChild(button);
            
            content.appendChild(header);
            content.appendChild(body);
            content.appendChild(footer);
            
            dialog.appendChild(content);
            modalElement.appendChild(dialog);
            
            // Create modal instance
            this.modal = new Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                closeOnBackdrop: false,
                closeOnEscape: true
            });
            
            // Bind button event
            Utils.Events.on(button, 'click', () => {
                this.modal.hide();
                if (this.resolve) this.resolve();
            });
            
            Utils.Events.on(modalElement, 'modal:hidden', () => {
                this.modal.destroy();
                if (this.resolve) this.resolve();
            });
        }
        
        /**
         * Get icon for alert type
         */
        getIcon(type) {
            const icons = {
                info: 'ℹ️',
                success: '✅',
                warning: '⚠️',
                error: '❌'
            };
            
            if (icons[type]) {
                return Utils.DOM.createElement('span', {
                    className: `alert-icon alert-icon--${type}`,
                    'aria-hidden': 'true'
                }, icons[type]);
            }
            
            return null;
        }
    }
    
    // Static methods for easy use
    Modal.confirm = function(options) {
        const dialog = new ConfirmDialog(options);
        return dialog.show();
    };
    
    Modal.alert = function(options) {
        const dialog = new AlertDialog(options);
        return dialog.show();
    };
    
    // Export classes
    return {
        Modal,
        ConfirmDialog,
        AlertDialog
    };
});