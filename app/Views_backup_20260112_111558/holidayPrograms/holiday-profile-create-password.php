<?php
/**
 * Password Creation View
 *
 * Form for creating password after email verification
 * Data passed from ProfileController@createPassword():
 * - $email: Verified email address
 * - $csrfToken: CSRF protection token
 * - $success: Success message from verification
 *
 * Phase 3 Week 3: Updated to use ProfileController
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Password - Sci-Bono Clubhouse</title>
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

        .password-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }

        .password-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .password-header i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .password-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .password-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .password-body {
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

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .email-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .email-display i {
            color: #28a745;
        }

        .email-display strong {
            color: #495057;
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
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .password-requirements {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 0.9rem;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }

        .password-requirements li {
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .password-strength {
            margin-top: 10px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .password-strength-weak {
            background: #dc3545;
            width: 33%;
        }

        .password-strength-medium {
            background: #ffc107;
            width: 66%;
        }

        .password-strength-strong {
            background: #28a745;
            width: 100%;
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
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @media (max-width: 480px) {
            .password-container {
                border-radius: 0;
            }

            .password-header {
                padding: 30px 20px;
            }

            .password-header h1 {
                font-size: 1.5rem;
            }

            .password-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="password-container">
        <!-- Header -->
        <div class="password-header">
            <i class="fas fa-key"></i>
            <h1>Create Your Password</h1>
            <p>Secure your holiday program account</p>
        </div>

        <!-- Form Body -->
        <div class="password-body">
            <!-- Success Message -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($_SESSION['password_error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['password_error']); unset($_SESSION['password_error']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Email Display -->
            <div class="email-display">
                <i class="fas fa-envelope"></i>
                <div>
                    <small style="color: #6c757d;">Creating password for:</small><br>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>
            </div>

            <!-- Password Requirements -->
            <div class="password-requirements">
                <h4><i class="fas fa-info-circle"></i> Password Requirements:</h4>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Use a combination of letters and numbers</li>
                    <li>Avoid common passwords</li>
                </ul>
            </div>

            <!-- Password Form -->
            <form action="/Sci-Bono_Clubhoue_LMS/holiday-create-password" method="POST" id="passwordForm">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="Enter your password"
                               required
                               minlength="8"
                               autofocus>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               class="form-control"
                               placeholder="Confirm your password"
                               required
                               minlength="8">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-check"></i>
                    <span>Create Password</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('passwordForm');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            // Length check
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;

            // Character variety
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;

            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('password-strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('password-strength-medium');
            } else {
                strengthBar.classList.add('password-strength-strong');
            }
        });

        // Form validation
        form.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPasswordInput.focus();
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                passwordInput.focus();
                return false;
            }
        });
    </script>
</body>
</html>
