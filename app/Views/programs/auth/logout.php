<?php
session_start();

// Clear all holiday program session variables
$holidaySessionVars = [
    'holiday_logged_in',
    'holiday_user_id',
    'holiday_email',
    'holiday_name',
    'holiday_surname',
    'holiday_is_mentor',
    'holiday_mentor_status',
    'holiday_program_id',
    'holiday_user_type',
    'holiday_is_admin'
];

foreach ($holidaySessionVars as $var) {
    if (isset($_SESSION[$var])) {
        unset($_SESSION[$var]);
    }
}

// Regenerate session ID for security
session_regenerate_id(true);

// Redirect to holiday programs index with success message
header('Location: holidayProgramIndex.php?logout=success');
exit();
?>