<?php
require_once __DIR__ . '/ConfigLoader.php';

return [
    'name' => ConfigLoader::env('APP_NAME', 'Sci-Bono Clubhouse LMS'),
    'env' => ConfigLoader::env('APP_ENV', 'production'),
    'debug' => ConfigLoader::env('APP_DEBUG', false),
    'url' => ConfigLoader::env('APP_URL', 'http://localhost'),
    
    'timezone' => 'Africa/Johannesburg',
    'locale' => 'en',
    
    'uploads' => [
        'path' => ConfigLoader::env('UPLOAD_PATH', 'public/assets/uploads/'),
        'max_size' => ConfigLoader::env('UPLOAD_MAX_SIZE', 10485760),
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
    ],
    
    'security' => [
        'csrf_token_name' => ConfigLoader::env('CSRF_TOKEN_NAME', '_token'),
        'bcrypt_rounds' => ConfigLoader::env('BCRYPT_ROUNDS', 12),
    ],
];