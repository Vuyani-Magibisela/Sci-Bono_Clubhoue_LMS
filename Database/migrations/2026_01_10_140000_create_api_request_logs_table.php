<?php
/**
 * Migration: Create API Request Logs Table
 *
 * Creates table for storing API request and response logs.
 * Used for monitoring, debugging, and analytics.
 *
 * Phase 5 Week 3 Day 4
 *
 * @since Phase 5 Week 3 Day 4 (January 10, 2026)
 */

require_once __DIR__ . '/../../server.php';

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Migration: Create API Request Logs Table\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // Create api_request_logs table
    $sql = "
        CREATE TABLE IF NOT EXISTS api_request_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            -- Request Information
            method VARCHAR(10) NOT NULL,
            uri VARCHAR(1000) NOT NULL,
            path VARCHAR(500) NOT NULL,
            query_string TEXT,

            -- Client Information
            user_agent VARCHAR(500),
            ip_address VARCHAR(45),

            -- Request Details
            headers JSON,
            body TEXT,
            query_params JSON,

            -- Response Information
            status_code INT,
            response_body TEXT,

            -- Performance Metrics
            duration_ms DECIMAL(10, 2),
            memory_usage BIGINT,

            -- Error Tracking
            is_error BOOLEAN DEFAULT FALSE,
            error_message TEXT,
            error_context JSON,

            -- Timestamps
            created_at DATETIME NOT NULL,
            updated_at DATETIME,

            -- Indexes for performance
            INDEX idx_method (method),
            INDEX idx_path (path(255)),
            INDEX idx_status_code (status_code),
            INDEX idx_is_error (is_error),
            INDEX idx_created_at (created_at),
            INDEX idx_ip_address (ip_address),
            INDEX idx_duration (duration_ms),
            INDEX idx_method_path (method, path(255))

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    if ($conn->query($sql)) {
        echo "  ✅ Table 'api_request_logs' created successfully\n";
    } else {
        echo "  ❌ Error creating table: " . $conn->error . "\n";
        exit(1);
    }

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  Migration completed successfully\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

} catch (Exception $e) {
    echo "  ❌ Migration failed: " . $e->getMessage() . "\n\n";
    exit(1);
}
