# Sci-Bono Clubhouse LMS

## Project Overview

The Sci-Bono Clubhouse LMS is a Learning Management System designed to facilitate educational programs, manage courses, and enhance user engagement. This system provides features for holiday program management, course administration, lesson delivery, user authentication, and reporting.

## Key Features

*   **Holiday Program Management**: Registration, dashboards, and term details.
*   **Course Management**: Admin course creation and enrollment tracking.
*   **Lesson Management**: Structured lesson delivery.
*   **User Authentication**: Secure login and logout for members and students.
*   **Reporting System**: Monthly report submission and viewing.
*   **Admin Dashboard**: Course management and user administration.

## Installation & Setup

### Prerequisites

*   XAMPP (Apache/MySQL)
*   PHP 7.4+
*   Composer

### Database Setup

1.  Import the SQL schemas located in the `Database/` folder into your MySQL database.

### Composer Dependencies

1.  Navigate to the project directory in your terminal.
2.  Run `composer install` to install the necessary PHP packages.

### Configuration

1.  Open `config/database.php`.
2.  Update the database connection details (host, username, password, database name) to match your MySQL setup.

## Directory Structure Explanation


## Usage Guide

### Frontend Access

*   Open your web browser and navigate to: `http://localhost/Sci-Bono_Clubhoue_LMS`

### Admin Access

*   Access the admin dashboard via the login page, typically found at `http://localhost/Sci-Bono_Clubhoue_LMS/login.php` or similar.
*   Use the admin credentials to log in.

### Holiday Programs

*   Access holiday programs through the holiday program index page.

## Tech Stack

*   **Backend**: PHP, MVC Pattern
*   **Database**: MySQL (XAMPP)
*   **Frontend**: HTML5, CSS3, JavaScript
*   **Server**: Apache

## Contributing Guidelines

*   Adhere to the MVC (Model-View-Controller) pattern.
*   Follow PSR-2 coding standards.
*   Document any database changes in the `Database/` folder.
