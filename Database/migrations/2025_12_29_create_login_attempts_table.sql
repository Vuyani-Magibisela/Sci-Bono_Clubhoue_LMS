-- Migration: Create login_attempts table for account lockout mechanism
-- Phase 4 Week 1 Day 1 - Fix failing authentication tests
-- Created: 2025-12-29

DROP TABLE IF EXISTS login_attempts;

CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL COMMENT 'Email or username used in login attempt',
    failed_attempts INT DEFAULT 0 COMMENT 'Number of consecutive failed attempts',
    last_attempt DATETIME NULL COMMENT 'Timestamp of last failed attempt',
    locked_until DATETIME NULL COMMENT 'Account locked until this timestamp',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_identifier (identifier),
    INDEX idx_locked_until (locked_until),
    INDEX idx_last_attempt (last_attempt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks failed login attempts and account lockouts for security';
