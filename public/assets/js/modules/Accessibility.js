/**
 * Accessibility Enhancements Module
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

// Define the Accessibility module with Utils dependency
ModuleLoader.define('Accessibility', ['Utils'], function(Utils) {
    'use strict';
    
    /**
     * Focus Management Utility
     */
    class FocusManager {
        constructor() {
            this.focusStack = [];
            this.trapStack = [];
            this.lastFocusedElement = null;
            
            this.init();
        }
        
        init() {
            // Track focus changes
            Utils.Events.on(document, 'focusin', (e) => {
                if (!this.isTrapActive() || this.isWithinCurrentTrap(e.target)) {
                    this.lastFocusedElement = e.target;
                }
            });
            
            // Handle tab key globally for focus trapping
            Utils.Events.on(document, 'keydown', (e) => {
                if (e.key === 'Tab' && this.isTrapActive()) {
                    this.handleTrapTab(e);
                }
            });
        }
        
        /**
         * Save current focus to stack
         */
        saveFocus() {
            const activeElement = document.activeElement;
            if (activeElement && activeElement !== document.body) {
                this.focusStack.push(activeElement);
            }
        }
        
        /**
         * Restore focus from stack
         */
        restoreFocus() {
            const element = this.focusStack.pop();
            if (element && this.isVisible(element) && this.isFocusable(element)) {
                element.focus();
                return true;
            }
            return false;
        }
        
        /**
         * Set focus to element
         */
        setFocus(element, options = {}) {
            if (typeof element === 'string') {
                element = Utils.DOM.$(element);
            }
            
            if (!element) return false;
            
            const {
                preventScroll = false,
                savePrevious = true
            } = options;
            
            if (savePrevious) {
                this.saveFocus();
            }
            
            if (this.isFocusable(element)) {
                element.focus({ preventScroll });
                return true;
            }
            
            return false;
        }
        
        /**
         * Trap focus within container
         */
        trapFocus(container, options = {}) {
            if (typeof container === 'string') {
                container = Utils.DOM.$(container);
            }
            
            if (!container) return false;
            
            const trap = {
                container,
                options: {
                    initialFocus: null,
                    returnFocus: true,
                    ...options
                },
                previouslyFocused: document.activeElement
            };
            
            this.trapStack.push(trap);
            
            // Set initial focus
            const initialFocus = trap.options.initialFocus || 
                               this.getFirstFocusable(container) || 
                               container;
            
            if (initialFocus) {
                initialFocus.focus();
            }
            
            return true;
        }
        
        /**
         * Release focus trap
         */
        releaseTrap() {
            const trap = this.trapStack.pop();
            
            if (!trap) return false;
            
            // Restore focus if requested
            if (trap.options.returnFocus && trap.previouslyFocused) {
                if (this.isVisible(trap.previouslyFocused) && 
                    this.isFocusable(trap.previouslyFocused)) {
                    trap.previouslyFocused.focus();
                }
            }
            
            return true;
        }
        
        /**
         * Handle tab key in focus trap
         */
        handleTrapTab(e) {
            const trap = this.getCurrentTrap();
            if (!trap) return;
            
            const focusableElements = this.getFocusableElements(trap.container);
            if (focusableElements.length === 0) return;
            
            const firstFocusable = focusableElements[0];
            const lastFocusable = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        }
        
        /**
         * Get focusable elements within container
         */
        getFocusableElements(container) {
            const selector = [
                'button:not([disabled])',
                '[href]:not([disabled])',
                'input:not([disabled])',
                'select:not([disabled])',
                'textarea:not([disabled])',
                '[tabindex]:not([tabindex="-1"]):not([disabled])',
                'details:not([disabled])',
                'summary:not([disabled])'
            ].join(', ');
            
            return Array.from(Utils.DOM.$$(selector, container))
                .filter(el => this.isVisible(el) && this.isFocusable(el));
        }
        
        /**
         * Get first focusable element
         */
        getFirstFocusable(container) {
            const focusable = this.getFocusableElements(container);
            return focusable.length > 0 ? focusable[0] : null;
        }
        
        /**
         * Get last focusable element
         */
        getLastFocusable(container) {
            const focusable = this.getFocusableElements(container);
            return focusable.length > 0 ? focusable[focusable.length - 1] : null;
        }
        
        /**
         * Check if element is visible
         */
        isVisible(element) {
            if (!element) return false;
            
            const style = window.getComputedStyle(element);
            return style.display !== 'none' && 
                   style.visibility !== 'hidden' && 
                   style.opacity !== '0';
        }
        
        /**
         * Check if element is focusable
         */
        isFocusable(element) {
            if (!element || element.disabled) return false;
            
            const tabIndex = parseInt(element.getAttribute('tabindex'), 10);
            if (tabIndex === -1) return false;
            
            return this.isVisible(element);
        }
        
        /**
         * Check if focus trap is active
         */
        isTrapActive() {
            return this.trapStack.length > 0;
        }
        
        /**
         * Check if element is within current trap
         */
        isWithinCurrentTrap(element) {
            const trap = this.getCurrentTrap();
            return trap ? trap.container.contains(element) : false;
        }
        
        /**
         * Get current focus trap
         */
        getCurrentTrap() {
            return this.trapStack.length > 0 ? 
                   this.trapStack[this.trapStack.length - 1] : null;
        }
    }
    
    /**
     * Screen Reader Utility
     */
    class ScreenReaderUtility {
        constructor() {
            this.liveRegions = new Map();
            this.init();
        }
        
        init() {
            this.createDefaultLiveRegions();
        }
        
        /**
         * Create default live regions
         */
        createDefaultLiveRegions() {
            this.createLiveRegion('polite', 'polite');
            this.createLiveRegion('assertive', 'assertive');
            this.createLiveRegion('status', 'polite');
        }
        
        /**
         * Create live region
         */
        createLiveRegion(id, politeness = 'polite') {
            if (this.liveRegions.has(id)) {
                return this.liveRegions.get(id);
            }
            
            const region = Utils.DOM.createElement('div', {
                id: `sr-live-${id}`,
                'aria-live': politeness,
                'aria-atomic': 'true',
                className: 'sr-only'
            });
            
            document.body.appendChild(region);
            this.liveRegions.set(id, region);
            
            return region;
        }
        
        /**
         * Announce message to screen readers
         */
        announce(message, priority = 'polite') {
            const region = this.liveRegions.get(priority) || 
                          this.liveRegions.get('polite');
            
            if (region) {
                // Clear previous message
                region.textContent = '';
                
                // Set new message after a short delay to ensure it's announced
                setTimeout(() => {
                    region.textContent = message;
                }, 100);
                
                // Clear message after announcement
                setTimeout(() => {
                    region.textContent = '';
                }, 3000);
            }
        }
        
        /**
         * Create describedby relationship
         */
        createDescribedBy(element, description, options = {}) {
            if (typeof element === 'string') {
                element = Utils.DOM.$(element);
            }
            
            if (!element) return null;
            
            const {
                id = `desc-${Utils.General.uniqueId()}`,
                className = 'sr-description',
                visible = false
            } = options;
            
            // Create description element
            const descElement = Utils.DOM.createElement('div', {
                id,
                className: visible ? className : `${className} sr-only`
            }, description);
            
            // Insert description after target element
            element.parentNode.insertBefore(descElement, element.nextSibling);
            
            // Link elements
            const existingDesc = element.getAttribute('aria-describedby');
            const newDesc = existingDesc ? `${existingDesc} ${id}` : id;
            element.setAttribute('aria-describedby', newDesc);
            
            return descElement;
        }
        
        /**
         * Create labelledby relationship
         */
        createLabelledBy(element, label, options = {}) {
            if (typeof element === 'string') {
                element = Utils.DOM.$(element);
            }
            
            if (!element) return null;
            
            const {
                id = `label-${Utils.General.uniqueId()}`,
                className = 'sr-label',
                visible = true
            } = options;
            
            // Create label element
            const labelElement = Utils.DOM.createElement('div', {
                id,
                className: visible ? className : `${className} sr-only`
            }, label);
            
            // Insert label before target element
            element.parentNode.insertBefore(labelElement, element);
            
            // Link elements
            const existingLabel = element.getAttribute('aria-labelledby');
            const newLabel = existingLabel ? `${existingLabel} ${id}` : id;
            element.setAttribute('aria-labelledby', newLabel);
            
            return labelElement;
        }
        
        /**
         * Update element accessibility state
         */
        updateState(element, states) {
            if (typeof element === 'string') {
                element = Utils.DOM.$(element);
            }
            
            if (!element) return;
            
            Object.keys(states).forEach(state => {
                const value = states[state];
                
                switch (state) {
                    case 'expanded':
                        element.setAttribute('aria-expanded', value);
                        break;
                        
                    case 'selected':
                        element.setAttribute('aria-selected', value);
                        break;
                        
                    case 'checked':
                        element.setAttribute('aria-checked', value);
                        break;
                        
                    case 'disabled':
                        element.setAttribute('aria-disabled', value);
                        if (value) {
                            element.setAttribute('tabindex', '-1');
                        } else {
                            element.removeAttribute('tabindex');
                        }
                        break;
                        
                    case 'hidden':
                        element.setAttribute('aria-hidden', value);
                        break;
                        
                    case 'pressed':
                        element.setAttribute('aria-pressed', value);
                        break;
                        
                    case 'current':
                        element.setAttribute('aria-current', value);
                        break;
                        
                    case 'invalid':
                        element.setAttribute('aria-invalid', value);
                        break;
                }
            });
        }
    }
    
    /**
     * Keyboard Navigation Helper
     */
    class KeyboardNavigation {
        constructor() {
            this.handlers = new Map();
        }
        
        /**
         * Register keyboard handler for element
         */
        register(element, keyMap, options = {}) {
            if (typeof element === 'string') {
                element = Utils.DOM.$(element);
            }
            
            if (!element) return;
            
            const handler = this.createHandler(keyMap, options);
            
            Utils.Events.on(element, 'keydown', handler);
            this.handlers.set(element, handler);
        }
        
        /**
         * Unregister keyboard handler
         */
        unregister(element) {
            if (typeof element === 'string') {
                element = Utils.DOM.$(element);
            }
            
            const handler = this.handlers.get(element);
            if (handler) {
                Utils.Events.off(element, 'keydown', handler);
                this.handlers.delete(element);
            }
        }
        
        /**
         * Create keyboard handler
         */
        createHandler(keyMap, options) {
            const {
                preventDefault = true,
                stopPropagation = false
            } = options;
            
            return function(e) {
                const key = this.normalizeKey(e);
                const handler = keyMap[key];
                
                if (handler) {
                    if (preventDefault) {
                        e.preventDefault();
                    }
                    
                    if (stopPropagation) {
                        e.stopPropagation();
                    }
                    
                    handler.call(this, e);
                }
            }.bind(this);
        }
        
        /**
         * Normalize key combination
         */
        normalizeKey(e) {
            const parts = [];
            
            if (e.ctrlKey) parts.push('ctrl');
            if (e.altKey) parts.push('alt');
            if (e.shiftKey) parts.push('shift');
            if (e.metaKey) parts.push('meta');
            
            parts.push(e.key.toLowerCase());
            
            return parts.join('+');
        }
        
        /**
         * Navigate between elements
         */
        navigateItems(items, currentIndex, direction, wrap = true) {
            if (!Array.isArray(items) || items.length === 0) return -1;
            
            let nextIndex;
            
            switch (direction) {
                case 'next':
                    nextIndex = currentIndex + 1;
                    if (nextIndex >= items.length) {
                        nextIndex = wrap ? 0 : items.length - 1;
                    }
                    break;
                    
                case 'previous':
                    nextIndex = currentIndex - 1;
                    if (nextIndex < 0) {
                        nextIndex = wrap ? items.length - 1 : 0;
                    }
                    break;
                    
                case 'first':
                    nextIndex = 0;
                    break;
                    
                case 'last':
                    nextIndex = items.length - 1;
                    break;
                    
                default:
                    return currentIndex;
            }
            
            if (items[nextIndex] && typeof items[nextIndex].focus === 'function') {
                items[nextIndex].focus();
            }
            
            return nextIndex;
        }
    }
    
    /**
     * Color Contrast Utility
     */
    class ColorContrastUtility {
        /**
         * Calculate relative luminance
         */
        getLuminance(color) {
            const rgb = this.parseColor(color);
            if (!rgb) return null;
            
            const [r, g, b] = rgb.map(channel => {
                channel = channel / 255;
                return channel <= 0.03928 
                    ? channel / 12.92 
                    : Math.pow((channel + 0.055) / 1.055, 2.4);
            });
            
            return 0.2126 * r + 0.7152 * g + 0.0722 * b;
        }
        
        /**
         * Calculate contrast ratio between two colors
         */
        getContrastRatio(color1, color2) {
            const l1 = this.getLuminance(color1);
            const l2 = this.getLuminance(color2);
            
            if (l1 === null || l2 === null) return null;
            
            const lighter = Math.max(l1, l2);
            const darker = Math.min(l1, l2);
            
            return (lighter + 0.05) / (darker + 0.05);
        }
        
        /**
         * Check if contrast meets WCAG standards
         */
        checkContrast(foreground, background, level = 'AA', size = 'normal') {
            const ratio = this.getContrastRatio(foreground, background);
            if (ratio === null) return null;
            
            const thresholds = {
                'AA': size === 'large' ? 3 : 4.5,
                'AAA': size === 'large' ? 4.5 : 7
            };
            
            const threshold = thresholds[level] || thresholds['AA'];
            
            return {
                ratio: ratio,
                passes: ratio >= threshold,
                level: level,
                threshold: threshold
            };
        }
        
        /**
         * Parse color string to RGB array
         */
        parseColor(color) {
            // Handle hex colors
            if (color.startsWith('#')) {
                const hex = color.slice(1);
                if (hex.length === 3) {
                    return [
                        parseInt(hex[0] + hex[0], 16),
                        parseInt(hex[1] + hex[1], 16),
                        parseInt(hex[2] + hex[2], 16)
                    ];
                } else if (hex.length === 6) {
                    return [
                        parseInt(hex.slice(0, 2), 16),
                        parseInt(hex.slice(2, 4), 16),
                        parseInt(hex.slice(4, 6), 16)
                    ];
                }
            }
            
            // Handle rgb/rgba colors
            const rgbMatch = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (rgbMatch) {
                return [
                    parseInt(rgbMatch[1], 10),
                    parseInt(rgbMatch[2], 10),
                    parseInt(rgbMatch[3], 10)
                ];
            }
            
            return null;
        }
        
        /**
         * Audit element color contrast
         */
        auditElement(element) {
            if (typeof element === 'string') {
                element = Utils.DOM.$(element);
            }
            
            if (!element) return null;
            
            const styles = window.getComputedStyle(element);
            const color = styles.color;
            const backgroundColor = styles.backgroundColor;
            
            // If background is transparent, find parent with background
            let bgColor = backgroundColor;
            let parent = element.parentElement;
            
            while (parent && (bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent')) {
                bgColor = window.getComputedStyle(parent).backgroundColor;
                parent = parent.parentElement;
            }
            
            if (bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent') {
                bgColor = '#ffffff'; // Assume white background
            }
            
            const fontSize = parseFloat(styles.fontSize);
            const fontWeight = styles.fontWeight;
            const size = (fontSize >= 18) || (fontSize >= 14 && parseInt(fontWeight) >= 700) 
                         ? 'large' : 'normal';
            
            return this.checkContrast(color, bgColor, 'AA', size);
        }
    }
    
    /**
     * Main Accessibility Manager
     */
    class AccessibilityManager {
        constructor() {
            this.focusManager = new FocusManager();
            this.screenReader = new ScreenReaderUtility();
            this.keyboard = new KeyboardNavigation();
            this.colorContrast = new ColorContrastUtility();
            
            this.init();
        }
        
        init() {
            this.setupGlobalFeatures();
            this.setupSkipLinks();
            this.auditAccessibility();
        }
        
        /**
         * Setup global accessibility features
         */
        setupGlobalFeatures() {
            // Add focus indicators for keyboard users
            let isUsingKeyboard = false;
            
            Utils.Events.on(document, 'keydown', () => {
                isUsingKeyboard = true;
                Utils.DOM.addClass(document.body, 'using-keyboard');
            });
            
            Utils.Events.on(document, 'mousedown', () => {
                isUsingKeyboard = false;
                Utils.DOM.removeClass(document.body, 'using-keyboard');
            });
            
            // Announce page changes
            this.announcePageChanges();
        }
        
        /**
         * Setup skip links
         */
        setupSkipLinks() {
            const mainContent = Utils.DOM.$('main, #main, .main-content');
            if (mainContent && !mainContent.id) {
                mainContent.id = 'main-content';
            }
            
            // Create skip link if it doesn't exist
            if (!Utils.DOM.$('.skip-link')) {
                const skipLink = Utils.DOM.createElement('a', {
                    href: '#main-content',
                    className: 'skip-link'
                }, 'Skip to main content');
                
                document.body.insertBefore(skipLink, document.body.firstChild);
            }
        }
        
        /**
         * Announce page changes for SPA
         */
        announcePageChanges() {
            let lastUrl = window.location.href;
            let lastTitle = document.title;
            
            const checkForChanges = () => {
                const currentUrl = window.location.href;
                const currentTitle = document.title;
                
                if (currentUrl !== lastUrl) {
                    this.screenReader.announce(`Navigated to ${currentTitle}`, 'polite');
                    lastUrl = currentUrl;
                    lastTitle = currentTitle;
                } else if (currentTitle !== lastTitle) {
                    this.screenReader.announce(`Page updated: ${currentTitle}`, 'polite');
                    lastTitle = currentTitle;
                }
            };
            
            // Check for changes periodically
            setInterval(checkForChanges, 1000);
            
            // Also check on popstate (browser back/forward)
            Utils.Events.on(window, 'popstate', checkForChanges);
        }
        
        /**
         * Run accessibility audit
         */
        auditAccessibility() {
            if (!window.console || !window.console.warn) return;
            
            const issues = [];
            
            // Check for missing alt text on images
            const images = Utils.DOM.$$('img');
            images.forEach(img => {
                if (!img.alt && !img.getAttribute('aria-hidden')) {
                    issues.push(`Image missing alt text: ${img.src || '[no src]'}`);
                }
            });
            
            // Check for missing form labels
            const inputs = Utils.DOM.$$('input[type]:not([type="hidden"]), textarea, select');
            inputs.forEach(input => {
                const id = input.id;
                const label = id ? Utils.DOM.$(`label[for="${id}"]`) : null;
                const ariaLabel = input.getAttribute('aria-label');
                const ariaLabelledBy = input.getAttribute('aria-labelledby');
                
                if (!label && !ariaLabel && !ariaLabelledBy) {
                    issues.push(`Form control missing label: ${input.name || input.type || '[unnamed]'}`);
                }
            });
            
            // Check for missing heading structure
            const headings = Utils.DOM.$$('h1, h2, h3, h4, h5, h6');
            if (headings.length > 0) {
                let hasH1 = false;
                let lastLevel = 0;
                
                headings.forEach(heading => {
                    const level = parseInt(heading.tagName.slice(1));
                    
                    if (level === 1) hasH1 = true;
                    
                    if (lastLevel > 0 && level > lastLevel + 1) {
                        issues.push(`Heading level skip: ${heading.tagName} follows h${lastLevel}`);
                    }
                    
                    lastLevel = level;
                });
                
                if (!hasH1) {
                    issues.push('Page is missing an h1 heading');
                }
            }
            
            // Report issues
            if (issues.length > 0) {
                console.warn('Accessibility issues found:', issues);
            }
        }
        
        /**
         * Get all accessibility utilities
         */
        getUtils() {
            return {
                focus: this.focusManager,
                screenReader: this.screenReader,
                keyboard: this.keyboard,
                colorContrast: this.colorContrast
            };
        }
    }
    
    // Create global instance
    const accessibilityManager = new AccessibilityManager();
    
    // Export all classes and utilities
    return {
        FocusManager,
        ScreenReaderUtility,
        KeyboardNavigation,
        ColorContrastUtility,
        AccessibilityManager,
        
        // Global instance for easy access
        manager: accessibilityManager,
        
        // Convenience methods
        focus: accessibilityManager.focusManager,
        announce: (message, priority) => accessibilityManager.screenReader.announce(message, priority),
        trapFocus: (container, options) => accessibilityManager.focusManager.trapFocus(container, options),
        releaseFocus: () => accessibilityManager.focusManager.releaseTrap(),
        checkContrast: (fg, bg, level, size) => accessibilityManager.colorContrast.checkContrast(fg, bg, level, size)
    };
});