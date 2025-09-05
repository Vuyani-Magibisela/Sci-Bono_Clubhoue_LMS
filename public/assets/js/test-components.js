/**
 * Frontend Components Test Suite
 * Phase 6: Frontend Improvements
 * Sci-Bono Clubhouse LMS
 */

(function() {
    'use strict';
    
    /**
     * Component Test Runner
     */
    class ComponentTestRunner {
        constructor() {
            this.tests = [];
            this.results = [];
            this.isRunning = false;
        }
        
        /**
         * Add test to suite
         */
        addTest(name, testFunction, options = {}) {
            this.tests.push({
                name,
                testFunction,
                options: {
                    timeout: 5000,
                    async: false,
                    ...options
                }
            });
        }
        
        /**
         * Run all tests
         */
        async runAllTests() {
            if (this.isRunning) return;
            
            this.isRunning = true;
            this.results = [];
            
            console.log('🧪 Running Frontend Component Tests...');
            console.log('=====================================');
            
            for (const test of this.tests) {
                await this.runTest(test);
            }
            
            this.printSummary();
            this.isRunning = false;
            
            return this.results;
        }
        
        /**
         * Run individual test
         */
        async runTest(test) {
            const startTime = performance.now();
            let result = {
                name: test.name,
                passed: false,
                error: null,
                duration: 0,
                details: null
            };
            
            try {
                if (test.options.async) {
                    const timeoutPromise = new Promise((_, reject) => {
                        setTimeout(() => reject(new Error('Test timeout')), test.options.timeout);
                    });
                    
                    const testPromise = test.testFunction();
                    const testResult = await Promise.race([testPromise, timeoutPromise]);
                    
                    result.passed = testResult !== false;
                    result.details = testResult;
                } else {
                    const testResult = test.testFunction();
                    result.passed = testResult !== false;
                    result.details = testResult;
                }
                
                console.log(`✅ ${test.name}`);
            } catch (error) {
                result.error = error.message;
                console.log(`❌ ${test.name}: ${error.message}`);
            }
            
            result.duration = performance.now() - startTime;
            this.results.push(result);
        }
        
        /**
         * Print test summary
         */
        printSummary() {
            const passed = this.results.filter(r => r.passed).length;
            const failed = this.results.length - passed;
            const totalTime = this.results.reduce((sum, r) => sum + r.duration, 0);
            
            console.log('\n📊 Test Summary');
            console.log('================');
            console.log(`Total Tests: ${this.results.length}`);
            console.log(`Passed: ${passed}`);
            console.log(`Failed: ${failed}`);
            console.log(`Success Rate: ${((passed / this.results.length) * 100).toFixed(2)}%`);
            console.log(`Total Time: ${totalTime.toFixed(2)}ms`);
            
            if (failed > 0) {
                console.log('\n❌ Failed Tests:');
                this.results
                    .filter(r => !r.passed)
                    .forEach(r => {
                        console.log(`  • ${r.name}: ${r.error || 'Unknown error'}`);
                    });
            }
            
            console.log('\n' + (passed === this.results.length ? '🎉 All tests passed!' : '⚠️ Some tests failed.'));
        }
    }
    
    // Create test runner instance
    const testRunner = new ComponentTestRunner();
    
    /**
     * Module Loader Tests
     */
    testRunner.addTest('ModuleLoader - Basic functionality', () => {
        return typeof window.ModuleLoader === 'object' &&
               typeof ModuleLoader.define === 'function' &&
               typeof ModuleLoader.require === 'function';
    });
    
    testRunner.addTest('ModuleLoader - Module definition and loading', async () => {
        // Define a test module
        ModuleLoader.define('TestModule', [], function() {
            return {
                test: true,
                getValue: () => 42
            };
        });
        
        // Load the module
        const modules = await ModuleLoader.require(['TestModule']);
        const testModule = modules[0];
        
        return testModule && testModule.test === true && testModule.getValue() === 42;
    }, { async: true });
    
    /**
     * Utils Module Tests
     */
    testRunner.addTest('Utils - Module loading', async () => {
        const modules = await ModuleLoader.require(['Utils']);
        const Utils = modules[0];
        
        return Utils &&
               typeof Utils.DOM === 'object' &&
               typeof Utils.Events === 'object' &&
               typeof Utils.AJAX === 'object' &&
               typeof Utils.Validation === 'object';
    }, { async: true });
    
    testRunner.addTest('Utils - DOM utilities', async () => {
        const [Utils] = await ModuleLoader.require(['Utils']);
        
        // Test element creation
        const div = Utils.DOM.createElement('div', { className: 'test-element' }, 'Test content');
        const hasCorrectTag = div.tagName === 'DIV';
        const hasCorrectClass = div.className === 'test-element';
        const hasCorrectContent = div.textContent === 'Test content';
        
        return hasCorrectTag && hasCorrectClass && hasCorrectContent;
    }, { async: true });
    
    testRunner.addTest('Utils - Validation utilities', async () => {
        const [Utils] = await ModuleLoader.require(['Utils']);
        
        const emailValid = Utils.Validation.email('test@example.com');
        const emailInvalid = !Utils.Validation.email('invalid-email');
        const requiredValid = Utils.Validation.required('test');
        const requiredInvalid = !Utils.Validation.required('');
        
        return emailValid && emailInvalid && requiredValid && requiredInvalid;
    }, { async: true });
    
    /**
     * Form Validator Tests
     */
    testRunner.addTest('FormValidator - Module loading', async () => {
        const modules = await ModuleLoader.require(['FormValidator']);
        const FormValidator = modules[0];
        
        return typeof FormValidator === 'function' &&
               typeof FormValidator.create === 'function';
    }, { async: true });
    
    testRunner.addTest('FormValidator - Basic validation', async () => {
        const [FormValidator] = await ModuleLoader.require(['FormValidator']);
        
        // Create test form
        const form = document.createElement('form');
        form.innerHTML = `
            <div class="form-group">
                <label for="test-email">Email</label>
                <input type="email" id="test-email" name="email" required>
            </div>
        `;
        
        document.body.appendChild(form);
        
        const validator = new FormValidator(form);
        const emailField = form.querySelector('#test-email');
        
        // Test invalid email
        emailField.value = 'invalid-email';
        const invalidResult = await validator.validateField(emailField);
        
        // Test valid email
        emailField.value = 'test@example.com';
        const validResult = await validator.validateField(emailField);
        
        // Cleanup
        document.body.removeChild(form);
        
        return !invalidResult && validResult;
    }, { async: true });
    
    /**
     * UI Components Tests
     */
    testRunner.addTest('UIComponents - Module loading', async () => {
        const modules = await ModuleLoader.require(['UIComponents']);
        const UIComponents = modules[0];
        
        return UIComponents &&
               typeof UIComponents.Alert === 'function' &&
               typeof UIComponents.Tabs === 'function' &&
               typeof UIComponents.Accordion === 'function';
    }, { async: true });
    
    testRunner.addTest('UIComponents - Alert component', async () => {
        const [UIComponents] = await ModuleLoader.require(['UIComponents']);
        
        // Create test alert
        const alertElement = document.createElement('div');
        alertElement.className = 'alert';
        alertElement.textContent = 'Test alert';
        document.body.appendChild(alertElement);
        
        const alert = new UIComponents.Alert(alertElement, {
            dismissible: true
        });
        
        const hasDismissButton = alertElement.querySelector('.alert__dismiss') !== null;
        
        // Test show/hide
        alert.show();
        const isVisible = alertElement.style.display === 'block';
        
        alert.hide();
        
        // Cleanup
        document.body.removeChild(alertElement);
        
        return hasDismissButton && isVisible;
    }, { async: true });
    
    /**
     * Modal Tests
     */
    testRunner.addTest('Modal - Module loading', async () => {
        const modules = await ModuleLoader.require(['Modal']);
        const Modal = modules[0];
        
        return Modal &&
               typeof Modal.Modal === 'function' &&
               typeof Modal.confirm === 'function' &&
               typeof Modal.alert === 'function';
    }, { async: true });
    
    testRunner.addTest('Modal - Basic modal functionality', async () => {
        const [Modal] = await ModuleLoader.require(['Modal']);
        
        // Create test modal
        const modalElement = document.createElement('div');
        modalElement.innerHTML = `
            <div class="modal__dialog">
                <div class="modal__content">
                    <div class="modal__header">
                        <h5 class="modal__title">Test Modal</h5>
                    </div>
                    <div class="modal__body">Test content</div>
                </div>
            </div>
        `;
        
        const modal = new Modal.Modal(modalElement, {
            show: false
        });
        
        // Test show
        modal.show();
        const isShown = modalElement.style.display === 'block';
        
        // Test hide
        modal.hide();
        
        // Cleanup
        modal.destroy();
        
        return isShown;
    }, { async: true });
    
    /**
     * Loading States Tests
     */
    testRunner.addTest('LoadingStates - Module loading', async () => {
        const modules = await ModuleLoader.require(['LoadingStates']);
        const LoadingStates = modules[0];
        
        return LoadingStates &&
               typeof LoadingStates.LoadingSpinner === 'function' &&
               typeof LoadingStates.ProgressBar === 'function' &&
               typeof LoadingStates.Toast === 'function';
    }, { async: true });
    
    testRunner.addTest('LoadingStates - Loading spinner', async () => {
        const [LoadingStates] = await ModuleLoader.require(['LoadingStates']);
        
        const container = document.createElement('div');
        document.body.appendChild(container);
        
        const spinner = new LoadingStates.LoadingSpinner(container, {
            text: 'Loading...'
        });
        
        spinner.show();
        const isVisible = spinner.isVisible;
        
        spinner.hide();
        spinner.destroy();
        
        // Cleanup
        document.body.removeChild(container);
        
        return isVisible;
    }, { async: true });
    
    /**
     * Navigation Tests
     */
    testRunner.addTest('Navigation - Module loading', async () => {
        const modules = await ModuleLoader.require(['Navigation']);
        const Navigation = modules[0];
        
        return Navigation &&
               typeof Navigation.MobileNavigation === 'function' &&
               typeof Navigation.DropdownMenu === 'function' &&
               typeof Navigation.Breadcrumb === 'function';
    }, { async: true });
    
    /**
     * Accessibility Tests
     */
    testRunner.addTest('Accessibility - Module loading', async () => {
        const modules = await ModuleLoader.require(['Accessibility']);
        const Accessibility = modules[0];
        
        return Accessibility &&
               typeof Accessibility.FocusManager === 'function' &&
               typeof Accessibility.ScreenReaderUtility === 'function' &&
               Accessibility.manager;
    }, { async: true });
    
    testRunner.addTest('Accessibility - Focus management', async () => {
        const [Accessibility] = await ModuleLoader.require(['Accessibility']);
        
        const focusManager = new Accessibility.FocusManager();
        
        // Create test element
        const button = document.createElement('button');
        button.textContent = 'Test button';
        document.body.appendChild(button);
        
        // Test focus setting
        const focusSet = focusManager.setFocus(button);
        const hasFocus = document.activeElement === button;
        
        // Cleanup
        document.body.removeChild(button);
        
        return focusSet && hasFocus;
    }, { async: true });
    
    /**
     * Progressive Enhancement Tests
     */
    testRunner.addTest('ProgressiveEnhancement - Module loading', async () => {
        const modules = await ModuleLoader.require(['ProgressiveEnhancement']);
        const ProgressiveEnhancement = modules[0];
        
        return ProgressiveEnhancement &&
               typeof ProgressiveEnhancement.FeatureDetection === 'function' &&
               ProgressiveEnhancement.manager;
    }, { async: true });
    
    testRunner.addTest('ProgressiveEnhancement - Feature detection', async () => {
        const [ProgressiveEnhancement] = await ModuleLoader.require(['ProgressiveEnhancement']);
        
        const featureDetection = new ProgressiveEnhancement.FeatureDetection();
        
        // Test basic feature detection
        const supportsPromises = featureDetection.supports('promises');
        const supportsFetch = featureDetection.supports('fetch');
        
        return typeof supportsPromises === 'boolean' &&
               typeof supportsFetch === 'boolean';
    }, { async: true });
    
    /**
     * CSS Component Tests
     */
    testRunner.addTest('CSS Variables - Design system loaded', () => {
        const rootStyle = getComputedStyle(document.documentElement);
        const primaryColor = rootStyle.getPropertyValue('--color-primary').trim();
        
        return primaryColor.length > 0;
    });
    
    testRunner.addTest('CSS Button Components - Classes available', () => {
        // Create test button and check if styles apply
        const button = document.createElement('button');
        button.className = 'button button--primary';
        document.body.appendChild(button);
        
        const styles = getComputedStyle(button);
        const hasButtonStyles = styles.display === 'inline-flex' || 
                               styles.padding !== '0px' ||
                               styles.borderRadius !== '0px';
        
        document.body.removeChild(button);
        return hasButtonStyles;
    });
    
    testRunner.addTest('CSS Form Components - Classes available', () => {
        // Create test form control
        const input = document.createElement('input');
        input.className = 'form-control';
        document.body.appendChild(input);
        
        const styles = getComputedStyle(input);
        const hasFormStyles = styles.width === '100%' || 
                            styles.padding !== '0px' ||
                            styles.borderRadius !== '0px';
        
        document.body.removeChild(input);
        return hasFormStyles;
    });
    
    /**
     * Integration Tests
     */
    testRunner.addTest('Integration - Module dependencies', async () => {
        // Test that modules can load their dependencies
        const modules = await ModuleLoader.require(['FormValidator', 'UIComponents', 'Modal', 'LoadingStates']);
        
        return modules.length === 4 && modules.every(module => module !== null);
    }, { async: true });
    
    testRunner.addTest('Integration - Cross-module functionality', async () => {
        const [Utils, LoadingStates] = await ModuleLoader.require(['Utils', 'LoadingStates']);
        
        // Test that Utils works with other modules
        const toast = LoadingStates.toast('Test message', { duration: 1000 });
        const toastCreated = toast && typeof toast.show === 'function';
        
        if (toast) {
            toast.destroy();
        }
        
        return toastCreated;
    }, { async: true });
    
    /**
     * Performance Tests
     */
    testRunner.addTest('Performance - Module loading time', async () => {
        const startTime = performance.now();
        
        await ModuleLoader.require(['Utils', 'FormValidator', 'UIComponents']);
        
        const endTime = performance.now();
        const loadTime = endTime - startTime;
        
        // Should load in under 100ms
        return loadTime < 100;
    }, { async: true });
    
    /**
     * Accessibility Tests
     */
    testRunner.addTest('Accessibility - ARIA attributes present', () => {
        // Check if HTML has proper language attribute
        const htmlLang = document.documentElement.getAttribute('lang');
        
        // Check for skip links
        const skipLink = document.querySelector('.skip-link, a[href="#main-content"], a[href="#main"]');
        
        return htmlLang !== null && skipLink !== null;
    });
    
    /**
     * Auto-run tests when page is ready
     */
    function initTests() {
        // Wait for ModuleLoader to be ready
        if (typeof ModuleLoader === 'undefined') {
            setTimeout(initTests, 100);
            return;
        }
        
        // Add test runner to global scope for manual testing
        window.ComponentTestRunner = testRunner;
        
        // Auto-run tests in development
        if (window.location.hostname === 'localhost' || 
            window.location.search.includes('test=true')) {
            
            setTimeout(() => {
                testRunner.runAllTests().then(results => {
                    // Create visual test results if needed
                    if (window.location.search.includes('test=visual')) {
                        createVisualTestResults(results);
                    }
                });
            }, 1000);
        }
    }
    
    /**
     * Create visual test results
     */
    function createVisualTestResults(results) {
        const container = document.createElement('div');
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            width: 400px;
            max-height: 80vh;
            overflow-y: auto;
            background: white;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 20px;
            font-family: monospace;
            font-size: 14px;
            z-index: 10000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        `;
        
        const passed = results.filter(r => r.passed).length;
        const failed = results.length - passed;
        
        container.innerHTML = `
            <h3>🧪 Component Test Results</h3>
            <div style="margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 4px;">
                <strong>Total:</strong> ${results.length}<br>
                <strong style="color: green;">Passed:</strong> ${passed}<br>
                <strong style="color: red;">Failed:</strong> ${failed}<br>
                <strong>Success Rate:</strong> ${((passed / results.length) * 100).toFixed(2)}%
            </div>
            <div style="max-height: 300px; overflow-y: auto;">
                ${results.map(result => `
                    <div style="margin: 5px 0; padding: 5px; border-left: 3px solid ${result.passed ? 'green' : 'red'}; background: ${result.passed ? '#f0fff0' : '#fff0f0'};">
                        <div style="font-weight: bold;">${result.passed ? '✅' : '❌'} ${result.name}</div>
                        ${result.error ? `<div style="color: red; font-size: 12px;">${result.error}</div>` : ''}
                        <div style="color: #666; font-size: 11px;">${result.duration.toFixed(2)}ms</div>
                    </div>
                `).join('')}
            </div>
            <button onclick="this.parentNode.remove()" style="margin-top: 10px; padding: 5px 10px; background: #333; color: white; border: none; border-radius: 4px; cursor: pointer;">Close</button>
        `;
        
        document.body.appendChild(container);
    }
    
    // Initialize tests
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTests);
    } else {
        initTests();
    }
    
})();