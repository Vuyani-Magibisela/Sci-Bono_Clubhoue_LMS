<?php
/**
 * Migration: CreateLessonsTable
 * Created: 2025-09-03 12:05:00
 */

class CreateLessonsTable {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run the migration
     */
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `lessons` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `content` LONGTEXT NULL,
            `lesson_type` ENUM('text', 'video', 'interactive', 'quiz', 'assignment', 'live') NOT NULL DEFAULT 'text',
            `order_number` INT NOT NULL,
            `duration` INT NULL COMMENT 'Duration in minutes',
            `video_url` VARCHAR(500) NULL,
            `video_duration` INT NULL,
            `materials` JSON NULL,
            `attachments` JSON NULL,
            `prerequisites` JSON NULL,
            `learning_objectives` JSON NULL,
            `instructions` TEXT NULL,
            `quiz_questions` JSON NULL,
            `assignment_details` JSON NULL,
            `is_required` BOOLEAN NOT NULL DEFAULT TRUE,
            `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
            `is_free_preview` BOOLEAN NOT NULL DEFAULT FALSE,
            `completion_criteria` JSON NULL,
            `points_awarded` INT NOT NULL DEFAULT 0,
            `difficulty_level` ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
            `estimated_time` INT NULL COMMENT 'Estimated completion time in minutes',
            `views` INT NOT NULL DEFAULT 0,
            `average_completion_time` INT NULL,
            `completion_rate` DECIMAL(5,2) NULL,
            `rating` DECIMAL(3,2) NULL,
            `total_ratings` INT NOT NULL DEFAULT 0,
            `feedback_summary` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
            INDEX `idx_lessons_course` (`course_id`),
            INDEX `idx_lessons_order` (`course_id`, `order_number`),
            INDEX `idx_lessons_type` (`lesson_type`),
            INDEX `idx_lessons_active` (`is_active`),
            INDEX `idx_lessons_required` (`is_required`),
            INDEX `idx_lessons_free` (`is_free_preview`),
            INDEX `idx_lessons_title` (`title`),
            FULLTEXT INDEX `idx_lessons_search` (`title`, `description`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);
    }
    
    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "DROP TABLE IF EXISTS `lessons`";
        $this->conn->query($sql);
    }
}