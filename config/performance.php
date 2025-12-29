<?php
/**
 * Performance Monitoring Configuration
 * Sci-Bono Clubhouse LMS - Phase 7: API Development & Testing
 * 
 * Configuration settings for the performance monitoring system
 */

return [
    // General settings
    'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
    // Phase 3 Week 9 - Performance: Reduced sampling from 100% to 10% (90% overhead reduction)
    'sample_rate' => env('PERFORMANCE_SAMPLE_RATE', 0.1), // 0.1 = 10% sampling
    'debug_mode' => env('PERFORMANCE_DEBUG_MODE', false),
    
    // Data retention settings
    'retention' => [
        'metrics_days' => env('PERFORMANCE_RETENTION_DAYS', 30),
        'alerts_days' => env('ALERT_RETENTION_DAYS', 90),
        'summary_days' => env('SUMMARY_RETENTION_DAYS', 365),
        'cleanup_interval' => env('CLEANUP_INTERVAL_HOURS', 24)
    ],
    
    // Performance thresholds
    'thresholds' => [
        'response_time' => [
            'warning' => env('RESPONSE_TIME_WARNING_MS', 1000),
            'critical' => env('RESPONSE_TIME_CRITICAL_MS', 3000)
        ],
        'memory_usage' => [
            'warning_mb' => env('MEMORY_WARNING_MB', 100),
            'critical_mb' => env('MEMORY_CRITICAL_MB', 200),
            'warning_percent' => env('MEMORY_WARNING_PERCENT', 75),
            'critical_percent' => env('MEMORY_CRITICAL_PERCENT', 90)
        ],
        'database' => [
            'slow_query_ms' => env('DB_SLOW_QUERY_MS', 500),
            'connection_timeout' => env('DB_CONNECTION_TIMEOUT', 30),
            'max_connections_warning' => env('DB_MAX_CONNECTIONS_WARNING', 80)
        ],
        'error_rate' => [
            'warning_percent' => env('ERROR_RATE_WARNING', 5.0),
            'critical_percent' => env('ERROR_RATE_CRITICAL', 10.0),
            'time_window_minutes' => env('ERROR_RATE_WINDOW_MINUTES', 5)
        ],
        'disk_space' => [
            'warning_percent' => env('DISK_WARNING_PERCENT', 80),
            'critical_percent' => env('DISK_CRITICAL_PERCENT', 90)
        ]
    ],
    
    // Monitoring features
    'features' => [
        'api_monitoring' => env('API_MONITORING_ENABLED', true),
        'database_monitoring' => env('DB_MONITORING_ENABLED', true),
        'memory_monitoring' => env('MEMORY_MONITORING_ENABLED', true),
        'error_tracking' => env('ERROR_TRACKING_ENABLED', true),
        'real_user_monitoring' => env('RUM_ENABLED', false),
        'custom_metrics' => env('CUSTOM_METRICS_ENABLED', true)
    ],
    
    // Alert settings
    'alerts' => [
        'enabled' => env('ALERTS_ENABLED', true),
        'channels' => [
            'email' => [
                'enabled' => env('EMAIL_ALERTS_ENABLED', false),
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', '')),
                'smtp_host' => env('SMTP_HOST', ''),
                'smtp_port' => env('SMTP_PORT', 587),
                'smtp_username' => env('SMTP_USERNAME', ''),
                'smtp_password' => env('SMTP_PASSWORD', ''),
                'from_email' => env('ALERT_FROM_EMAIL', 'alerts@sci-bono.co.za')
            ],
            'webhook' => [
                'enabled' => env('WEBHOOK_ALERTS_ENABLED', false),
                'url' => env('ALERT_WEBHOOK_URL', ''),
                'secret' => env('WEBHOOK_SECRET', ''),
                'timeout' => env('WEBHOOK_TIMEOUT', 10)
            ],
            'slack' => [
                'enabled' => env('SLACK_ALERTS_ENABLED', false),
                'webhook_url' => env('SLACK_WEBHOOK_URL', ''),
                'channel' => env('SLACK_CHANNEL', '#alerts'),
                'username' => env('SLACK_USERNAME', 'Performance Monitor')
            ]
        ],
        'rate_limiting' => [
            'max_alerts_per_hour' => env('MAX_ALERTS_PER_HOUR', 10),
            'duplicate_alert_cooldown_minutes' => env('DUPLICATE_ALERT_COOLDOWN', 60)
        ]
    ],
    
    // Database settings
    'database' => [
        'connection' => env('PERFORMANCE_DB_CONNECTION', 'default'),
        'table_prefix' => env('PERFORMANCE_TABLE_PREFIX', 'performance_'),
        'batch_size' => env('PERFORMANCE_BATCH_SIZE', 100),
        'max_query_length' => env('MAX_QUERY_LENGTH', 1000)
    ],
    
    // Caching settings
    'cache' => [
        'enabled' => env('PERFORMANCE_CACHE_ENABLED', true),
        'driver' => env('PERFORMANCE_CACHE_DRIVER', 'file'),
        'ttl' => env('PERFORMANCE_CACHE_TTL', 300), // 5 minutes
        'prefix' => env('PERFORMANCE_CACHE_PREFIX', 'perf_')
    ],
    
    // API endpoints to monitor
    'api_endpoints' => [
        'include_patterns' => [
            '/api/*',
            '/app/API/*'
        ],
        'exclude_patterns' => [
            '/api/health',
            '/api/metrics',
            '*.js',
            '*.css',
            '*.png',
            '*.jpg',
            '*.gif'
        ],
        'sensitive_endpoints' => [
            '/api/auth/login',
            '/api/auth/register',
            '/api/users/*/change-password'
        ]
    ],
    
    // Metrics collection
    'metrics' => [
        'collection_interval' => env('METRICS_COLLECTION_INTERVAL', 60), // seconds
        'aggregation_interval' => env('METRICS_AGGREGATION_INTERVAL', 300), // 5 minutes
        'percentiles' => [50, 95, 99],
        'custom_dimensions' => [
            'user_type',
            'endpoint',
            'method',
            'response_code',
            'environment'
        ]
    ],
    
    // Dashboard settings
    'dashboard' => [
        'enabled' => env('PERFORMANCE_DASHBOARD_ENABLED', true),
        'refresh_interval' => env('DASHBOARD_REFRESH_INTERVAL', 30), // seconds
        'max_data_points' => env('DASHBOARD_MAX_DATA_POINTS', 100),
        'auth_required' => env('DASHBOARD_AUTH_REQUIRED', true),
        'allowed_users' => explode(',', env('DASHBOARD_ALLOWED_USERS', 'admin')),
        'theme' => env('DASHBOARD_THEME', 'light') // light, dark
    ],
    
    // Export settings
    'export' => [
        'formats' => ['json', 'csv', 'excel'],
        'max_records' => env('EXPORT_MAX_RECORDS', 10000),
        'compression' => env('EXPORT_COMPRESSION', true),
        'encryption' => env('EXPORT_ENCRYPTION', false)
    ],
    
    // Performance optimization
    'optimization' => [
        'async_processing' => env('ASYNC_PROCESSING_ENABLED', false),
        'queue_driver' => env('QUEUE_DRIVER', 'database'),
        'background_processing' => env('BACKGROUND_PROCESSING_ENABLED', true),
        'memory_limit' => env('PERFORMANCE_MEMORY_LIMIT', '256M'),
        'max_execution_time' => env('PERFORMANCE_MAX_EXECUTION_TIME', 300)
    ],
    
    // Security settings
    'security' => [
        'encrypt_sensitive_data' => env('ENCRYPT_PERFORMANCE_DATA', false),
        'anonymize_ip_addresses' => env('ANONYMIZE_IP_ADDRESSES', true),
        'hash_user_identifiers' => env('HASH_USER_IDENTIFIERS', false),
        'audit_access' => env('AUDIT_PERFORMANCE_ACCESS', true)
    ],
    
    // Environment-specific settings
    'environments' => [
        'development' => [
            'sample_rate' => 1.0,
            'debug_mode' => true,
            'alerts' => ['enabled' => false]
        ],
        'testing' => [
            'sample_rate' => 0.1,
            'retention' => ['metrics_days' => 7],
            'alerts' => ['enabled' => false]
        ],
        'staging' => [
            'sample_rate' => 0.5,
            'retention' => ['metrics_days' => 14],
            'alerts' => ['enabled' => true]
        ],
        'production' => [
            'sample_rate' => 1.0,
            'retention' => ['metrics_days' => 90],
            'alerts' => ['enabled' => true]
        ]
    ],
    
    // Integration settings
    'integrations' => [
        'google_analytics' => [
            'enabled' => env('GA_PERFORMANCE_ENABLED', false),
            'tracking_id' => env('GA_TRACKING_ID', ''),
            'custom_metrics' => env('GA_CUSTOM_METRICS', true)
        ],
        'new_relic' => [
            'enabled' => env('NEW_RELIC_ENABLED', false),
            'app_name' => env('NEW_RELIC_APP_NAME', 'Sci-Bono LMS'),
            'license_key' => env('NEW_RELIC_LICENSE_KEY', '')
        ],
        'datadog' => [
            'enabled' => env('DATADOG_ENABLED', false),
            'api_key' => env('DATADOG_API_KEY', ''),
            'app_key' => env('DATADOG_APP_KEY', ''),
            'host' => env('DATADOG_HOST', 'localhost')
        ]
    ],
    
    // Logging settings
    'logging' => [
        'enabled' => env('PERFORMANCE_LOGGING_ENABLED', true),
        'level' => env('PERFORMANCE_LOG_LEVEL', 'info'), // debug, info, warning, error
        'file' => env('PERFORMANCE_LOG_FILE', 'logs/performance.log'),
        'max_size' => env('PERFORMANCE_LOG_MAX_SIZE', '10M'),
        'rotate' => env('PERFORMANCE_LOG_ROTATE', true),
        'format' => env('PERFORMANCE_LOG_FORMAT', 'json') // json, text
    ],
    
    // Feature flags
    'feature_flags' => [
        'real_time_alerts' => env('REAL_TIME_ALERTS', true),
        'predictive_analysis' => env('PREDICTIVE_ANALYSIS', false),
        'anomaly_detection' => env('ANOMALY_DETECTION', false),
        'advanced_filtering' => env('ADVANCED_FILTERING', true),
        'custom_dashboards' => env('CUSTOM_DASHBOARDS', false),
        'api_versioning' => env('API_VERSIONING', true)
    ]
];

/**
 * Helper function to get environment variable with default
 */
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Convert string booleans
    if (strtolower($value) === 'true') {
        return true;
    }
    
    if (strtolower($value) === 'false') {
        return false;
    }
    
    // Convert string numbers
    if (is_numeric($value)) {
        return strpos($value, '.') !== false ? (float)$value : (int)$value;
    }
    
    return $value;
}