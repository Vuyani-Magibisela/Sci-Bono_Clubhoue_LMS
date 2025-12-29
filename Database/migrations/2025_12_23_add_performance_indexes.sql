-- ============================================================================
-- Phase 3 Week 9 - Performance Optimization: Database Indexes
-- Created: 2025-12-23
-- Purpose: Add missing indexes to improve query performance
-- ============================================================================

-- Lesson progress optimization
-- Speeds up batch completion status queries (LessonService)
CREATE INDEX idx_user_lesson
ON lesson_progress(user_id, lesson_id);

-- Course structure optimization
-- Speeds up section queries and ordering
CREATE INDEX idx_section_course
ON course_sections(course_id, order_number);

-- Lesson structure optimization
-- Speeds up lesson queries and ordering within sections
CREATE INDEX idx_lesson_section
ON course_lessons(section_id, order_number);

-- Performance metrics optimization
-- Speeds up time-range queries in performance monitoring
CREATE INDEX idx_metrics_timestamp
ON performance_metrics(timestamp, metric_type);

-- Holiday program optimization
-- Speeds up program filtering by status and date
CREATE INDEX idx_program_status
ON clubhouse_programs(status, start_date);

-- Rate limiting cleanup optimization
-- Speeds up expired record cleanup operations
CREATE INDEX idx_rate_limit_cleanup
ON rate_limits(expires_at);

-- ============================================================================
-- Verify indexes were created
-- ============================================================================
-- You can verify these indexes by running:
-- SHOW INDEX FROM lesson_progress;
-- SHOW INDEX FROM course_sections;
-- SHOW INDEX FROM course_lessons;
-- SHOW INDEX FROM performance_metrics;
-- SHOW INDEX FROM clubhouse_programs;
-- SHOW INDEX FROM rate_limits;
