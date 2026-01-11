<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Forgot Password'; ?> - Sci-Bono Clubhouse</title>

    <!-- CSRF Meta Tag -->
    <meta name="csrf-token" content="<?php echo $csrf_token ?? ''; ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/style.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/screenSizes.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/cssRest.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/header.css">

    <script>
        function validateForm() {
            var email = document.forms["forgotPasswordForm"]["email"].value;
            if (email == "") {
                alert("Email must be filled out");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="header">
        <?php include __DIR__ . '/../../../header.php'; ?>
    </div>

    <main id="container-login">
        <div class="login_img">
            <img src="/Sci-Bono_Clubhoue_LMS/public/assets/images/SignIn_Img.png" alt="Illustration" width="301" height="303">
        </div>

        <div class="login_form">
            <div>
                <h2>Forgot Password</h2>
                <p style="margin-bottom: 20px; color: #666; font-size: 14px;">
                    Enter your email address and we'll send you a link to reset your password.
                </p>

                <!-- Flash Messages -->
                <?php include __DIR__ . '/../partials/alerts.php'; ?>

                <!-- Validation Errors -->
                <?php if (isset($validation_errors) && !empty($validation_errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($validation_errors as $field => $errors): ?>
                            <?php foreach ((array)$errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form name="forgotPasswordForm" action="/Sci-Bono_Clubhoue_LMS/forgot-password" method="post" onsubmit="return validateForm()">
                    <!-- CSRF Token -->
                    <input type="hidden" name="_csrf_token" value="<?php echo $csrf_token ?? ''; ?>">

                    <label for="email">Email Address:</label>
                    <input class="input_field"
                           type="email"
                           id="email"
                           name="email"
                           value="<?php echo htmlspecialchars($old_input['email'] ?? ''); ?>"
                           autocomplete="email"
                           required>
                    <br>

                    <input class="btn" type="submit" value="Send Reset Link">
                    <br>

                    <div class="form_links">
                        <a href="/Sci-Bono_Clubhoue_LMS/login">Back to Login</a>
                        <a href="/Sci-Bono_Clubhoue_LMS/signup">Create an Account</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <style>
        .form_links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }

        .form_links a {
            color: #6c63ff;
            text-decoration: none;
            font-size: 14px;
        }

        .form_links a:hover {
            text-decoration: underline;
        }
    </style>
</body>
</html>
