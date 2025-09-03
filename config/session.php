<?php
require_once __DIR__ . '/ConfigLoader.php';

return [
    'lifetime' => ConfigLoader::env('SESSION_LIFETIME', 120),
    'secure' => ConfigLoader::env('SESSION_SECURE', false),
    'http_only' => ConfigLoader::env('SESSION_HTTP_ONLY', true),
    'same_site' => 'lax',
    'name' => 'sci_bono_lms_session',
    'save_path' => '',
];