<?php
/**
 * Migration: CreateUsersTable
 * Created: 2025-09-03 12:00:00
 */

class CreateUsersTable {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run the migration
     */
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(255) NOT NULL UNIQUE,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `surname` VARCHAR(100) NOT NULL,
            `user_type` ENUM('admin', 'mentor', 'member', 'student') NOT NULL DEFAULT 'student',
            `phone` VARCHAR(20) NULL,
            `address` TEXT NULL,
            `school` VARCHAR(255) NULL,
            `grade` INT NULL,
            `bio` TEXT NULL,
            `profile_image` VARCHAR(255) NULL,
            `active` BOOLEAN NOT NULL DEFAULT TRUE,
            `email_verified` BOOLEAN NOT NULL DEFAULT FALSE,
            `email_verified_at` TIMESTAMP NULL,
            `password_reset_token` VARCHAR(255) NULL,
            `password_reset_expires` TIMESTAMP NULL,
            `last_login_at` TIMESTAMP NULL,
            `login_attempts` INT NOT NULL DEFAULT 0,
            `locked_until` TIMESTAMP NULL,
            `preferences` JSON NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX `idx_users_email` (`email`),
            INDEX `idx_users_username` (`username`),
            INDEX `idx_users_type` (`user_type`),
            INDEX `idx_users_active` (`active`),
            INDEX `idx_users_school_grade` (`school`, `grade`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);
    }
    
    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "DROP TABLE IF EXISTS `users`";
        $this->conn->query($sql);
    }
}