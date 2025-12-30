<?php
/**
 * Migration: CreateAttendanceTable
 * Created: 2025-09-03 12:02:00
 */

class CreateAttendanceTable {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run the migration
     */
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `attendance` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `course_id` INT NULL,
            `program_id` INT NULL,
            `activity_type` ENUM('general', 'course', 'program', 'event') NOT NULL DEFAULT 'general',
            `sign_in_time` TIMESTAMP NOT NULL,
            `sign_out_time` TIMESTAMP NULL,
            `sign_in_status` ENUM('signedIn', 'signedOut', 'absent', 'late') NOT NULL DEFAULT 'signedIn',
            `sign_in_method` ENUM('manual', 'qr_code', 'nfc', 'biometric') NOT NULL DEFAULT 'manual',
            `location` VARCHAR(255) NULL,
            `ip_address` VARCHAR(45) NULL,
            `device_info` JSON NULL,
            `notes` TEXT NULL,
            `duration_minutes` INT GENERATED ALWAYS AS (
                CASE 
                    WHEN sign_out_time IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, sign_in_time, sign_out_time) 
                    ELSE NULL 
                END
            ) STORED,
            `verified_by` INT NULL,
            `verification_status` ENUM('pending', 'verified', 'disputed') NOT NULL DEFAULT 'verified',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            INDEX `idx_attendance_user` (`user_id`),
            INDEX `idx_attendance_date` (`sign_in_time`),
            INDEX `idx_attendance_status` (`sign_in_status`),
            INDEX `idx_attendance_course` (`course_id`),
            INDEX `idx_attendance_program` (`program_id`),
            INDEX `idx_attendance_activity` (`activity_type`),
            INDEX `idx_attendance_user_date` (`user_id`, `sign_in_time`),
            UNIQUE INDEX `idx_attendance_unique_signin` (`user_id`, `sign_in_time`, `activity_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);
    }
    
    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "DROP TABLE IF EXISTS `attendance`";
        $this->conn->query($sql);
    }
}