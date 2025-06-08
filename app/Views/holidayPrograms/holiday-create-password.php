<?php
session_start();
require_once '../../../server.php';

$error = '';
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $attendeeId = intval($_POST['attendee_id']);
    
    // Validation
    if (empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the attendee record with the password
        $sql = "UPDATE holiday_program_attendees SET password = ? WHERE id = ? AND email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sis", $hashedPassword, $attendeeId, $email);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Get the user data for session
            $sql = "SELECT id, first_name, last_name, email, mentor_registration, mentor_status, program_id 
                    FROM holiday_program_attendees 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $attendeeId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Set session variables to log them in automatically
                $_SESSION['holiday_logged_in'] = true;
                $_SESSION['holiday_user_id'] = $user['id'];
                $_SESSION['holiday_email'] = $user['email'];
                $_SESSION['holiday_name'] = $user['first_name'];
                $_SESSION['holiday_surname'] = $user['last_name'];
                $_SESSION['holiday_is_mentor'] = $user['mentor_registration'];
                $_SESSION['holiday_mentor_status'] = $user['mentor_status'];
                $_SESSION['holiday_program_id'] = $user['program_id'];
                $_SESSION['holiday_user_type'] = $user['mentor_registration'] ? 'mentor' : 'member';
                $_SESSION['holiday_is_admin'] = false;
                
                // Redirect to dashboard
                header('Location: holiday-dashboard.php?welcome=1');
                exit();
            }
        } else {
            $error = "Failed to create password. Please try again.";
        }
    }
}

// If we reach here, there was an error or it's a GET request
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Password - Sci-Bono Clubhouse Holiday Programs</title>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .password-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .password-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .password-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
        }
        
        .error-message {
            background-color: rgba(244, 67, 54, 0.1);
            color: #f44336;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .submit-button {
            width: 100%;
            padding: 12px;
            background-color: #6c63ff;
            color: white;
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .submit-button:hover {
            background-color: #5a52d5;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include './holidayPrograms-header.php'; ?>
    
    <div class="password-container">
        <div class="password-header">
            <h1>Create Your Password</h1>
            <p>Complete your account setup to access your holiday program dashboard</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php if (isset($_POST['email'])): ?>
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email']); ?>">
            <?php endif; ?>
            <?php if (isset($_POST['attendee_id'])): ?>
                <input type="hidden" name="attendee_id" value="<?php echo htmlspecialchars($_POST['attendee_id']); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="password">Password <span style="color: red;">*</span></label>
                <input type="password" id="password" name="password" class="form-input" required>
                <div class="help-text">Must be at least 8 characters long</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password <span style="color: red;">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                <div class="help-text">Re-enter your password to confirm</div>
            </div>
            
            <button type="submit" class="submit-button">
                <i class="fas fa-key"></i> Create Password & Access Dashboard
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="holidayProgramLogin.php" style="color: #6c63ff; text-decoration: none;">
                Already have a password? Login here
            </a>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#f44336';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
        });
    </script>
</body>
</html>