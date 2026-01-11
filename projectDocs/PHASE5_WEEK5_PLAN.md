# Phase 5 Week 5: Public APIs & Search - PLAN

**Date**: January 11, 2026
**Status**: 📋 PLANNING
**Focus**: Public-facing APIs for course browsing, enrollment, program registration, and search

---

## 📋 Week 5 Overview

### Objectives

Week 5 focuses on implementing **public-facing APIs** that allow authenticated users to:
1. Browse and search available courses
2. Enroll in courses and track progress
3. View lessons and mark them complete
4. Browse and search holiday programs
5. Register for holiday programs
6. Search across all resources (courses, lessons, programs)

### Success Criteria

- ✅ 15+ public API endpoints implemented
- ✅ Full search functionality across courses, lessons, programs
- ✅ Enrollment and registration workflows complete
- ✅ Progress tracking for courses and lessons
- ✅ 50+ integration tests (target)
- ✅ Comprehensive documentation

---

## 🎯 Daily Breakdown

### Day 1: Public Course Listing & Search APIs

**Goal**: Implement public course browsing with filtering and search

**Endpoints to Implement** (4 endpoints):
1. `GET /api/v1/courses` - List all published courses
   - Query parameters: `category`, `level`, `featured`, `search`, `limit`, `offset`
   - Returns: Paginated course list with enrollment counts
   - Public access (authentication required)

2. `GET /api/v1/courses/{id}` - Get course details
   - Returns: Full course details with sections, lesson count, instructor info
   - Public access (authentication required)

3. `GET /api/v1/courses/search` - Search courses
   - Query parameters: `q` (search term), `category`, `level`, `limit`, `offset`
   - Search fields: title, description, course_code
   - Returns: Paginated search results with relevance scoring

4. `GET /api/v1/courses/featured` - Get featured courses
   - Returns: List of featured courses only
   - Public access (no authentication required for browsing)

**Technical Requirements**:
- CourseController (public methods, not admin-only)
- Full-text search implementation (MySQL FULLTEXT or LIKE queries)
- Pagination support (limit/offset)
- Filter by category, difficulty level, featured status
- Only show published courses (`is_published = 1`)

**Deliverables**:
- 4 API endpoints functional
- Search with relevance scoring
- Filtering and pagination
- Activity logging

---

### Day 2: Course Enrollment & User Courses

**Goal**: Implement course enrollment and user course management

**Endpoints to Implement** (4 endpoints):
1. `POST /api/v1/courses/{id}/enroll` - Enroll in course
   - Requires authentication
   - Validates user not already enrolled
   - Creates enrollment record
   - Returns: Enrollment details with course info

2. `DELETE /api/v1/courses/{id}/enroll` - Unenroll from course
   - Requires authentication
   - Validates user is enrolled
   - Soft delete or hard delete (based on progress)
   - Returns: Success confirmation

3. `GET /api/v1/user/courses` - Get user's enrolled courses
   - Requires authentication
   - Returns: List of enrolled courses with progress percentage
   - Query parameters: `status` (active, completed), `limit`, `offset`

4. `GET /api/v1/user/courses/{id}/progress` - Get course progress
   - Requires authentication
   - Returns: Detailed progress (sections completed, lessons completed, quiz scores)

**Technical Requirements**:
- Enrollment table operations (create, delete, query)
- Progress calculation (completed lessons / total lessons)
- Validation (prevent duplicate enrollments)
- Activity logging

**Deliverables**:
- 4 enrollment endpoints
- Progress tracking logic
- Enrollment validation
- User course dashboard data

---

### Day 3: Public Lesson APIs & Progress Tracking

**Goal**: Implement lesson viewing and progress tracking

**Endpoints to Implement** (4 endpoints):
1. `GET /api/v1/courses/{courseId}/lessons` - Get course lessons
   - Returns: All lessons in course (grouped by section)
   - Public access (shows titles/descriptions only if not enrolled)
   - Full content if enrolled

2. `GET /api/v1/lessons/{id}` - Get lesson details
   - Returns: Lesson content, resources, quiz (if enrolled)
   - Validates enrollment before showing full content
   - Tracks lesson view

3. `POST /api/v1/lessons/{id}/complete` - Mark lesson complete
   - Requires authentication and enrollment
   - Creates progress record
   - Returns: Updated progress percentage

4. `GET /api/v1/lessons/{id}/progress` - Get lesson progress
   - Returns: Completion status, time spent, quiz score
   - Requires authentication

**Technical Requirements**:
- Enrollment validation middleware
- Progress tracking (lesson_progress table)
- Content access control (enrolled users only)
- Section grouping for lessons

**Deliverables**:
- 4 lesson endpoints
- Progress tracking system
- Content access control
- Activity logging

---

### Day 4: Public Holiday Program APIs

**Goal**: Implement holiday program browsing and registration

**Endpoints to Implement** (4 endpoints):
1. `GET /api/v1/programs` - List available programs
   - Query parameters: `status` (upcoming, ongoing), `search`, `limit`, `offset`
   - Returns: Paginated program list with available spots
   - Public access (no authentication required)

2. `GET /api/v1/programs/{id}` - Get program details
   - Returns: Full program details with workshops, capacity info
   - Public access (no authentication required)

3. `POST /api/v1/programs/{id}/register` - Register for program
   - Requires authentication
   - Validates capacity, registration deadline
   - Creates registration record
   - Returns: Registration confirmation with details

4. `GET /api/v1/user/programs` - Get user's registered programs
   - Requires authentication
   - Returns: List of user's program registrations
   - Query parameters: `status` (upcoming, completed), `limit`, `offset`

**Technical Requirements**:
- HolidayProgramModel for public queries
- Registration validation (capacity, deadline, duplicate check)
- Workshop selection (future enhancement - basic registration for now)
- Activity logging

**Deliverables**:
- 4 program endpoints
- Registration workflow
- Capacity validation
- User program dashboard

---

### Day 5: Global Search & Advanced Filtering

**Goal**: Implement unified search across all resources

**Endpoints to Implement** (3 endpoints):
1. `GET /api/v1/search` - Global search
   - Query parameters: `q` (search term), `type` (courses, lessons, programs, all), `limit`, `offset`
   - Searches across courses, lessons, and programs
   - Returns: Unified search results with type indicators

2. `GET /api/v1/search/suggestions` - Search autocomplete
   - Query parameter: `q` (partial search term)
   - Returns: Autocomplete suggestions (top 10 matches)
   - Fast response (<50ms target)

3. `GET /api/v1/categories` - Get all categories
   - Returns: List of unique course categories with counts
   - Used for filtering dropdowns

**Technical Requirements**:
- SearchController (new controller)
- Multi-table search (courses, lessons, programs)
- Relevance scoring algorithm
- Autocomplete optimization
- Category aggregation

**Deliverables**:
- 3 search endpoints
- Global search functionality
- Autocomplete suggestions
- Category listing

---

### Day 6: Integration Testing & Documentation

**Goal**: Comprehensive testing and documentation for Week 5

**Tasks**:
1. Create integration test suite for course APIs (20+ tests)
   - Course listing and filtering
   - Course search
   - Course enrollment
   - Progress tracking

2. Create integration test suite for program APIs (15+ tests)
   - Program listing
   - Program registration
   - Capacity validation
   - User programs

3. Create integration test suite for search APIs (15+ tests)
   - Global search
   - Autocomplete
   - Category listing

4. Create PHASE5_WEEK5_COMPLETE.md documentation
   - All 15+ endpoints documented
   - Request/response examples
   - Search implementation details
   - Known limitations
   - Future enhancements

**Deliverables**:
- 50+ integration tests (target)
- Comprehensive week documentation
- API usage examples
- Update ImplementationProgress.md

---

## 📊 Week 5 Endpoint Summary

### Course APIs (8 endpoints)
| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/courses` | List published courses | Yes |
| GET | `/api/v1/courses/{id}` | Get course details | Yes |
| GET | `/api/v1/courses/search` | Search courses | Yes |
| GET | `/api/v1/courses/featured` | Get featured courses | No |
| POST | `/api/v1/courses/{id}/enroll` | Enroll in course | Yes |
| DELETE | `/api/v1/courses/{id}/enroll` | Unenroll from course | Yes |
| GET | `/api/v1/user/courses` | Get user's courses | Yes |
| GET | `/api/v1/user/courses/{id}/progress` | Get course progress | Yes |

### Lesson APIs (4 endpoints)
| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/courses/{courseId}/lessons` | Get course lessons | Yes |
| GET | `/api/v1/lessons/{id}` | Get lesson details | Yes |
| POST | `/api/v1/lessons/{id}/complete` | Mark lesson complete | Yes |
| GET | `/api/v1/lessons/{id}/progress` | Get lesson progress | Yes |

### Holiday Program APIs (4 endpoints)
| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/programs` | List programs | No |
| GET | `/api/v1/programs/{id}` | Get program details | No |
| POST | `/api/v1/programs/{id}/register` | Register for program | Yes |
| GET | `/api/v1/user/programs` | Get user's programs | Yes |

### Search APIs (3 endpoints)
| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/v1/search` | Global search | Yes |
| GET | `/api/v1/search/suggestions` | Autocomplete | Yes |
| GET | `/api/v1/categories` | Get categories | No |

**Total**: 19 endpoints (exceeds 15+ target)

---

## 🔒 Security Considerations

### Authentication Requirements
- All user-specific endpoints require authentication (JWT or session)
- Public browsing endpoints (courses/programs listing) may allow guest access
- Enrollment/registration requires authenticated user

### Authorization
- Users can only enroll/register themselves (no proxy enrollment)
- Users can only view their own progress
- Content access control (enrolled users only for full lesson content)

### Input Validation
- Search term sanitization (prevent SQL injection)
- Pagination limits (prevent excessive data requests)
- Enrollment validation (capacity, deadlines, duplicates)

### Rate Limiting
- Search endpoints: 30 requests/minute per user
- Enrollment endpoints: 10 requests/minute per user
- Public browsing: 60 requests/minute per IP

---

## 📈 Performance Targets

- Course listing: < 150ms
- Course search: < 200ms
- Enrollment operation: < 100ms
- Lesson marking complete: < 100ms
- Global search: < 250ms
- Autocomplete: < 50ms

---

## 🎯 Week 5 Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| API Endpoints | 15+ | 19 planned |
| Integration Tests | 50+ | 50+ (20 course + 15 program + 15 search) |
| Code Written | ~2,500 lines | Controllers + tests + docs |
| Search Performance | < 250ms | Average search response time |
| Test Pass Rate | 100% | All tests passing |
| Documentation | Complete | All endpoints documented |

---

## 🚀 Technical Implementation Notes

### New Controllers to Create
1. **PublicCourseController** - Public course browsing and enrollment
2. **PublicLessonController** - Lesson viewing and progress
3. **PublicProgramController** - Program browsing and registration
4. **SearchController** - Global search and autocomplete

### Database Tables Used
- `courses` - Course data
- `course_enrollments` - User enrollments
- `lessons` - Lesson content
- `lesson_progress` - User progress tracking
- `holiday_programs` - Program data
- `holiday_program_attendees` - Program registrations

### New Database Tables (if needed)
- `lesson_progress` (if not exists) - Track lesson completion
  - `id`, `user_id`, `lesson_id`, `completed`, `completed_at`, `time_spent`, `created_at`

---

## 📝 Known Limitations (Planned)

Week 5 will NOT include:
- Quiz/assessment submission (Week 6 or future)
- Certificate generation (future)
- Social features (comments, ratings) (future)
- Real-time notifications (future)
- Advanced analytics (future)
- Recommendation engine (future)

---

## 🔄 Week 5 Schedule

| Day | Focus | Endpoints | Estimated Time |
|-----|-------|-----------|----------------|
| 1 | Course Listing & Search | 4 | 6-8 hours |
| 2 | Course Enrollment | 4 | 6-8 hours |
| 3 | Lesson APIs & Progress | 4 | 6-8 hours |
| 4 | Program APIs | 4 | 6-8 hours |
| 5 | Global Search | 3 | 6-8 hours |
| 6 | Testing & Documentation | Tests + Docs | 6-8 hours |

**Total Estimated Time**: 36-48 hours (6 days @ 6-8 hours/day)

---

## ✅ Pre-Implementation Checklist

Before starting Day 1:
- [x] Week 4 complete and tested
- [x] All admin APIs functional
- [x] Database schema reviewed
- [x] Week 5 plan approved
- [ ] Check if `lesson_progress` table exists
- [ ] Check if `course_enrollments` table exists
- [ ] Review existing CourseModel for public methods
- [ ] Review existing HolidayProgramModel for public methods

---

**Next Step**: Begin Day 1 - Public Course Listing & Search APIs

**Document Version**: 1.0
**Status**: 📋 PLANNING
**Last Updated**: January 11, 2026
