<?php
/**
 * Migration: AddPasswordResetToUsers
 * Created: 2026-01-06 13:00:00
 * Purpose: Add password reset columns to users table
 *
 * @package Database\Migrations
 * @since Phase 5 Week 1 Day 5
 */

class AddPasswordResetToUsers {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Run the migration
     */
    public function up() {
        // Check if columns already exist
        $checkSql = "SHOW COLUMNS FROM users LIKE 'password_reset_token'";
        $result = $this->conn->query($checkSql);

        if ($result->num_rows > 0) {
            echo "⚠️  Password reset columns already exist, skipping migration\n";
            return;
        }

        $sql = "ALTER TABLE `users`
            ADD COLUMN `password_reset_token` VARCHAR(255) NULL,
            ADD COLUMN `password_reset_expires` TIMESTAMP NULL,
            ADD INDEX `idx_password_reset_token` (`password_reset_token`)";

        if ($this->conn->query($sql) === TRUE) {
            echo "✅ Password reset columns added successfully\n";
        } else {
            throw new Exception("Error adding password reset columns: " . $this->conn->error);
        }
    }

    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "ALTER TABLE `users`
            DROP INDEX `idx_password_reset_token`,
            DROP COLUMN `password_reset_expires`,
            DROP COLUMN `password_reset_token`";

        if ($this->conn->query($sql) === TRUE) {
            echo "✅ Password reset columns removed successfully\n";
        } else {
            throw new Exception("Error removing password reset columns: " . $this->conn->error);
        }
    }
}
