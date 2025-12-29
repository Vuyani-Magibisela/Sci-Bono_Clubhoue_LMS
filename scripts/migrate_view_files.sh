#!/bin/bash
# Tier 3 View Files Migration Script
# Replaces server.php with bootstrap.php in active view files

VIEWS_DIR="/var/www/html/Sci-Bono_Clubhoue_LMS/app/Views"

# List of active view files to migrate (excluding debug files and already migrated)
declare -a FILES=(
    "visitorsPage.php"
    "dailyAttendanceRegister.php"
    "monthlyReportForm.php"
    "monthlyReportView.php"
    "statsDashboard.php"
    "user_list.php"
    "admin/manage-courses.php"
    "admin/manage-lessons.php"
    "admin/manage-modules.php"
    "admin/manage-sections.php"
    "admin/manage-activities.php"
    "admin/manage-course-content.php"
    "admin/create-course.php"
    "admin/course.php"
    "admin/enhanced-manage-courses.php"
    "holidayPrograms/holiday-dashboard.php"
    "holidayPrograms/holiday-profile.php"
    "holidayPrograms/holiday-create-password.php"
    "holidayPrograms/holiday-profile-verify-email.php"
    "holidayPrograms/holiday-program-details-term.php"
    "holidayPrograms/holidayProgramAdminDashboard.php"
    "holidayPrograms/holidayProgramCreationForm.php"
    "holidayPrograms/holidayProgramRegistration.php"
    "holidayPrograms/process_registration.php"
    "holidayPrograms/show_errors.php"
    "holidayPrograms/simple_registration.php"
    "holidayPrograms/api/get-all-program-status.php"
)

echo "=== Tier 3 View Files Migration ==="
echo ""
MIGRATED=0
SKIPPED=0
ERRORS=0

for file in "${FILES[@]}"; do
    FILEPATH="$VIEWS_DIR/$file"
    
    if [ ! -f "$FILEPATH" ]; then
        echo "⚠ SKIP: $file (not found)"
        ((SKIPPED++))
        continue
    fi
    
    # Check if file contains server.php reference
    if ! grep -q "server\.php" "$FILEPATH"; then
        echo "⚠ SKIP: $file (no server.php reference)"
        ((SKIPPED++))
        continue
    fi
    
    # Create backup
    cp "$FILEPATH" "${FILEPATH}.bak"
    
    # Replace server.php with bootstrap.php
    # Handle various include/require patterns
    sed -i "s|require_once ['\"].*server\.php['\"]|require_once __DIR__ . '/../../bootstrap.php'|g" "$FILEPATH"
    sed -i "s|require ['\"].*server\.php['\"]|require __DIR__ . '/../../bootstrap.php'|g" "$FILEPATH"
    sed -i "s|include_once ['\"].*server\.php['\"]|require_once __DIR__ . '/../../bootstrap.php'|g" "$FILEPATH"
    sed -i "s|include ['\"].*server\.php['\"]|require_once __DIR__ . '/../../bootstrap.php'|g" "$FILEPATH"
    
    # Verify the change was made
    if grep -q "bootstrap\.php" "$FILEPATH" && ! grep -q "server\.php" "$FILEPATH"; then
        echo "✓ MIGRATED: $file"
        ((MIGRATED++))
        # Remove backup
        rm "${FILEPATH}.bak"
    else
        echo "✗ ERROR: $file (migration failed)"
        ((ERRORS++))
        # Restore from backup
        mv "${FILEPATH}.bak" "$FILEPATH"
    fi
done

echo ""
echo "=== Migration Summary ==="
echo "Migrated: $MIGRATED files"
echo "Skipped: $SKIPPED files"
echo "Errors: $ERRORS files"
echo ""

if [ $ERRORS -eq 0 ]; then
    echo "✓ All migrations successful!"
else
    echo "⚠ Some migrations failed - check errors above"
    exit 1
fi
