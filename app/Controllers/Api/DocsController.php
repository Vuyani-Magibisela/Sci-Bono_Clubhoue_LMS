<?php
/**
 * API Documentation Controller
 *
 * Serves OpenAPI specification and provides documentation endpoints.
 *
 * Phase 5 Week 3 Day 3
 *
 * @package App\Controllers\Api
 * @since Phase 5 Week 3
 */

namespace App\Controllers\Api;

require_once __DIR__ . '/../../API/BaseApiController.php';
require_once __DIR__ . '/../../Utils/OpenApiGenerator.php';

use App\API\BaseApiController;
use App\Utils\OpenApiGenerator;

class DocsController extends BaseApiController
{
    private $generator;

    public function __construct()
    {
        parent::__construct();
        $this->generator = new OpenApiGenerator();

        // Disable caching for docs endpoints (always get latest spec)
        $this->disableCaching();
    }

    /**
     * Serve OpenAPI specification as JSON
     *
     * GET /api/openapi.json
     * GET /api/v1/openapi.json
     *
     * Response:
     * {
     *     "openapi": "3.0.3",
     *     "info": {...},
     *     "paths": {...},
     *     "components": {...}
     * }
     */
    public function serveJson()
    {
        try {
            $spec = $this->generator->generate();
            $json = $this->generator->toJson(true);

            if (!headers_sent()) {
                header('Content-Type: application/json');
                header('Access-Control-Allow-Origin: *');
            }

            echo $json;

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate OpenAPI spec', 500);
        }
    }

    /**
     * Serve OpenAPI specification as YAML
     *
     * GET /api/openapi.yaml
     * GET /api/v1/openapi.yaml
     *
     * Response: YAML formatted OpenAPI spec
     */
    public function serveYaml()
    {
        try {
            $spec = $this->generator->generate();
            $yaml = $this->generator->toYaml();

            if (!headers_sent()) {
                header('Content-Type: application/x-yaml');
                header('Content-Disposition: inline; filename="openapi.yaml"');
                header('Access-Control-Allow-Origin: *');
            }

            echo $yaml;

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate OpenAPI spec', 500);
        }
    }

    /**
     * Serve Swagger UI HTML page
     *
     * GET /api/docs
     * GET /api/v1/docs
     *
     * Renders interactive Swagger UI interface
     */
    public function swaggerUi()
    {
        $baseUrl = $this->getBaseUrl();
        $specUrl = $baseUrl . '/api/openapi.json';

        // Generate Swagger UI HTML
        $html = $this->generateSwaggerUiHtml($specUrl);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo $html;
    }

    /**
     * Serve ReDoc HTML page (alternative documentation UI)
     *
     * GET /api/redoc
     * GET /api/v1/redoc
     */
    public function redoc()
    {
        $baseUrl = $this->getBaseUrl();
        $specUrl = $baseUrl . '/api/openapi.json';

        // Generate ReDoc HTML
        $html = $this->generateRedocHtml($specUrl);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo $html;
    }

    /**
     * Get API documentation metadata
     *
     * GET /api/docs/info
     *
     * Returns information about available documentation formats
     */
    public function info()
    {
        $baseUrl = $this->getBaseUrl();

        $docsInfo = [
            'title' => 'Sci-Bono Clubhouse LMS API Documentation',
            'version' => 'v1',
            'formats' => [
                'openapi_json' => [
                    'url' => $baseUrl . '/api/openapi.json',
                    'description' => 'OpenAPI 3.0 specification (JSON format)',
                    'content_type' => 'application/json'
                ],
                'openapi_yaml' => [
                    'url' => $baseUrl . '/api/openapi.yaml',
                    'description' => 'OpenAPI 3.0 specification (YAML format)',
                    'content_type' => 'application/x-yaml'
                ],
                'swagger_ui' => [
                    'url' => $baseUrl . '/api/docs',
                    'description' => 'Interactive Swagger UI documentation',
                    'content_type' => 'text/html'
                ],
                'redoc' => [
                    'url' => $baseUrl . '/api/redoc',
                    'description' => 'Interactive ReDoc documentation',
                    'content_type' => 'text/html'
                ]
            ],
            'endpoints_documented' => $this->countEndpoints(),
            'schemas_documented' => $this->countSchemas()
        ];

        return $this->successResponse($docsInfo, 'API documentation information');
    }

    /**
     * Generate Swagger UI HTML
     *
     * @param string $specUrl URL to OpenAPI spec
     * @return string HTML content
     */
    private function generateSwaggerUiHtml($specUrl)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sci-Bono Clubhouse LMS API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui.css">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .topbar {
            display: none;
        }
        .swagger-ui .info .title {
            font-size: 36px;
        }
        .swagger-ui .info {
            margin: 50px 0;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: '{$specUrl}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 3,
                docExpansion: 'list',
                filter: true,
                showRequestHeaders: true,
                persistAuthorization: true,
                tryItOutEnabled: true
            });

            window.ui = ui;
        };
    </script>
</body>
</html>
HTML;
    }

    /**
     * Generate ReDoc HTML
     *
     * @param string $specUrl URL to OpenAPI spec
     * @return string HTML content
     */
    private function generateRedocHtml($specUrl)
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sci-Bono Clubhouse LMS API Documentation - ReDoc</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <redoc spec-url='{$specUrl}'></redoc>

    <script src="https://cdn.jsdelivr.net/npm/redoc@2.1.3/bundles/redoc.standalone.js"></script>
</body>
</html>
HTML;
    }

    /**
     * Count documented endpoints
     *
     * @return int Number of endpoints
     */
    private function countEndpoints()
    {
        $spec = $this->generator->generate();
        $count = 0;

        foreach ($spec['paths'] as $path => $methods) {
            $count += count($methods);
        }

        return $count;
    }

    /**
     * Count documented schemas
     *
     * @return int Number of schemas
     */
    private function countSchemas()
    {
        $spec = $this->generator->generate();
        return count($spec['components']['schemas'] ?? []);
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
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        // Extract base path (e.g., /Sci-Bono_Clubhoue_LMS)
        $basePath = '';
        if (strpos($scriptName, 'Sci-Bono_Clubhoue_LMS') !== false) {
            $basePath = '/Sci-Bono_Clubhoue_LMS';
        }

        return $protocol . '://' . $host . $basePath;
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

        // Match /api/openapi.json
        if (preg_match('#/api(?:/v\d+)?/openapi\.json/?$#', $uri)) {
            return $this->serveJson();
        }

        // Match /api/openapi.yaml
        if (preg_match('#/api(?:/v\d+)?/openapi\.ya?ml/?$#', $uri)) {
            return $this->serveYaml();
        }

        // Match /api/docs (Swagger UI)
        if (preg_match('#/api(?:/v\d+)?/docs/?$#', $uri)) {
            return $this->swaggerUi();
        }

        // Match /api/redoc
        if (preg_match('#/api(?:/v\d+)?/redoc/?$#', $uri)) {
            return $this->redoc();
        }

        // Match /api/docs/info
        if (preg_match('#/api(?:/v\d+)?/docs/info/?$#', $uri)) {
            return $this->info();
        }

        return $this->errorResponse('Documentation endpoint not found', 404);
    }
}
