<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../core/SecureFileUploader.php';
require_once __DIR__ . '/../app/Middleware/RateLimitMiddleware.php';
require_once __DIR__ . '/../server.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// CSRF check
if (!CSRF::validateToken()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Rate limiting
$rateLimiter = new RateLimitMiddleware($conn);
if (!$rateLimiter->handle('upload')) {
    exit; // Rate limit response handled by middleware
}

// Process file upload
if (isset($_FILES['file'])) {
    $uploader = new SecureFileUploader();
    $result = $uploader->upload($_FILES['file']);
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file provided']);
}
?>