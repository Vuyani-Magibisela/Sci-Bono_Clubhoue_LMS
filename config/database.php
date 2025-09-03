<?php
require_once __DIR__ . '/ConfigLoader.php';

return [
    'default' => 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysqli',
            'host' => ConfigLoader::env('DB_HOST', 'localhost'),
            'database' => ConfigLoader::env('DB_NAME', 'accounts'),
            'username' => ConfigLoader::env('DB_USERNAME', 'root'),
            'password' => ConfigLoader::env('DB_PASSWORD', ''),
            'charset' => ConfigLoader::env('DB_CHARSET', 'utf8mb4'),
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];