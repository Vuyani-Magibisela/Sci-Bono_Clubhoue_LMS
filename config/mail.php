<?php
require_once __DIR__ . '/ConfigLoader.php';

return [
    'driver' => ConfigLoader::env('MAIL_DRIVER', 'smtp'),
    'host' => ConfigLoader::env('MAIL_HOST', 'localhost'),
    'port' => ConfigLoader::env('MAIL_PORT', 587),
    'username' => ConfigLoader::env('MAIL_USERNAME', ''),
    'password' => ConfigLoader::env('MAIL_PASSWORD', ''),
    'encryption' => ConfigLoader::env('MAIL_ENCRYPTION', 'tls'),
    
    'from' => [
        'address' => ConfigLoader::env('MAIL_FROM_ADDRESS', 'noreply@localhost'),
        'name' => ConfigLoader::env('MAIL_FROM_NAME', 'LMS System'),
    ],
];