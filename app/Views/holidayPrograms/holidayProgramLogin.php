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
            margin-top: 20px;
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
        }
        
        .option-label {
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--text-dark);
        }
        
        .option-description {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 15px;
        }
        
        .option-buttons {
            display: flex;
            gap: 15px;
        }
        
        .option-button {
            flex: 1;
            text-align: center;
            padding: 10px;
            background-color: var(--white);
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .option-button:hover {
            background-color: rgba(108, 99, 255, 0.05);
            border-color: var(--primary);
        }
        
        .option-button.active {
            background-color: rgba(108, 99, 255, 0.1);
            border-color: var(--primary);
        }
        
        .option-icon {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: var(--primary);
        }
        
        .option-text {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
        }
    </style>
</head>
<body>
    <?php include './holidayPrograms-header.php'; ?>
    
    <div class="login-container">
        <div class="login-header">
            <img src="../../../public/assets/images/Sci-Bono logo White.png" alt="Sci-Bono Clubhouse">
            <h1>Welcome Back</h1>
            <p>Sign in to access your holiday programs</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" required>
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
            <div class="option-label">Choose Your Path:</div>
            <div class="option-description">Select the option that best describes you:</div>
            
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
</body>
</html>