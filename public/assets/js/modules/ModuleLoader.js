/**
 * JavaScript Module Loader System
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

(function(window) {
    'use strict';

    /**
     * ModuleLoader - Manages module loading, dependencies, and initialization
     * 
     * Features:
     * - Dependency management
     * - Asynchronous module loading
     * - Module registry and caching
     * - Event-driven initialization
     * - Error handling and fallbacks
     * 
     * Usage:
     * ModuleLoader.define('MyModule', ['Utils'], function(Utils) {
     *     return {
     *         init: function() { ... }
     *     };
     * });
     * 
     * ModuleLoader.require(['MyModule'], function(MyModule) {
     *     MyModule.init();
     * });
     */
    const ModuleLoader = {
        // Module registry
        modules: new Map(),
        
        // Module definitions (before loading)
        definitions: new Map(),
        
        // Loading promises
        loadingPromises: new Map(),
        
        // Configuration
        config: {
            baseUrl: '/public/assets/js/modules/',
            timeout: 10000,
            retryCount: 3,
            cacheBusting: false,
            debug: false
        },
        
        /**
         * Configure the module loader
         * @param {Object} options - Configuration options
         */
        configure(options) {
            Object.assign(this.config, options);
            
            if (this.config.debug) {
                console.log('[ModuleLoader] Configuration updated:', this.config);
            }
        },
        
        /**
         * Define a module
         * @param {string} name - Module name
         * @param {Array} dependencies - Array of dependency names
         * @param {Function} factory - Module factory function
         */
        define(name, dependencies = [], factory) {
            if (typeof dependencies === 'function') {
                factory = dependencies;
                dependencies = [];
            }
            
            if (this.config.debug) {
                console.log(`[ModuleLoader] Defining module: ${name}`, dependencies);
            }
            
            this.definitions.set(name, {
                name,
                dependencies,
                factory,
                loaded: false,
                instance: null
            });
            
            // If module was already requested, try to load it now
            if (this.loadingPromises.has(name)) {
                this._loadModule(name);
            }
        },
        
        /**
         * Require modules and execute callback when ready
         * @param {Array} moduleNames - Array of module names to require
         * @param {Function} callback - Callback function to execute
         * @param {Function} errorCallback - Error callback function
         * @returns {Promise} Promise that resolves when all modules are loaded
         */
        require(moduleNames, callback, errorCallback) {
            if (typeof moduleNames === 'string') {
                moduleNames = [moduleNames];
            }
            
            if (this.config.debug) {
                console.log('[ModuleLoader] Requiring modules:', moduleNames);
            }
            
            return this._loadModules(moduleNames)
                .then(modules => {
                    if (callback) {
                        callback.apply(null, modules);
                    }
                    return modules;
                })
                .catch(error => {
                    console.error('[ModuleLoader] Error loading modules:', error);
                    if (errorCallback) {
                        errorCallback(error);
                    } else {
                        throw error;
                    }
                });
        },
        
        /**
         * Check if a module is loaded
         * @param {string} name - Module name
         * @returns {boolean} True if module is loaded
         */
        isLoaded(name) {
            const definition = this.definitions.get(name);
            return definition ? definition.loaded : false;
        },
        
        /**
         * Get a loaded module instance
         * @param {string} name - Module name
         * @returns {Object|null} Module instance or null
         */
        get(name) {
            const definition = this.definitions.get(name);
            return definition && definition.loaded ? definition.instance : null;
        },
        
        /**
         * Preload modules without executing callbacks
         * @param {Array} moduleNames - Array of module names to preload
         * @returns {Promise} Promise that resolves when modules are preloaded
         */
        preload(moduleNames) {
            if (typeof moduleNames === 'string') {
                moduleNames = [moduleNames];
            }
            
            if (this.config.debug) {
                console.log('[ModuleLoader] Preloading modules:', moduleNames);
            }
            
            return this._loadModules(moduleNames);
        },
        
        /**
         * Load multiple modules
         * @private
         * @param {Array} moduleNames - Array of module names to load
         * @returns {Promise} Promise that resolves to array of module instances
         */
        _loadModules(moduleNames) {
            const promises = moduleNames.map(name => this._loadModule(name));
            
            return Promise.all(promises)
                .then(modules => {
                    if (this.config.debug) {
                        console.log('[ModuleLoader] All modules loaded:', moduleNames);
                    }
                    return modules;
                });
        },
        
        /**
         * Load a single module
         * @private
         * @param {string} name - Module name
         * @returns {Promise} Promise that resolves to module instance
         */
        _loadModule(name) {
            // Return cached promise if already loading
            if (this.loadingPromises.has(name)) {
                return this.loadingPromises.get(name);
            }
            
            // Return cached module if already loaded
            const definition = this.definitions.get(name);
            if (definition && definition.loaded) {
                return Promise.resolve(definition.instance);
            }
            
            // Create loading promise
            const promise = this._fetchModule(name)
                .then(() => this._initializeModule(name))
                .catch(error => {
                    // Remove failed promise to allow retry
                    this.loadingPromises.delete(name);
                    throw new Error(`Failed to load module '${name}': ${error.message}`);
                });
            
            this.loadingPromises.set(name, promise);
            return promise;
        },
        
        /**
         * Fetch module script
         * @private
         * @param {string} name - Module name
         * @returns {Promise} Promise that resolves when script is loaded
         */
        _fetchModule(name) {
            // Check if module is already defined
            if (this.definitions.has(name)) {
                return Promise.resolve();
            }
            
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                const url = this._getModuleUrl(name);
                
                script.async = true;
                script.src = url;
                
                // Set up timeout
                const timeout = setTimeout(() => {
                    script.remove();
                    reject(new Error(`Module loading timeout: ${name}`));
                }, this.config.timeout);
                
                script.onload = () => {
                    clearTimeout(timeout);
                    
                    if (this.config.debug) {
                        console.log(`[ModuleLoader] Script loaded: ${name}`);
                    }
                    
                    resolve();
                };
                
                script.onerror = () => {
                    clearTimeout(timeout);
                    script.remove();
                    reject(new Error(`Script loading failed: ${name}`));
                };
                
                document.head.appendChild(script);
            });
        },
        
        /**
         * Initialize module after dependencies are loaded
         * @private
         * @param {string} name - Module name
         * @returns {Promise} Promise that resolves to module instance
         */
        _initializeModule(name) {
            const definition = this.definitions.get(name);
            if (!definition) {
                throw new Error(`Module '${name}' not found`);
            }
            
            if (definition.loaded) {
                return Promise.resolve(definition.instance);
            }
            
            // Load dependencies first
            return this._loadModules(definition.dependencies)
                .then(deps => {
                    // Initialize module
                    try {
                        const instance = definition.factory.apply(null, deps);
                        
                        definition.instance = instance;
                        definition.loaded = true;
                        
                        if (this.config.debug) {
                            console.log(`[ModuleLoader] Module initialized: ${name}`);
                        }
                        
                        // Trigger module loaded event
                        this._triggerEvent('moduleLoaded', { name, instance });
                        
                        return instance;
                        
                    } catch (error) {
                        throw new Error(`Module initialization failed for '${name}': ${error.message}`);
                    }
                });
        },
        
        /**
         * Get module URL
         * @private
         * @param {string} name - Module name
         * @returns {string} Module URL
         */
        _getModuleUrl(name) {
            let url = this.config.baseUrl + name + '.js';
            
            if (this.config.cacheBusting) {
                url += '?v=' + Date.now();
            }
            
            return url;
        },
        
        /**
         * Trigger custom event
         * @private
         * @param {string} eventType - Event type
         * @param {Object} detail - Event detail
         */
        _triggerEvent(eventType, detail) {
            const event = new CustomEvent(`moduleLoader:${eventType}`, {
                detail,
                bubbles: true,
                cancelable: true
            });
            
            document.dispatchEvent(event);
        },
        
        /**
         * Clear all modules (for testing/development)
         */
        clear() {
            this.modules.clear();
            this.definitions.clear();
            this.loadingPromises.clear();
            
            if (this.config.debug) {
                console.log('[ModuleLoader] All modules cleared');
            }
        },
        
        /**
         * Get module loading statistics
         * @returns {Object} Loading statistics
         */
        getStats() {
            const totalDefined = this.definitions.size;
            const totalLoaded = Array.from(this.definitions.values())
                .filter(def => def.loaded).length;
            
            return {
                defined: totalDefined,
                loaded: totalLoaded,
                loading: this.loadingPromises.size,
                loadedModules: Array.from(this.definitions.values())
                    .filter(def => def.loaded)
                    .map(def => def.name)
            };
        }
    };
    
    /**
     * Auto-configuration based on script tag attributes
     */
    (function autoConfig() {
        const scripts = document.querySelectorAll('script[src*="ModuleLoader"]');
        if (scripts.length > 0) {
            const script = scripts[scripts.length - 1];
            const config = {};
            
            if (script.hasAttribute('data-base-url')) {
                config.baseUrl = script.getAttribute('data-base-url');
            }
            
            if (script.hasAttribute('data-debug')) {
                config.debug = script.getAttribute('data-debug') === 'true';
            }
            
            if (script.hasAttribute('data-timeout')) {
                config.timeout = parseInt(script.getAttribute('data-timeout'), 10);
            }
            
            if (script.hasAttribute('data-cache-busting')) {
                config.cacheBusting = script.getAttribute('data-cache-busting') === 'true';
            }
            
            if (Object.keys(config).length > 0) {
                ModuleLoader.configure(config);
            }
        }
    })();
    
    /**
     * DOM Content Loaded event handler
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ModuleLoader._triggerEvent('ready', { ModuleLoader });
        });
    } else {
        // DOM already loaded
        setTimeout(() => {
            ModuleLoader._triggerEvent('ready', { ModuleLoader });
        }, 0);
    }
    
    // Expose to global scope
    window.ModuleLoader = ModuleLoader;
    
    // AMD support
    if (typeof define === 'function' && define.amd) {
        define('ModuleLoader', [], () => ModuleLoader);
    }
    
    // CommonJS support
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = ModuleLoader;
    }
    
})(window);