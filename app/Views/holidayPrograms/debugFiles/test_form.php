<?php
// Minimal Form Test - Save as test_form.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Minimal Form Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: green; color: white; padding: 15px; margin: 15px 0; border-radius: 8px;'>";
    echo "<h3>✅ POST REQUEST RECEIVED SUCCESSFULLY!</h3>";
    echo "<p>The server can process POST requests. The issue is specific to your registration form.</p>";
    echo "<h4>POST Data:</h4>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "</div>";
    
    // Test database connection
    try {
        require_once '../../../server.php';
        
        if (isset($conn) && $conn) {
            echo "<div style='background: blue; color: white; padding: 15px; margin: 15px 0; border-radius: 8px;'>";
            echo "<h3>✅ DATABASE CONNECTION SUCCESSFUL!</h3>";
            echo "<p>Database connection is working fine.</p>";
            echo "</div>";
            
            // Test a simple insert
            $testSql = "SELECT COUNT(*) as count FROM holiday_programs";
            $result = $conn->query($testSql);
            
            if ($result) {
                $row = $result->fetch_assoc();
                echo "<div style='background: purple; color: white; padding: 15px; margin: 15px 0; border-radius: 8px;'>";
                echo "<h3>✅ DATABASE QUERY SUCCESSFUL!</h3>";
                echo "<p>Found " . $row['count'] . " holiday programs in database.</p>";
                echo "</div>";
            }
        } else {
            echo "<div style='background: red; color: white; padding: 15px; margin: 15px 0; border-radius: 8px;'>";
            echo "<h3>❌ DATABASE CONNECTION FAILED!</h3>";
            echo "<p>Cannot connect to database.</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: red; color: white; padding: 15px; margin: 15px 0; border-radius: 8px;'>";
        echo "<h3>❌ DATABASE ERROR!</h3>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
} else {
    echo "<p>No POST data received yet. Please submit the form below:</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Minimal Form Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #007cba; color: white; padding: 15px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #005a8a; }
    </style>
</head>
<body>
    <form method="POST" action="">
        <h3>🧪 Test Form Submission</h3>
        <p>This form tests if basic POST processing works on your server.</p>
        
        <div class="form-group">
            <label>First Name:</label>
            <input type="text" name="first_name" value="Test" required>
        </div>
        
        <div class="form-group">
            <label>Last Name:</label>
            <input type="text" name="last_name" value="User" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="test@example.com" required>
        </div>
        
        <div class="form-group">
            <label>Registration Type:</label>
            <select name="mentor_registration">
                <option value="0">Participant</option>
                <option value="1">Mentor</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Why interested:</label>
            <textarea name="why_interested" rows="3">I want to learn new skills</textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="photo_permission" value="1" checked>
                I agree to photo permission
            </label>
        </div>
        
        <button type="submit" name="submit_registration">Test Submit</button>
    </form>
    
    <hr>
    <h3>📋 Next Steps:</h3>
    <ol>
        <li><strong>Save this as test_form.php</strong> in the same directory as your registration form</li>
        <li><strong>Access it:</strong> yoursite.com/app/Views/holidayPrograms/test_form.php</li>
        <li><strong>Submit the form</strong> and see if it works</li>
        <li><strong>If this works but your main form doesn't:</strong> The issue is in your complex form logic</li>
        <li><strong>If this doesn't work:</strong> There's a server-level issue with POST processing</li>
    </ol>
    
    <h3>🔍 Current PHP Environment:</h3>
    <ul>
        <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
        <li><strong>Request Method:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></li>
        <li><strong>Script Name:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></li>
        <li><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></li>
        <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
        <li><strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?></li>
        <li><strong>Upload Max Size:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
        <li><strong>Post Max Size:</strong> <?php echo ini_get('post_max_size'); ?></li>
    </ul>
</body>
</html>