<?php
session_start();
require_once '../../../server.php';

// Initialize variables
$error = '';
$email = '';

// Check if already logged in
if (isset($_SESSION['holiday_logged_in']) && $_SESSION['holiday_logged_in'] === true) {
    header('Location: holiday-dashboard.php');
    exit();
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Check in holiday_program_attendees table first (for holiday program participants)
        $sql = "SELECT id, first_name, last_name, email, password, mentor_registration, mentor_status, program_id 
                FROM holiday_program_attendees 
                WHERE email = ? AND password IS NOT NULL";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['holiday_logged_in'] = true;
                $_SESSION['holiday_user_id'] = $user['id'];
                $_SESSION['holiday_email'] = $user['email'];
                $_SESSION['holiday_name'] = $user['first_name'];
                $_SESSION['holiday_surname'] = $user['last_name'];
                $_SESSION['holiday_is_mentor'] = $user['mentor_registration'];
                $_SESSION['holiday_mentor_status'] = $user['mentor_status'];
                $_SESSION['holiday_program_id'] = $user['program_id'];
                $_SESSION['holiday_user_type'] = $user['mentor_registration'] ? 'mentor' : 'member';
                
                // Update last login
                $update_sql = "UPDATE holiday_program_attendees SET last_login = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                // Redirect to dashboard
                header('Location: holiday-dashboard.php');
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            // Check in main users table (for admin/staff access)
            $sql = "SELECT id, username, email, password, name, surname, user_type 
                    FROM users 
                    WHERE email = ? AND user_type IN ('admin', 'mentor')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables for admin/staff
                    $_SESSION['holiday_logged_in'] = true;
                    $_SESSION['holiday_user_id'] = $user['id'];
                    $_SESSION['holiday_email'] = $user['email'];
                    $_SESSION['holiday_name'] = $user['name'];
                    $_SESSION['holiday_surname'] = $user['surname'];
                    $_SESSION['holiday_is_mentor'] = false;
                    $_SESSION['holiday_mentor_status'] = null;
                    $_SESSION['holiday_program_id'] = null;
                    $_SESSION['holiday_user_type'] = $user['user_type'];
                    $_SESSION['holiday_is_admin'] = ($user['user_type'] === 'admin');
                    
                    // Redirect to dashboard
                    header('Location: holiday-dashboard.php');
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "No account found with this email address. Please register first.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sci-Bono Clubhouse Holiday Programs</title>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .login-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            height: 60px;
            margin-bottom: 20px;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: var(--text-light);
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
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
            outline: none;
        }
        
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-right: 8px;
        }
        
        .forgot-password {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .login-button:hover {
            background-color: var(--purple-dark);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
        }
        
        .register-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .error-message {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger);
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .user-options {
            margin-top: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .option-label {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
            text-align: center;
        }
        
        .option-description {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 20px;
            text-align: center;
        }
        
        .option-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .option-button {
            flex: 1;
            min-width: 150px;
            text-align: center;
            padding: 15px 10px;
            background-color: var(--white);
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-dark);
        }
        
        .option-button:hover {
            background-color: rgba(108, 99, 255, 0.05);
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .option-icon {
            display: block;
            font-size: 2rem;
            margin-bottom: 8px;
            color: var(--primary);
        }
        
        .option-text {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        
        :root {
            --primary: #6c63ff;
            --purple-dark: #5a52d5;
            --dark: #333;
            --text-light: #666;
            --text-dark: #333;
            --white: #fff;
            --danger: #f44336;
        }
    </style>
</head>
<body>
    <?php include './holidayPrograms-header.php'; ?>
    
    <div class="login-container">
        <div class="login-header">
            <img src="../../../public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse" style="filter: invert(1);">
            <h1>Welcome Back</h1>
            <p>Sign in to access your holiday programs</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <div class="login-options">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                
                <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
            </div>
            
            <button type="submit" name="login" class="login-button">Sign In</button>
            
            <div class="login-footer">
                Don't have an account? <a href="holidayProgramRegistration.php?program_id=1" class="register-link">Register Now</a>
            </div>
        </form>
        
        <div class="user-options">
            <div class="option-label">New User? Choose Your Path:</div>
            <div class="option-description">Select the option that best describes you to get started:</div>
            
            <div class="option-buttons">
                <a href="holidayProgramRegistration.php?program_id=1" class="option-button">
                    <span class="option-icon"><i class="fas fa-user-plus"></i></span>
                    <span class="option-text">New to Sci-Bono</span>
                </a>
                
                <a href="holidayProgramRegistration.php?program_id=1&member=1" class="option-button">
                    <span class="option-icon"><i class="fas fa-users"></i></span>
                    <span class="option-text">Clubhouse Member</span>
                </a>
                
                <a href="holidayProgramRegistration.php?program_id=1&mentor=1" class="option-button">
                    <span class="option-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                    <span class="option-text">Become a Mentor</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation (visible on mobile only) -->
    <nav class="mobile-nav">
        <a href="../../home.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-home"></i>
            </div>
            <span>Home</span>
        </a>
        <a href="holidayProgramIndex.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <span>Programs</span>
        </a>
        <a href="../../app/Views/learn.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-book"></i>
            </div>
            <span>Learn</span>
        </a>
        <a href="holiday-dashboard.php" class="mobile-menu-item">
            <div class="mobile-menu-icon">
                <i class="fas fa-user"></i>
            </div>
            <span>Account</span>
        </a>
    </nav>

    <script>
        // Add smooth transitions and form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            // Add real-time validation
            emailInput.addEventListener('blur', function() {
                if (this.value && !isValidEmail(this.value)) {
                    this.style.borderColor = '#f44336';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            // Form submission validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                if (!emailInput.value.trim()) {
                    emailInput.style.borderColor = '#f44336';
                    isValid = false;
                }
                
                if (!passwordInput.value.trim()) {
                    passwordInput.style.borderColor = '#f44336';
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields correctly.');
                }
            });
        });
    </script>
</body>
</html>