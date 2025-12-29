<?php
/**
 * Feature Flags Configuration
 *
 * Centralized feature flag management for gradual rollouts and A/B testing
 *
 * Phase 3 Week 4: Modern Routing Migration
 * Created: November 26, 2025
 *
 * Usage:
 * - Include this file in your views or controllers
 * - Access flags via FEATURE_FLAGS constant or individual constants
 * - Inject into JavaScript via <script> tags for frontend feature control
 *
 * Example (PHP):
 * ```php
 * if (USE_MODERN_ROUTING) {
 *     // Use new ModernRouter endpoints
 * } else {
 *     // Use legacy direct-file endpoints
 * }
 * ```
 *
 * Example (JavaScript injection in view):
 * ```php
 * <script>
 *     window.USE_MODERN_ROUTING = <?php echo USE_MODERN_ROUTING ? 'true' : 'false'; ?>;
 * </script>
 * ```
 */

// ====== ROUTING MIGRATION FLAGS ======

/**
 * Modern Routing System
 *
 * Controls whether to use the new ModernRouter architecture or legacy direct-file routing
 *
 * Status: ENABLED (Phase 3 Week 4 Migration Complete)
 * Default: true (use modern routing)
 * Legacy: false (use old attendance_routes.php, etc.)
 *
 * Migration Timeline:
 * - Phase 1 (Nov 26, 2025): Controllers implemented
 * - Phase 2 (Nov 27, 2025): Frontend migration
 * - Phase 3 (Nov 28-30, 2025): Testing & validation
 * - Phase 4 (Dec 2025): Remove legacy code
 */
define('USE_MODERN_ROUTING', getenv('USE_MODERN_ROUTING') !== false ? (bool)getenv('USE_MODERN_ROUTING') : true);

/**
 * Attendance System Modern Routing
 *
 * Specifically controls attendance system routing
 * Can be disabled independently if issues arise
 */
define('USE_MODERN_ATTENDANCE', getenv('USE_MODERN_ATTENDANCE') !== false ? (bool)getenv('USE_MODERN_ATTENDANCE') : USE_MODERN_ROUTING);

// ====== FEATURE FLAGS ======

/**
 * API Rate Limiting
 *
 * Enable rate limiting for API endpoints
 * Default: false (disabled in development)
 */
define('ENABLE_API_RATE_LIMITING', getenv('ENABLE_API_RATE_LIMITING') !== false ? (bool)getenv('ENABLE_API_RATE_LIMITING') : false);

/**
 * Enhanced Logging
 *
 * Enable detailed request/response logging
 * Default: true (enabled for debugging)
 */
define('ENABLE_ENHANCED_LOGGING', getenv('ENABLE_ENHANCED_LOGGING') !== false ? (bool)getenv('ENABLE_ENHANCED_LOGGING') : true);

/**
 * CSRF Validation
 *
 * Enable CSRF token validation on POST/PUT/DELETE requests
 * Default: true (security feature)
 */
define('ENABLE_CSRF_VALIDATION', getenv('ENABLE_CSRF_VALIDATION') !== false ? (bool)getenv('ENABLE_CSRF_VALIDATION') : true);

/**
 * Maintenance Mode
 *
 * Put system in maintenance mode (shows maintenance page)
 * Default: false (system active)
 */
define('MAINTENANCE_MODE', getenv('MAINTENANCE_MODE') !== false ? (bool)getenv('MAINTENANCE_MODE') : false);

/**
 * Debug Mode
 *
 * Enable debug output and error display
 * Default: false (production mode)
 */
define('DEBUG_MODE', getenv('DEBUG_MODE') !== false ? (bool)getenv('DEBUG_MODE') : false);

// ====== EXPERIMENTAL FEATURES ======

/**
 * New Dashboard UI
 *
 * Enable new dashboard design
 * Default: false (experimental)
 */
define('ENABLE_NEW_DASHBOARD', getenv('ENABLE_NEW_DASHBOARD') !== false ? (bool)getenv('ENABLE_NEW_DASHBOARD') : false);

/**
 * Real-time Notifications
 *
 * Enable WebSocket-based real-time notifications
 * Default: false (experimental)
 */
define('ENABLE_REALTIME_NOTIFICATIONS', getenv('ENABLE_REALTIME_NOTIFICATIONS') !== false ? (bool)getenv('ENABLE_REALTIME_NOTIFICATIONS') : false);

// ====== FEATURE FLAGS ARRAY ======

/**
 * All feature flags in array format for easy iteration
 */
define('FEATURE_FLAGS', [
    // Routing
    'use_modern_routing' => USE_MODERN_ROUTING,
    'use_modern_attendance' => USE_MODERN_ATTENDANCE,

    // Core Features
    'enable_api_rate_limiting' => ENABLE_API_RATE_LIMITING,
    'enable_enhanced_logging' => ENABLE_ENHANCED_LOGGING,
    'enable_csrf_validation' => ENABLE_CSRF_VALIDATION,
    'maintenance_mode' => MAINTENANCE_MODE,
    'debug_mode' => DEBUG_MODE,

    // Experimental
    'enable_new_dashboard' => ENABLE_NEW_DASHBOARD,
    'enable_realtime_notifications' => ENABLE_REALTIME_NOTIFICATIONS,
]);

// ====== HELPER FUNCTIONS ======

/**
 * Check if a feature is enabled
 *
 * @param string $featureName Feature flag name (snake_case)
 * @return bool True if enabled, false otherwise
 */
function isFeatureEnabled($featureName) {
    $flags = FEATURE_FLAGS;
    return $flags[$featureName] ?? false;
}

/**
 * Get all enabled features
 *
 * @return array List of enabled feature names
 */
function getEnabledFeatures() {
    return array_keys(array_filter(FEATURE_FLAGS));
}

/**
 * Inject JavaScript feature flags
 *
 * Returns JavaScript code to inject feature flags into window object
 * Use this in your view templates to make flags available to frontend
 *
 * @return string JavaScript code
 */
function injectJavaScriptFlags() {
    $flags = FEATURE_FLAGS;
    $jsFlags = [];

    foreach ($flags as $key => $value) {
        $jsKey = strtoupper($key); // Convert to SCREAMING_SNAKE_CASE
        $jsValue = $value ? 'true' : 'false';
        $jsFlags[] = "window.{$jsKey} = {$jsValue};";
    }

    return "<script>\n" . implode("\n", $jsFlags) . "\n</script>";
}

/**
 * Get feature flags as JSON
 *
 * Useful for API responses or debugging
 *
 * @return string JSON representation of feature flags
 */
function getFeatureFlagsJSON() {
    return json_encode(FEATURE_FLAGS, JSON_PRETTY_PRINT);
}

// ====== ENVIRONMENT CONFIGURATION ======

/**
 * Load .env file if it exists (for local development)
 */
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envFile as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// ====== DEPRECATION WARNINGS ======

/**
 * Log deprecation warnings for legacy features
 */
if (!USE_MODERN_ROUTING && function_exists('error_log')) {
    error_log('[WARNING] Legacy routing is enabled. Please migrate to ModernRouter (USE_MODERN_ROUTING=true)');
}
