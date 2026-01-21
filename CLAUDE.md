# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Setup and Installation
- `composer install` - Install PHP dependencies (required for project setup)

### Database Management
- Import SQL schemas from `Database/` folder into MySQL database
- Update database connection details in `config/database.php` and `server.php`
- Database name: `accounts` (as defined in server.php)

### Development Server
- Use XAMPP or similar LAMP/WAMP stack
- Access via `http://localhost/Sci-Bono_Clubhoue_LMS`
- Main entry point: `index.php`

## Architecture Overview

### MVC Pattern Implementation
This is a custom PHP MVC framework with the following structure:
- **Models** (`app/Models/`): Handle database operations and business logic
- **Views** (`app/Views/`): Handle presentation layer (HTML templates)
- **Controllers** (`app/Controllers/`): Handle request processing and coordinate between models and views

### Key Architectural Components

#### Database Connection
- Primary connection defined in `server.php` with hardcoded credentials
- Secondary configuration in `config/database.php` (appears unused)
- Uses MySQLi for database operations

#### Routing System
- Custom routing implemented in `core/Router.php`
- Entry points are individual PHP files (not centralized routing)
- Each major feature has its own controller entry point
- **New API Router**: `attendance_routes.php` provides RESTful API routing for attendance system with CORS support

#### Session Management
- PHP sessions used for authentication
- Session checks implemented across controllers
- User roles: admin, mentor, member, parent, project officer, manger

### Major Feature Modules

#### Holiday Programs System
- **Controllers**: `HolidayProgramController.php`, `HolidayProgramAdminController.php`, `HolidayProgramCreationController.php`
- **Models**: `HolidayProgramModel.php`, `HolidayProgramAdminModel.php`, `holiday-program-functions.php`
- **Views**: Extensive holiday program views in `app/Views/holidayPrograms/`
- **Features**: Registration, admin dashboard, program creation, profile management, workshop selection, capacity management, enrollment tracking

#### Course Management System
- **Controllers**: `CourseController.php`, `Admin/AdminCourseController.php`
- **Models**: `CourseModel.php`, `Admin/AdminCourseModel.php`
- **Features**: Course creation, enrollment, lesson management, curriculum structure, prerequisites, assessment integration

#### User Management & Authentication
- **Controllers**: `AuthController.php`, `UserController.php`
- **Models**: `UserModel.php`, `User.php`
- **Features**: Login/logout, user CRUD, role-based access, password recovery, user profiles, progress tracking

#### Attendance System
- **Controllers**: `AttendanceController.php`, `AttendanceRegisterController.php`, `attendance_routes.php` (API router)
- **Models**: `AttendanceModel.php`, `AttendanceRegisterModel.php`
- **Views**: `app/Views/attendance/signin.php` (enhanced modal system)
- **Features**: Digital attendance tracking, reporting, check-in system, real-time sign-in/sign-out with modal interface
- **API**: RESTful endpoints for AJAX operations (signin, signout, search, stats)

#### Visitor Management System
- **Handler**: `handlers/visitors-handler.php`
- **Views**: `app/Views/visitorsPage.php`
- **JavaScript**: `public/assets/js/visitors-script.js`
- **Features**: Registration process, form validation, data capture, QR code generation, visitor analytics

#### Reporting & Analytics
- **Models**: `dashboard-functions.php`, `dashboardStats.php`
- **Views**: `dynamic-dashboard-content.php`
- **JavaScript**: `public/assets/js/homedashboard.js`
- **Features**: Real-time metrics, customizable widgets, data visualization, automated reports

#### Competition & Event Management
- **Features**: Competition setup, participant management, event planning, calendar integration, result tracking

#### Asset Management
- **Features**: Resource library, equipment tracking, inventory management, booking system

### Database Schema
- Primary database: `accounts`
- Key tables include: `users`, `holiday_programs`, `courses`, `lessons`, `attendance`, `visitors`, `enrollments`, `program_registrations`
- **Key Relationships**:
  - One-to-Many: Users → Courses (instructor relationship)
  - Many-to-Many: Users ↔ Courses (enrollment relationship) 
  - One-to-Many: Courses → Lessons
  - One-to-Many: Holiday Programs → Registrations
  - Many-to-Many: Visitors ↔ Programs
- Stored procedures for automated tasks (e.g., `CheckRegistrationDeadlines`)
- Complex holiday program registration system with cohort management

### File Upload System
- Upload directory: `public/assets/uploads/`
- Organized by date folders (YYYY-MM format)
- Supports images for courses and user profiles

### Frontend Architecture

#### JavaScript Module Structure
- **Namespace Pattern**: Uses `SciBonoCLubhouse` global namespace
- **Key Modules**:
  - `SciBonoCLubhouse.Visitors` - Visitor registration and management
  - `SciBonoCLubhouse.Workshops` - Workshop selection system
  - `SciBonoCLubhouse.Dashboard` - Dashboard functionality
- **Key Scripts**:
  - `visitors-script.js` - Form validation, AJAX submission, error handling
  - `workshopSelection.js` - Dynamic workshop listing, filter/search, booking workflow
  - `homedashboard.js` - Widget management, data refresh, interactive charts
  - `script.js` - Enhanced attendance system with dynamic API endpoints, modal management, and real-time updates

#### CSS Architecture
- **Methodology**: BEM (Block Element Modifier)
- **Approach**: Mobile-first responsive design
- **Structure**: Component-based organization
- Custom CSS in `public/assets/css/`
- Responsive design with mobile-specific styles
- Component-based approach for holiday program forms
- **Enhanced Attendance UI**: `modern-signin.css` with improved modal system, card layouts, and responsive grids

### API Endpoints & Request Flow

#### Request Flow Pattern
1. Client Request → Router
2. Router → Controller
3. Controller → Model
4. Model → Database
5. Database → Model (data)
6. Model → Controller (processed data)
7. Controller → View
8. View → Client (response)

#### Key Endpoints
- **Attendance Management**:
  - `POST /app/Controllers/attendance_routes.php?action=signin` - User sign-in with authentication
  - `POST /app/Controllers/attendance_routes.php?action=signout` - User sign-out
  - `GET /app/Controllers/attendance_routes.php?action=search` - Search attendance records
  - `GET /app/Controllers/attendance_routes.php?action=stats` - Get attendance statistics
- **Visitor Management**:
  - `POST /visitors/register` - New visitor registration
  - `GET /visitors/list` - Retrieve visitor list
  - `PUT /visitors/update/{id}` - Update visitor information
- **Holiday Programs**:
  - `GET /programs/available` - List available programs
  - `POST /programs/enroll` - Enroll in program
  - `GET /programs/workshops/{program_id}` - Get program workshops
- **Dashboard**:
  - `GET /dashboard/data` - Fetch dashboard metrics
  - `GET /dashboard/widgets` - Get widget configuration

## Development Guidelines

### Code Conventions
- Follow PSR-2 coding standards as mentioned in README (upgrading to PSR-12 recommended)
- Use camelCase for methods and variables
- Database operations should use prepared statements (already implemented)
- Models should extend base functionality where possible
- **JavaScript**: Use ES6+ features where appropriate, maintain consistent naming conventions
- **Documentation**: Inline comments for complex logic, JSDoc for JavaScript functions, PHPDoc for PHP methods

### Version Control Practices
- **Branch Strategy**: Git Flow
- **Commit Messages**: Conventional Commits format
- **Code Review**: Required for all PRs
- **Testing**: Unit tests required for new features

### Security Considerations
- **Authentication**: Secure password hashing (bcrypt), session management, CSRF token protection, rate limiting
- **Input Validation**: Server-side validation, client-side pre-validation, SQL injection prevention, XSS protection
- **Authorization**: Role-based access control, permission checking, resource-level security
- **Data Protection**: HTTPS enforcement recommended, secure cookie flags, audit logging
- Database credentials are hardcoded in `server.php` (not ideal for production)
- Sessions are used for authentication state
- CORS headers implemented for AJAX requests

### File Organization
- Controllers handle request routing and business logic coordination
- Models contain all database operations and data manipulation
- Views contain presentation logic only
- Separate admin interfaces from user interfaces
- Debug files kept in dedicated `debugFiles/` folders

### Database Operations
- All models expect a database connection object in constructor
- Use MySQLi prepared statements for queries
- Implement proper error handling for database operations
- Follow existing patterns for CRUD operations

### Adding New Features
- Create controller in appropriate subfolder within `app/Controllers/`
- Create corresponding model in `app/Models/`
- Create views in appropriate subfolder within `app/Views/`
- Update database schema files in `Database/` folder
- Follow existing naming conventions (e.g., `HolidayProgramController` -> `HolidayProgramModel`)
- **For API endpoints**: Create route files following `attendance_routes.php` pattern with action-based routing and CORS headers

## Server Requirements & Environment

### Server Requirements
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher  
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Required PHP Extensions**:
  - PDO PHP Extension
  - MySQLi Extension
  - Mbstring PHP Extension
  - OpenSSL PHP Extension
  - JSON PHP Extension
  - GD PHP Extension

### Environment Configuration
- **Timezone**: Africa/Johannesburg
- **Base URL**: Configured dynamically in `config/config.php`, passed to JavaScript via PHP injection
- **Development Environment**: XAMPP recommended for local development
- **Database Name**: `accounts` (primary database)
- **API Configuration**: Dynamic endpoint resolution using BASE_URL for AJAX requests

### Maintenance Tasks
- **Regular Database Optimization**: Weekly database optimization recommended
- **Cache Cleanup**: Clear cache periodically for optimal performance
- **Security Updates**: Monthly security updates recommended
- **Error Log Monitoring**: Daily monitoring of error logs

## Project Context
This LMS serves the Sci-Bono Clubhouse educational programs with focus on:
- Holiday program registration and management
- Course delivery and tracking
- User engagement and progress monitoring
- Administrative oversight and reporting

The system handles multiple user types with different access levels and provides comprehensive educational program management capabilities.

## Future Enhancements & Roadmap

### Planned Features (Context for Development)
- **Mobile Application**: Native iOS and Android apps with offline capability
- **Advanced Analytics**: Machine learning insights, predictive analytics, custom dashboard builder
- **Integration Capabilities**: Third-party LMS integration, payment gateway integration
- **Enhanced Communication**: Real-time messaging, video conferencing, discussion forums  
- **Gamification**: Achievement system, leaderboards, virtual rewards

These planned features should be considered when making architectural decisions to ensure forward compatibility.

## Recent Enhancements (August 2025)

### Enhanced Attendance System
The attendance system has been significantly improved with modern UI/UX and robust backend functionality:

#### Frontend Improvements
- **Modal System**: Dynamic user selection modal that displays clicked user's information (name, surname, role)
- **Responsive Design**: Improved card layouts with proper alignment and stacking
- **Real-time Updates**: Immediate visual feedback and page refresh after sign-in/sign-out
- **Role-based Styling**: Color-coded role badges (admin=red, mentor=orange, member=green)
- **Enhanced UX**: Better error handling, loading states, and user feedback

#### Backend Architecture
- **API Router**: New `attendance_routes.php` provides RESTful endpoints with action-based routing
- **CORS Support**: Cross-origin request handling for AJAX operations
- **Enhanced Authentication**: Multiple password validation methods including development passwords
- **Simplified Dependencies**: Removed ActivityLog dependency to prevent errors
- **Database Integration**: Proper attendance table operations with status tracking

#### Development Features
- **Development Passwords**: Multiple authentication methods for testing (user ID, "test123", "clubhouse", username)
- **Enhanced Debugging**: Comprehensive error logging and status reporting
- **Dynamic Configuration**: BASE_URL injection from PHP to JavaScript for flexible endpoint resolution

#### Security Considerations
- **Password Hashing**: Production-ready bcrypt implementation with legacy support
- **Input Validation**: Server-side validation with proper error handling
- **SQL Injection Prevention**: Prepared statements throughout the system
- **Development Safety**: Clear separation of development and production authentication methods
## Views Directory Structure (Updated January 2026)

The `app/Views/` directory has been reorganized for consistency and maintainability. All view paths use **dot notation** in controllers.

### Structure Overview
```
app/Views/
├── shared/              # Common components used across multiple areas
├── dashboard/           # Centralized dashboard views (home, stats)
├── courses/             # Public course views (index, catalog, show)
├── lessons/             # Public lesson views
├── reports/             # All reporting views (daily, monthly, forms)
├── programs/            # Holiday program views (formerly holidayPrograms/)
│   ├── auth/           # Program authentication (login, logout, create-password)
│   ├── profile/        # Participant profiles (index, verify-email, create-password, components/)
│   ├── dashboard/      # Program dashboards (participant, admin)
│   ├── shared/         # Program-specific shared components (header, errors)
│   ├── api/            # API-related views
│   ├── admin/          # Admin program views (registrations, etc.)
│   └── processing/     # Background processing views
├── admin/              # Admin panel views
│   ├── shared/         # Admin shared components (header, footer, learn-header)
│   ├── courses/        # Course management (manage, manage-enhanced, manage-content, etc.)
│   ├── lessons/        # Lesson management
│   ├── programs/       # Program management (add-program)
│   ├── system/         # System views (deprecation-monitor)
│   ├── dashboard/      # Admin dashboard
│   ├── users/          # User management (index, create, edit, show)
│   ├── reports/        # Admin reports
│   └── visitors/       # Visitor management
├── member/             # Member area views
│   ├── courses/        # Member courses
│   ├── dashboard/      # Member dashboard
│   ├── lessons/        # Member lessons
│   └── settings/       # Member settings (index, profile, password, notifications, delete-account)
├── mentor/             # Mentor-specific views
│   └── attendance/     # Mentor attendance management
├── auth/               # Authentication views (login, register, forgot-password, change-password)
├── attendance/         # Attendance system views (signin)
├── visitors/           # Visitor views (index from visitorsPage.php)
├── layouts/            # Layout templates
├── partials/           # Reusable partial views (alerts, navigation, etc.)
├── errors/             # Error pages (404, 500, 403)
└── settings.php        # Legacy settings file (root level)
```

### View Path Examples

Controllers use **dot notation** to reference views:

```php
// Programs (formerly holidayPrograms)
$this->view('programs.index');                    // /programs/index.php
$this->view('programs.auth.login');               // /programs/auth/login.php
$this->view('programs.dashboard.participant');    // /programs/dashboard/participant.php
$this->view('programs.profile.index');            // /programs/profile/index.php

// Admin
$this->view('admin.courses.manage');              // /admin/courses/manage.php
$this->view('admin.system.deprecation-monitor');  // /admin/system/deprecation-monitor.php
$this->view('admin.shared.header');               // /admin/shared/header.php

// Member
$this->view('member.settings.index');             // /member/settings/index.php
$this->view('member.dashboard.index');            // /member/dashboard/index.php

// Other
$this->view('dashboard.home');                    // /dashboard/home.php
$this->view('auth.login');                        // /auth/login.php
$this->view('errors.404');                        // /errors/404.php
```

### Key Naming Conventions

1. **Dot Notation**: All view paths use dots (`.`) instead of slashes (`/`)
2. **Kebab Case**: Multi-word file names use kebab-case (e.g., `create-password.php`, `my-programs.php`)
3. **Descriptive Names**: Files named by their action (e.g., `index.php`, `create.php`, `edit.php`, `show.php`)

### Internal View Includes

Views that include other views use relative paths:

```php
// From programs/ root
<?php include __DIR__ . '/shared/header.php'; ?>

// From programs/dashboard/
<?php include '../shared/header.php'; ?>

// From programs/auth/
<?php include '../shared/header.php'; ?>
```

### Migration Notes

- **holidayPrograms/** folder renamed to **programs/** (all files moved)
- **settings/** folder moved to **member/settings/**
- **Deleted Duplicates**: `user_list.php`, `admin/user_edit.php`, `admin/course.php`
- **Backup Created**: `app/Views_backup_20260112_111558/` (available for rollback)

See `VIEWS_REORGANIZATION_LOG.md` for complete reorganization details.

