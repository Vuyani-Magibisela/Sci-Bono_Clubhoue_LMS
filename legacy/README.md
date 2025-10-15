# Legacy Files Directory

This directory contains legacy authentication and user management files that have been replaced by the modern MVC architecture.

## Why These Files Were Moved

As part of Phase 4+ implementation, the application has been refactored to use a proper MVC architecture with:
- Controllers in `app/Controllers/`
- Views in `app/Views/`
- Models in `app/Models/`
- Services in `app/Services/`
- Modern routing in `routes/web.php`

These legacy files are no longer actively used but are preserved for:
- Historical reference
- Temporary fallback during migration
- Understanding the original implementation

## Files Moved (2025-10-15)

### Legacy Authentication Files
These have been replaced by `AuthController` and views in `app/Views/auth/`:

- **login.php** → Replaced by `/app/Views/auth/login.php`
  - Old standalone login page with inline HTML
  - Now handled by `AuthController@showLogin`

- **login_process.php** → Replaced by `AuthController@login`
  - Old form processing logic
  - Now in `app/Controllers/AuthController.php:56`

- **signup.php** → Replaced by `/app/Views/auth/register.php`
  - Old standalone registration page
  - Now handled by `AuthController@showSignup`

- **signup_process.php** → Replaced by `AuthController@signup`
  - Old registration processing
  - Now in `app/Controllers/AuthController.php:183`

- **logout_process.php** → Replaced by `AuthController@logout`
  - Old logout handler
  - Now in `app/Controllers/AuthController.php:125`

- **validate_password.php**
  - Old password validation utility
  - Now handled by validation traits and UserService

### Legacy User Management Files
These have been replaced by `UserController` and admin controllers:

- **display_members.php**
  - Old member listing page
  - Replaced by `UserController` or admin user management

- **display_users.php**
  - Old user listing
  - Replaced by `Admin\UserController@index`

- **edit_user.php**
  - Old user editing form
  - Replaced by `Admin\UserController@edit`

- **user-delete.php**
  - Old user deletion handler
  - Replaced by `Admin\UserController@destroy`

- **members.php**
  - Old member management
  - Replaced by mentor/admin controllers

- **profile_updater.php**
  - Old profile update handler
  - Replaced by `UserController@updateProfile`

## Modern Routing

All authentication and user management now goes through the routing system:

```php
// Authentication
GET  /login           → AuthController@showLogin
POST /login           → AuthController@login
GET  /signup          → AuthController@showSignup
POST /signup          → AuthController@signup
POST /logout          → AuthController@logout

// User Profile
GET  /profile         → UserController@profile
POST /profile         → UserController@updateProfile

// Admin User Management
GET  /admin/users     → Admin\UserController@index
GET  /admin/users/{id}/edit → Admin\UserController@edit
PUT  /admin/users/{id} → Admin\UserController@update
DELETE /admin/users/{id} → Admin\UserController@destroy
```

## When Can These Be Deleted?

These files can be safely deleted once:
1. All legacy links/bookmarks have been updated
2. All functionality has been verified in the new MVC system
3. Full testing has been completed
4. A backup has been made
5. Team consensus has been reached

**Status**: Currently in migration phase - keep for reference.

## Migration Progress

- ✅ Authentication system fully migrated
- ✅ Registration system fully migrated
- ✅ Login/Logout functionality working in MVC
- ⏳ User management partially migrated
- ⏳ Profile management in progress
- ⏳ Admin panels under development

## Need to Use Legacy Code?

If you need to reference or temporarily use these files:
1. Check the new MVC implementation first in `app/Controllers/`
2. Consider submitting a bug report if functionality is missing
3. Contact the development team before reverting to legacy code

---

**Last Updated**: October 15, 2025
**Migration Phase**: Phase 4-5 (MVC Architecture Complete)
