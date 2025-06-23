-- Holiday Program Database Schema Updates
-- Run these commands to enhance the holiday program system

-- Add a status column to holiday_programs table for better status management
-- This is optional - you can use just registration_open or add this for more granular control
ALTER TABLE `holiday_programs` 
ADD COLUMN `status` ENUM('draft', 'open', 'closing_soon', 'closed', 'completed', 'cancelled') 
DEFAULT 'draft' 
AFTER `registration_open`;

-- Add some useful indexes for better performance
ALTER TABLE `holiday_programs` 
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_registration_open` (`registration_open`),
ADD INDEX `idx_dates` (`start_date`, `end_date`),
ADD INDEX `idx_term` (`term`);

-- Add indexes to workshop table
ALTER TABLE `holiday_program_workshops` 
ADD INDEX `idx_program_id` (`program_id`),
ADD INDEX `idx_instructor` (`instructor`);

-- Add indexes to attendees table for better performance
ALTER TABLE `holiday_program_attendees` 
ADD INDEX `idx_registration_status` (`registration_status`),
ADD INDEX `idx_mentor_registration` (`mentor_registration`),
ADD INDEX `idx_mentor_status` (`mentor_status`),
ADD INDEX `idx_email` (`email`),
ADD INDEX `idx_created_at` (`created_at`);

-- Update existing programs to have proper status
UPDATE `holiday_programs` 
SET `status` = CASE 
    WHEN `registration_open` = 1 AND `end_date` >= CURDATE() THEN 'open'
    WHEN `registration_open` = 0 AND `end_date` >= CURDATE() THEN 'closed'
    WHEN `end_date` < CURDATE() THEN 'completed'
    ELSE 'draft'
END;

-- Add a program_status column to better track program lifecycle
-- This gives you more flexibility than just registration_open
-- You can choose to use this instead of or alongside registration_open

-- Example of how to use the status field:
-- 'draft' - Program created but not published
-- 'open' - Registration is open
-- 'closing_soon' - Registration closing in a few days
-- 'closed' - Registration closed but program hasn't started
-- 'completed' - Program has ended
-- 'cancelled' - Program was cancelled

-- If you want to use this enhanced status system, update your code to check both fields:
-- registration_open = 1 AND status IN ('open', 'closing_soon') for allowing registrations
-- status = 'completed' for finished programs
-- etc.

-- Optional: Create a view for easier program status queries
CREATE OR REPLACE VIEW `holiday_programs_with_status` AS
SELECT 
    p.*,
    CASE 
        WHEN p.status = 'completed' OR p.end_date < CURDATE() THEN 'Completed'
        WHEN p.status = 'cancelled' THEN 'Cancelled'
        WHEN p.status = 'draft' THEN 'Draft'
        WHEN p.registration_open = 1 AND p.status IN ('open', 'closing_soon') THEN 'Registration Open'
        WHEN p.registration_open = 0 OR p.status = 'closed' THEN 'Registration Closed'
        WHEN p.start_date > CURDATE() THEN 'Upcoming'
        WHEN CURDATE() BETWEEN p.start_date AND p.end_date THEN 'In Progress'
        ELSE 'Unknown'
    END AS display_status,
    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = p.id) as total_registrations,
    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = p.id AND mentor_registration = 0) as member_registrations,
    (SELECT COUNT(*) FROM holiday_program_attendees WHERE program_id = p.id AND mentor_registration = 1) as mentor_registrations,
    (SELECT COUNT(*) FROM holiday_program_workshops WHERE program_id = p.id) as workshop_count
FROM holiday_programs p;

-- Create a helpful view for admin dashboard statistics
CREATE OR REPLACE VIEW `holiday_program_stats` AS
SELECT 
    p.id,
    p.term,
    p.title,
    p.dates,
    p.registration_open,
    p.status,
    p.max_participants,
    COUNT(DISTINCT a.id) as total_registrations,
    COUNT(DISTINCT CASE WHEN a.mentor_registration = 0 THEN a.id END) as member_count,
    COUNT(DISTINCT CASE WHEN a.mentor_registration = 1 THEN a.id END) as mentor_count,
    COUNT(DISTINCT CASE WHEN a.registration_status = 'confirmed' THEN a.id END) as confirmed_count,
    COUNT(DISTINCT CASE WHEN a.registration_status = 'pending' THEN a.id END) as pending_count,
    COUNT(DISTINCT w.id) as workshop_count,
    ROUND((COUNT(DISTINCT a.id) / p.max_participants) * 100, 1) as capacity_percentage
FROM holiday_programs p
LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
LEFT JOIN holiday_program_workshops w ON p.id = w.program_id
GROUP BY p.id, p.term, p.title, p.dates, p.registration_open, p.status, p.max_participants;