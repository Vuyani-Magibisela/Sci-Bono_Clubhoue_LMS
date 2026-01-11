<?php
/**
 * CORS Middleware
 *
 * Handles Cross-Origin Resource Sharing (CORS) for API endpoints.
 * Implements proper CORS headers, preflight request handling, and origin validation.
 *
 * Phase 5 Week 3 Day 4
 *
 * @package App\Middleware
 * @since Phase 5 Week 3
 */

namespace App\Middleware;

class CorsMiddleware
{
    /**
     * @var array Configuration options
     */
    private $config;

    /**
     * @var array Default configuration
     */
    private $defaultConfig = [
        // Allowed origins (* for all, or array of specific origins)
        'allowed_origins' => ['*'],

        // Allowed HTTP methods
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],

        // Allowed headers
        'allowed_headers' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept',
            'Origin',
            'Accept-Version',
            'X-API-Key',
            'If-None-Match',
            'If-Modified-Since'
        ],

        // Exposed headers (visible to JavaScript)
        'exposed_headers' => [
            'Content-Length',
            'Content-Type',
            'ETag',
            'Cache-Control',
            'Last-Modified',
            'API-Version',
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset'
        ],

        // Allow credentials (cookies, authorization headers)
        'supports_credentials' => true,

        // Max age for preflight cache (in seconds)
        'max_age' => 86400, // 24 hours

        // Enable CORS for all requests
        'enabled' => true
    ];

    /**
     * Constructor
     *
     * @param array $config Custom configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->defaultConfig, $config);
    }

    /**
     * Handle CORS for incoming request
     *
     * @return bool True to continue, false to stop (preflight handled)
     */
    public function handle()
    {
        if (!$this->config['enabled']) {
            return true;
        }

        $origin = $this->getOrigin();

        // Check if origin is allowed
        if (!$this->isOriginAllowed($origin)) {
            // Origin not allowed - don't add CORS headers
            return true;
        }

        // Handle preflight request
        if ($this->isPreflightRequest()) {
            $this->handlePreflightRequest($origin);
            return false; // Stop processing, preflight response sent
        }

        // Add CORS headers to actual request
        $this->addCorsHeaders($origin);

        return true;
    }

    /**
     * Check if current request is a preflight request
     *
     * @return bool
     */
    private function isPreflightRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $hasOrigin = isset($_SERVER['HTTP_ORIGIN']);
        $hasAccessControlRequestMethod = isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);

        return $method === 'OPTIONS' && $hasOrigin && $hasAccessControlRequestMethod;
    }

    /**
     * Handle preflight request (OPTIONS)
     *
     * @param string $origin Request origin
     */
    private function handlePreflightRequest($origin)
    {
        // Add preflight headers
        if (!headers_sent()) {
            // Origin
            header('Access-Control-Allow-Origin: ' . $this->getAllowOriginHeader($origin));

            // Methods
            header('Access-Control-Allow-Methods: ' . implode(', ', $this->config['allowed_methods']));

            // Headers
            $requestHeaders = $this->getRequestedHeaders();
            if (!empty($requestHeaders)) {
                $allowedHeaders = $this->filterAllowedHeaders($requestHeaders);
                if (!empty($allowedHeaders)) {
                    header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders));
                }
            } else {
                header('Access-Control-Allow-Headers: ' . implode(', ', $this->config['allowed_headers']));
            }

            // Credentials
            if ($this->config['supports_credentials']) {
                header('Access-Control-Allow-Credentials: true');
            }

            // Max age
            header('Access-Control-Max-Age: ' . $this->config['max_age']);

            // Status code
            http_response_code(204);
        }

        // Send empty response
        exit;
    }

    /**
     * Add CORS headers to actual request
     *
     * @param string $origin Request origin
     */
    private function addCorsHeaders($origin)
    {
        if (!headers_sent()) {
            // Origin
            header('Access-Control-Allow-Origin: ' . $this->getAllowOriginHeader($origin));

            // Exposed headers
            if (!empty($this->config['exposed_headers'])) {
                header('Access-Control-Expose-Headers: ' . implode(', ', $this->config['exposed_headers']));
            }

            // Credentials
            if ($this->config['supports_credentials']) {
                header('Access-Control-Allow-Credentials: true');
            }

            // Vary header (important for caching)
            header('Vary: Origin', false);
        }
    }

    /**
     * Get request origin
     *
     * @return string|null
     */
    private function getOrigin()
    {
        return $_SERVER['HTTP_ORIGIN'] ?? null;
    }

    /**
     * Check if origin is allowed
     *
     * @param string|null $origin Request origin
     * @return bool
     */
    private function isOriginAllowed($origin)
    {
        if (empty($origin)) {
            return false;
        }

        $allowedOrigins = $this->config['allowed_origins'];

        // Allow all origins
        if (in_array('*', $allowedOrigins)) {
            return true;
        }

        // Check specific origins
        if (in_array($origin, $allowedOrigins)) {
            return true;
        }

        // Check wildcard patterns (e.g., *.example.com)
        foreach ($allowedOrigins as $allowedOrigin) {
            if ($this->matchesWildcard($origin, $allowedOrigin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match origin against wildcard pattern
     *
     * @param string $origin Request origin
     * @param string $pattern Wildcard pattern
     * @return bool
     */
    private function matchesWildcard($origin, $pattern)
    {
        // Convert wildcard pattern to regex
        $regex = str_replace(
            ['*', '.'],
            ['.*', '\.'],
            $pattern
        );
        $regex = '/^' . $regex . '$/i';

        return preg_match($regex, $origin) === 1;
    }

    /**
     * Get Access-Control-Allow-Origin header value
     *
     * @param string $origin Request origin
     * @return string
     */
    private function getAllowOriginHeader($origin)
    {
        // If credentials are supported, must return specific origin (not *)
        if ($this->config['supports_credentials']) {
            return $origin;
        }

        // If only * is allowed, return *
        if ($this->config['allowed_origins'] === ['*']) {
            return '*';
        }

        // Return specific origin
        return $origin;
    }

    /**
     * Get requested headers from preflight request
     *
     * @return array
     */
    private function getRequestedHeaders()
    {
        $header = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? '';

        if (empty($header)) {
            return [];
        }

        return array_map('trim', explode(',', $header));
    }

    /**
     * Filter requested headers against allowed headers
     *
     * @param array $requestedHeaders Requested headers
     * @return array Allowed headers
     */
    private function filterAllowedHeaders(array $requestedHeaders)
    {
        $allowedHeaders = array_map('strtolower', $this->config['allowed_headers']);
        $filtered = [];

        foreach ($requestedHeaders as $header) {
            if (in_array(strtolower($header), $allowedHeaders)) {
                $filtered[] = $header;
            }
        }

        return $filtered;
    }

    /**
     * Add allowed origin to configuration
     *
     * @param string $origin Origin to allow
     */
    public function addAllowedOrigin($origin)
    {
        if (!in_array($origin, $this->config['allowed_origins'])) {
            $this->config['allowed_origins'][] = $origin;
        }
    }

    /**
     * Set allowed origins
     *
     * @param array $origins Array of allowed origins
     */
    public function setAllowedOrigins(array $origins)
    {
        $this->config['allowed_origins'] = $origins;
    }

    /**
     * Add allowed method
     *
     * @param string $method HTTP method
     */
    public function addAllowedMethod($method)
    {
        $method = strtoupper($method);
        if (!in_array($method, $this->config['allowed_methods'])) {
            $this->config['allowed_methods'][] = $method;
        }
    }

    /**
     * Add allowed header
     *
     * @param string $header Header name
     */
    public function addAllowedHeader($header)
    {
        if (!in_array($header, $this->config['allowed_headers'])) {
            $this->config['allowed_headers'][] = $header;
        }
    }

    /**
     * Add exposed header
     *
     * @param string $header Header name
     */
    public function addExposedHeader($header)
    {
        if (!in_array($header, $this->config['exposed_headers'])) {
            $this->config['exposed_headers'][] = $header;
        }
    }

    /**
     * Enable CORS
     */
    public function enable()
    {
        $this->config['enabled'] = true;
    }

    /**
     * Disable CORS
     */
    public function disable()
    {
        $this->config['enabled'] = false;
    }

    /**
     * Check if CORS is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->config['enabled'];
    }

    /**
     * Get current configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get allowed origins
     *
     * @return array
     */
    public function getAllowedOrigins()
    {
        return $this->config['allowed_origins'];
    }

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->config['allowed_methods'];
    }

    /**
     * Get allowed headers
     *
     * @return array
     */
    public function getAllowedHeaders()
    {
        return $this->config['allowed_headers'];
    }

    /**
     * Get exposed headers
     *
     * @return array
     */
    public function getExposedHeaders()
    {
        return $this->config['exposed_headers'];
    }
}
