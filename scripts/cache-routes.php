#!/usr/bin/env php
<?php
/**
 * Route Caching Script
 * Phase 3 Implementation
 */

require_once __DIR__ . '/../bootstrap.php';

// Ensure cache directory exists
$cacheDir = __DIR__ . '/../storage/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Load routes and cache them
echo "Caching web routes...\n";
$webRouter = require __DIR__ . '/../routes/web.php';
$webRouter->cache($cacheDir . '/web-routes.php');

echo "Caching API routes...\n";
$apiRouter = require __DIR__ . '/../routes/api.php';
$apiRouter->cache($cacheDir . '/api-routes.php');

echo "Route caching completed!\n";
?>