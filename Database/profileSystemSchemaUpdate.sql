-- Add these columns to holiday_program_attendees if they don't exist
ALTER TABLE `holiday_program_attendees` 
ADD COLUMN `last_login` datetime DEFAULT NULL AFTER `password`,
ADD COLUMN `profile_completed` tinyint(1) DEFAULT 0 AFTER `last_login`,
ADD COLUMN `email_verified` tinyint(1) DEFAULT 0 AFTER `profile_completed`;

-- Create profile access tokens table
CREATE TABLE `holiday_program_access_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attendee_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendee_id` (`attendee_id`),
  UNIQUE KEY `token` (`token`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `access_tokens_attendee_fk` FOREIGN KEY (`attendee_id`) 
    REFERENCES `holiday_program_attendees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better performance
ALTER TABLE `holiday_program_attendees` 
ADD INDEX `idx_email_password` (`email`, `password`),
ADD INDEX `idx_last_login` (`last_login`),
ADD INDEX `idx_updated_at` (`updated_at`);

-- =====================================================================================
-- HOLIDAY PROGRAM PROFILE SYSTEM - SAFE DATABASE SCHEMA UPDATES
-- =====================================================================================
-- 
-- This script safely updates the database schema for the Holiday Program Profile System
-- with comprehensive checks, backups, and rollback capabilities.
--
-- IMPORTANT: 
-- 1. Always backup your database before running this script
-- 2. Test on a development environment first
-- 3. Run during maintenance window
-- 4. Have rollback plan ready
--
-- Author: Holiday Program Profile System
-- Date: 2025-06-29
-- Version: 1.0
-- =====================================================================================

-- Enable strict mode for safety
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- Start transaction for atomic operations
START TRANSACTION;

-- =====================================================================================
-- SECTION 1: PRE-FLIGHT CHECKS
-- =====================================================================================

-- Check if we're connected to the right database
SELECT 
    DATABASE() as current_database,
    NOW() as update_timestamp,
    USER() as executing_user;

-- Verify that the holiday_program_attendees table exists
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'PASS: holiday_program_attendees table exists'
        ELSE 'FAIL: holiday_program_attendees table does not exist'
    END as table_check
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'holiday_program_attendees';

-- Check current table structure
DESCRIBE holiday_program_attendees;

-- Get current row count for reference
SELECT 
    COUNT(*) as current_attendee_count,
    'Before schema updates' as note
FROM holiday_program_attendees;

-- =====================================================================================
-- SECTION 2: BACKUP EXISTING DATA (Create backup table)
-- =====================================================================================

-- Create backup table with timestamp
SET @backup_table_name = CONCAT('holiday_program_attendees_backup_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s'));
SET @backup_sql = CONCAT('CREATE TABLE ', @backup_table_name, ' AS SELECT * FROM holiday_program_attendees');

PREPARE backup_stmt FROM @backup_sql;
EXECUTE backup_stmt;
DEALLOCATE PREPARE backup_stmt;

-- Verify backup was created successfully
SET @verify_backup_sql = CONCAT(
    'SELECT COUNT(*) as backup_count, "Backup table: ', @backup_table_name, '" as backup_table FROM ', @backup_table_name
);
PREPARE verify_backup_stmt FROM @verify_backup_sql;
EXECUTE verify_backup_stmt;
DEALLOCATE PREPARE verify_backup_stmt;

SELECT CONCAT('✓ Backup table created: ', @backup_table_name) as backup_status;

-- =====================================================================================
-- SECTION 3: SAFE COLUMN ADDITIONS
-- =====================================================================================

-- Add last_login column if it doesn't exist
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS: last_login column already exists'
        ELSE 'ADDING: last_login column'
    END as last_login_status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'holiday_program_attendees' 
AND column_name = 'last_login';

-- Conditional addition of last_login column
SET @sql = (
    SELECT CASE 
        WHEN COUNT(*) = 0 THEN 
            'ALTER TABLE holiday_program_attendees ADD COLUMN last_login datetime DEFAULT NULL AFTER password'
        ELSE 
            'SELECT "last_login column already exists" as result'
    END
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'holiday_program_attendees' 
    AND column_name = 'last_login'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add profile_completed column if it doesn't exist
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS: profile_completed column already exists'
        ELSE 'ADDING: profile_completed column'
    END as profile_completed_status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'holiday_program_attendees' 
AND column_name = 'profile_completed';

SET @sql = (
    SELECT CASE 
        WHEN COUNT(*) = 0 THEN 
            'ALTER TABLE holiday_program_attendees ADD COLUMN profile_completed tinyint(1) DEFAULT 0 AFTER last_login'
        ELSE 
            'SELECT "profile_completed column already exists" as result'
    END
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'holiday_program_attendees' 
    AND column_name = 'profile_completed'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add email_verified column if it doesn't exist
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS: email_verified column already exists'
        ELSE 'ADDING: email_verified column'
    END as email_verified_status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'holiday_program_attendees' 
AND column_name = 'email_verified';

SET @sql = (
    SELECT CASE 
        WHEN COUNT(*) = 0 THEN 
            'ALTER TABLE holiday_program_attendees ADD COLUMN email_verified tinyint(1) DEFAULT 0 AFTER profile_completed'
        ELSE 
            'SELECT "email_verified column already exists" as result'
    END
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'holiday_program_attendees' 
    AND column_name = 'email_verified'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================================================
-- SECTION 4: CREATE ACCESS TOKENS TABLE
-- =====================================================================================

-- Check if access tokens table already exists
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS: holiday_program_access_tokens table already exists'
        ELSE 'CREATING: holiday_program_access_tokens table'
    END as access_tokens_status
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'holiday_program_access_tokens';

-- Create access tokens table if it doesn't exist
CREATE TABLE IF NOT EXISTS `holiday_program_access_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attendee_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL COMMENT 'Admin user who created the token',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address when token was created',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `attendee_id` (`attendee_id`),
  KEY `expires_at` (`expires_at`),
  KEY `idx_token_valid` (`token`, `expires_at`, `used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Secure tokens for holiday program profile access';

-- Add foreign key constraint if it doesn't exist
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.key_column_usage 
    WHERE table_schema = DATABASE() 
    AND table_name = 'holiday_program_access_tokens' 
    AND constraint_name = 'access_tokens_attendee_fk'
);

SET @sql = CASE 
    WHEN @fk_exists = 0 THEN 
        'ALTER TABLE holiday_program_access_tokens 
         ADD CONSTRAINT access_tokens_attendee_fk 
         FOREIGN KEY (attendee_id) REFERENCES holiday_program_attendees (id) 
         ON DELETE CASCADE ON UPDATE CASCADE'
    ELSE 
        'SELECT "Foreign key constraint already exists" as result'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================================================
-- SECTION 5: SAFE INDEX ADDITIONS
-- =====================================================================================

-- Add indexes for better performance (only if they don't exist)

-- Check and add idx_email_password index
SET @index_exists = (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'holiday_program_attendees' 
    AND index_name = 'idx_email_password'
);

SET @sql = CASE 
    WHEN @index_exists = 0 THEN 
        'ALTER TABLE holiday_program_attendees ADD INDEX idx_email_password (email, password)'
    ELSE 
        'SELECT "idx_email_password index already exists" as result'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add idx_last_login index
SET @index_exists = (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'holiday_program_attendees' 
    AND index_name = 'idx_last_login'
);

SET @sql = CASE 
    WHEN @index_exists = 0 THEN 
        'ALTER TABLE holiday_program_attendees ADD INDEX idx_last_login (last_login)'
    ELSE 
        'SELECT "idx_last_login index already exists" as result'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add idx_profile_status index
SET @index_exists = (
    SELECT COUNT(*) 
    FROM information_schema.statistics 
    WHERE table_schema = DATABASE() 
    AND table_name = 'holiday_program_attendees' 
    AND index_name = 'idx_profile_status'
);

SET @sql = CASE 
    WHEN @index_exists = 0 THEN 
        'ALTER TABLE holiday_program_attendees ADD INDEX idx_profile_status (profile_completed, email_verified)'
    ELSE 
        'SELECT "idx_profile_status index already exists" as result'
END;

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================================================
-- SECTION 6: DATA MIGRATION AND CLEANUP
-- =====================================================================================

-- Set default values for existing records
UPDATE holiday_program_attendees 
SET profile_completed = CASE 
    WHEN password IS NOT NULL AND password != '' THEN 1 
    ELSE 0 
END,
email_verified = CASE 
    WHEN password IS NOT NULL AND password != '' THEN 1 
    ELSE 0 
END
WHERE profile_completed IS NULL OR email_verified IS NULL;

-- Get count of updated records
SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN profile_completed = 1 THEN 1 ELSE 0 END) as profiles_completed,
    SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as emails_verified,
    SUM(CASE WHEN password IS NOT NULL AND password != '' THEN 1 ELSE 0 END) as has_password
FROM holiday_program_attendees;

-- =====================================================================================
-- SECTION 7: CREATE AUDIT LOG TABLE (Optional but Recommended)
-- =====================================================================================

-- Create audit log table for profile changes if it doesn't exist
CREATE TABLE IF NOT EXISTS `holiday_program_profile_audit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attendee_id` int NOT NULL,
  `admin_user_id` int DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `field_name` varchar(100) DEFAULT NULL,
  `old_value` text,
  `new_value` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `attendee_id` (`attendee_id`),
  KEY `admin_user_id` (`admin_user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Audit trail for holiday program profile changes';

-- =====================================================================================
-- SECTION 8: VERIFICATION AND VALIDATION
-- =====================================================================================

-- Verify all changes were applied successfully
SELECT '=== SCHEMA UPDATE VERIFICATION ===' as status;

-- Check new columns exist
SELECT 
    CASE 
        WHEN COUNT(*) = 3 THEN 'PASS: All new columns added successfully'
        ELSE CONCAT('FAIL: Only ', COUNT(*), ' out of 3 columns added')
    END as column_check
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'holiday_program_attendees' 
AND column_name IN ('last_login', 'profile_completed', 'email_verified');

-- Check access tokens table exists
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'PASS: Access tokens table created successfully'
        ELSE 'FAIL: Access tokens table not found'
    END as tokens_table_check
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'holiday_program_access_tokens';

-- Check indexes were created
SELECT 
    CASE 
        WHEN COUNT(*) >= 3 THEN 'PASS: Performance indexes created'
        ELSE CONCAT('WARNING: Only ', COUNT(*), ' indexes found')
    END as index_check
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'holiday_program_attendees' 
AND index_name IN ('idx_email_password', 'idx_last_login', 'idx_profile_status');

-- Final data integrity check
SELECT 
    COUNT(*) as total_attendees,
    COUNT(CASE WHEN password IS NOT NULL THEN 1 END) as with_password,
    COUNT(CASE WHEN profile_completed = 1 THEN 1 END) as profiles_completed,
    COUNT(CASE WHEN email_verified = 1 THEN 1 END) as emails_verified,
    'Data integrity check' as note
FROM holiday_program_attendees;

-- Show updated table structure
SHOW CREATE TABLE holiday_program_attendees;

-- =====================================================================================
-- SECTION 9: COMMIT OR ROLLBACK
-- =====================================================================================

-- If you've reached this point without errors, the schema update was successful
SELECT '=== SCHEMA UPDATE COMPLETED SUCCESSFULLY ===' as final_status;
SELECT CONCAT('Backup table created: ', @backup_table_name) as backup_info;
SELECT 'You can now COMMIT the transaction' as next_step;

-- COMMIT; -- Uncomment this line to commit the changes

-- =====================================================================================
-- ROLLBACK INSTRUCTIONS (Run only if something went wrong)
-- =====================================================================================

/*
-- If you need to rollback the changes, run these commands:

ROLLBACK;

-- Drop the new table if created
DROP TABLE IF EXISTS holiday_program_access_tokens;
DROP TABLE IF EXISTS holiday_program_profile_audit;

-- Remove added columns (replace with actual column names if different)
ALTER TABLE holiday_program_attendees DROP COLUMN IF EXISTS last_login;
ALTER TABLE holiday_program_attendees DROP COLUMN IF EXISTS profile_completed;
ALTER TABLE holiday_program_attendees DROP COLUMN IF EXISTS email_verified;

-- Remove indexes
ALTER TABLE holiday_program_attendees DROP INDEX IF EXISTS idx_email_password;
ALTER TABLE holiday_program_attendees DROP INDEX IF EXISTS idx_last_login;
ALTER TABLE holiday_program_attendees DROP INDEX IF EXISTS idx_profile_status;

-- Restore from backup if needed (replace backup_table_name with actual name)
-- RENAME TABLE holiday_program_attendees TO holiday_program_attendees_failed;
-- RENAME TABLE [backup_table_name] TO holiday_program_attendees;

SELECT 'Rollback completed' as rollback_status;
*/

-- =====================================================================================
-- SECTION 10: POST-UPDATE CLEANUP RECOMMENDATIONS
-- =====================================================================================

/*
-- After confirming everything works correctly, you can clean up backup tables:
-- (Run this manually after testing, not part of the transaction)

-- List all backup tables
SELECT 
    table_name,
    create_time
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name LIKE 'holiday_program_attendees_backup_%'
ORDER BY create_time DESC;

-- Drop old backup tables (after confirming new system works)
-- DROP TABLE holiday_program_attendees_backup_[timestamp];
*/

-- =====================================================================================
-- USAGE INSTRUCTIONS:
-- =====================================================================================

/*
1. BEFORE RUNNING:
   - Create a full database backup
   - Test on development environment
   - Schedule maintenance window
   - Inform stakeholders

2. TO RUN THIS SCRIPT:
   - Execute the entire script in one session
   - Monitor for any error messages
   - Review all verification checks
   - Only COMMIT if all checks pass

3. AFTER RUNNING:
   - Test the profile system functionality
   - Monitor application logs
   - Keep backup tables for a few days
   - Update application code to use new features

4. IF PROBLEMS OCCUR:
   - Run ROLLBACK instead of COMMIT
   - Use the rollback instructions in Section 9
   - Restore from database backup if needed
   - Contact development team

5. SUCCESS CRITERIA:
   - All verification checks show "PASS"
   - No error messages during execution
   - Profile system works as expected
   - Performance is maintained or improved
*/