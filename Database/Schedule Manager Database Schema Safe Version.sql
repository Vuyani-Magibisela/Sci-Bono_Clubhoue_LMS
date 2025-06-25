-- Holiday Program Schedule Manager Database Schema Updates (Safe Version)
-- This version checks for existing columns and tables before creating them

-- First, let's check what columns already exist
-- You can run this to see your current table structure:
-- DESCRIBE holiday_programs;
-- DESCRIBE holiday_program_workshops;
-- DESCRIBE holiday_program_attendees;

-- Safely add program_structure column to holiday_programs (only if it doesn't exist)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_programs ADD COLUMN program_structure JSON NULL COMMENT "Stores program configuration: duration, cohorts, prerequisites"',
        'SELECT "Column program_structure already exists" AS message'
    )
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_programs' 
    AND COLUMN_NAME = 'program_structure'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Safely add updated_at column to holiday_programs (only if it doesn't exist)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_programs ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'SELECT "Column updated_at already exists in holiday_programs" AS message'
    )
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_programs' 
    AND COLUMN_NAME = 'updated_at'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create holiday_program_cohorts table (only if it doesn't exist)
CREATE TABLE IF NOT EXISTS holiday_program_cohorts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    max_participants INT DEFAULT 20,
    current_participants INT DEFAULT 0,
    status ENUM('active', 'full', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_program_dates (program_id, start_date, end_date)
);

-- Add foreign key constraint for cohorts (only if it doesn't exist)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_cohorts ADD CONSTRAINT fk_cohorts_program FOREIGN KEY (program_id) REFERENCES holiday_programs(id) ON DELETE CASCADE',
        'SELECT "Foreign key constraint already exists for cohorts" AS message'
    )
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_cohorts' 
    AND CONSTRAINT_NAME = 'fk_cohorts_program'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Safely add prerequisites column to holiday_program_workshops
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_workshops ADD COLUMN prerequisites TEXT NULL COMMENT "Workshop prerequisites and requirements"',
        'SELECT "Column prerequisites already exists" AS message'
    )
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_workshops' 
    AND COLUMN_NAME = 'prerequisites'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Safely add cohort_id column to holiday_program_workshops
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_workshops ADD COLUMN cohort_id INT NULL COMMENT "Link workshop to specific cohort"',
        'SELECT "Column cohort_id already exists in workshops" AS message'
    )
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_workshops' 
    AND COLUMN_NAME = 'cohort_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Safely add week_number column to holiday_program_workshops
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_workshops ADD COLUMN week_number INT DEFAULT 1 COMMENT "Which week of program this workshop runs"',
        'SELECT "Column week_number already exists" AS message'
    )
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_workshops' 
    AND COLUMN_NAME = 'week_number'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Safely add updated_at column to holiday_program_workshops
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_workshops ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'SELECT "Column updated_at already exists in workshops" AS message'
    )
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_workshops' 
    AND COLUMN_NAME = 'updated_at'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for workshop cohorts (only if it doesn't exist)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_workshops ADD CONSTRAINT fk_workshops_cohort FOREIGN KEY (cohort_id) REFERENCES holiday_program_cohorts(id) ON DELETE SET NULL',
        'SELECT "Foreign key constraint already exists for workshop cohorts" AS message'
    )
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_workshops' 
    AND CONSTRAINT_NAME = 'fk_workshops_cohort'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create holiday_program_offerings table (only if it doesn't exist)
CREATE TABLE IF NOT EXISTS holiday_program_offerings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    workshop_id INT NOT NULL,
    cohort_id INT NULL,
    offering_date DATE NULL,
    time_slot VARCHAR(50) NULL,
    available_spots INT NOT NULL,
    status ENUM('scheduled', 'running', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_offering (workshop_id, cohort_id, offering_date),
    INDEX idx_program_schedule (program_id, offering_date)
);

-- Add foreign key constraints for offerings (only if they don't exist)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_offerings ADD CONSTRAINT fk_offerings_program FOREIGN KEY (program_id) REFERENCES holiday_programs(id) ON DELETE CASCADE',
        'SELECT "Foreign key constraint already exists for offerings program" AS message'
    )
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_offerings' 
    AND CONSTRAINT_NAME = 'fk_offerings_program'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_offerings ADD CONSTRAINT fk_offerings_workshop FOREIGN KEY (workshop_id) REFERENCES holiday_program_workshops(id) ON DELETE CASCADE',
        'SELECT "Foreign key constraint already exists for offerings workshop" AS message'
    )
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_offerings' 
    AND CONSTRAINT_NAME = 'fk_offerings_workshop'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_offerings ADD CONSTRAINT fk_offerings_cohort FOREIGN KEY (cohort_id) REFERENCES holiday_program_cohorts(id) ON DELETE SET NULL',
        'SELECT "Foreign key constraint already exists for offerings cohort" AS message'
    )
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_offerings' 
    AND CONSTRAINT_NAME = 'fk_offerings_cohort'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Safely add cohort_id column to holiday_program_attendees
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_attendees ADD COLUMN cohort_id INT NULL COMMENT "Assigned cohort for this attendee"',
        'SELECT "Column cohort_id already exists in attendees" AS message'
    )
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_attendees' 
    AND COLUMN_NAME = 'cohort_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Safely add prerequisites_met column to holiday_program_attendees
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_attendees ADD COLUMN prerequisites_met JSON NULL COMMENT "Track which prerequisites are satisfied"',
        'SELECT "Column prerequisites_met already exists" AS message'
    )
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_attendees' 
    AND COLUMN_NAME = 'prerequisites_met'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for attendee cohorts (only if it doesn't exist)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_attendees ADD CONSTRAINT fk_attendees_cohort FOREIGN KEY (cohort_id) REFERENCES holiday_program_cohorts(id) ON DELETE SET NULL',
        'SELECT "Foreign key constraint already exists for attendee cohorts" AS message'
    )
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_attendees' 
    AND CONSTRAINT_NAME = 'fk_attendees_cohort'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create holiday_program_prerequisites table (only if it doesn't exist)
CREATE TABLE IF NOT EXISTS holiday_program_prerequisites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workshop_id INT NOT NULL,
    prerequisite_type ENUM('age', 'skill', 'workshop', 'equipment') NOT NULL,
    requirement_value VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_workshop_prereqs (workshop_id, prerequisite_type)
);

-- Add foreign key for prerequisites (only if it doesn't exist)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE holiday_program_prerequisites ADD CONSTRAINT fk_prerequisites_workshop FOREIGN KEY (workshop_id) REFERENCES holiday_program_workshops(id) ON DELETE CASCADE',
        'SELECT "Foreign key constraint already exists for prerequisites" AS message'
    )
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_prerequisites' 
    AND CONSTRAINT_NAME = 'fk_prerequisites_workshop'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes for performance (only if they don't exist)
SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'CREATE INDEX idx_attendee_cohort ON holiday_program_attendees(cohort_id)',
        'SELECT "Index idx_attendee_cohort already exists" AS message'
    )
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_attendees' 
    AND INDEX_NAME = 'idx_attendee_cohort'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'CREATE INDEX idx_workshop_cohort ON holiday_program_workshops(cohort_id)',
        'SELECT "Index idx_workshop_cohort already exists" AS message'
    )
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'holiday_program_workshops' 
    AND INDEX_NAME = 'idx_workshop_cohort'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Sample data for testing (only if no data exists)
-- Update existing programs with sample structure
UPDATE holiday_programs 
SET program_structure = JSON_OBJECT(
    'duration_weeks', 2,
    'cohort_system', true,
    'prerequisites_enabled', true,
    'max_cohorts', 2,
    'cohort_size', 20,
    'updated_at', NOW()
)
WHERE program_structure IS NULL AND id = 1;

-- Insert sample cohorts for testing (only if none exist)
INSERT IGNORE INTO holiday_program_cohorts (program_id, name, start_date, end_date, max_participants) 
SELECT 1, 'Week 1 - Morning Cohort', '2025-07-07', '2025-07-11', 20
WHERE NOT EXISTS (SELECT 1 FROM holiday_program_cohorts WHERE program_id = 1 AND name = 'Week 1 - Morning Cohort');

INSERT IGNORE INTO holiday_program_cohorts (program_id, name, start_date, end_date, max_participants) 
SELECT 1, 'Week 2 - Morning Cohort', '2025-07-14', '2025-07-18', 20
WHERE NOT EXISTS (SELECT 1 FROM holiday_program_cohorts WHERE program_id = 1 AND name = 'Week 2 - Morning Cohort');

-- Update workshops with sample prerequisites (only if not already set)
UPDATE holiday_program_workshops 
SET prerequisites = 'Basic computer skills, Age 13+',
    week_number = 1
WHERE program_id = 1 AND id = 1 AND (prerequisites IS NULL OR prerequisites = '');

UPDATE holiday_program_workshops 
SET prerequisites = 'Familiarity with editing software, Age 15+',
    week_number = 1
WHERE program_id = 1 AND id = 2 AND (prerequisites IS NULL OR prerequisites = '');

UPDATE holiday_program_workshops 
SET prerequisites = 'Creative mindset, Age 12+',
    week_number = 2
WHERE program_id = 1 AND id = 3 AND (prerequisites IS NULL OR prerequisites = '');

-- Insert sample prerequisites (only if none exist for these workshops)
INSERT IGNORE INTO holiday_program_prerequisites (workshop_id, prerequisite_type, requirement_value, description, is_mandatory) 
SELECT 1, 'age', '13', 'Minimum age requirement for graphic design workshop', true
WHERE NOT EXISTS (SELECT 1 FROM holiday_program_prerequisites WHERE workshop_id = 1 AND prerequisite_type = 'age');

INSERT IGNORE INTO holiday_program_prerequisites (workshop_id, prerequisite_type, requirement_value, description, is_mandatory) 
SELECT 1, 'skill', 'basic_computer', 'Basic computer navigation and file management', true
WHERE NOT EXISTS (SELECT 1 FROM holiday_program_prerequisites WHERE workshop_id = 1 AND prerequisite_type = 'skill');

INSERT IGNORE INTO holiday_program_prerequisites (workshop_id, prerequisite_type, requirement_value, description, is_mandatory) 
SELECT 2, 'age', '15', 'Minimum age for video editing due to software complexity', true
WHERE NOT EXISTS (SELECT 1 FROM holiday_program_prerequisites WHERE workshop_id = 2 AND prerequisite_type = 'age');

INSERT IGNORE INTO holiday_program_prerequisites (workshop_id, prerequisite_type, requirement_value, description, is_mandatory) 
SELECT 3, 'age', '12', 'Minimum age for animation workshop', true
WHERE NOT EXISTS (SELECT 1 FROM holiday_program_prerequisites WHERE workshop_id = 3 AND prerequisite_type = 'age');

-- Create views for easy querying (recreate them if they exist)
DROP VIEW IF EXISTS program_structure_view;
CREATE VIEW program_structure_view AS
SELECT 
    p.id,
    p.title,
    p.start_date,
    p.end_date,
    JSON_EXTRACT(p.program_structure, '$.duration_weeks') as duration_weeks,
    JSON_EXTRACT(p.program_structure, '$.cohort_system') as has_cohorts,
    JSON_EXTRACT(p.program_structure, '$.prerequisites_enabled') as has_prerequisites,
    COUNT(DISTINCT c.id) as total_cohorts,
    COUNT(DISTINCT w.id) as total_workshops,
    COUNT(DISTINCT a.id) as total_attendees
FROM holiday_programs p
LEFT JOIN holiday_program_cohorts c ON p.id = c.program_id
LEFT JOIN holiday_program_workshops w ON p.id = w.program_id  
LEFT JOIN holiday_program_attendees a ON p.id = a.program_id
GROUP BY p.id;

DROP VIEW IF EXISTS workshop_capacity_view;
CREATE VIEW workshop_capacity_view AS
SELECT 
    w.id as workshop_id,
    w.title,
    w.max_participants,
    w.prerequisites,
    c.name as cohort_name,
    c.id as cohort_id,
    COUNT(DISTINCT a.id) as enrolled_count,
    (w.max_participants - COUNT(DISTINCT a.id)) as available_spots,
    CASE 
        WHEN w.max_participants > 0 THEN ROUND((COUNT(DISTINCT a.id) / w.max_participants) * 100, 1)
        ELSE 0 
    END as capacity_percentage
FROM holiday_program_workshops w
LEFT JOIN holiday_program_cohorts c ON w.cohort_id = c.id
LEFT JOIN holiday_program_attendees a ON w.program_id = a.program_id 
    AND JSON_CONTAINS(a.workshop_preference, CAST(w.id AS JSON))
GROUP BY w.id, c.id;

--or
CREATE VIEW workshop_capacity_view AS
SELECT 
    w.id as workshop_id,
    w.title,
    w.max_participants,
    w.prerequisites,
    c.name as cohort_name,
    c.id as cohort_id,
    COUNT(DISTINCT a.id) as enrolled_count,
    (w.max_participants - COUNT(DISTINCT a.id)) as available_spots,
    CASE 
        WHEN w.max_participants > 0 THEN ROUND((COUNT(DISTINCT a.id) / w.max_participants) * 100, 1)
        ELSE 0 
    END as capacity_percentage
FROM holiday_program_workshops w
LEFT JOIN holiday_program_cohorts c ON w.cohort_id = c.id
LEFT JOIN holiday_program_attendees a ON w.program_id = a.program_id 
    AND INSTR(a.workshop_preference, CONCAT('"', w.id, '"')) > 0
GROUP BY w.id, c.id;


-- Final verification queries (uncomment to run these manually)
-- SELECT 'Schema update completed successfully!' AS status;
-- SELECT COUNT(*) as total_cohorts FROM holiday_program_cohorts;
-- SELECT COUNT(*) as total_prerequisites FROM holiday_program_prerequisites;
-- DESCRIBE holiday_programs;
-- SELECT * FROM program_structure_view;