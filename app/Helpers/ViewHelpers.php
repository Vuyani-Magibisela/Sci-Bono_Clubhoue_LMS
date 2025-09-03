<?php
/**
 * View Helper Functions for Templates
 * Phase 3 Implementation
 */

/**
 * Generate URL for named route
 */
function route($name, $params = []) {
    return UrlHelper::route($name, $params);
}

/**
 * Generate URL with base path
 */
function url($path) {
    return UrlHelper::to($path);
}

/**
 * Generate asset URL
 */
function asset($path) {
    return UrlHelper::asset($path);
}

/**
 * Check if current route matches pattern
 */
function is_active($pattern, $class = 'active') {
    return UrlHelper::is($pattern) ? $class : '';
}

/**
 * Generate CSRF token field
 */
function csrf_field() {
    require_once __DIR__ . '/../../core/CSRF.php';
    return CSRF::field();
}

/**
 * Generate CSRF meta tag
 */
function csrf_meta() {
    require_once __DIR__ . '/../../core/CSRF.php';
    return CSRF::metaTag();
}

/**
 * Get configuration value
 */
function config($key, $default = null) {
    return ConfigLoader::get($key, $default);
}

/**
 * Escape HTML output
 */
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user has role
 */
function user_has_role($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

/**
 * Get current user data
 */
function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'name' => $_SESSION['name'] ?? '',
        'user_type' => $_SESSION['user_type'] ?? ''
    ];
}

/**
 * Format date for display
 */
function format_date($date, $format = 'Y-m-d H:i') {
    if (empty($date)) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Generate pagination links
 */
function paginate($currentPage, $totalPages, $routeName, $params = []) {
    $links = [];
    
    // Previous page
    if ($currentPage > 1) {
        $params['page'] = $currentPage - 1;
        $links[] = '<a href="' . route($routeName, $params) . '" class="page-link">Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $params['page'] = $i;
        $class = $i === $currentPage ? 'page-link active' : 'page-link';
        $links[] = '<a href="' . route($routeName, $params) . '" class="' . $class . '">' . $i . '</a>';
    }
    
    // Next page
    if ($currentPage < $totalPages) {
        $params['page'] = $currentPage + 1;
        $links[] = '<a href="' . route($routeName, $params) . '" class="page-link">Next</a>';
    }
    
    return '<div class="pagination">' . implode(' ', $links) . '</div>';
}