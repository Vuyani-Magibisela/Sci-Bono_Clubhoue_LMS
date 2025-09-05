/**
 * UI Component Modules
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

// Define the UIComponents module with Utils dependency
ModuleLoader.define('UIComponents', ['Utils'], function(Utils) {
    'use strict';
    
    /**
     * Base Component Class
     * All UI components extend from this base class
     */
    class BaseComponent {
        constructor(element, options = {}) {
            this.element = typeof element === 'string' ? Utils.DOM.$(element) : element;
            if (!this.element) {
                throw new Error('Element not found');
            }
            
            this.options = { ...this.defaultOptions, ...options };
            this.isInitialized = false;
            this.isDestroyed = false;
            
            this.init();
        }
        
        init() {
            if (this.isInitialized) return;
            
            this.bindEvents();
            this.isInitialized = true;
            
            Utils.Events.trigger(this.element, 'component:init', {
                component: this.constructor.name,
                instance: this
            });
        }
        
        destroy() {
            if (this.isDestroyed) return;
            
            this.unbindEvents();
            this.isDestroyed = true;
            this.isInitialized = false;
            
            Utils.Events.trigger(this.element, 'component:destroy', {
                component: this.constructor.name,
                instance: this
            });
        }
        
        bindEvents() {
            // Override in subclasses
        }
        
        unbindEvents() {
            // Override in subclasses
        }
        
        get defaultOptions() {
            return {};
        }
    }
    
    /**
     * Alert Component
     * Displays dismissible alert messages
     */
    class Alert extends BaseComponent {
        get defaultOptions() {
            return {
                dismissible: true,
                autoHide: false,
                autoHideDelay: 5000,
                animation: true,
                classes: {
                    alert: 'alert',
                    dismissButton: 'alert__dismiss',
                    show: 'alert--show',
                    hide: 'alert--hide'
                }
            };
        }
        
        init() {
            super.init();
            
            if (this.options.dismissible) {
                this.createDismissButton();
            }
            
            if (this.options.autoHide) {
                this.setupAutoHide();
            }
            
            // Show alert with animation if specified
            if (this.options.animation) {
                Utils.DOM.addClass(this.element, this.options.classes.show);
            }
        }
        
        createDismissButton() {
            let dismissButton = Utils.DOM.$('.alert__dismiss', this.element);
            
            if (!dismissButton) {
                dismissButton = Utils.DOM.createElement('button', {
                    className: this.options.classes.dismissButton,
                    type: 'button',
                    'aria-label': 'Close alert'
                }, '×');
                
                this.element.appendChild(dismissButton);
            }
            
            this.dismissButton = dismissButton;
        }
        
        setupAutoHide() {
            this.autoHideTimeout = setTimeout(() => {
                this.hide();
            }, this.options.autoHideDelay);
        }
        
        bindEvents() {
            if (this.dismissButton) {
                Utils.Events.on(this.dismissButton, 'click', (e) => {
                    e.preventDefault();
                    this.hide();
                });
            }
        }
        
        show() {
            if (this.options.animation) {
                Utils.DOM.removeClass(this.element, this.options.classes.hide);
                Utils.DOM.addClass(this.element, this.options.classes.show);
            }
            
            this.element.style.display = 'block';
            Utils.Events.trigger(this.element, 'alert:show');
        }
        
        hide() {
            if (this.autoHideTimeout) {
                clearTimeout(this.autoHideTimeout);
            }
            
            if (this.options.animation) {
                Utils.DOM.removeClass(this.element, this.options.classes.show);
                Utils.DOM.addClass(this.element, this.options.classes.hide);
                
                setTimeout(() => {
                    this.element.style.display = 'none';
                    Utils.Events.trigger(this.element, 'alert:hide');
                }, 300);
            } else {
                this.element.style.display = 'none';
                Utils.Events.trigger(this.element, 'alert:hide');
            }
        }
    }
    
    /**
     * Tabs Component
     * Manages tabbed content panels
     */
    class Tabs extends BaseComponent {
        get defaultOptions() {
            return {
                activeIndex: 0,
                keyboard: true,
                classes: {
                    tabList: 'tabs__list',
                    tab: 'tabs__tab',
                    tabPanel: 'tabs__panel',
                    active: 'tabs__tab--active',
                    activePanel: 'tabs__panel--active'
                }
            };
        }
        
        init() {
            this.tabList = Utils.DOM.$('.tabs__list', this.element);
            this.tabs = Utils.DOM.$$('.tabs__tab', this.element);
            this.panels = Utils.DOM.$$('.tabs__panel', this.element);
            
            if (!this.tabList || this.tabs.length === 0 || this.panels.length === 0) {
                throw new Error('Required tab elements not found');
            }
            
            this.setupAccessibility();
            super.init();
            this.setActiveTab(this.options.activeIndex);
        }
        
        setupAccessibility() {
            // Set up ARIA attributes
            this.tabList.setAttribute('role', 'tablist');
            
            this.tabs.forEach((tab, index) => {
                const panelId = this.panels[index].id || `panel-${Utils.General.uniqueId()}`;
                const tabId = tab.id || `tab-${Utils.General.uniqueId()}`;
                
                tab.setAttribute('role', 'tab');
                tab.setAttribute('aria-controls', panelId);
                tab.setAttribute('aria-selected', 'false');
                tab.setAttribute('tabindex', '-1');
                tab.id = tabId;
                
                this.panels[index].setAttribute('role', 'tabpanel');
                this.panels[index].setAttribute('aria-labelledby', tabId);
                this.panels[index].id = panelId;
            });
        }
        
        bindEvents() {
            // Tab click events
            this.tabs.forEach((tab, index) => {
                Utils.Events.on(tab, 'click', (e) => {
                    e.preventDefault();
                    this.setActiveTab(index);
                });
            });
            
            // Keyboard navigation
            if (this.options.keyboard) {
                Utils.Events.on(this.tabList, 'keydown', (e) => {
                    this.handleKeydown(e);
                });
            }
        }
        
        handleKeydown(e) {
            const activeTab = Utils.DOM.$('.tabs__tab--active', this.element);
            const activeIndex = Array.from(this.tabs).indexOf(activeTab);
            
            switch (e.key) {
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    this.setActiveTab(activeIndex > 0 ? activeIndex - 1 : this.tabs.length - 1);
                    break;
                    
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    this.setActiveTab(activeIndex < this.tabs.length - 1 ? activeIndex + 1 : 0);
                    break;
                    
                case 'Home':
                    e.preventDefault();
                    this.setActiveTab(0);
                    break;
                    
                case 'End':
                    e.preventDefault();
                    this.setActiveTab(this.tabs.length - 1);
                    break;
            }
        }
        
        setActiveTab(index) {
            if (index < 0 || index >= this.tabs.length) return;
            
            // Update tabs
            this.tabs.forEach((tab, i) => {
                const isActive = i === index;
                Utils.DOM.toggleClass(tab, this.options.classes.active, isActive);
                tab.setAttribute('aria-selected', isActive);
                tab.setAttribute('tabindex', isActive ? '0' : '-1');
            });
            
            // Update panels
            this.panels.forEach((panel, i) => {
                Utils.DOM.toggleClass(panel, this.options.classes.activePanel, i === index);
            });
            
            // Focus active tab
            this.tabs[index].focus();
            
            Utils.Events.trigger(this.element, 'tabs:change', {
                activeIndex: index,
                activeTab: this.tabs[index],
                activePanel: this.panels[index]
            });
        }
        
        getActiveIndex() {
            return Array.from(this.tabs).findIndex(tab => 
                Utils.DOM.hasClass(tab, this.options.classes.active)
            );
        }
    }
    
    /**
     * Accordion Component
     * Manages collapsible content sections
     */
    class Accordion extends BaseComponent {
        get defaultOptions() {
            return {
                multiple: false,
                keyboard: true,
                classes: {
                    item: 'accordion__item',
                    header: 'accordion__header',
                    content: 'accordion__content',
                    expanded: 'accordion__item--expanded',
                    collapsed: 'accordion__item--collapsed'
                }
            };
        }
        
        init() {
            this.items = Utils.DOM.$$('.accordion__item', this.element);
            this.headers = Utils.DOM.$$('.accordion__header', this.element);
            this.contents = Utils.DOM.$$('.accordion__content', this.element);
            
            this.setupAccessibility();
            super.init();
        }
        
        setupAccessibility() {
            this.headers.forEach((header, index) => {
                const contentId = this.contents[index].id || `accordion-content-${Utils.General.uniqueId()}`;
                const headerId = header.id || `accordion-header-${Utils.General.uniqueId()}`;
                
                header.setAttribute('role', 'button');
                header.setAttribute('aria-expanded', 'false');
                header.setAttribute('aria-controls', contentId);
                header.setAttribute('tabindex', '0');
                header.id = headerId;
                
                this.contents[index].setAttribute('role', 'region');
                this.contents[index].setAttribute('aria-labelledby', headerId);
                this.contents[index].id = contentId;
            });
        }
        
        bindEvents() {
            this.headers.forEach((header, index) => {
                Utils.Events.on(header, 'click', (e) => {
                    e.preventDefault();
                    this.toggle(index);
                });
                
                if (this.options.keyboard) {
                    Utils.Events.on(header, 'keydown', (e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.toggle(index);
                        }
                    });
                }
            });
        }
        
        toggle(index) {
            const item = this.items[index];
            const header = this.headers[index];
            const content = this.contents[index];
            const isExpanded = Utils.DOM.hasClass(item, this.options.classes.expanded);
            
            if (isExpanded) {
                this.collapse(index);
            } else {
                if (!this.options.multiple) {
                    // Collapse all other items
                    this.items.forEach((_, i) => {
                        if (i !== index) {
                            this.collapse(i);
                        }
                    });
                }
                this.expand(index);
            }
        }
        
        expand(index) {
            const item = this.items[index];
            const header = this.headers[index];
            const content = this.contents[index];
            
            Utils.DOM.removeClass(item, this.options.classes.collapsed);
            Utils.DOM.addClass(item, this.options.classes.expanded);
            header.setAttribute('aria-expanded', 'true');
            
            // Animate content
            content.style.height = 'auto';
            const height = content.offsetHeight;
            content.style.height = '0';
            content.offsetHeight; // Force reflow
            content.style.height = height + 'px';
            
            Utils.Events.trigger(this.element, 'accordion:expand', {
                index,
                item,
                header,
                content
            });
        }
        
        collapse(index) {
            const item = this.items[index];
            const header = this.headers[index];
            const content = this.contents[index];
            
            Utils.DOM.removeClass(item, this.options.classes.expanded);
            Utils.DOM.addClass(item, this.options.classes.collapsed);
            header.setAttribute('aria-expanded', 'false');
            
            // Animate content
            content.style.height = content.offsetHeight + 'px';
            content.offsetHeight; // Force reflow
            content.style.height = '0';
            
            Utils.Events.trigger(this.element, 'accordion:collapse', {
                index,
                item,
                header,
                content
            });
        }
    }
    
    /**
     * Tooltip Component
     * Shows contextual information on hover/focus
     */
    class Tooltip extends BaseComponent {
        get defaultOptions() {
            return {
                placement: 'top',
                trigger: 'hover focus',
                delay: { show: 0, hide: 0 },
                html: false,
                classes: {
                    tooltip: 'tooltip',
                    arrow: 'tooltip__arrow',
                    inner: 'tooltip__inner',
                    show: 'tooltip--show'
                }
            };
        }
        
        init() {
            this.content = this.element.getAttribute('title') || 
                           this.element.getAttribute('data-tooltip') || '';
            
            if (!this.content) return;
            
            // Remove title to prevent native tooltip
            this.element.removeAttribute('title');
            
            this.createTooltip();
            super.init();
        }
        
        createTooltip() {
            this.tooltip = Utils.DOM.createElement('div', {
                className: this.options.classes.tooltip,
                role: 'tooltip'
            });
            
            const arrow = Utils.DOM.createElement('div', {
                className: this.options.classes.arrow
            });
            
            const inner = Utils.DOM.createElement('div', {
                className: this.options.classes.inner
            });
            
            if (this.options.html) {
                inner.innerHTML = this.content;
            } else {
                inner.textContent = this.content;
            }
            
            this.tooltip.appendChild(arrow);
            this.tooltip.appendChild(inner);
            document.body.appendChild(this.tooltip);
        }
        
        bindEvents() {
            const triggers = this.options.trigger.split(' ');
            
            triggers.forEach(trigger => {
                switch (trigger) {
                    case 'hover':
                        Utils.Events.on(this.element, 'mouseenter', () => this.show());
                        Utils.Events.on(this.element, 'mouseleave', () => this.hide());
                        break;
                        
                    case 'focus':
                        Utils.Events.on(this.element, 'focus', () => this.show());
                        Utils.Events.on(this.element, 'blur', () => this.hide());
                        break;
                        
                    case 'click':
                        Utils.Events.on(this.element, 'click', () => this.toggle());
                        break;
                }
            });
        }
        
        show() {
            if (this.showTimeout) {
                clearTimeout(this.showTimeout);
            }
            
            this.showTimeout = setTimeout(() => {
                this.position();
                Utils.DOM.addClass(this.tooltip, this.options.classes.show);
                Utils.Events.trigger(this.element, 'tooltip:show');
            }, this.options.delay.show);
        }
        
        hide() {
            if (this.showTimeout) {
                clearTimeout(this.showTimeout);
            }
            
            setTimeout(() => {
                Utils.DOM.removeClass(this.tooltip, this.options.classes.show);
                Utils.Events.trigger(this.element, 'tooltip:hide');
            }, this.options.delay.hide);
        }
        
        toggle() {
            if (Utils.DOM.hasClass(this.tooltip, this.options.classes.show)) {
                this.hide();
            } else {
                this.show();
            }
        }
        
        position() {
            const elementRect = this.element.getBoundingClientRect();
            const tooltipRect = this.tooltip.getBoundingClientRect();
            
            let left, top;
            
            switch (this.options.placement) {
                case 'top':
                    left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                    top = elementRect.top - tooltipRect.height - 8;
                    break;
                    
                case 'bottom':
                    left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                    top = elementRect.bottom + 8;
                    break;
                    
                case 'left':
                    left = elementRect.left - tooltipRect.width - 8;
                    top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                    break;
                    
                case 'right':
                    left = elementRect.right + 8;
                    top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                    break;
            }
            
            this.tooltip.style.left = left + window.scrollX + 'px';
            this.tooltip.style.top = top + window.scrollY + 'px';
        }
        
        destroy() {
            if (this.tooltip && this.tooltip.parentNode) {
                this.tooltip.parentNode.removeChild(this.tooltip);
            }
            super.destroy();
        }
    }
    
    /**
     * Dropdown Component
     * Manages dropdown menus and content
     */
    class Dropdown extends BaseComponent {
        get defaultOptions() {
            return {
                trigger: 'click',
                placement: 'bottom-start',
                closeOnClickOutside: true,
                closeOnEscape: true,
                classes: {
                    dropdown: 'dropdown',
                    toggle: 'dropdown__toggle',
                    menu: 'dropdown__menu',
                    show: 'dropdown--show',
                    item: 'dropdown__item'
                }
            };
        }
        
        init() {
            this.toggle = Utils.DOM.$('.dropdown__toggle', this.element);
            this.menu = Utils.DOM.$('.dropdown__menu', this.element);
            
            if (!this.toggle || !this.menu) {
                throw new Error('Required dropdown elements not found');
            }
            
            this.setupAccessibility();
            super.init();
        }
        
        setupAccessibility() {
            const menuId = this.menu.id || `dropdown-menu-${Utils.General.uniqueId()}`;
            
            this.toggle.setAttribute('aria-haspopup', 'true');
            this.toggle.setAttribute('aria-expanded', 'false');
            this.toggle.setAttribute('aria-controls', menuId);
            
            this.menu.setAttribute('role', 'menu');
            this.menu.id = menuId;
            
            const items = Utils.DOM.$$('.dropdown__item', this.menu);
            items.forEach(item => {
                item.setAttribute('role', 'menuitem');
                item.setAttribute('tabindex', '-1');
            });
        }
        
        bindEvents() {
            if (this.options.trigger === 'click') {
                Utils.Events.on(this.toggle, 'click', (e) => {
                    e.preventDefault();
                    this.toggle();
                });
            } else if (this.options.trigger === 'hover') {
                Utils.Events.on(this.element, 'mouseenter', () => this.show());
                Utils.Events.on(this.element, 'mouseleave', () => this.hide());
            }
            
            // Close on click outside
            if (this.options.closeOnClickOutside) {
                Utils.Events.on(document, 'click', (e) => {
                    if (!this.element.contains(e.target)) {
                        this.hide();
                    }
                });
            }
            
            // Close on escape
            if (this.options.closeOnEscape) {
                Utils.Events.on(document, 'keydown', (e) => {
                    if (e.key === 'Escape' && this.isOpen()) {
                        this.hide();
                    }
                });
            }
            
            // Menu item navigation
            Utils.Events.on(this.menu, 'keydown', (e) => {
                this.handleMenuKeydown(e);
            });
        }
        
        handleMenuKeydown(e) {
            const items = Utils.DOM.$$('.dropdown__item', this.menu);
            const currentIndex = Array.from(items).findIndex(item => 
                document.activeElement === item
            );
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                    items[nextIndex].focus();
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                    items[prevIndex].focus();
                    break;
                    
                case 'Escape':
                    this.hide();
                    this.toggle.focus();
                    break;
            }
        }
        
        show() {
            Utils.DOM.addClass(this.element, this.options.classes.show);
            this.toggle.setAttribute('aria-expanded', 'true');
            Utils.Events.trigger(this.element, 'dropdown:show');
        }
        
        hide() {
            Utils.DOM.removeClass(this.element, this.options.classes.show);
            this.toggle.setAttribute('aria-expanded', 'false');
            Utils.Events.trigger(this.element, 'dropdown:hide');
        }
        
        toggle() {
            if (this.isOpen()) {
                this.hide();
            } else {
                this.show();
            }
        }
        
        isOpen() {
            return Utils.DOM.hasClass(this.element, this.options.classes.show);
        }
    }
    
    // Export all components
    return {
        BaseComponent,
        Alert,
        Tabs,
        Accordion,
        Tooltip,
        Dropdown,
        
        // Factory methods for easy instantiation
        createAlert: (element, options) => new Alert(element, options),
        createTabs: (element, options) => new Tabs(element, options),
        createAccordion: (element, options) => new Accordion(element, options),
        createTooltip: (element, options) => new Tooltip(element, options),
        createDropdown: (element, options) => new Dropdown(element, options)
    };
});