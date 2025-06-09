-- Holiday Program Status Logging Migration
-- Complete Database Migration for Holiday Program Status Management
-- Run this script to add all required tables, triggers, and functions

-- First, check if we need to create the status log table
CREATE TABLE IF NOT EXISTS `holiday_program_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `old_status` tinyint(1) NOT NULL DEFAULT 0,
  `new_status` tinyint(1) NOT NULL DEFAULT 0,
  `change_reason` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_program_id` (`program_id`),
  KEY `idx_changed_by` (`changed_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `status_log_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `status_log_user_fk` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add automation settings columns to holiday_programs table if they don't exist
SET @sql = 'ALTER TABLE `holiday_programs` 
ADD COLUMN `auto_close_on_capacity` tinyint(1) NOT NULL DEFAULT 0 COMMENT "Auto-close registration when capacity is reached"';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE table_name = 'holiday_programs' 
    AND column_name = 'auto_close_on_capacity' 
    AND table_schema = DATABASE()) = 0, @sql, 'SELECT "Column auto_close_on_capacity already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE `holiday_programs` 
ADD COLUMN `auto_close_on_date` tinyint(1) NOT NULL DEFAULT 0 COMMENT "Auto-close registration on deadline"';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE table_name = 'holiday_programs' 
    AND column_name = 'auto_close_on_date' 
    AND table_schema = DATABASE()) = 0, @sql, 'SELECT "Column auto_close_on_date already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE `holiday_programs` 
ADD COLUMN `registration_deadline` datetime DEFAULT NULL COMMENT "Deadline for registration"';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE table_name = 'holiday_programs' 
    AND column_name = 'registration_deadline' 
    AND table_schema = DATABASE()) = 0, @sql, 'SELECT "Column registration_deadline already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE `holiday_programs` 
ADD COLUMN `notification_settings` json DEFAULT NULL COMMENT "JSON settings for notifications"';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE table_name = 'holiday_programs' 
    AND column_name = 'notification_settings' 
    AND table_schema = DATABASE()) = 0, @sql, 'SELECT "Column notification_settings already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create indexes for better performance on status queries if they don't exist
CREATE INDEX IF NOT EXISTS `idx_registration_open` ON `holiday_programs` (`registration_open`);
CREATE INDEX IF NOT EXISTS `idx_updated_at` ON `holiday_programs` (`updated_at`);
CREATE INDEX IF NOT EXISTS `idx_attendee_status` ON `holiday_program_attendees` (`status`);
CREATE INDEX IF NOT EXISTS `idx_attendee_mentor` ON `holiday_program_attendees` (`mentor_registration`);
CREATE INDEX IF NOT EXISTS `idx_program_dates` ON `holiday_programs` (`start_date`, `end_date`);

-- Create view for admin dashboard statistics
DROP VIEW IF EXISTS `holiday_program_dashboard_stats`;
CREATE VIEW `holiday_program_dashboard_stats` AS
SELECT 
    p.id as program_id,
    p.term,
    p.title,
    p.registration_open,
    p.max_participants,
    p.auto_close_on_capacity,
    p.auto_close_on_date,
    p.registration_deadline,
    p.created_at,
    p.updated_at,
    COUNT(a.id) as total_registrations,
    COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_registrations,
    COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_registrations,
    COUNT(CASE WHEN a.mentor_registration = 1 THEN 1 END) as mentor_applications,
    COUNT(CASE WHEN a.mentor_registration = 0 THEN 1 END) as member_registrations,
    CASE 
        WHEN p.max_participants > 0 THEN ROUND((COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) / p.max_participants) * 100, 1)
        ELSE 0 
    END as capacity_percentage,
    CASE 
        WHEN COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) >= p.max_participants THEN 'full'
        WHEN COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) >= (p.max_participants * 0.9) THEN 'nearly_full'
        WHEN COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) >= (p.max_participants * 0.75) THEN 'filling_up'
        ELSE 'available'
    END as capacity_status
FROM holiday_programs p
LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
GROUP BY p.id, p.term, p.title, p.registration_open, p.max_participants, 
         p.auto_close_on_capacity, p.auto_close_on_date, p.registration_deadline,
         p.created_at, p.updated_at;

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS `holiday_program_status_change_log`;
DROP TRIGGER IF EXISTS `holiday_program_deadline_check`;
DROP TRIGGER IF EXISTS `holiday_program_capacity_check`;

-- Create triggers for automatic status logging
DELIMITER //

CREATE TRIGGER `holiday_program_status_change_log` 
AFTER UPDATE ON `holiday_programs`
FOR EACH ROW 
BEGIN
    -- Log status changes
    IF OLD.registration_open != NEW.registration_open THEN
        INSERT INTO holiday_program_status_log 
        (program_id, changed_by, old_status, new_status, change_reason, ip_address, created_at)
        VALUES 
        (NEW.id, 
         COALESCE(@current_user_id, NULL), 
         OLD.registration_open, 
         NEW.registration_open, 
         CASE 
             WHEN NEW.registration_open = 1 THEN 'Registration opened'
             ELSE 'Registration closed'
         END,
         COALESCE(@user_ip, 'system'), 
         NOW());
    END IF;
END//

CREATE TRIGGER `holiday_program_capacity_check`
AFTER INSERT ON `holiday_program_attendees`
FOR EACH ROW 
BEGIN
    DECLARE v_confirmed_count INT DEFAULT 0;
    DECLARE v_max_participants INT DEFAULT 0;
    DECLARE v_auto_close TINYINT DEFAULT 0;
    DECLARE v_registration_open TINYINT DEFAULT 0;
    
    -- Get program details
    SELECT max_participants, auto_close_on_capacity, registration_open
    INTO v_max_participants, v_auto_close, v_registration_open
    FROM holiday_programs 
    WHERE id = NEW.program_id;
    
    -- Count confirmed attendees
    SELECT COUNT(*) INTO v_confirmed_count
    FROM holiday_program_attendees 
    WHERE program_id = NEW.program_id AND status = 'confirmed';
    
    -- Auto-close if capacity reached and auto-close is enabled
    IF v_auto_close = 1 AND v_registration_open = 1 AND v_confirmed_count >= v_max_participants THEN
        UPDATE holiday_programs 
        SET registration_open = 0, updated_at = NOW()
        WHERE id = NEW.program_id;
        
        INSERT INTO holiday_program_status_log 
        (program_id, old_status, new_status, change_reason, ip_address, created_at)
        VALUES 
        (NEW.program_id, 1, 0, 'Auto-closed: capacity reached', 'system', NOW());
    END IF;
END//

CREATE TRIGGER `holiday_program_capacity_check_update`
AFTER UPDATE ON `holiday_program_attendees`
FOR EACH ROW 
BEGIN
    DECLARE v_confirmed_count INT DEFAULT 0;
    DECLARE v_max_participants INT DEFAULT 0;
    DECLARE v_auto_close TINYINT DEFAULT 0;
    DECLARE v_registration_open TINYINT DEFAULT 0;
    
    -- Only check if status changed to confirmed
    IF OLD.status != 'confirmed' AND NEW.status = 'confirmed' THEN
        -- Get program details
        SELECT max_participants, auto_close_on_capacity, registration_open
        INTO v_max_participants, v_auto_close, v_registration_open
        FROM holiday_programs 
        WHERE id = NEW.program_id;
        
        -- Count confirmed attendees
        SELECT COUNT(*) INTO v_confirmed_count
        FROM holiday_program_attendees 
        WHERE program_id = NEW.program_id AND status = 'confirmed';
        
        -- Auto-close if capacity reached and auto-close is enabled
        IF v_auto_close = 1 AND v_registration_open = 1 AND v_confirmed_count >= v_max_participants THEN
            UPDATE holiday_programs 
            SET registration_open = 0, updated_at = NOW()
            WHERE id = NEW.program_id;
            
            INSERT INTO holiday_program_status_log 
            (program_id, old_status, new_status, change_reason, ip_address, created_at)
            VALUES 
            (NEW.program_id, 1, 0, 'Auto-closed: capacity reached', 'system', NOW());
        END IF;
    END IF;
END//

DELIMITER ;

-- Drop existing stored procedures if they exist
DROP PROCEDURE IF EXISTS `UpdateProgramStatus`;
DROP PROCEDURE IF EXISTS `CheckRegistrationDeadlines`;
DROP PROCEDURE IF EXISTS `GetProgramStatistics`;

-- Create stored procedure for manual status updates with logging
DELIMITER //

CREATE PROCEDURE `UpdateProgramStatus`(
    IN p_program_id INT,
    IN p_new_status TINYINT,
    IN p_user_id INT,
    IN p_reason VARCHAR(255),
    IN p_ip_address VARCHAR(45)
)
BEGIN
    DECLARE v_old_status TINYINT DEFAULT 0;
    DECLARE v_program_exists INT DEFAULT 0;
    DECLARE exit handler for sqlexception
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Check if program exists and get current status
    SELECT registration_open, 1 INTO v_old_status, v_program_exists
    FROM holiday_programs 
    WHERE id = p_program_id;
    
    IF v_program_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Program not found';
    END IF;
    
    -- Only update if status is actually changing
    IF v_old_status != p_new_status THEN
        -- Set session variables for trigger
        SET @current_user_id = p_user_id;
        SET @user_ip = p_ip_address;
        
        -- Update the program status
        UPDATE holiday_programs 
        SET registration_open = p_new_status,
            updated_at = NOW()
        WHERE id = p_program_id;
        
        -- Manual log entry with additional details
        INSERT INTO holiday_program_status_log 
        (program_id, changed_by, old_status, new_status, change_reason, ip_address, created_at)
        VALUES 
        (p_program_id, p_user_id, v_old_status, p_new_status, p_reason, p_ip_address, NOW());
        
        -- Clear the session variables
        SET @current_user_id = NULL;
        SET @user_ip = NULL;
        
        SELECT 'success' as result, 'Status updated successfully' as message, v_old_status as old_status, p_new_status as new_status;
    ELSE
        SELECT 'no_change' as result, 'Status is already set to the requested value' as message, v_old_status as old_status, p_new_status as new_status;
    END IF;
    
    COMMIT;
END//

CREATE PROCEDURE `CheckRegistrationDeadlines`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_program_id INT;
    DECLARE deadline_cursor CURSOR FOR 
        SELECT id FROM holiday_programs 
        WHERE auto_close_on_date = 1 
          AND registration_open = 1 
          AND registration_deadline IS NOT NULL 
          AND NOW() >= registration_deadline;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN deadline_cursor;
    
    deadline_loop: LOOP
        FETCH deadline_cursor INTO v_program_id;
        IF done THEN
            LEAVE deadline_loop;
        END IF;
        
        -- Close registration
        UPDATE holiday_programs 
        SET registration_open = 0, updated_at = NOW()
        WHERE id = v_program_id;
        
        -- Log the closure
        INSERT INTO holiday_program_status_log 
        (program_id, old_status, new_status, change_reason, ip_address, created_at)
        SELECT 
            id, 1, 0, 
            CONCAT('Auto-closed: deadline reached (', registration_deadline, ')'), 
            'system', 
            NOW()
        FROM holiday_programs 
        WHERE id = v_program_id;
        
    END LOOP;
    
    CLOSE deadline_cursor;
    
    SELECT ROW_COUNT() as programs_closed;
END//

CREATE PROCEDURE `GetProgramStatistics`(IN p_program_id INT)
BEGIN
    SELECT 
        p.id,
        p.term,
        p.title,
        p.registration_open,
        p.max_participants,
        COUNT(a.id) as total_registrations,
        COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_registrations,
        COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_registrations,
        COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) as cancelled_registrations,
        COUNT(CASE WHEN a.mentor_registration = 1 THEN 1 END) as mentor_applications,
        COUNT(CASE WHEN a.mentor_registration = 0 THEN 1 END) as member_registrations,
        CASE 
            WHEN p.max_participants > 0 THEN 
                ROUND((COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) / p.max_participants) * 100, 1)
            ELSE 0 
        END as capacity_percentage,
        (p.max_participants - COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END)) as spots_remaining
    FROM holiday_programs p
    LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
    WHERE p.id = p_program_id
    GROUP BY p.id;
END//

DELIMITER ;

-- Drop existing functions if they exist
DROP FUNCTION IF EXISTS `GetProgramCapacityStatus`;
DROP FUNCTION IF EXISTS `GetProgramCapacityPercentage`;

-- Create function to check program capacity status
DELIMITER //

CREATE FUNCTION `GetProgramCapacityStatus`(p_program_id INT)
RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_confirmed_count INT DEFAULT 0;
    DECLARE v_max_participants INT DEFAULT 0;
    DECLARE v_percentage DECIMAL(5,2) DEFAULT 0;
    
    SELECT 
        COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END),
        p.max_participants
    INTO v_confirmed_count, v_max_participants
    FROM holiday_programs p
    LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
    WHERE p.id = p_program_id
    GROUP BY p.id, p.max_participants;
    
    IF v_max_participants > 0 THEN
        SET v_percentage = (v_confirmed_count / v_max_participants) * 100;
        
        CASE 
            WHEN v_confirmed_count >= v_max_participants THEN
                RETURN 'full';
            WHEN v_percentage >= 90 THEN
                RETURN 'nearly_full';
            WHEN v_percentage >= 75 THEN
                RETURN 'filling_up';
            ELSE
                RETURN 'available';
        END CASE;
    ELSE
        RETURN 'unlimited';
    END IF;
END//

CREATE FUNCTION `GetProgramCapacityPercentage`(p_program_id INT)
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_confirmed_count INT DEFAULT 0;
    DECLARE v_max_participants INT DEFAULT 0;
    
    SELECT 
        COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END),
        p.max_participants
    INTO v_confirmed_count, v_max_participants
    FROM holiday_programs p
    LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
    WHERE p.id = p_program_id
    GROUP BY p.id, p.max_participants;
    
    IF v_max_participants > 0 THEN
        RETURN ROUND((v_confirmed_count / v_max_participants) * 100, 2);
    ELSE
        RETURN 0;
    END IF;
END//

DELIMITER ;

-- Create notification preferences table
CREATE TABLE IF NOT EXISTS `holiday_program_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `notification_type` enum('status_change','capacity_warning','deadline_reminder','registration_received') NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `recipient_emails` text DEFAULT NULL,
  `last_sent` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_program_notification` (`program_id`, `notification_type`),
  KEY `idx_program_notification` (`program_id`, `notification_type`),
  CONSTRAINT `notification_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create audit trail table for better tracking
CREATE TABLE IF NOT EXISTS `holiday_program_audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_program_audit` (`program_id`),
  KEY `idx_user_audit` (`user_id`),
  KEY `idx_action_audit` (`action`),
  KEY `idx_created_audit` (`created_at`),
  CONSTRAINT `audit_program_fk` FOREIGN KEY (`program_id`) REFERENCES `holiday_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audit_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample automation settings for existing programs
INSERT IGNORE INTO holiday_programs (id) 
SELECT id FROM holiday_programs WHERE 1=0; -- This ensures the INSERT doesn't add new records

-- Update existing programs with default settings
UPDATE holiday_programs 
SET auto_close_on_capacity = COALESCE(auto_close_on_capacity, 1),
    auto_close_on_date = COALESCE(auto_close_on_date, 1),
    registration_deadline = COALESCE(registration_deadline, 
        CASE 
            WHEN start_date IS NOT NULL THEN DATE_SUB(start_date, INTERVAL 7 DAY)
            ELSE DATE_ADD(NOW(), INTERVAL 30 DAY)
        END)
WHERE registration_deadline IS NULL;

-- Drop existing events if they exist
DROP EVENT IF EXISTS `check_registration_deadlines`;
DROP EVENT IF EXISTS `cleanup_old_logs`;

-- Create event scheduler for automatic deadline checking (runs every hour)
DELIMITER //

CREATE EVENT `check_registration_deadlines`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Log any errors but don't stop the event
        INSERT INTO holiday_program_status_log 
        (program_id, old_status, new_status, change_reason, ip_address, created_at)
        VALUES 
        (0, 0, 0, CONCAT('Event error: ', SQLSTATE), 'system', NOW());
    END;
    
    CALL CheckRegistrationDeadlines();
END//

-- Create event to cleanup old logs (runs daily at 2 AM)
CREATE EVENT `cleanup_old_logs`
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 2 HOUR)
DO
BEGIN
    -- Keep only last 6 months of logs
    DELETE FROM holiday_program_status_log 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- Keep only last 1 year of audit trail
    DELETE FROM holiday_program_audit_trail 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
END//

DELIMITER ;

-- Enable event scheduler if not already enabled
SET GLOBAL event_scheduler = ON;

-- Insert default notification settings for existing programs
INSERT IGNORE INTO holiday_program_notifications 
(program_id, notification_type, enabled, recipient_emails)
SELECT 
    id, 
    'status_change', 
    1, 
    'admin@sci-bono.co.za'
FROM holiday_programs;

INSERT IGNORE INTO holiday_program_notifications 
(program_id, notification_type, enabled, recipient_emails)
SELECT 
    id, 
    'capacity_warning', 
    1, 
    'admin@sci-bono.co.za'
FROM holiday_programs;

INSERT IGNORE INTO holiday_program_notifications 
(program_id, notification_type, enabled, recipient_emails)
SELECT 
    id, 
    'deadline_reminder', 
    1, 
    'admin@sci-bono.co.za'
FROM holiday_programs;

-- Insert initial status log entries for existing programs
INSERT IGNORE INTO holiday_program_status_log 
(program_id, old_status, new_status, change_reason, ip_address, created_at)
SELECT 
    id, 
    0, 
    registration_open, 
    'Initial status log entry - migration', 
    'system', 
    COALESCE(created_at, NOW())
FROM holiday_programs
WHERE id NOT IN (SELECT DISTINCT program_id FROM holiday_program_status_log WHERE program_id > 0);

-- Create a view for easy status monitoring
CREATE OR REPLACE VIEW `holiday_program_status_monitor` AS
SELECT 
    p.id,
    p.term,
    p.title,
    p.registration_open,
    p.max_participants,
    COUNT(a.id) as total_registrations,
    COUNT(CASE WHEN a.status = 'confirmed' THEN 1 END) as confirmed_count,
    GetProgramCapacityPercentage(p.id) as capacity_percentage,
    GetProgramCapacityStatus(p.id) as capacity_status,
    p.auto_close_on_capacity,
    p.auto_close_on_date,
    p.registration_deadline,
    CASE 
        WHEN p.registration_deadline IS NOT NULL AND NOW() >= p.registration_deadline THEN 'expired'
        WHEN p.registration_deadline IS NOT NULL AND TIMESTAMPDIFF(HOUR, NOW(), p.registration_deadline) <= 24 THEN 'expiring_soon'
        ELSE 'active'
    END as deadline_status,
    p.updated_at as last_updated
FROM holiday_programs p
LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
GROUP BY p.id
ORDER BY p.created_at DESC;

-- Add some useful indexes for the new tables
CREATE INDEX IF NOT EXISTS `idx_status_log_program_date` ON `holiday_program_status_log` (`program_id`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_audit_program_date` ON `holiday_program_audit_trail` (`program_id`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_notifications_type` ON `holiday_program_notifications` (`notification_type`, `enabled`);

-- Final verification and summary
SELECT 
    'Migration completed successfully!' as status,
    (SELECT COUNT(*) FROM holiday_programs) as total_programs,
    (SELECT COUNT(*) FROM holiday_program_status_log) as status_log_entries,
    (SELECT COUNT(*) FROM holiday_program_notifications) as notification_settings,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'holiday_program%') as holiday_tables_created;

-- Show current program status overview
SELECT 
    id,
    term,
    title,
    registration_open,
    capacity_percentage,
    capacity_status,
    deadline_status
FROM holiday_program_status_monitor
LIMIT 10;