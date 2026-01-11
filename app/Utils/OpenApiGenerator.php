<?php
/**
 * OpenAPI Generator - Generate OpenAPI 3.0 specification
 *
 * Generates OpenAPI/Swagger documentation for the REST API.
 *
 * Phase 5 Week 3 Day 3
 *
 * @package App\Utils
 * @since Phase 5 Week 3
 */

namespace App\Utils;

class OpenApiGenerator
{
    private $spec;
    private $version = 'v1';

    public function __construct($version = 'v1')
    {
        $this->version = $version;
        $this->initializeSpec();
    }

    /**
     * Initialize OpenAPI specification structure
     */
    private function initializeSpec()
    {
        $this->spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Sci-Bono Clubhouse LMS API',
                'description' => 'REST API for the Sci-Bono Clubhouse Learning Management System. This API provides endpoints for user management, authentication, courses, and more.',
                'version' => $this->version,
                'contact' => [
                    'name' => 'Sci-Bono Discovery Centre',
                    'email' => 'api-support@sci-bono.co.za',
                    'url' => 'https://www.sci-bono.co.za'
                ],
                'license' => [
                    'name' => 'Proprietary',
                    'url' => 'https://www.sci-bono.co.za/api/license'
                ]
            ],
            'servers' => [
                [
                    'url' => $this->getBaseUrl() . '/api/' . $this->version,
                    'description' => 'Production server'
                ],
                [
                    'url' => 'http://localhost/Sci-Bono_Clubhoue_LMS/api/' . $this->version,
                    'description' => 'Development server'
                ]
            ],
            'tags' => [
                ['name' => 'Authentication', 'description' => 'User authentication and token management'],
                ['name' => 'Users', 'description' => 'User profile operations'],
                ['name' => 'Admin - Users', 'description' => 'Administrative user management (admin only)'],
                ['name' => 'Versions', 'description' => 'API version information']
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'JWT token obtained from /auth/login endpoint'
                    ]
                ],
                'responses' => [],
                'parameters' => []
            ],
            'security' => []
        ];
    }

    /**
     * Generate complete OpenAPI specification
     */
    public function generate()
    {
        // Add paths (endpoints)
        $this->addAuthenticationPaths();
        $this->addUserPaths();
        $this->addAdminUserPaths();
        $this->addVersionPaths();

        // Add schemas (data models)
        $this->addSchemas();

        // Add common responses
        $this->addCommonResponses();

        // Add common parameters
        $this->addCommonParameters();

        return $this->spec;
    }

    /**
     * Get specification as JSON
     */
    public function toJson($pretty = true)
    {
        $options = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0;
        return json_encode($this->spec, $options);
    }

    /**
     * Get specification as YAML
     */
    public function toYaml()
    {
        // Simple YAML conversion (for basic use)
        // In production, use symfony/yaml or similar
        return $this->arrayToYaml($this->spec);
    }

    /**
     * Add authentication endpoints
     */
    private function addAuthenticationPaths()
    {
        // POST /auth/login
        $this->spec['paths']['/auth/login'] = [
            'post' => [
                'tags' => ['Authentication'],
                'summary' => 'User login',
                'description' => 'Authenticate user and receive access and refresh tokens',
                'operationId' => 'login',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/LoginRequest'
                            ],
                            'example' => [
                                'email' => 'user@example.com',
                                'password' => 'password123'
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Login successful',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/LoginResponse'
                                ]
                            ]
                        ]
                    ],
                    '401' => ['$ref' => '#/components/responses/Unauthorized'],
                    '422' => ['$ref' => '#/components/responses/ValidationError']
                ]
            ]
        ];

        // POST /auth/logout
        $this->spec['paths']['/auth/logout'] = [
            'post' => [
                'tags' => ['Authentication'],
                'summary' => 'User logout',
                'description' => 'Logout user and blacklist current token',
                'operationId' => 'logout',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => [
                        'description' => 'Logout successful',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/SuccessResponse'
                                ]
                            ]
                        ]
                    ],
                    '401' => ['$ref' => '#/components/responses/Unauthorized']
                ]
            ]
        ];

        // POST /auth/refresh
        $this->spec['paths']['/auth/refresh'] = [
            'post' => [
                'tags' => ['Authentication'],
                'summary' => 'Refresh access token',
                'description' => 'Exchange refresh token for new access and refresh tokens (token rotation)',
                'operationId' => 'refresh',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/RefreshRequest'
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Token refreshed successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/RefreshResponse'
                                ]
                            ]
                        ]
                    ],
                    '401' => ['$ref' => '#/components/responses/Unauthorized']
                ]
            ]
        ];

        // POST /auth/forgot-password
        $this->spec['paths']['/auth/forgot-password'] = [
            'post' => [
                'tags' => ['Authentication'],
                'summary' => 'Request password reset',
                'description' => 'Send password reset email to user',
                'operationId' => 'forgotPassword',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email'],
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email']
                                ]
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => ['$ref' => '#/components/responses/Success'],
                    '422' => ['$ref' => '#/components/responses/ValidationError']
                ]
            ]
        ];
    }

    /**
     * Add user endpoints
     */
    private function addUserPaths()
    {
        // GET /users
        $this->spec['paths']['/users'] = [
            'get' => [
                'tags' => ['Users'],
                'summary' => 'List users',
                'description' => 'Get paginated list of users',
                'operationId' => 'getUsers',
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    ['$ref' => '#/components/parameters/Page'],
                    ['$ref' => '#/components/parameters/Limit'],
                    ['$ref' => '#/components/parameters/Sort'],
                    ['$ref' => '#/components/parameters/Order']
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Users retrieved successfully',
                        'headers' => [
                            'X-RateLimit-Limit' => ['$ref' => '#/components/headers/X-RateLimit-Limit'],
                            'X-RateLimit-Remaining' => ['$ref' => '#/components/headers/X-RateLimit-Remaining'],
                            'ETag' => ['$ref' => '#/components/headers/ETag']
                        ],
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UserListResponse'
                                ]
                            ]
                        ]
                    ],
                    '401' => ['$ref' => '#/components/responses/Unauthorized']
                ]
            ]
        ];

        // GET /users/{id}
        $this->spec['paths']['/users/{id}'] = [
            'get' => [
                'tags' => ['Users'],
                'summary' => 'Get user by ID',
                'description' => 'Retrieve detailed information about a specific user',
                'operationId' => 'getUserById',
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer'],
                        'description' => 'User ID'
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'User retrieved successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UserResponse'
                                ]
                            ]
                        ]
                    ],
                    '404' => ['$ref' => '#/components/responses/NotFound'],
                    '401' => ['$ref' => '#/components/responses/Unauthorized']
                ]
            ]
        ];

        // GET /users/me
        $this->spec['paths']['/users/me'] = [
            'get' => [
                'tags' => ['Users'],
                'summary' => 'Get current user profile',
                'description' => 'Retrieve profile of authenticated user',
                'operationId' => 'getCurrentUser',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => [
                        'description' => 'Profile retrieved successfully',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/UserResponse'
                                ]
                            ]
                        ]
                    ],
                    '401' => ['$ref' => '#/components/responses/Unauthorized']
                ]
            ]
        ];
    }

    /**
     * Add admin user management endpoints
     */
    private function addAdminUserPaths()
    {
        // GET /admin/users
        $this->spec['paths']['/admin/users'] = [
            'get' => [
                'tags' => ['Admin - Users'],
                'summary' => 'List all users (Admin)',
                'description' => 'Get paginated list of all users with filtering and sorting',
                'operationId' => 'adminGetUsers',
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    ['$ref' => '#/components/parameters/Page'],
                    ['$ref' => '#/components/parameters/Limit'],
                    ['$ref' => '#/components/parameters/Sort'],
                    ['$ref' => '#/components/parameters/Order'],
                    [
                        'name' => 'search',
                        'in' => 'query',
                        'schema' => ['type' => 'string'],
                        'description' => 'Search in name, surname, email'
                    ],
                    [
                        'name' => 'user_type',
                        'in' => 'query',
                        'schema' => [
                            'type' => 'string',
                            'enum' => ['admin', 'mentor', 'member', 'student', 'parent', 'project_officer', 'manager']
                        ],
                        'description' => 'Filter by user type'
                    ]
                ],
                'responses' => [
                    '200' => ['$ref' => '#/components/responses/UserListSuccess'],
                    '403' => ['$ref' => '#/components/responses/Forbidden']
                ]
            ],
            'post' => [
                'tags' => ['Admin - Users'],
                'summary' => 'Create new user (Admin)',
                'description' => 'Create a new user account',
                'operationId' => 'adminCreateUser',
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/CreateUserRequest'
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '201' => ['$ref' => '#/components/responses/UserCreated'],
                    '422' => ['$ref' => '#/components/responses/ValidationError'],
                    '403' => ['$ref' => '#/components/responses/Forbidden']
                ]
            ]
        ];

        // PUT /admin/users/{id}
        $this->spec['paths']['/admin/users/{id}'] = [
            'put' => [
                'tags' => ['Admin - Users'],
                'summary' => 'Update user (Admin)',
                'description' => 'Update user information',
                'operationId' => 'adminUpdateUser',
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/UpdateUserRequest'
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => ['$ref' => '#/components/responses/UserUpdated'],
                    '404' => ['$ref' => '#/components/responses/NotFound'],
                    '422' => ['$ref' => '#/components/responses/ValidationError'],
                    '403' => ['$ref' => '#/components/responses/Forbidden']
                ]
            ],
            'delete' => [
                'tags' => ['Admin - Users'],
                'summary' => 'Delete user (Admin)',
                'description' => 'Soft delete user (sets active=0)',
                'operationId' => 'adminDeleteUser',
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'integer']
                    ]
                ],
                'responses' => [
                    '200' => ['$ref' => '#/components/responses/UserDeleted'],
                    '404' => ['$ref' => '#/components/responses/NotFound'],
                    '422' => ['$ref' => '#/components/responses/ValidationError'],
                    '403' => ['$ref' => '#/components/responses/Forbidden']
                ]
            ]
        ];
    }

    /**
     * Add version information endpoints
     */
    private function addVersionPaths()
    {
        $this->spec['paths']['/versions'] = [
            'get' => [
                'tags' => ['Versions'],
                'summary' => 'Get API versions',
                'description' => 'List all supported API versions',
                'operationId' => 'getVersions',
                'responses' => [
                    '200' => [
                        'description' => 'Version information retrieved',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/VersionInfoResponse'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Add schema definitions
     */
    private function addSchemas()
    {
        // User schema
        $this->spec['components']['schemas']['User'] = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'example' => 1],
                'username' => ['type' => 'string', 'example' => 'john.doe'],
                'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john.doe@example.com'],
                'name' => ['type' => 'string', 'example' => 'John'],
                'surname' => ['type' => 'string', 'example' => 'Doe'],
                'user_type' => [
                    'type' => 'string',
                    'enum' => ['admin', 'mentor', 'member', 'student', 'parent', 'project_officer', 'manager'],
                    'example' => 'member'
                ],
                'active' => ['type' => 'boolean', 'example' => true],
                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                'updated_at' => ['type' => 'string', 'format' => 'date-time']
            ]
        ];

        // Login request
        $this->spec['components']['schemas']['LoginRequest'] = [
            'type' => 'object',
            'required' => ['email', 'password'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8]
            ]
        ];

        // Login response
        $this->spec['components']['schemas']['LoginResponse'] = [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean', 'example' => true],
                'message' => ['type' => 'string', 'example' => 'Login successful'],
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'access_token' => ['type' => 'string'],
                        'refresh_token' => ['type' => 'string'],
                        'token_type' => ['type' => 'string', 'example' => 'Bearer'],
                        'expires_in' => ['type' => 'integer', 'example' => 3600],
                        'user' => ['$ref' => '#/components/schemas/User']
                    ]
                ]
            ]
        ];

        // Refresh request
        $this->spec['components']['schemas']['RefreshRequest'] = [
            'type' => 'object',
            'required' => ['refresh_token'],
            'properties' => [
                'refresh_token' => ['type' => 'string']
            ]
        ];

        // Refresh response
        $this->spec['components']['schemas']['RefreshResponse'] = [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'message' => ['type' => 'string'],
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'access_token' => ['type' => 'string'],
                        'refresh_token' => ['type' => 'string'],
                        'token_type' => ['type' => 'string'],
                        'expires_in' => ['type' => 'integer']
                    ]
                ]
            ]
        ];

        // Create user request
        $this->spec['components']['schemas']['CreateUserRequest'] = [
            'type' => 'object',
            'required' => ['username', 'email', 'password', 'password_confirmation', 'name', 'surname', 'user_type'],
            'properties' => [
                'username' => ['type' => 'string', 'minLength' => 3],
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'minLength' => 8],
                'password_confirmation' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'surname' => ['type' => 'string'],
                'user_type' => [
                    'type' => 'string',
                    'enum' => ['admin', 'mentor', 'member', 'student', 'parent', 'project_officer', 'manager']
                ]
            ]
        ];

        // Update user request
        $this->spec['components']['schemas']['UpdateUserRequest'] = [
            'type' => 'object',
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'name' => ['type' => 'string'],
                'surname' => ['type' => 'string'],
                'user_type' => ['type' => 'string'],
                'active' => ['type' => 'boolean']
            ]
        ];

        // Success response
        $this->spec['components']['schemas']['SuccessResponse'] = [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean', 'example' => true],
                'message' => ['type' => 'string'],
                'data' => ['type' => 'object']
            ]
        ];

        // Error response
        $this->spec['components']['schemas']['ErrorResponse'] = [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean', 'example' => false],
                'message' => ['type' => 'string'],
                'errors' => ['type' => 'object']
            ]
        ];

        // User list response
        $this->spec['components']['schemas']['UserListResponse'] = [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'message' => ['type' => 'string'],
                'data' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/components/schemas/User']
                ],
                'meta' => ['$ref' => '#/components/schemas/PaginationMeta']
            ]
        ];

        // User response
        $this->spec['components']['schemas']['UserResponse'] = [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'message' => ['type' => 'string'],
                'data' => ['$ref' => '#/components/schemas/User']
            ]
        ];

        // Pagination meta
        $this->spec['components']['schemas']['PaginationMeta'] = [
            'type' => 'object',
            'properties' => [
                'current_page' => ['type' => 'integer'],
                'per_page' => ['type' => 'integer'],
                'total' => ['type' => 'integer'],
                'total_pages' => ['type' => 'integer'],
                'has_next' => ['type' => 'boolean'],
                'has_previous' => ['type' => 'boolean']
            ]
        ];

        // Version info response
        $this->spec['components']['schemas']['VersionInfoResponse'] = [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'current_version' => ['type' => 'string'],
                        'supported_versions' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'version' => ['type' => 'string'],
                                    'status' => ['type' => 'string'],
                                    'is_default' => ['type' => 'boolean']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Add common responses
     */
    private function addCommonResponses()
    {
        $this->spec['components']['responses']['Success'] = [
            'description' => 'Operation successful',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/SuccessResponse']
                ]
            ]
        ];

        $this->spec['components']['responses']['Unauthorized'] = [
            'description' => 'Unauthorized - Invalid or missing authentication token',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                ]
            ]
        ];

        $this->spec['components']['responses']['Forbidden'] = [
            'description' => 'Forbidden - Insufficient permissions',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                ]
            ]
        ];

        $this->spec['components']['responses']['NotFound'] = [
            'description' => 'Resource not found',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                ]
            ]
        ];

        $this->spec['components']['responses']['ValidationError'] = [
            'description' => 'Validation error',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/ErrorResponse']
                ]
            ]
        ];

        $this->spec['components']['responses']['UserListSuccess'] = [
            'description' => 'Users retrieved successfully',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/UserListResponse']
                ]
            ]
        ];

        $this->spec['components']['responses']['UserCreated'] = [
            'description' => 'User created successfully',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/UserResponse']
                ]
            ]
        ];

        $this->spec['components']['responses']['UserUpdated'] = [
            'description' => 'User updated successfully',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/UserResponse']
                ]
            ]
        ];

        $this->spec['components']['responses']['UserDeleted'] = [
            'description' => 'User deleted successfully',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/SuccessResponse']
                ]
            ]
        ];

        // Add common headers
        $this->spec['components']['headers'] = [
            'X-RateLimit-Limit' => [
                'description' => 'Maximum requests allowed in window',
                'schema' => ['type' => 'integer']
            ],
            'X-RateLimit-Remaining' => [
                'description' => 'Remaining requests in current window',
                'schema' => ['type' => 'integer']
            ],
            'X-RateLimit-Reset' => [
                'description' => 'Unix timestamp when limit resets',
                'schema' => ['type' => 'integer']
            ],
            'ETag' => [
                'description' => 'Entity tag for cache validation',
                'schema' => ['type' => 'string']
            ],
            'API-Version' => [
                'description' => 'API version used for this request',
                'schema' => ['type' => 'string']
            ]
        ];
    }

    /**
     * Add common parameters
     */
    private function addCommonParameters()
    {
        $this->spec['components']['parameters']['Page'] = [
            'name' => 'page',
            'in' => 'query',
            'description' => 'Page number',
            'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1]
        ];

        $this->spec['components']['parameters']['Limit'] = [
            'name' => 'limit',
            'in' => 'query',
            'description' => 'Items per page',
            'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 10]
        ];

        $this->spec['components']['parameters']['Sort'] = [
            'name' => 'sort',
            'in' => 'query',
            'description' => 'Sort field',
            'schema' => ['type' => 'string', 'default' => 'id']
        ];

        $this->spec['components']['parameters']['Order'] = [
            'name' => 'order',
            'in' => 'query',
            'description' => 'Sort order',
            'schema' => ['type' => 'string', 'enum' => ['asc', 'desc'], 'default' => 'asc']
        ];
    }

    /**
     * Get base URL
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Simple array to YAML converter
     */
    private function arrayToYaml($array, $indent = 0)
    {
        $yaml = '';
        $indentStr = str_repeat('  ', $indent);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (array_values($value) === $value) {
                    // Indexed array
                    $yaml .= $indentStr . $key . ":\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= $indentStr . "  -\n";
                            $yaml .= $this->arrayToYaml($item, $indent + 2);
                        } else {
                            $yaml .= $indentStr . "  - " . $this->yamlValue($item) . "\n";
                        }
                    }
                } else {
                    // Associative array
                    $yaml .= $indentStr . $key . ":\n";
                    $yaml .= $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= $indentStr . $key . ': ' . $this->yamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    /**
     * Format value for YAML
     */
    private function yamlValue($value)
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_numeric($value)) {
            return $value;
        }
        if (strpos($value, "\n") !== false || strpos($value, ':') !== false) {
            return '"' . addslashes($value) . '"';
        }
        return $value;
    }
}
