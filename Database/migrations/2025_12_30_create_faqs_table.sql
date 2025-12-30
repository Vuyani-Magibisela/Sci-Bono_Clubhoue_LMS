-- ================================================================
-- Migration: Create faqs table
-- Date: December 30, 2025
-- Phase: 4 Week 2 Day 1
-- Purpose: Migrate hardcoded FAQs to database
-- ================================================================

CREATE TABLE IF NOT EXISTS faqs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(100) NOT NULL COMMENT 'Category of FAQ (e.g., Registration, Programs, General)',
    question TEXT NOT NULL COMMENT 'The frequently asked question',
    answer TEXT NOT NULL COMMENT 'The answer to the question',
    order_number INT DEFAULT 0 COMMENT 'Display order within category',
    is_active BOOLEAN DEFAULT 1 COMMENT 'Whether this FAQ is active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_order (category, order_number),
    FULLTEXT INDEX ft_question_answer (question, answer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores frequently asked questions and answers';

-- ================================================================
-- Sample Data Migration from HolidayProgramModel::getFaqsForProgram()
-- This data will be inserted via seeder
-- ================================================================

-- INSERT INTO faqs (category, question, answer, order_number) VALUES
-- ('General', 'Do I need prior experience to participate?',
--  'No prior experience is necessary. Our workshops are designed for beginners, though those with experience will also benefit and can work on more advanced projects.',
--  1),
-- ('Registration', 'How do I register for a program?',
--  'You can register through our online portal. Simply create an account, select your desired program, and complete the registration form.',
--  1),
-- ('Programs', 'What should I bring to the program?',
--  'Please bring a notebook, pen/pencil, water bottle, and your enthusiasm! Lunch will be provided.',
--  1);
