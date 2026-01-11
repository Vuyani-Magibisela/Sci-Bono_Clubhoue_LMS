<?php
/**
 * API v2 Routes
 *
 * Example routes file for API version 2.
 * This demonstrates how to maintain multiple API versions in parallel.
 *
 * Phase 5 Week 3 Day 2
 *
 * @since API v2
 */

// Require v2 controllers (when they exist)
// require_once __DIR__ . '/../app/Controllers/Api/V2/UserController.php';
// require_once __DIR__ . '/../app/Controllers/Api/V2/CourseController.php';

/**
 * API v2 Breaking Changes from v1:
 *
 * 1. Response structure updated:
 *    - All dates in ISO 8601 format (YYYY-MM-DDTHH:MM:SSZ)
 *    - Pagination uses cursor-based instead of offset
 *    - Error responses include error codes
 *
 * 2. Authentication:
 *    - Refresh tokens now rotate automatically (already in v1)
 *    - Token expiration extended to 2 hours (was 1 hour)
 *
 * 3. User endpoints:
 *    - User response includes additional fields (created_at, updated_at)
 *    - Filtering supports advanced operators (gt, lt, gte, lte, in)
 *
 * 4. New features:
 *    - Batch operations support
 *    - Webhooks for real-time notifications
 *    - GraphQL endpoint (planned)
 */

// For now, v2 routes can fall back to v1 controllers
// In a real implementation, you would have separate v2 controllers

$routes = [
    // Authentication (same as v1 for now)
    'POST /api/v2/auth/login' => 'App\Controllers\Api\AuthController@login',
    'POST /api/v2/auth/logout' => 'App\Controllers\Api\AuthController@logout',
    'POST /api/v2/auth/refresh' => 'App\Controllers\Api\AuthController@refresh',

    // Version info
    'GET /api/v2/versions' => 'App\Controllers\Api\VersionController@index',
    'GET /api/v2/versions/{version}' => 'App\Controllers\Api\VersionController@show',

    // Users (would use v2 controllers in real implementation)
    'GET /api/v2/users' => 'App\Controllers\Api\UserController@index',
    'GET /api/v2/users/{id}' => 'App\Controllers\Api\UserController@show',
    'GET /api/v2/users/me' => 'App\Controllers\Api\UserController@profile',

    // Admin users (would use v2 controllers in real implementation)
    'GET /api/v2/admin/users' => 'App\Controllers\Api\Admin\UserController@index',
    'GET /api/v2/admin/users/{id}' => 'App\Controllers\Api\Admin\UserController@show',
    'POST /api/v2/admin/users' => 'App\Controllers\Api\Admin\UserController@store',
    'PUT /api/v2/admin/users/{id}' => 'App\Controllers\Api\Admin\UserController@update',
    'DELETE /api/v2/admin/users/{id}' => 'App\Controllers\Api\Admin\UserController@destroy',

    // New in v2: Batch operations (example)
    // 'POST /api/v2/admin/users/batch' => 'App\Controllers\Api\V2\Admin\UserController@batchCreate',
    // 'PUT /api/v2/admin/users/batch' => 'App\Controllers\Api\V2\Admin\UserController@batchUpdate',
    // 'DELETE /api/v2/admin/users/batch' => 'App\Controllers\Api\V2\Admin\UserController@batchDelete',

    // New in v2: Courses (example)
    // 'GET /api/v2/courses' => 'App\Controllers\Api\V2\CourseController@index',
    // 'GET /api/v2/courses/{id}' => 'App\Controllers\Api\V2\CourseController@show',
    // 'POST /api/v2/courses' => 'App\Controllers\Api\V2\CourseController@store',

    // New in v2: Webhooks (example)
    // 'GET /api/v2/webhooks' => 'App\Controllers\Api\V2\WebhookController@index',
    // 'POST /api/v2/webhooks' => 'App\Controllers\Api\V2\WebhookController@store',
    // 'DELETE /api/v2/webhooks/{id}' => 'App\Controllers\Api\V2\WebhookController@destroy',
];

return $routes;
