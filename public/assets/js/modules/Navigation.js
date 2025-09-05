/**
 * Responsive Navigation Components
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

// Define the Navigation module with Utils dependency
ModuleLoader.define('Navigation', ['Utils'], function(Utils) {
    'use strict';
    
    /**
     * Mobile Navigation Component
     * Handles responsive navigation with hamburger menu
     */
    class MobileNavigation {
        constructor(element, options = {}) {
            this.element = typeof element === 'string' ? Utils.DOM.$(element) : element;
            
            if (!this.element) {
                throw new Error('Navigation element not found');
            }
            
            this.options = {
                breakpoint: 768,
                toggleSelector: '.nav-toggle',
                menuSelector: '.nav-menu',
                overlaySelector: '.nav-overlay',
                animationDuration: 300,
                closeOnLinkClick: true,
                closeOnOutsideClick: true,
                classes: {
                    open: 'nav--open',
                    toggle: 'nav-toggle',
                    toggleActive: 'nav-toggle--active',
                    menu: 'nav-menu',
                    menuOpen: 'nav-menu--open',
                    overlay: 'nav-overlay',
                    overlayVisible: 'nav-overlay--visible'
                },
                ...options
            };
            
            this.isOpen = false;
            this.toggle = null;
            this.menu = null;
            this.overlay = null;
            
            this.init();
        }
        
        init() {
            this.findElements();
            this.createOverlay();
            this.setupAccessibility();
            this.bindEvents();
            
            // Check initial state
            this.handleResize();
        }
        
        findElements() {
            this.toggle = Utils.DOM.$(this.options.toggleSelector, this.element);
            this.menu = Utils.DOM.$(this.options.menuSelector, this.element);
            
            if (!this.toggle) {
                this.createToggle();
            }
            
            if (!this.menu) {
                throw new Error('Navigation menu not found');
            }
        }
        
        createToggle() {
            this.toggle = Utils.DOM.createElement('button', {
                type: 'button',
                className: this.options.classes.toggle,
                'aria-label': 'Toggle navigation menu'
            });
            
            // Create hamburger icon
            const icon = Utils.DOM.createElement('span', {
                className: 'nav-toggle__icon'
            });
            
            this.toggle.appendChild(icon);
            this.element.insertBefore(this.toggle, this.menu);
        }
        
        createOverlay() {
            this.overlay = Utils.DOM.createElement('div', {
                className: this.options.classes.overlay
            });
            
            document.body.appendChild(this.overlay);
        }
        
        setupAccessibility() {
            // Toggle button
            this.toggle.setAttribute('aria-expanded', 'false');
            this.toggle.setAttribute('aria-controls', this.menu.id || 'nav-menu');
            
            // Menu
            if (!this.menu.id) {
                this.menu.id = 'nav-menu-' + Utils.General.uniqueId();
            }
            
            this.menu.setAttribute('role', 'menu');
            this.menu.setAttribute('aria-hidden', 'true');
            
            // Menu items
            const menuItems = Utils.DOM.$$('a', this.menu);
            menuItems.forEach(item => {
                item.setAttribute('role', 'menuitem');
            });
        }
        
        bindEvents() {
            // Toggle button click
            Utils.Events.on(this.toggle, 'click', (e) => {
                e.preventDefault();
                this.toggle();
            });
            
            // Overlay click
            if (this.options.closeOnOutsideClick) {
                Utils.Events.on(this.overlay, 'click', () => {
                    this.close();
                });
            }
            
            // Menu link clicks
            if (this.options.closeOnLinkClick) {
                Utils.Events.on(this.menu, 'click', (e) => {
                    if (e.target.tagName === 'A') {
                        this.close();
                    }
                });
            }
            
            // Escape key
            Utils.Events.on(document, 'keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
            
            // Window resize
            Utils.Events.on(window, 'resize', Utils.Events.debounce(() => {
                this.handleResize();
            }, 250));
        }
        
        handleResize() {
            if (window.innerWidth >= this.options.breakpoint) {
                this.close();
                this.hide();
            } else {
                this.show();
            }
        }
        
        show() {
            Utils.DOM.removeClass(this.element, 'nav--desktop');
            Utils.DOM.addClass(this.element, 'nav--mobile');
        }
        
        hide() {
            Utils.DOM.removeClass(this.element, 'nav--mobile');
            Utils.DOM.addClass(this.element, 'nav--desktop');
        }
        
        open() {
            if (this.isOpen) return;
            
            this.isOpen = true;
            
            // Update classes
            Utils.DOM.addClass(this.element, this.options.classes.open);
            Utils.DOM.addClass(this.toggle, this.options.classes.toggleActive);
            Utils.DOM.addClass(this.menu, this.options.classes.menuOpen);
            Utils.DOM.addClass(this.overlay, this.options.classes.overlayVisible);
            
            // Update ARIA attributes
            this.toggle.setAttribute('aria-expanded', 'true');
            this.menu.setAttribute('aria-hidden', 'false');
            
            // Prevent body scroll
            Utils.DOM.addClass(document.body, 'nav-open');
            
            Utils.Events.trigger(this.element, 'nav:open');
        }
        
        close() {
            if (!this.isOpen) return;
            
            this.isOpen = false;
            
            // Update classes
            Utils.DOM.removeClass(this.element, this.options.classes.open);
            Utils.DOM.removeClass(this.toggle, this.options.classes.toggleActive);
            Utils.DOM.removeClass(this.menu, this.options.classes.menuOpen);
            Utils.DOM.removeClass(this.overlay, this.options.classes.overlayVisible);
            
            // Update ARIA attributes
            this.toggle.setAttribute('aria-expanded', 'false');
            this.menu.setAttribute('aria-hidden', 'true');
            
            // Restore body scroll
            Utils.DOM.removeClass(document.body, 'nav-open');
            
            Utils.Events.trigger(this.element, 'nav:close');
        }
        
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }
        
        destroy() {
            this.close();
            
            if (this.overlay && this.overlay.parentNode) {
                this.overlay.parentNode.removeChild(this.overlay);
            }
            
            Utils.DOM.removeClass(document.body, 'nav-open');
            Utils.Events.trigger(this.element, 'nav:destroy');
        }
    }
    
    /**
     * Dropdown Menu Component
     * Handles multi-level dropdown menus
     */
    class DropdownMenu {
        constructor(element, options = {}) {
            this.element = typeof element === 'string' ? Utils.DOM.$(element) : element;
            
            if (!this.element) {
                throw new Error('Dropdown menu element not found');
            }
            
            this.options = {
                trigger: 'hover', // hover, click, focus
                delay: 150,
                closeDelay: 300,
                keyboard: true,
                classes: {
                    dropdown: 'dropdown-menu',
                    item: 'dropdown-menu__item',
                    link: 'dropdown-menu__link',
                    submenu: 'dropdown-menu__submenu',
                    open: 'dropdown-menu--open',
                    hasSubmenu: 'dropdown-menu__item--has-submenu'
                },
                ...options
            };
            
            this.activeDropdowns = new Set();
            this.timers = new Map();
            
            this.init();
        }
        
        init() {
            this.setupStructure();
            this.setupAccessibility();
            this.bindEvents();
        }
        
        setupStructure() {
            Utils.DOM.addClass(this.element, this.options.classes.dropdown);
            
            // Find and mark items with submenus
            const items = Utils.DOM.$$('.dropdown-menu__item', this.element);
            items.forEach(item => {
                const submenu = Utils.DOM.$('.dropdown-menu__submenu', item);
                if (submenu) {
                    Utils.DOM.addClass(item, this.options.classes.hasSubmenu);
                }
            });
        }
        
        setupAccessibility() {
            const links = Utils.DOM.$$('.dropdown-menu__link', this.element);
            
            links.forEach(link => {
                const parent = Utils.DOM.closest(link, '.dropdown-menu__item');
                const submenu = Utils.DOM.$('.dropdown-menu__submenu', parent);
                
                if (submenu) {
                    const submenuId = submenu.id || `submenu-${Utils.General.uniqueId()}`;
                    submenu.id = submenuId;
                    
                    link.setAttribute('aria-haspopup', 'true');
                    link.setAttribute('aria-expanded', 'false');
                    link.setAttribute('aria-controls', submenuId);
                    
                    submenu.setAttribute('role', 'menu');
                    submenu.setAttribute('aria-hidden', 'true');
                }
                
                link.setAttribute('role', 'menuitem');
            });
        }
        
        bindEvents() {
            const items = Utils.DOM.$$('.dropdown-menu__item', this.element);
            
            items.forEach(item => {
                const link = Utils.DOM.$('.dropdown-menu__link', item);
                const submenu = Utils.DOM.$('.dropdown-menu__submenu', item);
                
                if (!submenu) return;
                
                if (this.options.trigger === 'hover') {
                    this.bindHoverEvents(item, link, submenu);
                } else if (this.options.trigger === 'click') {
                    this.bindClickEvents(item, link, submenu);
                }
                
                if (this.options.keyboard) {
                    this.bindKeyboardEvents(item, link, submenu);
                }
            });
            
            // Close dropdowns when clicking outside
            Utils.Events.on(document, 'click', (e) => {
                if (!this.element.contains(e.target)) {
                    this.closeAll();
                }
            });
        }
        
        bindHoverEvents(item, link, submenu) {
            Utils.Events.on(item, 'mouseenter', () => {
                this.clearTimer(item);
                this.openDropdown(item, link, submenu);
            });
            
            Utils.Events.on(item, 'mouseleave', () => {
                this.setTimer(item, () => {
                    this.closeDropdown(item, link, submenu);
                }, this.options.closeDelay);
            });
        }
        
        bindClickEvents(item, link, submenu) {
            Utils.Events.on(link, 'click', (e) => {
                e.preventDefault();
                
                if (this.activeDropdowns.has(item)) {
                    this.closeDropdown(item, link, submenu);
                } else {
                    this.closeAll();
                    this.openDropdown(item, link, submenu);
                }
            });
        }
        
        bindKeyboardEvents(item, link, submenu) {
            Utils.Events.on(link, 'keydown', (e) => {
                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        this.openDropdown(item, link, submenu);
                        this.focusFirstItem(submenu);
                        break;
                        
                    case 'ArrowUp':
                        e.preventDefault();
                        this.openDropdown(item, link, submenu);
                        this.focusLastItem(submenu);
                        break;
                        
                    case 'ArrowRight':
                        e.preventDefault();
                        if (!this.activeDropdowns.has(item)) {
                            this.openDropdown(item, link, submenu);
                            this.focusFirstItem(submenu);
                        }
                        break;
                        
                    case 'ArrowLeft':
                    case 'Escape':
                        e.preventDefault();
                        this.closeDropdown(item, link, submenu);
                        link.focus();
                        break;
                        
                    case 'Enter':
                    case ' ':
                        if (submenu) {
                            e.preventDefault();
                            this.openDropdown(item, link, submenu);
                            this.focusFirstItem(submenu);
                        }
                        break;
                }
            });
            
            // Submenu keyboard navigation
            Utils.Events.on(submenu, 'keydown', (e) => {
                this.handleSubmenuKeydown(e, item, link, submenu);
            });
        }
        
        handleSubmenuKeydown(e, parentItem, parentLink, submenu) {
            const items = Utils.DOM.$$('.dropdown-menu__link', submenu);
            const currentIndex = Array.from(items).indexOf(document.activeElement);
            
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
                    
                case 'ArrowLeft':
                case 'Escape':
                    e.preventDefault();
                    this.closeDropdown(parentItem, parentLink, submenu);
                    parentLink.focus();
                    break;
                    
                case 'Home':
                    e.preventDefault();
                    items[0].focus();
                    break;
                    
                case 'End':
                    e.preventDefault();
                    items[items.length - 1].focus();
                    break;
            }
        }
        
        openDropdown(item, link, submenu) {
            if (this.activeDropdowns.has(item)) return;
            
            Utils.DOM.addClass(item, this.options.classes.open);
            link.setAttribute('aria-expanded', 'true');
            submenu.setAttribute('aria-hidden', 'false');
            
            this.activeDropdowns.add(item);
            
            Utils.Events.trigger(this.element, 'dropdown:open', {
                item: item,
                link: link,
                submenu: submenu
            });
        }
        
        closeDropdown(item, link, submenu) {
            if (!this.activeDropdowns.has(item)) return;
            
            Utils.DOM.removeClass(item, this.options.classes.open);
            link.setAttribute('aria-expanded', 'false');
            submenu.setAttribute('aria-hidden', 'true');
            
            this.activeDropdowns.delete(item);
            this.clearTimer(item);
            
            Utils.Events.trigger(this.element, 'dropdown:close', {
                item: item,
                link: link,
                submenu: submenu
            });
        }
        
        closeAll() {
            const items = Utils.DOM.$$('.dropdown-menu__item', this.element);
            
            items.forEach(item => {
                const link = Utils.DOM.$('.dropdown-menu__link', item);
                const submenu = Utils.DOM.$('.dropdown-menu__submenu', item);
                
                if (submenu) {
                    this.closeDropdown(item, link, submenu);
                }
            });
        }
        
        focusFirstItem(submenu) {
            const firstItem = Utils.DOM.$('.dropdown-menu__link', submenu);
            if (firstItem) {
                firstItem.focus();
            }
        }
        
        focusLastItem(submenu) {
            const items = Utils.DOM.$$('.dropdown-menu__link', submenu);
            if (items.length > 0) {
                items[items.length - 1].focus();
            }
        }
        
        setTimer(key, callback, delay) {
            this.clearTimer(key);
            this.timers.set(key, setTimeout(callback, delay));
        }
        
        clearTimer(key) {
            if (this.timers.has(key)) {
                clearTimeout(this.timers.get(key));
                this.timers.delete(key);
            }
        }
        
        destroy() {
            this.closeAll();
            this.timers.forEach(timer => clearTimeout(timer));
            this.timers.clear();
            this.activeDropdowns.clear();
        }
    }
    
    /**
     * Breadcrumb Navigation Component
     */
    class Breadcrumb {
        constructor(element, options = {}) {
            this.element = typeof element === 'string' ? Utils.DOM.$(element) : element;
            
            if (!this.element) {
                throw new Error('Breadcrumb element not found');
            }
            
            this.options = {
                separator: '/',
                maxItems: 0, // 0 = no limit
                classes: {
                    breadcrumb: 'breadcrumb',
                    list: 'breadcrumb__list',
                    item: 'breadcrumb__item',
                    link: 'breadcrumb__link',
                    separator: 'breadcrumb__separator',
                    current: 'breadcrumb__item--current',
                    collapsed: 'breadcrumb__item--collapsed'
                },
                ...options
            };
            
            this.items = [];
            
            this.init();
        }
        
        init() {
            this.setupStructure();
            this.parseItems();
            this.setupAccessibility();
            
            if (this.options.maxItems > 0 && this.items.length > this.options.maxItems) {
                this.collapse();
            }
        }
        
        setupStructure() {
            Utils.DOM.addClass(this.element, this.options.classes.breadcrumb);
            
            let list = Utils.DOM.$('.breadcrumb__list', this.element);
            if (!list) {
                list = Utils.DOM.createElement('ol', {
                    className: this.options.classes.list
                });
                
                // Move existing content to list
                while (this.element.firstChild) {
                    list.appendChild(this.element.firstChild);
                }
                
                this.element.appendChild(list);
            }
            
            this.list = list;
        }
        
        parseItems() {
            const items = Utils.DOM.$$('.breadcrumb__item', this.list);
            
            this.items = Array.from(items).map((item, index) => {
                const link = Utils.DOM.$('.breadcrumb__link', item);
                const text = link ? link.textContent : item.textContent;
                const url = link ? link.getAttribute('href') : null;
                const isCurrent = index === items.length - 1;
                
                if (isCurrent) {
                    Utils.DOM.addClass(item, this.options.classes.current);
                }
                
                return {
                    element: item,
                    text: text,
                    url: url,
                    isCurrent: isCurrent
                };
            });
        }
        
        setupAccessibility() {
            this.element.setAttribute('aria-label', 'Breadcrumb navigation');
            this.list.setAttribute('role', 'list');
            
            this.items.forEach((item, index) => {
                item.element.setAttribute('role', 'listitem');
                
                if (item.isCurrent) {
                    item.element.setAttribute('aria-current', 'page');
                }
                
                // Add separator
                if (index < this.items.length - 1) {
                    const separator = Utils.DOM.createElement('span', {
                        className: this.options.classes.separator,
                        'aria-hidden': 'true'
                    }, this.options.separator);
                    
                    item.element.appendChild(separator);
                }
            });
        }
        
        collapse() {
            if (this.items.length <= this.options.maxItems) return;
            
            const visibleItems = this.options.maxItems;
            const hiddenCount = this.items.length - visibleItems;
            
            // Hide middle items
            for (let i = 1; i < this.items.length - visibleItems + 1; i++) {
                Utils.DOM.addClass(this.items[i].element, this.options.classes.collapsed);
                this.items[i].element.style.display = 'none';
            }
            
            // Create collapse indicator
            const collapseItem = Utils.DOM.createElement('li', {
                className: `${this.options.classes.item} breadcrumb__item--ellipsis`,
                role: 'listitem'
            });
            
            const collapseButton = Utils.DOM.createElement('button', {
                type: 'button',
                className: this.options.classes.link,
                'aria-label': `Show ${hiddenCount} hidden items`
            }, '...');
            
            collapseItem.appendChild(collapseButton);
            
            // Add separator
            const separator = Utils.DOM.createElement('span', {
                className: this.options.classes.separator,
                'aria-hidden': 'true'
            }, this.options.separator);
            
            collapseItem.appendChild(separator);
            
            // Insert collapse item
            this.items[1].element.parentNode.insertBefore(collapseItem, this.items[1].element);
            
            // Expand on click
            Utils.Events.on(collapseButton, 'click', () => {
                this.expand();
            });
        }
        
        expand() {
            // Show all hidden items
            this.items.forEach(item => {
                if (Utils.DOM.hasClass(item.element, this.options.classes.collapsed)) {
                    Utils.DOM.removeClass(item.element, this.options.classes.collapsed);
                    item.element.style.display = '';
                }
            });
            
            // Remove ellipsis item
            const ellipsisItem = Utils.DOM.$('.breadcrumb__item--ellipsis', this.list);
            if (ellipsisItem) {
                ellipsisItem.remove();
            }
        }
        
        addItem(text, url = null, position = -1) {
            const item = {
                text: text,
                url: url,
                isCurrent: false
            };
            
            // Create DOM element
            const li = Utils.DOM.createElement('li', {
                className: this.options.classes.item,
                role: 'listitem'
            });
            
            if (url) {
                const link = Utils.DOM.createElement('a', {
                    href: url,
                    className: this.options.classes.link
                }, text);
                li.appendChild(link);
            } else {
                li.textContent = text;
            }
            
            item.element = li;
            
            // Insert item
            if (position === -1 || position >= this.items.length) {
                // Mark previous current item as not current
                if (this.items.length > 0) {
                    const lastItem = this.items[this.items.length - 1];
                    Utils.DOM.removeClass(lastItem.element, this.options.classes.current);
                    lastItem.element.removeAttribute('aria-current');
                    lastItem.isCurrent = false;
                }
                
                this.list.appendChild(li);
                this.items.push(item);
                
                // Mark new item as current
                Utils.DOM.addClass(li, this.options.classes.current);
                li.setAttribute('aria-current', 'page');
                item.isCurrent = true;
            } else {
                this.list.insertBefore(li, this.items[position].element);
                this.items.splice(position, 0, item);
            }
            
            this.updateSeparators();
        }
        
        removeItem(index) {
            if (index < 0 || index >= this.items.length) return;
            
            const item = this.items[index];
            item.element.remove();
            this.items.splice(index, 1);
            
            // Update current item if needed
            if (item.isCurrent && this.items.length > 0) {
                const newCurrent = this.items[this.items.length - 1];
                Utils.DOM.addClass(newCurrent.element, this.options.classes.current);
                newCurrent.element.setAttribute('aria-current', 'page');
                newCurrent.isCurrent = true;
            }
            
            this.updateSeparators();
        }
        
        updateSeparators() {
            // Remove existing separators
            Utils.DOM.$$('.breadcrumb__separator', this.list).forEach(sep => sep.remove());
            
            // Add separators between items
            this.items.forEach((item, index) => {
                if (index < this.items.length - 1) {
                    const separator = Utils.DOM.createElement('span', {
                        className: this.options.classes.separator,
                        'aria-hidden': 'true'
                    }, this.options.separator);
                    
                    item.element.appendChild(separator);
                }
            });
        }
    }
    
    // Export all navigation components
    return {
        MobileNavigation,
        DropdownMenu,
        Breadcrumb,
        
        // Factory methods
        createMobileNav: (element, options) => new MobileNavigation(element, options),
        createDropdownMenu: (element, options) => new DropdownMenu(element, options),
        createBreadcrumb: (element, options) => new Breadcrumb(element, options)
    };
});