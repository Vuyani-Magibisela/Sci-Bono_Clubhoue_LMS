<?php
/**
 * Migration: CreateTokenBlacklistTable
 * Created: 2026-01-06 12:00:00
 * Purpose: Create token blacklist table for JWT logout functionality
 *
 * This table tracks blacklisted JWT tokens to prevent reuse after logout.
 * Tokens are automatically cleaned up after expiration.
 *
 * @package Database\Migrations
 * @since Phase 5 Week 1 Day 1
 */

class CreateTokenBlacklistTable {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Run the migration
     */
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `token_blacklist` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `token_jti` VARCHAR(255) NOT NULL UNIQUE COMMENT 'JWT Token ID (jti claim)',
            `user_id` INT NOT NULL COMMENT 'User who owned the token',
            `expires_at` DATETIME NOT NULL COMMENT 'Token expiration timestamp',
            `blacklisted_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'When token was blacklisted',
            `reason` VARCHAR(255) NULL COMMENT 'Reason for blacklisting (logout, password_change, etc)',
            `ip_address` VARCHAR(45) NULL COMMENT 'IP address when token was blacklisted',
            `user_agent` TEXT NULL COMMENT 'User agent when token was blacklisted',

            INDEX `idx_token_jti` (`token_jti`) COMMENT 'Fast lookup for token validation',
            INDEX `idx_user_id` (`user_id`) COMMENT 'Lookup all blacklisted tokens for a user',
            INDEX `idx_expires_at` (`expires_at`) COMMENT 'Fast cleanup of expired tokens',
            INDEX `idx_blacklisted_at` (`blacklisted_at`) COMMENT 'Audit trail queries',

            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Blacklisted JWT tokens for logout functionality'";

        if ($this->conn->query($sql) === TRUE) {
            echo "✅ Token blacklist table created successfully\n";
        } else {
            throw new Exception("Error creating token_blacklist table: " . $this->conn->error);
        }
    }

    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "DROP TABLE IF EXISTS `token_blacklist`";

        if ($this->conn->query($sql) === TRUE) {
            echo "✅ Token blacklist table dropped successfully\n";
        } else {
            throw new Exception("Error dropping token_blacklist table: " . $this->conn->error);
        }
    }
}
