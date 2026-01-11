<?php
/**
 * API Version Controller
 *
 * Provides version information and metadata endpoints.
 *
 * Phase 5 Week 3 Day 2
 *
 * @package App\Controllers\Api
 * @since Phase 5 Week 3
 */

namespace App\Controllers\Api;

require_once __DIR__ . '/../../API/BaseApiController.php';
require_once __DIR__ . '/../../Middleware/ApiVersionMiddleware.php';

use App\API\BaseApiController;
use App\Middleware\ApiVersionMiddleware;

class VersionController extends BaseApiController
{
    private $versionMiddleware;

    public function __construct()
    {
        parent::__construct();
        $this->versionMiddleware = new ApiVersionMiddleware();
    }

    /**
     * Get API version information
     *
     * GET /api/versions
     * GET /api/v1/versions
     *
     * Response:
     * {
     *     "success": true,
     *     "data": {
     *         "current_version": "v1",
     *         "supported_versions": [...],
     *         "deprecation_policy": "...",
     *         "upgrade_guide": "..."
     *     }
     * }
     */
    public function index()
    {
        try {
            $versionInfo = $this->versionMiddleware->getVersionInfo();

            return $this->successResponse($versionInfo, 'API version information retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve version information', 500);
        }
    }

    /**
     * Get detailed information about a specific version
     *
     * GET /api/versions/{version}
     *
     * @param string $version Version to get info for (e.g., 'v1')
     */
    public function show($version)
    {
        try {
            $supportedVersions = $this->versionMiddleware->getSupportedVersions();

            if (!in_array($version, $supportedVersions)) {
                return $this->errorResponse("Version '{$version}' not found", 404);
            }

            $versionDetails = $this->getVersionDetails($version);

            return $this->successResponse($versionDetails, "Version {$version} information retrieved");

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve version details', 500);
        }
    }

    /**
     * Get detailed information about a specific version
     *
     * @param string $version Version identifier
     * @return array Version details
     */
    private function getVersionDetails($version)
    {
        $details = [
            'version' => $version,
            'release_date' => $this->getVersionReleaseDate($version),
            'status' => 'active',
            'endpoints' => $this->getVersionEndpoints($version),
            'breaking_changes' => $this->getBreakingChanges($version),
            'features' => $this->getVersionFeatures($version),
            'documentation_url' => $this->getBaseUrl() . '/api/docs/' . $version
        ];

        return $details;
    }

    /**
     * Get release date for version
     *
     * @param string $version Version identifier
     * @return string Release date
     */
    private function getVersionReleaseDate($version)
    {
        $releaseDates = [
            'v1' => '2026-01-06', // Phase 5 Week 1
            'v2' => '2026-01-10'  // Phase 5 Week 3 (example)
        ];

        return $releaseDates[$version] ?? 'Unknown';
    }

    /**
     * Get endpoints available in version
     *
     * @param string $version Version identifier
     * @return array Endpoint list
     */
    private function getVersionEndpoints($version)
    {
        // v1 endpoints
        $v1Endpoints = [
            'auth' => [
                'POST /api/v1/auth/login',
                'POST /api/v1/auth/logout',
                'POST /api/v1/auth/refresh',
                'POST /api/v1/auth/forgot-password',
                'POST /api/v1/auth/reset-password'
            ],
            'users' => [
                'GET /api/v1/users',
                'GET /api/v1/users/{id}',
                'GET /api/v1/users/me'
            ],
            'admin' => [
                'GET /api/v1/admin/users',
                'GET /api/v1/admin/users/{id}',
                'POST /api/v1/admin/users',
                'PUT /api/v1/admin/users/{id}',
                'DELETE /api/v1/admin/users/{id}'
            ]
        ];

        // v2 endpoints (example - would be different in reality)
        $v2Endpoints = array_merge($v1Endpoints, [
            'courses' => [
                'GET /api/v2/courses',
                'GET /api/v2/courses/{id}',
                'POST /api/v2/courses'
            ]
        ]);

        $endpoints = [
            'v1' => $v1Endpoints,
            'v2' => $v2Endpoints
        ];

        return $endpoints[$version] ?? [];
    }

    /**
     * Get breaking changes in version
     *
     * @param string $version Version identifier
     * @return array Breaking changes
     */
    private function getBreakingChanges($version)
    {
        $changes = [
            'v1' => [],
            'v2' => [
                'User response structure changed',
                'Pagination format updated',
                'Date format changed to ISO 8601'
            ]
        ];

        return $changes[$version] ?? [];
    }

    /**
     * Get features introduced in version
     *
     * @param string $version Version identifier
     * @return array Features list
     */
    private function getVersionFeatures($version)
    {
        $features = [
            'v1' => [
                'JWT authentication',
                'Token refresh rotation',
                'Rate limiting',
                'HTTP caching with ETags',
                'CRUD operations for users',
                'Pagination and filtering'
            ],
            'v2' => [
                'Enhanced filtering',
                'Batch operations',
                'Webhooks support',
                'GraphQL endpoint (planned)'
            ]
        ];

        return $features[$version] ?? [];
    }

    /**
     * Get base URL
     *
     * @return string Base URL
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . '://' . $host;
    }

    /**
     * Get migration guide between versions
     *
     * GET /api/versions/migration/{from}/{to}
     *
     * @param string $fromVersion Source version
     * @param string $toVersion Target version
     */
    public function migration($fromVersion, $toVersion)
    {
        try {
            $supportedVersions = $this->versionMiddleware->getSupportedVersions();

            if (!in_array($fromVersion, $supportedVersions) || !in_array($toVersion, $supportedVersions)) {
                return $this->errorResponse('Invalid version specified', 400);
            }

            $migrationGuide = $this->getMigrationGuide($fromVersion, $toVersion);

            return $this->successResponse($migrationGuide, "Migration guide from {$fromVersion} to {$toVersion}");

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve migration guide', 500);
        }
    }

    /**
     * Get migration guide between versions
     *
     * @param string $from Source version
     * @param string $to Target version
     * @return array Migration guide
     */
    private function getMigrationGuide($from, $to)
    {
        $guide = [
            'from_version' => $from,
            'to_version' => $to,
            'estimated_effort' => 'Medium',
            'breaking_changes' => $this->getBreakingChanges($to),
            'deprecated_endpoints' => [],
            'new_endpoints' => [],
            'changed_endpoints' => [],
            'steps' => [
                '1. Update base URL from /api/' . $from . '/ to /api/' . $to . '/',
                '2. Review breaking changes list',
                '3. Update request/response structures',
                '4. Test all endpoints',
                '5. Monitor error rates after deployment'
            ],
            'support' => [
                'documentation' => $this->getBaseUrl() . '/api/docs/migration-' . $from . '-to-' . $to,
                'contact' => 'api-support@sci-bono.co.za'
            ]
        ];

        return $guide;
    }

    /**
     * Handle GET requests
     */
    protected function handleGet()
    {
        // Parse route segments
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // Remove query string
        $uri = strtok($uri, '?');

        // Match /api/versions
        if (preg_match('#/api(?:/v\d+)?/versions/?$#', $uri)) {
            return $this->index();
        }

        // Match /api/versions/{version}
        if (preg_match('#/api(?:/v\d+)?/versions/([^/]+)/?$#', $uri, $matches)) {
            return $this->show($matches[1]);
        }

        // Match /api/versions/migration/{from}/{to}
        if (preg_match('#/api(?:/v\d+)?/versions/migration/([^/]+)/([^/]+)/?$#', $uri, $matches)) {
            return $this->migration($matches[1], $matches[2]);
        }

        return $this->errorResponse('Endpoint not found', 404);
    }
}
