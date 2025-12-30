-- ================================================================
-- Migration: Create program_requirements table
-- Date: December 30, 2025
-- Phase: 4 Week 2 Day 1
-- Purpose: Migrate hardcoded project requirements to database
-- ================================================================

CREATE TABLE IF NOT EXISTS program_requirements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(100) NOT NULL COMMENT 'Category of requirement (e.g., General, Technical, Age)',
    requirement TEXT NOT NULL COMMENT 'The requirement description',
    order_number INT DEFAULT 0 COMMENT 'Display order within category',
    is_active BOOLEAN DEFAULT 1 COMMENT 'Whether this requirement is active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_order (category, order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores project and program requirements';

-- ================================================================
-- Sample Data Migration from HolidayProgramModel::getRequirementsForProgram()
-- This data will be inserted via seeder
-- ================================================================

-- INSERT INTO program_requirements (category, requirement, order_number) VALUES
-- ('Project Guidelines', 'All projects must address at least one UN Sustainable Development Goal', 1),
-- ('Project Guidelines', 'Projects must be completed by the end of the program', 2),
-- ('Project Guidelines', 'Each participant/team must prepare a brief presentation for the showcase', 3),
-- ('Project Guidelines', 'Projects should demonstrate application of skills learned during the program', 4);
