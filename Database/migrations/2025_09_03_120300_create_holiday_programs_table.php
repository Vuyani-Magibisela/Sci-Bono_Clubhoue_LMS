<?php
/**
 * Migration: CreateHolidayProgramsTable
 * Created: 2025-09-03 12:03:00
 */

class CreateHolidayProgramsTable {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Run the migration
     */
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS `holiday_programs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `program_type` ENUM('summer', 'winter', 'spring', 'special') NOT NULL DEFAULT 'summer',
            `start_date` DATE NOT NULL,
            `end_date` DATE NOT NULL,
            `registration_start` DATE NULL,
            `registration_deadline` DATE NULL,
            `age_min` INT NULL,
            `age_max` INT NULL,
            `max_participants` INT NOT NULL,
            `current_participants` INT NOT NULL DEFAULT 0,
            `waiting_list_count` INT NOT NULL DEFAULT 0,
            `fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `currency` VARCHAR(3) NOT NULL DEFAULT 'ZAR',
            `discount_available` BOOLEAN NOT NULL DEFAULT FALSE,
            `early_bird_discount` DECIMAL(5,2) NULL,
            `early_bird_deadline` DATE NULL,
            `requirements` JSON NULL,
            `what_to_bring` JSON NULL,
            `daily_schedule` JSON NULL,
            `activities` JSON NULL,
            `learning_outcomes` JSON NULL,
            `coordinators` JSON NULL,
            `location` VARCHAR(255) NULL,
            `venue_details` TEXT NULL,
            `transport_provided` BOOLEAN NOT NULL DEFAULT FALSE,
            `transport_details` TEXT NULL,
            `meals_provided` BOOLEAN NOT NULL DEFAULT FALSE,
            `meal_details` TEXT NULL,
            `special_requirements` TEXT NULL,
            `emergency_contact` VARCHAR(255) NULL,
            `status` ENUM('draft', 'published', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
            `featured` BOOLEAN NOT NULL DEFAULT FALSE,
            `rating` DECIMAL(3,2) NULL,
            `total_ratings` INT NOT NULL DEFAULT 0,
            `image_url` VARCHAR(500) NULL,
            `gallery` JSON NULL,
            `documents` JSON NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX `idx_programs_dates` (`start_date`, `end_date`),
            INDEX `idx_programs_registration` (`registration_deadline`),
            INDEX `idx_programs_status` (`status`),
            INDEX `idx_programs_featured` (`featured`),
            INDEX `idx_programs_type` (`program_type`),
            INDEX `idx_programs_age` (`age_min`, `age_max`),
            INDEX `idx_programs_name` (`name`),
            FULLTEXT INDEX `idx_programs_search` (`name`, `description`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);
    }
    
    /**
     * Reverse the migration
     */
    public function down() {
        $sql = "DROP TABLE IF EXISTS `holiday_programs`";
        $this->conn->query($sql);
    }
}