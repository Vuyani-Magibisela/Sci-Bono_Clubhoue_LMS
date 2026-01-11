<?php
/**
 * API Version Middleware - Handle API versioning
 *
 * Implements URL-based API versioning with:
 * - Version parsing from URL (/api/v1/, /api/v2/)
 * - Accept-Version header support
 * - Deprecation warnings
 * - Version validation
 * - Default version handling
 *
 * Phase 5 Week 3 Day 2
 *
 * @package App\Middleware
 * @since Phase 5 Week 3
 */

namespace App\Middleware;

require_once __DIR__ . '/../../core/Logger.php';

use Logger;

class ApiVersionMiddleware
{
    /**
     * Supported API versions
     */
    private $supportedVersions = ['v1', 'v2'];

    /**
     * Default API version (latest)
     */
    private $defaultVersion = 'v1';

    /**
     * Deprecated versions with sunset dates
     */
    private $deprecatedVersions = [
        // 'v1' => '2026-12-31' // Example: v1 will be sunset on Dec 31, 2026
    ];

    /**
     * Current request version
     */
    private $currentVersion = null;

    /**
     * Parse and validate API version from request
     *
     * Version precedence:
     * 1. URL path (/api/v1/users)
     * 2. Accept-Version header
     * 3. Default version
     *
     * @return string Validated version (e.g., 'v1')
     */
    public function parseVersion()
    {
        // Try to parse version from URL
        $urlVersion = $this->parseVersionFromUrl();

        // Try to parse version from Accept-Version header
        $headerVersion = $this->parseVersionFromHeader();

        // Determine version (URL takes precedence)
        $version = $urlVersion ?? $headerVersion ?? $this->defaultVersion;

        // Validate version
        if (!$this->isValidVersion($version)) {
            $this->sendVersionError($version);
        }

        // Check if version is deprecated
        if ($this->isDeprecated($version)) {
            $this->addDeprecationWarning($version);
        }

        $this->currentVersion = $version;

        // Add version header to response
        $this->addVersionHeader($version);

        return $version;
    }

    /**
     * Get current API version
     *
     * @return string|null Current version
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * Parse version from URL path
     *
     * Extracts version from paths like:
     * - /api/v1/users
     * - /api/v2/courses
     *
     * @return string|null Version string (e.g., 'v1') or null
     */
    private function parseVersionFromUrl()
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Match /api/v{number}/
        if (preg_match('#/api/(v\d+)/#', $requestUri, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Parse version from Accept-Version header
     *
     * Supports headers like:
     * - Accept-Version: v1
     * - Accept-Version: 1 (converted to v1)
     *
     * @return string|null Version string or null
     */
    private function parseVersionFromHeader()
    {
        // Try standard format
        $acceptVersion = $_SERVER['HTTP_ACCEPT_VERSION'] ?? null;

        if (!$acceptVersion && function_exists('getallheaders')) {
            $headers = getallheaders();
            $acceptVersion = $headers['Accept-Version'] ?? null;
        }

        if (!$acceptVersion) {
            return null;
        }

        // Normalize version (convert "1" to "v1")
        $acceptVersion = trim($acceptVersion);
        if (is_numeric($acceptVersion)) {
            $acceptVersion = 'v' . $acceptVersion;
        }

        return $acceptVersion;
    }

    /**
     * Check if version is valid
     *
     * @param string $version Version to check
     * @return bool True if valid
     */
    private function isValidVersion($version)
    {
        return in_array($version, $this->supportedVersions);
    }

    /**
     * Check if version is deprecated
     *
     * @param string $version Version to check
     * @return bool True if deprecated
     */
    private function isDeprecated($version)
    {
        return array_key_exists($version, $this->deprecatedVersions);
    }

    /**
     * Get sunset date for deprecated version
     *
     * @param string $version Version to check
     * @return string|null Sunset date (YYYY-MM-DD) or null
     */
    private function getSunsetDate($version)
    {
        return $this->deprecatedVersions[$version] ?? null;
    }

    /**
     * Add deprecation warning header
     *
     * @param string $version Deprecated version
     * @return void
     */
    private function addDeprecationWarning($version)
    {
        $sunsetDate = $this->getSunsetDate($version);

        if (!headers_sent()) {
            // Add Deprecation header (RFC 8594)
            header('Deprecation: true');

            // Add Sunset header if sunset date is set
            if ($sunsetDate) {
                // Convert to HTTP date format
                $sunsetTimestamp = strtotime($sunsetDate . ' 00:00:00');
                $sunsetHttpDate = gmdate('D, d M Y H:i:s', $sunsetTimestamp) . ' GMT';
                header('Sunset: ' . $sunsetHttpDate);
            }

            // Add custom warning header
            $message = "API version {$version} is deprecated.";
            if ($sunsetDate) {
                $message .= " It will be removed on {$sunsetDate}.";
            }
            $message .= " Please upgrade to the latest version.";

            header('Warning: 299 - "' . $message . '"');
        }

        // Log deprecation usage (disabled - handled by ApiLogger instead)
        // ApiLogger will automatically log deprecated version usage
    }

    /**
     * Add API version header to response
     *
     * @param string $version Current version
     * @return void
     */
    private function addVersionHeader($version)
    {
        if (!headers_sent()) {
            header('API-Version: ' . $version);
        }
    }

    /**
     * Send version error response
     *
     * @param string $requestedVersion Invalid version requested
     * @return void
     */
    private function sendVersionError($requestedVersion)
    {
        if (!headers_sent()) {
            http_response_code(400);
            header('Content-Type: application/json');
        }

        echo json_encode([
            'success' => false,
            'error' => 'Unsupported API version',
            'message' => "API version '{$requestedVersion}' is not supported.",
            'supported_versions' => $this->supportedVersions,
            'default_version' => $this->defaultVersion,
            'requested_version' => $requestedVersion
        ], JSON_PRETTY_PRINT);

        exit;
    }

    /**
     * Get version information
     *
     * Returns metadata about all API versions.
     *
     * @return array Version information
     */
    public function getVersionInfo()
    {
        $versions = [];

        foreach ($this->supportedVersions as $version) {
            $isDeprecated = $this->isDeprecated($version);
            $isDefault = $version === $this->defaultVersion;

            $versionInfo = [
                'version' => $version,
                'status' => $isDeprecated ? 'deprecated' : 'active',
                'is_default' => $isDefault,
                'base_url' => $this->getBaseUrl() . '/api/' . $version
            ];

            if ($isDeprecated) {
                $versionInfo['deprecated'] = true;
                $sunsetDate = $this->getSunsetDate($version);
                if ($sunsetDate) {
                    $versionInfo['sunset_date'] = $sunsetDate;
                }
            }

            $versions[] = $versionInfo;
        }

        return [
            'current_version' => $this->defaultVersion,
            'supported_versions' => $versions,
            'deprecation_policy' => 'Deprecated versions will be supported for 6 months after deprecation announcement.',
            'upgrade_guide' => $this->getBaseUrl() . '/api/docs/migration'
        ];
    }

    /**
     * Get base URL
     *
     * @return string Base URL (e.g., "https://example.com")
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . '://' . $host;
    }

    /**
     * Get supported versions list
     *
     * @return array Supported versions
     */
    public function getSupportedVersions()
    {
        return $this->supportedVersions;
    }

    /**
     * Get default version
     *
     * @return string Default version
     */
    public function getDefaultVersion()
    {
        return $this->defaultVersion;
    }

    /**
     * Add a new supported version
     *
     * @param string $version Version to add (e.g., 'v3')
     * @return void
     */
    public function addSupportedVersion($version)
    {
        if (!in_array($version, $this->supportedVersions)) {
            $this->supportedVersions[] = $version;
        }
    }

    /**
     * Deprecate a version
     *
     * @param string $version Version to deprecate
     * @param string $sunsetDate Sunset date (YYYY-MM-DD)
     * @return void
     */
    public function deprecateVersion($version, $sunsetDate)
    {
        if (in_array($version, $this->supportedVersions)) {
            $this->deprecatedVersions[$version] = $sunsetDate;
        }
    }

    /**
     * Remove a version from support
     *
     * @param string $version Version to remove
     * @return bool True if removed, false if version is default or not found
     */
    public function removeVersion($version)
    {
        // Cannot remove default version
        if ($version === $this->defaultVersion) {
            return false;
        }

        $key = array_search($version, $this->supportedVersions);
        if ($key !== false) {
            unset($this->supportedVersions[$key]);
            unset($this->deprecatedVersions[$version]);
            $this->supportedVersions = array_values($this->supportedVersions); // Re-index
            return true;
        }

        return false;
    }

    /**
     * Set default version
     *
     * @param string $version Version to set as default
     * @return bool True if set, false if version not supported
     */
    public function setDefaultVersion($version)
    {
        if (in_array($version, $this->supportedVersions)) {
            $this->defaultVersion = $version;
            return true;
        }

        return false;
    }

    /**
     * Rewrite request URI to include version
     *
     * Rewrites requests like /api/users to /api/v1/users based on negotiated version.
     *
     * @param string $version Version to use
     * @return void
     */
    public function rewriteUri($version)
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // If URI already has version, don't rewrite
        if (preg_match('#/api/v\d+/#', $requestUri)) {
            return;
        }

        // Rewrite /api/... to /api/v1/...
        $newUri = preg_replace('#/api/#', '/api/' . $version . '/', $requestUri, 1);

        // Update $_SERVER variables
        $_SERVER['REQUEST_URI'] = $newUri;
        $_SERVER['REDIRECT_URL'] = $newUri;
    }

    /**
     * Get version from request (without validation)
     *
     * Useful for logging and debugging.
     *
     * @return string Version string or 'unknown'
     */
    public function getRawVersion()
    {
        return $this->parseVersionFromUrl()
            ?? $this->parseVersionFromHeader()
            ?? 'unknown';
    }
}
