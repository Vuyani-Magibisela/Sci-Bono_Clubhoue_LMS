<?php
echo "<!DOCTYPE html><html><head><title>Debug Test</title></head><body>";
echo "<h1>Debug Test File</h1>";
echo "<p>Current file: " . __FILE__ . "</p>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<p>Script name: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>PHP version: " . PHP_VERSION . "</p>";
echo "<p>Server software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<hr>";
echo "<h2>All $_SERVER variables:</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
echo "</body></html>";
echo "<p>auto_prepend_file: " . ini_get('auto_prepend_file') . "</p>";
echo "<p>auto_append_file: " . ini_get('auto_append_file') . "</p>";
?>