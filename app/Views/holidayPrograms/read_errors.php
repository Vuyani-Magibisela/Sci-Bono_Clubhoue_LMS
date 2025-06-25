<?php
// Error Log Reader - DELETE AFTER DEBUGGING
echo "<h2>🔍 PHP Error Log Reader</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

// Try to read PHP error log from various locations
$possible_logs = [
    ini_get('error_log'),
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/var/log/php_errors.log',
    __DIR__ . '/debug_errors.log',
    $_SERVER['DOCUMENT_ROOT'] . '/error_log',
    dirname($_SERVER['SCRIPT_FILENAME']) . '/error_log'
];

echo "<h3>Searching for Error Logs:</h3>";

foreach ($possible_logs as $log_path) {
    if (empty($log_path)) continue;
    
    echo "<p><strong>Checking:</strong> $log_path</p>";
    
    if (file_exists($log_path) && is_readable($log_path)) {
        echo "<p style='color: green;'>✅ Found readable log file!</p>";
        
        // Read last 50 lines
        $lines = file($log_path);
        if ($lines) {
            $recent_lines = array_slice($lines, -50);
            
            echo "<h4>Last 50 lines from: $log_path</h4>";
            echo "<pre style='background: #f4f4f4; padding: 10px; max-height: 400px; overflow-y: scroll;'>";
            
            foreach ($recent_lines as $line) {
                // Highlight PHP errors
                if (strpos($line, 'PHP') !== false) {
                    echo "<span style='color: red; font-weight: bold;'>$line</span>";
                } else {
                    echo htmlspecialchars($line);
                }
            }
            echo "</pre>";
            break; // Stop after finding first readable log
        }
    } else {
        echo "<p style='color: #666;'>❌ Not found or not readable</p>";
    }
}

// Also check for custom error logs
echo "<h3>Creating Custom Error Log:</h3>";
$custom_log = __DIR__ . '/custom_debug.log';
error_log("Test error message from error reader", 3, $custom_log);

if (file_exists($custom_log)) {
    echo "<p>✅ Custom log created at: $custom_log</p>";
    echo "<p>Content: " . file_get_contents($custom_log) . "</p>";
} else {
    echo "<p>❌ Could not create custom log</p>";
}

echo "<hr>";
echo "<p><strong>Remember to delete this file when done debugging!</strong></p>";
?>