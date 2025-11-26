<?php
/**
 * Migration: CreateEnrollmentsTable
 * Created: 2025-09-03 12:04:00
 */

class CreateEnrollmentsTable {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run the migration
     */
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `enrollments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `course_id` INT NOT NULL,
            `enrollment_date` DATE NOT NULL,
            `completion_date` DATE NULL,
            `status` ENUM('enrolled', 'active', 'completed', 'dropped', 'suspended') NOT NULL DEFAULT 'enrolled',
            `progress` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Progress percentage 0-100',
            `grade` DECIMAL(5,2) NULL COMMENT 'Final grade percentage',
            `grade_letter` VARCHAR(2) NULL,
            `certificate_issued` BOOLEAN NOT NULL DEFAULT FALSE,
            `certificate_url` VARCHAR(500) NULL,
            `certificate_issued_at` TIMESTAMP NULL,
            `last_accessed_at` TIMESTAMP NULL,
            `total_time_spent` INT NOT NULL DEFAULT 0 COMMENT 'Total time in minutes',
            `lessons_completed` INT NOT NULL DEFAULT 0,
            `total_lessons` INT NOT NULL DEFAULT 0,
            `assignments_completed` INT NOT NULL DEFAULT 0,
            `total_assignments` INT NOT NULL DEFAULT 0,
            `quizzes_passed` INT NOT NULL DEFAULT 0,
            `total_quizzes` INT NOT NULL DEFAULT 0,
            `attendance_percentage` DECIMAL(5,2) NULL,
            `engagement_score` DECIMAL(5,2) NULL COMMENT 'Calculated engagement score',
            `feedback` TEXT NULL,
            `instructor_notes` TEXT NULL,
            `payment_status` ENUM('pending', 'paid', 'partial', 'refunded', 'waived') NOT NULL DEFAULT 'waived',
            `payment_date` DATE NULL,
            `payment_amount` DECIMAL(10,2) NULL,
            `discount_applied` DECIMAL(5,2) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
            UNIQUE INDEX `idx_enrollments_unique` (`user_id`, `course_id`),
            INDEX `idx_enrollments_user` (`user_id`),
            INDEX `idx_enrollments_course` (`course_id`),
            INDEX `idx_enrollments_status` (`status`),
            INDEX `idx_enrollments_date` (`enrollment_date`),
            INDEX `idx_enrollments_progress` (`progress`),
            INDEX `idx_enrollments_completion` (`completion_date`),
            INDEX `idx_enrollments_payment` (`payment_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);
    }
    
    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "DROP TABLE IF EXISTS `enrollments`";
        $this->conn->query($sql);
    }
}