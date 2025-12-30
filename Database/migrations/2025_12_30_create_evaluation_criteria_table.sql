-- ================================================================
-- Migration: Create evaluation_criteria table
-- Date: December 30, 2025
-- Phase: 4 Week 2 Day 1
-- Purpose: Migrate hardcoded evaluation criteria to database
-- ================================================================

CREATE TABLE IF NOT EXISTS evaluation_criteria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'Criterion name (e.g., Technical Execution)',
    description TEXT COMMENT 'Description of what this criterion evaluates',
    points INT NOT NULL DEFAULT 0 COMMENT 'Maximum points for this criterion',
    category VARCHAR(100) COMMENT 'Category for grouping criteria',
    order_number INT DEFAULT 0 COMMENT 'Display order within category',
    is_active BOOLEAN DEFAULT 1 COMMENT 'Whether this criterion is active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_order (category, order_number),
    UNIQUE KEY unique_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores evaluation criteria for projects and programs';

-- ================================================================
-- Sample Data Migration from HolidayProgramModel::getCriteriaForProgram()
-- This data will be inserted via seeder
-- ================================================================

-- INSERT INTO evaluation_criteria (name, description, category, order_number, points) VALUES
-- ('Technical Execution', 'Quality of technical skills demonstrated', 'Project Evaluation', 1, 20),
-- ('Creativity', 'Original ideas and creative approach', 'Project Evaluation', 2, 20),
-- ('Message', 'Clear connection to SDGs and effective communication of message', 'Project Evaluation', 3, 20),
-- ('Completion', 'Level of completion and polish', 'Project Evaluation', 4, 20),
-- ('Presentation', 'Quality of showcase presentation', 'Project Evaluation', 5, 20);
