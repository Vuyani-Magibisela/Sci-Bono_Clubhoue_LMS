<?php
/**
 * Holiday Program Login View
 *
 * Login form for holiday program attendees
 * Data passed from ProfileController@login():
 * - $csrfToken: CSRF protection token
 * - $error: Error message if any
 *
 * Phase 3 Week 3: Updated to use ProfileController
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Program Login - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/holidayProgramStyles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .login-body {
            padding: 40px 30px;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }

        .divider span {
            padding: 0 15px;
            color: #999;
            font-size: 0.9rem;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .additional-links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .additional-links a {
            color: #666;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .additional-links a:hover {
            color: #667eea;
        }

        @media (max-width: 480px) {
            .login-container {
                border-radius: 0;
            }

            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .login-body {
                padding: 30px 20px;
            }

            .additional-links {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <i class="fas fa-graduation-cap"></i>
            <h1>Holiday Program Login</h1>
            <p>Access your holiday program dashboard</p>
        </div>

        <!-- Login Form -->
        <div class="login-body">
            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (isset($_SESSION['logout_success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['logout_success']); unset($_SESSION['logout_success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['login_success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['login_success']); unset($_SESSION['login_success']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="/Sci-Bono_Clubhoue_LMS/holiday-login" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-control"
                               placeholder="Enter your email"
                               required
                               autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="Enter your password"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <span>OR</span>
            </div>

            <!-- Register Link -->
            <div class="links">
                <p>Don't have an account?</p>
                <a href="/Sci-Bono_Clubhoue_LMS/programs"><i class="fas fa-user-plus"></i> Register for a Program</a>
            </div>

            <!-- Additional Links -->
            <div class="additional-links">
                <a href="/Sci-Bono_Clubhoue_LMS/programs">
                    <i class="fas fa-arrow-left"></i> Back to Programs
                </a>
                <a href="mailto:info@sci-bono.co.za">
                    <i class="fas fa-question-circle"></i> Need Help?
                </a>
            </div>
        </div>
    </div>
</body>
</html>
