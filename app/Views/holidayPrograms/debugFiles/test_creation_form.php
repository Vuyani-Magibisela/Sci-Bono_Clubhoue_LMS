<?php
// Test Creation Form - DELETE AFTER DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Testing Holiday Program Creation Form</h2>";
echo "<p><strong>WARNING:</strong> This is a debug version. Remove after fixing!</p>";

try {
    echo "<p>Step 1: Starting session...</p>";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Session started successfully</p>";

    echo "<p>Step 2: Checking authentication...</p>";
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || $_SESSION['user_type'] !== 'admin') {
        echo "<p>❌ Authentication failed. Redirecting...</p>";
        echo "<p>Current session: <pre>" . print_r($_SESSION, true) . "</pre></p>";
        // Don't redirect for debugging
        // header("Location: ../../../login.php");
        // exit;
    } else {
        echo "<p>✅ Authentication successful</p>";
    }

    echo "<p>Step 3: Including server.php...</p>";
    require_once '../../../server.php';
    echo "<p>✅ Server included successfully</p>";

    echo "<p>Step 4: Testing database connection...</p>";
    if (isset($conn) && $conn->ping()) {
        echo "<p>✅ Database connection active</p>";
    } else {
        echo "<p>❌ Database connection failed</p>";
    }

    echo "<p>Step 5: Including controller...</p>";
    require_once '../../Controllers/HolidayProgramCreationController.php';
    echo "<p>✅ Controller included successfully</p>";

    echo "<p>Step 6: Creating controller instance...</p>";
    $creationController = new HolidayProgramCreationController($conn);
    echo "<p>✅ Controller created successfully</p>";

    echo "<h3>🎉 All components loaded successfully!</h3>";
    echo "<p>The issue might be in form submission or database operations.</p>";
    
    // Test basic form rendering
    echo "<h3>Basic Form Test:</h3>";
    echo '<form method="POST" action="">
            <input type="text" name="test_field" placeholder="Test input">
            <button type="submit" name="test_submit">Test Submit</button>
          </form>';
    
    if (isset($_POST['test_submit'])) {
        echo "<p>✅ Form submission works! Test value: " . htmlspecialchars($_POST['test_field'] ?? 'empty') . "</p>";
    }

} catch (Exception $e) {
    echo "<h3>❌ Error Caught:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<h3>❌ Fatal Error Caught:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If this loads successfully, try <a href='holidayProgramCreationForm.php'>the original form</a></li>";
echo "<li>If you see errors above, those are the issues to fix</li>";
echo "<li>Remember to delete all debug files when done!</li>";
echo "</ul>";
?>