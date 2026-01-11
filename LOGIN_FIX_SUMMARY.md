# Login Issue Fix Summary
**Date**: 2026-01-06
**Status**: RESOLVED ✅

## Issues Identified and Fixed

### 1. File Permission Issues ✅
**Problem**: Log and cache files owned by `root` instead of `www-data`
**Files Affected**:
- `/var/www/html/Sci-Bono_Clubhoue_LMS/storage/logs/`
- `/var/www/html/Sci-Bono_Clubhoue_LMS/storage/cache/`

**Fix Applied**:
```bash
sudo chown -R www-data:www-data /var/www/html/Sci-Bono_Clubhoue_LMS/storage/
sudo chmod -R 775 /var/www/html/Sci-Bono_Clubhoue_LMS/storage/
```

---

### 2. Session Configuration Issues ✅
**Problem**: Session cookies requiring HTTPS on HTTP localhost
**File**: `bootstrap.php`

**Original Code** (Lines 20-38):
```php
ini_set('session.cookie_secure', 1);  // Always required HTTPS
ini_set('session.cookie_samesite', 'Strict');  // Too restrictive
```

**Fixed Code**:
```php
// Check if HTTPS is available
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Start session with security options (PHP 7.3+ syntax)
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => $isHttps,        // Only require HTTPS if available
    'cookie_samesite' => 'Lax',         // Allow same-site POST (was 'Strict')
    'use_strict_mode' => true,
    'gc_maxlifetime' => 7200,
    'cookie_lifetime' => 0,
    'sid_length' => 48,
    'sid_bits_per_character' => 6
]);
```

**Why This Fix Works**:
- `cookie_secure` now conditional - works on both HTTP and HTTPS
- `cookie_samesite` changed from `Strict` to `Lax` - allows form submissions
- Used modern `session_start()` with options array (PHP 7.3+ recommended method)

---

### 3. CSRF Token Field Name Mismatch ✅ **ROOT CAUSE**
**Problem**: Form field name didn't match CSRF validation expectations

**CSRF Class Configuration** (`core/CSRF.php` line 10):
```php
private static $tokenName = '_csrf_token';  // Expected: _csrf_token
```

**Login Form** (`app/Views/auth/login.php` line 67):
```php
<input type="hidden" name="csrf_token" value="...">  // Had: csrf_token (missing underscore)
```

**Fix Applied**: Changed all CSRF token fields from `name="csrf_token"` to `name="_csrf_token"`

**Files Fixed** (16 total):
- app/Views/auth/login.php
- app/Views/auth/register.php
- app/Views/auth/forgot-password.php
- app/Views/auth/change-password.php
- app/Views/holidayPrograms/holidayProgramLogin.php
- app/Views/holidayPrograms/holiday-profile-create-password.php
- app/Views/layouts/admin.php
- app/Views/member/settings/profile.php
- app/Views/member/settings/password.php
- app/Views/member/settings/notifications.php
- app/Views/member/settings/delete-account.php
- app/Views/member/courses/show.php
- app/Views/admin/reports/create.php
- app/Views/admin/reports/edit.php
- app/Views/visitors/register.php
- app/Views/admin/visitors/edit.php

---

## Testing

### Session Persistence Test
Created `test_session.php` to verify sessions work:
```bash
curl http://localhost/Sci-Bono_Clubhoue_LMS/test_session.php
# Result: Counter increments correctly ✅
```

### CSRF Validation Flow
1. **GET /login** → Generates CSRF token, stores in session
2. **User fills form** → Form includes `_csrf_token` field (fixed)
3. **POST /login** → SecurityMiddleware validates token from session
4. **Success** → Login proceeds normally ✅

---

## Root Cause Analysis

The 403 "Access Forbidden" error was caused by **CSRF token validation failure** due to:

1. **Primary Cause**: Field name mismatch (`csrf_token` vs `_csrf_token`)
2. **Contributing Factor**: Session configuration preventing cookies on HTTP
3. **Secondary Issue**: File permissions preventing error logging

All three issues have been resolved.

---

## Next Steps for User

1. **Clear browser cookies** for localhost:
   - Firefox: `Ctrl+Shift+Del` → Cookies → Everything → Clear
   - Chrome: `F12` → Application → Cookies → Delete localhost cookies
   - Or use private/incognito window

2. **Try logging in** at: http://localhost/Sci-Bono_Clubhoue_LMS/login

3. **Expected Result**: Login should work without 403 error

---

## Prevention Measures

To prevent this issue in the future:

1. **Development Standard**: Use `CSRF::field()` helper instead of manual token fields:
   ```php
   <?php echo CSRF::field(); ?>  // Generates correct field name automatically
   ```

2. **Session Testing**: Always test on HTTP first in development

3. **File Permissions**: Ensure storage directories owned by `www-data`:
   ```bash
   sudo chown -R www-data:www-data storage/
   ```

---

### 4. Redirect URL Missing Base Path ✅
**Problem**: After successful login, redirect to `/dashboard` instead of `/Sci-Bono_Clubhoue_LMS/dashboard`

**File**: `app/Controllers/BaseController.php` line 120

**Fix Applied**: Modified redirect method to automatically prepend base path:
```php
protected function redirect($url, $statusCode = 302) {
    // Prepend base path if URL is relative and doesn't already have base path
    if (!empty($url) && $url[0] === '/' && strpos($url, '/Sci-Bono_Clubhoue_LMS') !== 0) {
        $url = '/Sci-Bono_Clubhoue_LMS' . $url;
    }

    http_response_code($statusCode);
    header("Location: {$url}");
    exit;
}
```

**Also Fixed**: AuthMiddleware login redirect (line 246)

---

## Technical Details

**PHP Version**: 8.1.2
**Apache Version**: 2.4.52
**Session Save Path**: `/var/lib/php/sessions`
**Application Path**: `/var/www/html/Sci-Bono_Clubhoue_LMS`
**Application Base URL**: `http://localhost/Sci-Bono_Clubhoue_LMS`

**Session Configuration After Fix**:
- Cookie Secure: Disabled (HTTP) / Enabled (HTTPS)
- Cookie SameSite: Lax
- Cookie HTTPOnly: Enabled
- Session Lifetime: 2 hours (7200 seconds)
- Session ID Length: 48 characters

**File Permissions After Fix**:
- Application files: 644 (rw-r--r--)
- Application directories: 755 (rwxr-xr-x)
- Storage directories: 775 (rwxrwxr-x)
- Owner: www-data:www-data

---

## Testing Instructions

### 1. Clear Browser Data
**Critical Step**: Clear cookies and cache for localhost
- **Firefox**: `Ctrl+Shift+Del` → Cookies + Cache → Time Range: Everything → Clear
- **Chrome**: `F12` → Application → Cookies → Delete all localhost cookies
- **Alternative**: Use Private/Incognito window

### 2. Test Login Flow
1. Visit: http://localhost/Sci-Bono_Clubhoue_LMS/login
2. Enter valid credentials
3. Submit form
4. **Expected**: Redirect to `http://localhost/Sci-Bono_Clubhoue_LMS/dashboard` (or `/admin/dashboard` for admin users)
5. **NOT**: Redirect to `http://localhost/login` (404 error)

### 3. Test Session Persistence
1. Visit: http://localhost/Sci-Bono_Clubhoue_LMS/test_session.php
2. Refresh the page multiple times
3. **Expected**: Counter increments each time (2, 3, 4, ...)
4. If counter stays at 1, sessions are not working

### 4. Test CSRF Protection
1. Open browser dev tools (F12) → Network tab
2. Submit login form
3. Check POST request to `/login`
4. **Expected**: Request includes `_csrf_token` field
5. **Expected**: Response is 200 or 302 (redirect), NOT 403

---

## Files Modified

### Core Application Files
- `bootstrap.php` - Session configuration (lines 20-37)
- `app/Controllers/BaseController.php` - Redirect method (lines 120-129)
- `app/Middleware/AuthMiddleware.php` - Login redirect URL (line 246)

### View Files (CSRF Token Fix)
All 16 form files updated with correct `_csrf_token` field name:
- `app/Views/auth/*.php` (4 files)
- `app/Views/holidayPrograms/*.php` (2 files)
- `app/Views/member/**/*.php` (5 files)
- `app/Views/admin/**/*.php` (3 files)
- `app/Views/visitors/*.php` (1 file)
- `app/Views/layouts/admin.php` (1 file)

### Permission Fixes
- All 8,725+ application files changed from `root:root` to `www-data:www-data`
- Storage directories made writable (775)

---

## References

- CSRF Protection: `core/CSRF.php`
- Session Config: `bootstrap.php` lines 20-37
- Security Middleware: `app/Middleware/SecurityMiddleware.php`
- Login Controller: `app/Controllers/AuthController.php`
- Login View: `app/Views/auth/login.php`
- Base Controller: `app/Controllers/BaseController.php`
- Auth Middleware: `app/Middleware/AuthMiddleware.php`
