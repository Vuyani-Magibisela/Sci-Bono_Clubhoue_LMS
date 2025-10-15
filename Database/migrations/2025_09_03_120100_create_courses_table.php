<?php
/**
 * Migration: CreateCoursesTable
 * Created: 2025-09-03 12:01:00
 */

class CreateCoursesTable {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run the migration
     */
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `courses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `content` LONGTEXT NULL,
            `instructor_id` INT NOT NULL,
            `duration` INT NULL COMMENT 'Duration in hours',
            `level` ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
            `category` VARCHAR(100) NULL,
            `tags` JSON NULL,
            `prerequisites` JSON NULL,
            `learning_objectives` JSON NULL,
            `course_image` VARCHAR(255) NULL,
            `video_url` VARCHAR(500) NULL,
            `resources` JSON NULL,
            `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `currency` VARCHAR(3) NOT NULL DEFAULT 'ZAR',
            `max_participants` INT NULL,
            `current_participants` INT NOT NULL DEFAULT 0,
            `start_date` DATE NULL,
            `end_date` DATE NULL,
            `enrollment_deadline` DATE NULL,
            `certificate_template` VARCHAR(255) NULL,
            `active` BOOLEAN NOT NULL DEFAULT TRUE,
            `featured` BOOLEAN NOT NULL DEFAULT FALSE,
            `rating` DECIMAL(3,2) NULL,
            `total_ratings` INT NOT NULL DEFAULT 0,
            `completion_rate` DECIMAL(5,2) NULL,
            `views` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_courses_instructor` (`instructor_id`),
            INDEX `idx_courses_level` (`level`),
            INDEX `idx_courses_category` (`category`),
            INDEX `idx_courses_active` (`active`),
            INDEX `idx_courses_featured` (`featured`),
            INDEX `idx_courses_dates` (`start_date`, `end_date`),
            INDEX `idx_courses_title` (`title`),
            FULLTEXT INDEX `idx_courses_search` (`title`, `description`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);
    }
    
    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "DROP TABLE IF EXISTS `courses`";
        $this->conn->query($sql);
    }
}