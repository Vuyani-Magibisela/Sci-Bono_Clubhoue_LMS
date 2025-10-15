# Debug and Test Files Directory

This directory contains test files, debug utilities, and development-only files that were previously scattered in the root directory.

## Purpose

These files are used for:
- Testing database connectivity
- Debugging routing issues
- Testing specific functionality
- Development credentials and test data
- Experimental features (e.g., AI login)

## Files in This Directory

### Test Files

- **test_connection.php**
  - Database connection testing utility
  - Used to verify MySQL connectivity
  - Safe to run - performs read-only connection test

- **test_routing.php**
  - Router testing and debugging
  - Tests the ModernRouter implementation
  - Useful for verifying route resolution

- **testhtml.php**
  - HTML/CSS testing page
  - Frontend component testing
  - Layout and styling experiments

### Development Data

- **testUsers.txt**
  - Test user credentials and data
  - Used for development and testing
  - **DO NOT** use in production
  - Contains sample usernames, passwords, etc.

### Experimental Features

- **Ai_Login.php**
  - Experimental AI-based login implementation
  - Proof of concept
  - Not part of production system
  - May contain incomplete functionality

## Existing Test Directory

Note that there is also a `/tests/` directory in the project root containing:
- Proper PHPUnit test cases
- Test frameworks
- Model tests
- API tests
- Architecture tests

**The `/tests/` directory contains formal unit tests, while this `/debug/` directory contains ad-hoc testing and debugging utilities.**

## Usage Guidelines

### For Developers

1. **Database Testing**
   ```bash
   php debug/test_connection.php
   ```

2. **Routing Testing**
   ```bash
   php debug/test_routing.php
   ```

3. **Browser Testing**
   Navigate to: `http://localhost/Sci-Bono_Clubhoue_LMS/debug/testhtml.php`

### Security Notes

- **Never** commit real credentials to test files
- **Always** use dummy data in `testUsers.txt`
- **Never** enable debug files in production
- Add `.htaccess` rules to block public access if needed

### Recommended .htaccess for Debug Directory

```apache
# Block public access to debug files in production
<FilesMatch "\.(php|txt)$">
    Require ip 127.0.0.1 ::1
    # Add your development IPs here
</FilesMatch>
```

## When to Add Files Here

Add files to this directory if they are:
- One-off testing scripts
- Database debugging utilities
- Development-only tools
- Test data files
- Experimental features not ready for production
- Quick prototypes or proofs of concept

## When to Use /tests/ Instead

Use the `/tests/` directory for:
- PHPUnit test cases
- Automated integration tests
- CI/CD pipeline tests
- Formal test suites
- Test fixtures and mocks
- Long-term maintained tests

## Cleanup

These files can be deleted or gitignored once:
- Development is complete
- Features are tested and working
- No longer needed for debugging
- Production deployment is ready

## Git Configuration

Consider adding to `.gitignore`:
```
debug/*.txt
debug/*_local.php
debug/temp_*.php
```

To preserve structure but ignore test data:
```
debug/*
!debug/README.md
!debug/.gitkeep
```

---

**Last Updated**: October 15, 2025
**Purpose**: Development and debugging utilities
**Status**: Active during development phase
