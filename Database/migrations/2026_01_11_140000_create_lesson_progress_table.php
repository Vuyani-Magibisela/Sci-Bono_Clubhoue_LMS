<?php
/**
 * Migration: CreateLessonProgressTable
 * Created: 2026-01-11 14:00:00
 * Purpose: Track individual lesson completion and progress for enrolled users
 */

class CreateLessonProgressTable {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Run the migration
     */
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `lesson_progress` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `lesson_id` INT NOT NULL,
            `enrollment_id` INT NOT NULL COMMENT 'Link to course enrollment',
            `status` ENUM('not_started', 'in_progress', 'completed', 'skipped') NOT NULL DEFAULT 'not_started',
            `started_at` TIMESTAMP NULL,
            `completed_at` TIMESTAMP NULL,
            `last_accessed_at` TIMESTAMP NULL,
            `time_spent` INT NOT NULL DEFAULT 0 COMMENT 'Total time spent in minutes',
            `progress_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '0-100%',
            `video_progress` DECIMAL(5,2) NULL COMMENT 'Video watch percentage',
            `quiz_score` DECIMAL(5,2) NULL COMMENT 'Quiz score percentage',
            `quiz_attempts` INT NOT NULL DEFAULT 0,
            `quiz_passed` BOOLEAN NOT NULL DEFAULT FALSE,
            `assignment_submitted` BOOLEAN NOT NULL DEFAULT FALSE,
            `assignment_score` DECIMAL(5,2) NULL,
            `assignment_feedback` TEXT NULL,
            `completion_criteria_met` BOOLEAN NOT NULL DEFAULT FALSE,
            `notes` TEXT NULL COMMENT 'User notes for this lesson',
            `bookmarked` BOOLEAN NOT NULL DEFAULT FALSE,
            `rating` INT NULL COMMENT '1-5 star rating',
            `feedback` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE CASCADE,
            UNIQUE INDEX `idx_lesson_progress_unique` (`user_id`, `lesson_id`),
            INDEX `idx_lesson_progress_user` (`user_id`),
            INDEX `idx_lesson_progress_lesson` (`lesson_id`),
            INDEX `idx_lesson_progress_enrollment` (`enrollment_id`),
            INDEX `idx_lesson_progress_status` (`status`),
            INDEX `idx_lesson_progress_completed` (`completed_at`),
            INDEX `idx_lesson_progress_percentage` (`progress_percentage`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($sql);
    }

    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "DROP TABLE IF EXISTS `lesson_progress`";
        $this->conn->query($sql);
    }
}
