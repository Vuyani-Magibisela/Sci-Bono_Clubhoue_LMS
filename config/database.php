<?php
require_once __DIR__ . '/ConfigLoader.php';

return [
    'default' => 'mysql',
    
    // Connection pooling settings
    'max_connections' => ConfigLoader::env('DB_MAX_CONNECTIONS', 10),
    'pool_size' => ConfigLoader::env('DB_POOL_SIZE', 5),
    'connection_timeout' => ConfigLoader::env('DB_CONNECTION_TIMEOUT', 30),
    'retry_attempts' => ConfigLoader::env('DB_RETRY_ATTEMPTS', 3),
    'retry_delay' => ConfigLoader::env('DB_RETRY_DELAY', 1),
    
    // Query monitoring settings
    'log_queries' => ConfigLoader::env('DB_LOG_QUERIES', false),
    'slow_query_threshold' => ConfigLoader::env('DB_SLOW_QUERY_THRESHOLD', 1.0),
    'query_cache' => ConfigLoader::env('DB_QUERY_CACHE', false),
    
    // Health monitoring
    'health_check_interval' => ConfigLoader::env('DB_HEALTH_CHECK_INTERVAL', 300), // 5 minutes
    'auto_reconnect' => ConfigLoader::env('DB_AUTO_RECONNECT', true),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysqli',
            'host' => ConfigLoader::env('DB_HOST', 'localhost'),
            'database' => ConfigLoader::env('DB_NAME', 'accounts'),
            'username' => ConfigLoader::env('DB_USERNAME', 'root'),
            'password' => ConfigLoader::env('DB_PASSWORD', ''),
            'port' => ConfigLoader::env('DB_PORT', 3306),
            'charset' => ConfigLoader::env('DB_CHARSET', 'utf8mb4'),
            'collation' => ConfigLoader::env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            
            // Connection-specific settings
            'timezone' => ConfigLoader::env('DB_TIMEZONE', '+00:00'),
            'wait_timeout' => ConfigLoader::env('DB_WAIT_TIMEOUT', 28800), // 8 hours
            'interactive_timeout' => ConfigLoader::env('DB_INTERACTIVE_TIMEOUT', 28800),
            'max_allowed_packet' => ConfigLoader::env('DB_MAX_ALLOWED_PACKET', '16M'),
            
            // SQL Mode settings
            'sql_mode' => ConfigLoader::env('DB_SQL_MODE', 
                'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'
            ),
            
            // Performance settings
            'innodb_buffer_pool_size' => ConfigLoader::env('DB_INNODB_BUFFER_POOL_SIZE', '128M'),
            'query_cache_size' => ConfigLoader::env('DB_QUERY_CACHE_SIZE', '16M'),
            'tmp_table_size' => ConfigLoader::env('DB_TMP_TABLE_SIZE', '16M'),
            'max_heap_table_size' => ConfigLoader::env('DB_MAX_HEAP_TABLE_SIZE', '16M'),
            
            // SSL settings (optional)
            'ssl_ca' => ConfigLoader::env('DB_SSL_CA', null),
            'ssl_cert' => ConfigLoader::env('DB_SSL_CERT', null),
            'ssl_key' => ConfigLoader::env('DB_SSL_KEY', null),
            'ssl_verify_server_cert' => ConfigLoader::env('DB_SSL_VERIFY_SERVER_CERT', false),
            
            // Monitoring settings
            'log_queries' => ConfigLoader::env('DB_LOG_QUERIES', false),
            'slow_query_log' => ConfigLoader::env('DB_SLOW_QUERY_LOG', false),
            'general_log' => ConfigLoader::env('DB_GENERAL_LOG', false)
        ],
        
        // Read replica connection (optional)
        'mysql_read' => [
            'driver' => 'mysqli',
            'host' => ConfigLoader::env('DB_READ_HOST', ConfigLoader::env('DB_HOST', 'localhost')),
            'database' => ConfigLoader::env('DB_READ_NAME', ConfigLoader::env('DB_NAME', 'accounts')),
            'username' => ConfigLoader::env('DB_READ_USERNAME', ConfigLoader::env('DB_USERNAME', 'root')),
            'password' => ConfigLoader::env('DB_READ_PASSWORD', ConfigLoader::env('DB_PASSWORD', '')),
            'port' => ConfigLoader::env('DB_READ_PORT', ConfigLoader::env('DB_PORT', 3306)),
            'charset' => ConfigLoader::env('DB_READ_CHARSET', ConfigLoader::env('DB_CHARSET', 'utf8mb4')),
            'collation' => ConfigLoader::env('DB_READ_COLLATION', ConfigLoader::env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'timezone' => ConfigLoader::env('DB_READ_TIMEZONE', ConfigLoader::env('DB_TIMEZONE', '+00:00')),
            'wait_timeout' => ConfigLoader::env('DB_READ_WAIT_TIMEOUT', ConfigLoader::env('DB_WAIT_TIMEOUT', 28800)),
            'sql_mode' => ConfigLoader::env('DB_READ_SQL_MODE', ConfigLoader::env('DB_SQL_MODE', 
                'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'
            )),
            'read_only' => true
        ],
        
        // Testing connection
        'mysql_test' => [
            'driver' => 'mysqli',
            'host' => ConfigLoader::env('DB_TEST_HOST', 'localhost'),
            'database' => ConfigLoader::env('DB_TEST_NAME', 'accounts_test'),
            'username' => ConfigLoader::env('DB_TEST_USERNAME', 'root'),
            'password' => ConfigLoader::env('DB_TEST_PASSWORD', ''),
            'port' => ConfigLoader::env('DB_TEST_PORT', 3306),
            'charset' => ConfigLoader::env('DB_TEST_CHARSET', 'utf8mb4'),
            'collation' => ConfigLoader::env('DB_TEST_COLLATION', 'utf8mb4_unicode_ci'),
            'timezone' => ConfigLoader::env('DB_TEST_TIMEZONE', '+00:00'),
            'wait_timeout' => 3600, // 1 hour for tests
            'sql_mode' => 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'
        ]
    ],
    
    // Migration settings
    'migrations' => [
        'path' => __DIR__ . '/../database/migrations/',
        'table' => 'migrations',
        'batch_size' => ConfigLoader::env('DB_MIGRATION_BATCH_SIZE', 50),
        'timeout' => ConfigLoader::env('DB_MIGRATION_TIMEOUT', 300) // 5 minutes
    ],
    
    // Backup settings
    'backup' => [
        'path' => __DIR__ . '/../storage/backups/',
        'compression' => ConfigLoader::env('DB_BACKUP_COMPRESSION', 'gzip'),
        'retention_days' => ConfigLoader::env('DB_BACKUP_RETENTION_DAYS', 30),
        'max_size' => ConfigLoader::env('DB_BACKUP_MAX_SIZE', '1G'),
        'exclude_tables' => ConfigLoader::env('DB_BACKUP_EXCLUDE_TABLES', 'sessions,cache'),
        'mysqldump_options' => ConfigLoader::env('DB_BACKUP_MYSQLDUMP_OPTIONS', '--single-transaction --routines --triggers')
    ],
    
    // Performance monitoring
    'monitoring' => [
        'enabled' => ConfigLoader::env('DB_MONITORING_ENABLED', true),
        'slow_query_threshold' => ConfigLoader::env('DB_SLOW_QUERY_THRESHOLD', 1.0),
        'connection_timeout_threshold' => ConfigLoader::env('DB_CONNECTION_TIMEOUT_THRESHOLD', 5.0),
        'memory_usage_threshold' => ConfigLoader::env('DB_MEMORY_USAGE_THRESHOLD', '80%'),
        'disk_usage_threshold' => ConfigLoader::env('DB_DISK_USAGE_THRESHOLD', '90%'),
        'alert_on_errors' => ConfigLoader::env('DB_ALERT_ON_ERRORS', true),
        'metrics_retention_hours' => ConfigLoader::env('DB_METRICS_RETENTION_HOURS', 24)
    ],
    
    // Cache settings for query results
    // Phase 3 Week 9 - Performance: Enabled query result caching
    'cache' => [
        'enabled' => ConfigLoader::env('DB_CACHE_ENABLED', true),  // Changed from false
        'default_ttl' => ConfigLoader::env('DB_CACHE_TTL', 300),   // Changed to 5 minutes
        'key_prefix' => ConfigLoader::env('DB_CACHE_PREFIX', 'db_'),
        'driver' => ConfigLoader::env('CACHE_DRIVER', 'file') // file, redis, memcached
    ]
];