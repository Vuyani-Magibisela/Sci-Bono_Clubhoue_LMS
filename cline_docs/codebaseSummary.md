# Codebase Summary

## Project Structure
- **Frontend:** All JS and CSS files are located in `public/assets`
- **Backend:** PHP files are organized in `app/Controllers`, `app/Models`, and `app/Views`
- **Database:** SQL files are stored in `Database` directory
- **Utilities:** Configuration files in `config` directory

## Key Components
### Visitor Management System
- `app/Views/visitorsPage.php`: Main visitor registration and management interface
- `public/assets/js/visitors-script.js`: Handles form validation and functionality
- `handlers/visitors-handler.php`: Processes visitor data

### Holiday Programs
- `app/Views/holidayPrograms`: Contains all holiday program-related views
- `public/assets/js/workshopSelection.js`: Manages workshop selection logic
- `app/Models/holiday-program-functions.php`: Handles program data

### Home Dashboard
- `public/assets/js/homedashboard.js`: Implements dashboard functionality
- `app/Views/dynamic-dashboard-content.php`: Generates dynamic dashboard content
- `app/Models/dashboard-functions.php`: Provides dashboard-related utilities
