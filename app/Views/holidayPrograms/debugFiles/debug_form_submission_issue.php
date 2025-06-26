<?php
// Debug Form Submission Issue - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Debug Form Submission Issue</h2>";
echo "<p><strong>WARNING:</strong> Remove this file after debugging!</p>";

echo "<h3>🧪 Testing Form Submission Detection</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>✅ FORM SUBMITTED SUCCESSFULLY!</h4>";
    echo "<p>The form IS posting to the server.</p>";
    echo "<p><strong>POST Data Received:</strong></p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>";
    print_r($_POST);
    echo "</pre>";
    echo "</div>";
} else {
    echo "<p>🔍 No POST data received yet. Please submit the test form below.</p>";
}

echo "<h3>📝 Simple Form Submission Test</h3>";
echo "<p>This will test if forms can submit at all on this server:</p>";
?>

<style>
    .test-form { 
        max-width: 600px; margin: 20px 0; background: #f8f9fa; 
        padding: 20px; border-radius: 8px; border: 2px solid #007cba;
    }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-group input, .form-group select { 
        width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; 
        box-sizing: border-box; font-size: 14px;
    }
    .submit-btn { 
        background: #007cba; color: white; padding: 15px 30px; 
        border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
        font-weight: bold;
    }
    .submit-btn:hover { background: #005a8a; }
    .error-test { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0; }
</style>

<form method="POST" action="" class="test-form" onsubmit="return testFormSubmission(event)">
    <h4>🧪 Form Submission Test</h4>
    <p>Fill this out and click submit to test if forms work:</p>
    
    <div class="form-group">
        <label for="test_name">Name:</label>
        <input type="text" id="test_name" name="test_name" value="Test User" required>
    </div>
    
    <div class="form-group">
        <label for="test_email">Email:</label>
        <input type="email" id="test_email" name="test_email" value="test@example.com" required>
    </div>
    
    <div class="form-group">
        <label for="test_type">Registration Type:</label>
        <select id="test_type" name="test_type" required>
            <option value="">Select Type</option>
            <option value="participant">Participant</option>
            <option value="mentor">Mentor</option>
        </select>
    </div>
    
    <div class="form-group">
        <input type="checkbox" id="test_agree" name="test_agree" value="1" required>
        <label for="test_agree">I agree to the test terms</label>
    </div>
    
    <button type="submit" name="submit_test" class="submit-btn" onclick="console.log('Submit button clicked!')">
        🚀 Submit Test Form
    </button>
</form>

<div id="javascript-test" style="margin: 20px 0;"></div>

<script>
// Test JavaScript functionality
console.log("JavaScript is working!");

document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded successfully");
    
    // Test if JavaScript can manipulate the page
    const testDiv = document.getElementById('javascript-test');
    if (testDiv) {
        testDiv.innerHTML = '<p style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px;">✅ JavaScript is working and can manipulate the DOM</p>';
        console.log("JavaScript DOM manipulation working");
    }
    
    // Test form elements
    const form = document.querySelector('.test-form');
    if (form) {
        console.log("Form found:", form);
        
        // Add event listeners to test
        form.addEventListener('submit', function(e) {
            console.log("Form submit event triggered");
            console.log("Form data:", new FormData(form));
        });
        
        // Test if submit button exists and is clickable
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            console.log("Submit button found:", submitBtn);
            submitBtn.addEventListener('click', function(e) {
                console.log("Submit button click event triggered");
            });
        }
    }
});

function testFormSubmission(event) {
    console.log("Form submission function called");
    console.log("Event:", event);
    
    // Check if required fields are filled
    const name = document.getElementById('test_name').value;
    const email = document.getElementById('test_email').value;
    const type = document.getElementById('test_type').value;
    const agree = document.getElementById('test_agree').checked;
    
    console.log("Form values:", { name, email, type, agree });
    
    if (!name || !email || !type || !agree) {
        alert("Please fill all required fields!");
        return false;
    }
    
    // If we get here, form should submit
    console.log("Form validation passed, submitting...");
    return true;
}

// Test for common JavaScript errors
window.addEventListener('error', function(e) {
    console.error("JavaScript error detected:", e.error);
    alert("JavaScript error: " + e.message);
});

// Test jQuery if it's loaded
if (typeof jQuery !== 'undefined') {
    console.log("jQuery is loaded, version:", jQuery.fn.jquery);
    jQuery(document).ready(function($) {
        console.log("jQuery document ready fired");
    });
} else {
    console.log("jQuery is NOT loaded");
}
</script>

<?php
echo "<h3>🔍 Debugging Information</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>Server Information:</h4>";
echo "<ul>";
echo "<li><strong>Request Method:</strong> " . $_SERVER['REQUEST_METHOD'] . "</li>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Content Type:</strong> " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "</li>";
echo "<li><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not set') . "</li>";
echo "<li><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</li>";
echo "</ul>";

echo "<h4>Potential Issues to Check:</h4>";
echo "<ol>";
echo "<li><strong>JavaScript Errors:</strong> Open browser DevTools (F12) → Console tab</li>";
echo "<li><strong>Network Issues:</strong> Check DevTools → Network tab when submitting</li>";
echo "<li><strong>Form Validation:</strong> Check if JavaScript is preventing submission</li>";
echo "<li><strong>Missing jQuery:</strong> Registration form might depend on jQuery</li>";
echo "<li><strong>CSRF/Security:</strong> Server might be blocking form submissions</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🛠️ Common Solutions:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>If the test form above DOES submit:</h4>";
echo "<p>The issue is specific to the registration form. Check for:</p>";
echo "<ul>";
echo "<li>JavaScript validation errors</li>";
echo "<li>Missing form fields or validation rules</li>";
echo "<li>jQuery conflicts or missing dependencies</li>";
echo "</ul>";

echo "<h4>If the test form does NOT submit:</h4>";
echo "<p>There's a server-level issue. Check for:</p>";
echo "<ul>";
echo "<li>Server security settings blocking POST requests</li>";
echo "<li>PHP configuration issues</li>";
echo "<li>Server firewall or security modules</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Submit the test form above and see if it works</li>";
echo "<li>Check browser DevTools (F12) for JavaScript errors</li>";
echo "<li>Look at Network tab to see if form submissions are being sent</li>";
echo "<li>Report back what happens with this simple test form</li>";
echo "</ol>";
?>