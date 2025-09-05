/**
 * Progressive Enhancement Features
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

// Define the ProgressiveEnhancement module with Utils dependency
ModuleLoader.define('ProgressiveEnhancement', ['Utils'], function(Utils) {
    'use strict';
    
    /**
     * Feature Detection Utility
     */
    class FeatureDetection {
        constructor() {
            this.features = new Map();
            this.init();
        }
        
        init() {
            this.detectBrowserFeatures();
            this.detectDeviceCapabilities();
            this.applyFeatureClasses();
        }
        
        /**
         * Detect browser features
         */
        detectBrowserFeatures() {
            const features = {
                // JavaScript features
                es6: this.supportsES6(),
                modules: this.supportsModules(),
                fetch: this.supportsFetch(),
                promises: this.supportsPromises(),
                customElements: this.supportsCustomElements(),
                
                // CSS features
                cssGrid: this.supportsCSSGrid(),
                flexbox: this.supportsFlexbox(),
                customProperties: this.supportsCSSCustomProperties(),
                cssSupports: this.supportsCSSSupports(),
                
                // Web APIs
                intersectionObserver: this.supportsIntersectionObserver(),
                mutationObserver: this.supportsMutationObserver(),
                localStorage: this.supportsLocalStorage(),
                sessionStorage: this.supportsSessionStorage(),
                webWorkers: this.supportsWebWorkers(),
                serviceWorkers: this.supportsServiceWorkers(),
                
                // Media features
                webp: false, // Will be detected asynchronously
                avif: false, // Will be detected asynchronously
                
                // Input capabilities
                touch: this.supportsTouch(),
                pointer: this.supportsPointer(),
                hover: this.supportsHover()
            };
            
            Object.keys(features).forEach(feature => {
                this.features.set(feature, features[feature]);
            });
            
            // Async feature detection
            this.detectImageFormats();
        }
        
        /**
         * Detect device capabilities
         */
        detectDeviceCapabilities() {
            const capabilities = {
                mobile: this.isMobile(),
                tablet: this.isTablet(),
                desktop: this.isDesktop(),
                highDPI: this.isHighDPI(),
                darkMode: this.prefersDarkMode(),
                reducedMotion: this.prefersReducedMotion(),
                highContrast: this.prefersHighContrast()
            };
            
            Object.keys(capabilities).forEach(capability => {
                this.features.set(capability, capabilities[capability]);
            });
        }
        
        /**
         * Apply feature classes to HTML element
         */
        applyFeatureClasses() {
            const html = document.documentElement;
            
            this.features.forEach((supported, feature) => {
                const className = supported ? `has-${feature}` : `no-${feature}`;
                Utils.DOM.addClass(html, className);
            });
        }
        
        // Feature detection methods
        supportsES6() {
            try {
                new Function('(a = 0) => a');
                return true;
            } catch (e) {
                return false;
            }
        }
        
        supportsModules() {
            const script = document.createElement('script');
            return 'noModule' in script;
        }
        
        supportsFetch() {
            return 'fetch' in window;
        }
        
        supportsPromises() {
            return 'Promise' in window;
        }
        
        supportsCustomElements() {
            return 'customElements' in window;
        }
        
        supportsCSSGrid() {
            return CSS.supports('display', 'grid');
        }
        
        supportsFlexbox() {
            return CSS.supports('display', 'flex');
        }
        
        supportsCSSCustomProperties() {
            return CSS.supports('color', 'var(--fake-var)');
        }
        
        supportsCSSSupports() {
            return 'CSS' in window && 'supports' in CSS;
        }
        
        supportsIntersectionObserver() {
            return 'IntersectionObserver' in window;
        }
        
        supportsMutationObserver() {
            return 'MutationObserver' in window;
        }
        
        supportsLocalStorage() {
            try {
                const test = '__test__';
                localStorage.setItem(test, test);
                localStorage.removeItem(test);
                return true;
            } catch (e) {
                return false;
            }
        }
        
        supportsSessionStorage() {
            try {
                const test = '__test__';
                sessionStorage.setItem(test, test);
                sessionStorage.removeItem(test);
                return true;
            } catch (e) {
                return false;
            }
        }
        
        supportsWebWorkers() {
            return 'Worker' in window;
        }
        
        supportsServiceWorkers() {
            return 'serviceWorker' in navigator;
        }
        
        supportsTouch() {
            return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        }
        
        supportsPointer() {
            return window.matchMedia('(pointer: fine)').matches;
        }
        
        supportsHover() {
            return window.matchMedia('(hover: hover)').matches;
        }
        
        isMobile() {
            return window.matchMedia('(max-width: 767px)').matches;
        }
        
        isTablet() {
            return window.matchMedia('(min-width: 768px) and (max-width: 1023px)').matches;
        }
        
        isDesktop() {
            return window.matchMedia('(min-width: 1024px)').matches;
        }
        
        isHighDPI() {
            return window.devicePixelRatio > 1;
        }
        
        prefersDarkMode() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        
        prefersReducedMotion() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }
        
        prefersHighContrast() {
            return window.matchMedia('(prefers-contrast: high)').matches;
        }
        
        /**
         * Detect image format support
         */
        detectImageFormats() {
            this.detectWebP().then(supported => {
                this.features.set('webp', supported);
                Utils.DOM.toggleClass(document.documentElement, 'has-webp', supported);
                Utils.DOM.toggleClass(document.documentElement, 'no-webp', !supported);
            });
            
            this.detectAVIF().then(supported => {
                this.features.set('avif', supported);
                Utils.DOM.toggleClass(document.documentElement, 'has-avif', supported);
                Utils.DOM.toggleClass(document.documentElement, 'no-avif', !supported);
            });
        }
        
        detectWebP() {
            return new Promise(resolve => {
                const webP = new Image();
                webP.onload = webP.onerror = () => {
                    resolve(webP.height === 2);
                };
                webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
            });
        }
        
        detectAVIF() {
            return new Promise(resolve => {
                const avif = new Image();
                avif.onload = avif.onerror = () => {
                    resolve(avif.height === 2);
                };
                avif.src = 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgABogQEAwgMg8f8D///8WfhwB8+ErK42A=';
            });
        }
        
        /**
         * Check if feature is supported
         */
        supports(feature) {
            return this.features.get(feature) || false;
        }
        
        /**
         * Get all supported features
         */
        getSupportedFeatures() {
            const supported = {};
            this.features.forEach((value, key) => {
                if (value) supported[key] = true;
            });
            return supported;
        }
        
        /**
         * Get all unsupported features
         */
        getUnsupportedFeatures() {
            const unsupported = {};
            this.features.forEach((value, key) => {
                if (!value) unsupported[key] = true;
            });
            return unsupported;
        }
    }
    
    /**
     * Lazy Loading Manager
     */
    class LazyLoader {
        constructor(options = {}) {
            this.options = {
                rootMargin: '50px',
                threshold: 0,
                selector: '[data-lazy]',
                loadingClass: 'lazy-loading',
                loadedClass: 'lazy-loaded',
                errorClass: 'lazy-error',
                ...options
            };
            
            this.observer = null;
            this.elements = new Set();
            
            this.init();
        }
        
        init() {
            if (this.supportsIntersectionObserver()) {
                this.setupIntersectionObserver();
            } else {
                this.loadAllImages();
            }
            
            this.bindEvents();
        }
        
        supportsIntersectionObserver() {
            return 'IntersectionObserver' in window;
        }
        
        setupIntersectionObserver() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadElement(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: this.options.rootMargin,
                threshold: this.options.threshold
            });
            
            this.observeElements();
        }
        
        observeElements() {
            const elements = Utils.DOM.$$(this.options.selector);
            elements.forEach(element => {
                this.observer.observe(element);
                this.elements.add(element);
            });
        }
        
        loadElement(element) {
            Utils.DOM.addClass(element, this.options.loadingClass);
            
            if (element.tagName === 'IMG') {
                this.loadImage(element);
            } else if (element.tagName === 'IFRAME') {
                this.loadIframe(element);
            } else {
                this.loadContent(element);
            }
        }
        
        loadImage(img) {
            const src = img.dataset.lazy || img.dataset.src;
            const srcset = img.dataset.lazySrcset || img.dataset.srcset;
            
            if (!src) {
                this.handleError(img);
                return;
            }
            
            const imageLoader = new Image();
            
            imageLoader.onload = () => {
                img.src = src;
                if (srcset) img.srcset = srcset;
                
                Utils.DOM.removeClass(img, this.options.loadingClass);
                Utils.DOM.addClass(img, this.options.loadedClass);
                
                Utils.Events.trigger(img, 'lazy:loaded');
            };
            
            imageLoader.onerror = () => {
                this.handleError(img);
            };
            
            imageLoader.src = src;
        }
        
        loadIframe(iframe) {
            const src = iframe.dataset.lazy || iframe.dataset.src;
            
            if (!src) {
                this.handleError(iframe);
                return;
            }
            
            iframe.onload = () => {
                Utils.DOM.removeClass(iframe, this.options.loadingClass);
                Utils.DOM.addClass(iframe, this.options.loadedClass);
                
                Utils.Events.trigger(iframe, 'lazy:loaded');
            };
            
            iframe.onerror = () => {
                this.handleError(iframe);
            };
            
            iframe.src = src;
        }
        
        loadContent(element) {
            const url = element.dataset.lazy || element.dataset.src;
            
            if (!url) {
                this.handleError(element);
                return;
            }
            
            Utils.AJAX.get(url)
                .then(content => {
                    element.innerHTML = content;
                    
                    Utils.DOM.removeClass(element, this.options.loadingClass);
                    Utils.DOM.addClass(element, this.options.loadedClass);
                    
                    Utils.Events.trigger(element, 'lazy:loaded');
                })
                .catch(() => {
                    this.handleError(element);
                });
        }
        
        handleError(element) {
            Utils.DOM.removeClass(element, this.options.loadingClass);
            Utils.DOM.addClass(element, this.options.errorClass);
            
            Utils.Events.trigger(element, 'lazy:error');
        }
        
        loadAllImages() {
            const elements = Utils.DOM.$$(this.options.selector);
            elements.forEach(element => {
                this.loadElement(element);
            });
        }
        
        bindEvents() {
            // Observe new elements added to DOM
            if ('MutationObserver' in window && this.observer) {
                const mutationObserver = new MutationObserver((mutations) => {
                    mutations.forEach(mutation => {
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                const lazyElements = Utils.DOM.$$(this.options.selector, node);
                                lazyElements.forEach(element => {
                                    if (!this.elements.has(element)) {
                                        this.observer.observe(element);
                                        this.elements.add(element);
                                    }
                                });
                            }
                        });
                    });
                });
                
                mutationObserver.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        }
        
        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
            this.elements.clear();
        }
    }
    
    /**
     * Service Worker Manager
     */
    class ServiceWorkerManager {
        constructor(options = {}) {
            this.options = {
                scriptUrl: '/sw.js',
                scope: '/',
                updateInterval: 60000, // 1 minute
                ...options
            };
            
            this.registration = null;
            this.updateAvailable = false;
        }
        
        register() {
            if (!('serviceWorker' in navigator)) {
                console.warn('Service Workers not supported');
                return Promise.reject('Service Workers not supported');
            }
            
            return navigator.serviceWorker.register(this.options.scriptUrl, {
                scope: this.options.scope
            })
                .then(registration => {
                    this.registration = registration;
                    this.setupUpdateHandling();
                    this.startUpdateChecking();
                    
                    console.log('Service Worker registered:', registration.scope);
                    return registration;
                })
                .catch(error => {
                    console.error('Service Worker registration failed:', error);
                    throw error;
                });
        }
        
        setupUpdateHandling() {
            if (!this.registration) return;
            
            // Handle updates
            this.registration.addEventListener('updatefound', () => {
                const newWorker = this.registration.installing;
                
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        this.updateAvailable = true;
                        Utils.Events.trigger(document, 'sw:updateavailable');
                    }
                });
            });
        }
        
        startUpdateChecking() {
            setInterval(() => {
                if (this.registration) {
                    this.registration.update();
                }
            }, this.options.updateInterval);
        }
        
        skipWaiting() {
            if (this.registration && this.registration.waiting) {
                this.registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            }
        }
        
        unregister() {
            if (this.registration) {
                return this.registration.unregister();
            }
            return Promise.resolve();
        }
    }
    
    /**
     * Network Status Manager
     */
    class NetworkStatusManager {
        constructor() {
            this.isOnline = navigator.onLine;
            this.connectionType = this.getConnectionType();
            this.listeners = new Set();
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.updateConnectionInfo();
            this.applyStatusClasses();
        }
        
        bindEvents() {
            Utils.Events.on(window, 'online', () => {
                this.isOnline = true;
                this.handleStatusChange();
            });
            
            Utils.Events.on(window, 'offline', () => {
                this.isOnline = false;
                this.handleStatusChange();
            });
            
            // Network Information API
            if ('connection' in navigator) {
                navigator.connection.addEventListener('change', () => {
                    this.updateConnectionInfo();
                });
            }
        }
        
        getConnectionType() {
            if ('connection' in navigator) {
                return navigator.connection.effectiveType || 'unknown';
            }
            return 'unknown';
        }
        
        updateConnectionInfo() {
            if ('connection' in navigator) {
                this.connectionType = navigator.connection.effectiveType || 'unknown';
                this.bandwidth = navigator.connection.downlink || null;
                this.saveData = navigator.connection.saveData || false;
            }
            
            this.applyStatusClasses();
        }
        
        applyStatusClasses() {
            const html = document.documentElement;
            
            Utils.DOM.toggleClass(html, 'is-online', this.isOnline);
            Utils.DOM.toggleClass(html, 'is-offline', !this.isOnline);
            
            // Connection type classes
            const connectionClasses = ['connection-slow-2g', 'connection-2g', 'connection-3g', 'connection-4g'];
            connectionClasses.forEach(className => {
                Utils.DOM.removeClass(html, className);
            });
            
            if (this.connectionType !== 'unknown') {
                Utils.DOM.addClass(html, `connection-${this.connectionType}`);
            }
            
            // Save data mode
            Utils.DOM.toggleClass(html, 'save-data', this.saveData);
        }
        
        handleStatusChange() {
            this.applyStatusClasses();
            
            const event = {
                isOnline: this.isOnline,
                connectionType: this.connectionType,
                bandwidth: this.bandwidth,
                saveData: this.saveData
            };
            
            Utils.Events.trigger(document, 'network:change', event);
            
            this.listeners.forEach(callback => {
                callback(event);
            });
        }
        
        addListener(callback) {
            this.listeners.add(callback);
        }
        
        removeListener(callback) {
            this.listeners.delete(callback);
        }
        
        getStatus() {
            return {
                isOnline: this.isOnline,
                connectionType: this.connectionType,
                bandwidth: this.bandwidth,
                saveData: this.saveData
            };
        }
    }
    
    /**
     * Performance Monitor
     */
    class PerformanceMonitor {
        constructor(options = {}) {
            this.options = {
                trackNavigation: true,
                trackResources: true,
                trackLCP: true,
                trackFID: true,
                trackCLS: true,
                reportInterval: 30000,
                ...options
            };
            
            this.metrics = {};
            this.observers = [];
            
            this.init();
        }
        
        init() {
            if (this.options.trackNavigation) {
                this.trackNavigationTiming();
            }
            
            if (this.options.trackResources) {
                this.trackResourceTiming();
            }
            
            if (this.options.trackLCP) {
                this.trackLargestContentfulPaint();
            }
            
            if (this.options.trackFID) {
                this.trackFirstInputDelay();
            }
            
            if (this.options.trackCLS) {
                this.trackCumulativeLayoutShift();
            }
        }
        
        trackNavigationTiming() {
            if ('performance' in window && 'getEntriesByType' in performance) {
                window.addEventListener('load', () => {
                    setTimeout(() => {
                        const navigation = performance.getEntriesByType('navigation')[0];
                        if (navigation) {
                            this.metrics.navigation = {
                                domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
                                loadComplete: navigation.loadEventEnd - navigation.loadEventStart,
                                timeToFirstByte: navigation.responseStart - navigation.requestStart,
                                domInteractive: navigation.domInteractive - navigation.navigationStart
                            };
                        }
                    }, 0);
                });
            }
        }
        
        trackResourceTiming() {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    const resources = list.getEntries();
                    resources.forEach(resource => {
                        if (!this.metrics.resources) {
                            this.metrics.resources = [];
                        }
                        
                        this.metrics.resources.push({
                            name: resource.name,
                            type: resource.initiatorType,
                            duration: resource.duration,
                            size: resource.transferSize
                        });
                    });
                });
                
                observer.observe({ entryTypes: ['resource'] });
                this.observers.push(observer);
            }
        }
        
        trackLargestContentfulPaint() {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    this.metrics.lcp = lastEntry.startTime;
                });
                
                try {
                    observer.observe({ entryTypes: ['largest-contentful-paint'] });
                    this.observers.push(observer);
                } catch (e) {
                    // LCP not supported
                }
            }
        }
        
        trackFirstInputDelay() {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach(entry => {
                        this.metrics.fid = entry.processingStart - entry.startTime;
                    });
                });
                
                try {
                    observer.observe({ entryTypes: ['first-input'] });
                    this.observers.push(observer);
                } catch (e) {
                    // FID not supported
                }
            }
        }
        
        trackCumulativeLayoutShift() {
            if ('PerformanceObserver' in window) {
                let clsValue = 0;
                let clsEntries = [];
                
                const observer = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    entries.forEach(entry => {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                            clsEntries.push(entry);
                        }
                    });
                    
                    this.metrics.cls = clsValue;
                });
                
                try {
                    observer.observe({ entryTypes: ['layout-shift'] });
                    this.observers.push(observer);
                } catch (e) {
                    // CLS not supported
                }
            }
        }
        
        getMetrics() {
            return { ...this.metrics };
        }
        
        reportMetrics() {
            const metrics = this.getMetrics();
            
            // Log to console in development
            if (window.location.hostname === 'localhost') {
                console.log('Performance Metrics:', metrics);
            }
            
            // Could send to analytics service
            Utils.Events.trigger(document, 'performance:report', metrics);
            
            return metrics;
        }
        
        destroy() {
            this.observers.forEach(observer => {
                observer.disconnect();
            });
            this.observers = [];
        }
    }
    
    /**
     * Main Progressive Enhancement Manager
     */
    class ProgressiveEnhancementManager {
        constructor() {
            this.featureDetection = new FeatureDetection();
            this.lazyLoader = null;
            this.serviceWorkerManager = null;
            this.networkStatus = new NetworkStatusManager();
            this.performanceMonitor = new PerformanceMonitor();
            
            this.init();
        }
        
        init() {
            // Initialize lazy loading
            this.lazyLoader = new LazyLoader();
            
            // Initialize service worker if supported
            if (this.featureDetection.supports('serviceWorkers')) {
                this.serviceWorkerManager = new ServiceWorkerManager();
                // Don't auto-register - let the app decide
            }
            
            this.setupEnhancements();
        }
        
        setupEnhancements() {
            // Add no-js/js classes
            Utils.DOM.removeClass(document.documentElement, 'no-js');
            Utils.DOM.addClass(document.documentElement, 'js');
            
            // Setup responsive images if supported
            if (this.featureDetection.supports('webp')) {
                this.setupResponsiveImages();
            }
            
            // Setup connection-aware loading
            this.setupConnectionAwareLoading();
            
            // Setup critical resource hints
            this.setupResourceHints();
        }
        
        setupResponsiveImages() {
            const images = Utils.DOM.$$('img[data-webp]');
            images.forEach(img => {
                const webpSrc = img.dataset.webp;
                if (webpSrc) {
                    img.src = webpSrc;
                }
            });
        }
        
        setupConnectionAwareLoading() {
            const networkStatus = this.networkStatus.getStatus();
            
            // Disable auto-play videos on slow connections
            if (networkStatus.connectionType === 'slow-2g' || networkStatus.connectionType === '2g') {
                const videos = Utils.DOM.$$('video[autoplay]');
                videos.forEach(video => {
                    video.removeAttribute('autoplay');
                    video.preload = 'none';
                });
            }
            
            // Enable save-data optimizations
            if (networkStatus.saveData) {
                Utils.DOM.addClass(document.documentElement, 'save-data-enabled');
                
                // Disable non-essential features
                const nonEssential = Utils.DOM.$$('[data-save-data="disable"]');
                nonEssential.forEach(element => {
                    element.style.display = 'none';
                });
            }
        }
        
        setupResourceHints() {
            // Add preconnect hints for external domains
            const externalLinks = Utils.DOM.$$('a[href^="http"]:not([href*="' + location.hostname + '"])');
            const domains = new Set();
            
            externalLinks.forEach(link => {
                try {
                    const url = new URL(link.href);
                    domains.add(url.origin);
                } catch (e) {
                    // Invalid URL
                }
            });
            
            domains.forEach(domain => {
                const link = Utils.DOM.createElement('link', {
                    rel: 'preconnect',
                    href: domain
                });
                document.head.appendChild(link);
            });
        }
        
        // Public API methods
        enableServiceWorker() {
            if (this.serviceWorkerManager) {
                return this.serviceWorkerManager.register();
            }
            return Promise.reject('Service Workers not supported');
        }
        
        getFeatures() {
            return this.featureDetection;
        }
        
        getNetworkStatus() {
            return this.networkStatus.getStatus();
        }
        
        getPerformanceMetrics() {
            return this.performanceMonitor.getMetrics();
        }
        
        destroy() {
            if (this.lazyLoader) {
                this.lazyLoader.destroy();
            }
            
            if (this.performanceMonitor) {
                this.performanceMonitor.destroy();
            }
        }
    }
    
    // Create global instance
    const progressiveEnhancement = new ProgressiveEnhancementManager();
    
    // Export classes and global instance
    return {
        FeatureDetection,
        LazyLoader,
        ServiceWorkerManager,
        NetworkStatusManager,
        PerformanceMonitor,
        ProgressiveEnhancementManager,
        
        // Global instance
        manager: progressiveEnhancement,
        
        // Convenience methods
        supports: (feature) => progressiveEnhancement.featureDetection.supports(feature),
        getNetworkStatus: () => progressiveEnhancement.getNetworkStatus(),
        enableServiceWorker: () => progressiveEnhancement.enableServiceWorker(),
        getMetrics: () => progressiveEnhancement.getPerformanceMetrics()
    };
});