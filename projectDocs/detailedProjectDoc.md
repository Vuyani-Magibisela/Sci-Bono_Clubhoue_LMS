# Sci-Bono Clubhouse LMS - Complete Project Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Technology Stack](#technology-stack)
4. [Project Structure](#project-structure)
5. [Core Features & Modules](#core-features--modules)
6. [Component Details](#component-details)
7. [Database Architecture](#database-architecture)
8. [API & Handlers](#api--handlers)
9. [Frontend Architecture](#frontend-architecture)
10. [Security & Authentication](#security--authentication)
11. [Development Guidelines](#development-guidelines)
12. [Deployment & Configuration](#deployment--configuration)

---

## Project Overview

### Purpose
The Sci-Bono Clubhouse Learning Management System (LMS) is a comprehensive educational platform designed to streamline and enhance the management of educational programs, courses, and clubhouse activities. The system serves as a central hub for educational content delivery, user engagement, and administrative operations.

### Target Audience
- **Students/Learners**: Primary users accessing educational content and participating in programs
- **Educators/Instructors**: Managing courses, delivering lessons, and tracking student progress
- **Administrators**: Overseeing system operations, user management, and reporting
- **Visitors**: Registering for programs and accessing public information

### Key Objectives
- Facilitate seamless educational program management
- Enhance user engagement through interactive features
- Provide comprehensive reporting and analytics
- Streamline administrative processes
- Ensure scalable and maintainable system architecture

---

## System Architecture

### Architectural Pattern
The system follows a **Model-View-Controller (MVC)** architectural pattern with a custom PHP framework implementation:

```
┌─────────────────────────────────────────────────┐
│                   Frontend Layer                 │
│         (HTML, CSS, JavaScript, jQuery)          │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│              Controller Layer (PHP)              │
│         (Request Handling & Routing)             │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│                Model Layer (PHP)                 │
│          (Business Logic & Data Access)          │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│              Database Layer (MySQL)              │
│            (Data Storage & Retrieval)            │
└─────────────────────────────────────────────────┘
```

### Design Principles
- **Separation of Concerns**: Clear distinction between presentation, logic, and data layers
- **Modularity**: Component-based architecture for maintainability
- **Scalability**: Designed to accommodate growth in users and features
- **Security-First**: Built-in authentication and authorization mechanisms

---

## Technology Stack

### Backend Technologies
| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| **Language** | PHP | 7.4+ | Server-side programming |
| **Framework** | Custom MVC | - | Application structure |
| **Database** | MySQL | 5.7+ | Data persistence |
| **Authentication** | Custom PHP | - | User authentication & session management |
| **File Handling** | PHP Native | - | Document and media management |

### Frontend Technologies
| Component | Technology | Purpose |
|-----------|------------|---------|
| **Markup** | HTML5 | Semantic structure |
| **Styling** | Custom CSS | Visual design and responsive layout |
| **Scripting** | Vanilla JavaScript | Core interactivity |
| **Library** | jQuery | DOM manipulation and AJAX |
| **Validation** | Custom JS | Form validation and data integrity |
| **UI Components** | Custom Components | Reusable interface elements |

### Development Tools
| Tool | Purpose |
|------|---------|
| **Git** | Version control and collaboration |
| **Visual Studio Code** | Primary IDE for development |
| **Webpack** | JavaScript module bundling |
| **Unit Testing Framework** | Code quality assurance |
| **MySQL Workbench** | Database design and management |

---

## Project Structure

### Directory Organization

```
sci-bono-clubhouse-lms/
│
├── app/                          # Application core
│   ├── Controllers/              # Request handlers and business logic
│   ├── Models/                   # Data models and database interactions
│   └── Views/                    # Presentation templates
│       ├── holidayPrograms/      # Holiday program views
│       ├── visitorsPage.php      # Visitor management interface
│       └── dynamic-dashboard-content.php  # Dashboard generation
│
├── public/                       # Publicly accessible files
│   └── assets/                   # Static resources
│       ├── js/                   # JavaScript files
│       │   ├── visitors-script.js
│       │   ├── workshopSelection.js
│       │   └── homedashboard.js
│       ├── css/                  # Stylesheets
│       └── images/               # Image assets
│
├── Database/                     # Database files
│   ├── migrations/               # Database migration scripts
│   ├── seeds/                    # Initial data seeders
│   └── schema.sql                # Database schema
│
├── handlers/                     # Request handlers
│   └── visitors-handler.php      # Visitor data processing
│
├── config/                       # Configuration files
│   ├── database.php              # Database configuration
│   ├── app.php                   # Application settings
│   └── routes.php                # URL routing definitions
│
├── storage/                      # File storage
│   ├── uploads/                  # User uploads
│   ├── logs/                     # Application logs
│   └── cache/                    # Cache files
│
└── vendor/                       # Third-party dependencies
```

---

## Core Features & Modules

### 1. User Management System
- **User Registration & Authentication**
  - Secure password hashing
  - Session management
  - Role-based access control (RBAC)
  - Password recovery mechanisms

- **User Profiles**
  - Personal information management
  - Progress tracking
  - Achievement badges
  - Activity history

### 2. Course Management
- **Course Administration**
  - Course creation and editing
  - Curriculum structure management
  - Resource attachment
  - Prerequisites configuration

- **Lesson Delivery**
  - Sequential lesson progression
  - Multimedia content support
  - Interactive exercises
  - Assessment integration

### 3. Holiday Program Management
- **Program Creation**
  - Date scheduling
  - Capacity management
  - Age group targeting
  - Resource allocation

- **Workshop Selection**
  - Interactive workshop browser
  - Real-time availability checking
  - Booking confirmation system
  - Waitlist management

### 4. Competition Management
- **Competition Setup**
  - Rules and guidelines
  - Registration periods
  - Judging criteria
  - Prize configuration

- **Participant Management**
  - Registration processing
  - Submission handling
  - Result tracking
  - Certificate generation

### 5. Event Management
- **Event Planning**
  - Calendar integration
  - Venue management
  - Speaker/presenter profiles
  - Registration handling

- **Event Execution**
  - Check-in system
  - Attendance tracking
  - Feedback collection
  - Post-event reporting

### 6. Asset Management
- **Resource Library**
  - Document management
  - Media organization
  - Version control
  - Access permissions

- **Equipment Tracking**
  - Inventory management
  - Booking system
  - Maintenance schedules
  - Usage analytics

### 7. Visitor Management System
- **Registration Process**
  - Form validation
  - Data capture
  - Confirmation emails
  - QR code generation

- **Visitor Analytics**
  - Traffic patterns
  - Demographics analysis
  - Conversion tracking
  - Engagement metrics

### 8. Reporting & Analytics
- **Dashboard System**
  - Real-time metrics
  - Customizable widgets
  - Data visualization
  - Export capabilities

- **Report Generation**
  - Automated reports
  - Custom report builder
  - Scheduled delivery
  - Multiple format support (PDF, Excel, CSV)

### 9. Clubhouse Activity Management
- **Activity Scheduling**
  - Calendar management
  - Resource allocation
  - Instructor assignment
  - Participant enrollment

- **Activity Tracking**
  - Attendance monitoring
  - Progress evaluation
  - Performance metrics
  - Completion certificates

---

## Component Details

### Frontend Components

#### Visitor Management Interface
**File**: `app/Views/visitorsPage.php`
- **Purpose**: Main interface for visitor registration and management
- **Features**:
  - Dynamic form generation
  - Real-time validation
  - Multi-step registration process
  - Data preview before submission

**Associated Script**: `public/assets/js/visitors-script.js`
- Form validation logic
- AJAX submission handling
- Error display management
- Success notification system

#### Workshop Selection System
**File**: `public/assets/js/workshopSelection.js`
- **Functionality**:
  - Dynamic workshop listing
  - Filter and search capabilities
  - Real-time availability updates
  - Shopping cart functionality
  - Booking confirmation workflow

#### Dashboard System
**File**: `public/assets/js/homedashboard.js`
- **Components**:
  - Widget management
  - Data refresh mechanisms
  - Interactive charts
  - Quick action buttons
  - Notification center

**Dynamic Content Generation**: `app/Views/dynamic-dashboard-content.php`
- User-specific content rendering
- Permission-based display
- Data aggregation
- Performance optimization

### Backend Components

#### Model Layer
**Holiday Program Functions**: `app/Models/holiday-program-functions.php`
- Program CRUD operations
- Enrollment management
- Capacity tracking
- Schedule conflict resolution

**Dashboard Functions**: `app/Models/dashboard-functions.php`
- Data aggregation methods
- Metric calculations
- Cache management
- Query optimization

#### Handler Layer
**Visitor Handler**: `handlers/visitors-handler.php`
- Request validation
- Data sanitization
- Database operations
- Response formatting
- Error handling

---

## Database Architecture

### Schema Overview
```sql
-- Core Tables
users
├── id (PK)
├── username
├── email
├── password_hash
├── role_id (FK)
├── created_at
└── updated_at

courses
├── id (PK)
├── title
├── description
├── instructor_id (FK)
├── category_id (FK)
├── status
├── created_at
└── updated_at

lessons
├── id (PK)
├── course_id (FK)
├── title
├── content
├── order_index
├── duration
└── resources

holiday_programs
├── id (PK)
├── name
├── description
├── start_date
├── end_date
├── capacity
├── age_group
└── status

visitors
├── id (PK)
├── first_name
├── last_name
├── email
├── phone
├── registration_date
└── program_id (FK)

-- Relationship Tables
enrollments
├── id (PK)
├── user_id (FK)
├── course_id (FK)
├── enrollment_date
├── completion_date
└── progress

program_registrations
├── id (PK)
├── visitor_id (FK)
├── program_id (FK)
├── workshop_id (FK)
├── registration_date
└── status
```

### Database Relationships
- **One-to-Many**: Users → Courses (instructor relationship)
- **Many-to-Many**: Users ↔ Courses (enrollment relationship)
- **One-to-Many**: Courses → Lessons
- **One-to-Many**: Holiday Programs → Registrations
- **Many-to-Many**: Visitors ↔ Programs

---

## API & Handlers

### Request Flow
1. **Client Request** → Router
2. **Router** → Controller
3. **Controller** → Model
4. **Model** → Database
5. **Database** → Model (data)
6. **Model** → Controller (processed data)
7. **Controller** → View
8. **View** → Client (response)

### Key Endpoints

#### Visitor Management
- `POST /visitors/register` - New visitor registration
- `GET /visitors/list` - Retrieve visitor list
- `PUT /visitors/update/{id}` - Update visitor information
- `DELETE /visitors/delete/{id}` - Remove visitor record

#### Holiday Programs
- `GET /programs/available` - List available programs
- `POST /programs/enroll` - Enroll in program
- `GET /programs/workshops/{program_id}` - Get program workshops
- `POST /programs/workshop/select` - Select workshop

#### Dashboard
- `GET /dashboard/data` - Fetch dashboard metrics
- `GET /dashboard/widgets` - Get widget configuration
- `POST /dashboard/customize` - Save dashboard preferences

---

## Frontend Architecture

### JavaScript Module Structure
```javascript
// Main application namespace
const SciBonoCLubhouse = {
    // Module definitions
    Visitors: {
        init: function() {},
        validate: function() {},
        submit: function() {}
    },
    
    Workshops: {
        load: function() {},
        filter: function() {},
        select: function() {}
    },
    
    Dashboard: {
        initialize: function() {},
        refresh: function() {},
        customize: function() {}
    }
};
```

### CSS Architecture
- **Methodology**: BEM (Block Element Modifier)
- **Organization**: Component-based structure
- **Responsive Design**: Mobile-first approach
- **Browser Support**: Modern browsers + IE11

---

## Security & Authentication

### Security Measures
1. **Input Validation**
   - Server-side validation
   - Client-side pre-validation
   - SQL injection prevention
   - XSS protection

2. **Authentication System**
   - Secure password hashing (bcrypt)
   - Session management
   - CSRF token protection
   - Rate limiting

3. **Authorization**
   - Role-based access control
   - Permission checking
   - Resource-level security
   - API authentication

4. **Data Protection**
   - HTTPS enforcement
   - Secure cookie flags
   - Data encryption at rest
   - Audit logging

---

## Development Guidelines

### Coding Standards

#### PHP Guidelines
```php
// Follow PSR-12 coding standard
namespace App\Controllers;

class UserController
{
    /**
     * Display user profile
     * 
     * @param int $userId
     * @return View
     */
    public function showProfile(int $userId): View
    {
        // Implementation
    }
}
```

#### JavaScript Guidelines
```javascript
// Use ES6+ features where appropriate
// Maintain consistent naming conventions
const handleFormSubmission = async (formData) => {
    try {
        const response = await fetch('/api/submit', {
            method: 'POST',
            body: formData
        });
        return await response.json();
    } catch (error) {
        console.error('Submission failed:', error);
    }
};
```

### Version Control Practices
- **Branch Strategy**: Git Flow
- **Commit Messages**: Conventional Commits format
- **Code Review**: Required for all PRs
- **Testing**: Unit tests required for new features

### Documentation Requirements
- Inline code comments for complex logic
- JSDoc for JavaScript functions
- PHPDoc for PHP methods
- README files for each module

---

## Deployment & Configuration

### Environment Configuration
```php
// config/app.php
return [
    'name' => 'Sci-Bono Clubhouse LMS',
    'environment' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://lms.sci-bono.co.za'),
    'timezone' => 'Africa/Johannesburg',
];
```

### Database Configuration
```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'scibono_lms'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
];
```

### Server Requirements
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP Extensions**:
  - PDO PHP Extension
  - Mbstring PHP Extension
  - OpenSSL PHP Extension
  - JSON PHP Extension
  - GD PHP Extension

### Deployment Process
1. **Pre-deployment**
   - Run test suite
   - Build frontend assets
   - Update version numbers
   - Create backup

2. **Deployment**
   - Pull latest code
   - Run database migrations
   - Clear cache
   - Update dependencies
   - Set proper permissions

3. **Post-deployment**
   - Verify functionality
   - Monitor error logs
   - Check performance metrics
   - Update documentation

---

## Maintenance & Support

### Regular Maintenance Tasks
- **Daily**:
  - Monitor error logs
  - Check system performance
  - Review security alerts

- **Weekly**:
  - Database optimization
  - Cache cleanup
  - Backup verification

- **Monthly**:
  - Security updates
  - Performance analysis
  - User feedback review

### Troubleshooting Guide
| Issue | Possible Cause | Solution |
|-------|---------------|----------|
| Login failures | Session issues | Clear session cache |
| Slow performance | Database queries | Optimize indexes |
| Upload errors | Permission issues | Check directory permissions |
| Display issues | Cache problems | Clear browser and server cache |

### Support Channels
- **Documentation**: Internal wiki
- **Issue Tracking**: GitHub Issues
- **Communication**: Development team Slack
- **Emergency Contact**: System administrator

---

## Future Enhancements

### Planned Features
1. **Mobile Application**
   - Native iOS and Android apps
   - Offline capability
   - Push notifications

2. **Advanced Analytics**
   - Machine learning insights
   - Predictive analytics
   - Custom dashboard builder

3. **Integration Capabilities**
   - Third-party LMS integration
   - Payment gateway integration
   - Social media connectivity

4. **Enhanced Communication**
   - Real-time messaging
   - Video conferencing
   - Discussion forums

5. **Gamification**
   - Achievement system
   - Leaderboards
   - Virtual rewards

---

## Conclusion

The Sci-Bono Clubhouse LMS represents a comprehensive educational management platform built with scalability, security, and user experience in mind. This documentation serves as a living document that should be updated as the system evolves and new features are added.

For specific implementation details or technical questions, please refer to the inline code documentation or contact the development team.

---

*Last Updated: [Current Date]*
*Version: 1.0.0*
*Maintained by: Sci-Bono Development Team*