<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Change Password'; ?> - Sci-Bono Clubhouse</title>

    <!-- CSRF Meta Tag -->
    <meta name="csrf-token" content="<?php echo $csrf_token ?? ''; ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/style.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/screenSizes.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/cssRest.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/header.css">

    <script>
        function validateForm() {
            var currentPassword = document.getElementById("current_password").value;
            var newPassword = document.getElementById("new_password").value;
            var confirmPassword = document.getElementById("new_password_confirmation").value;

            if (currentPassword == "") {
                alert("Current password must be filled out");
                return false;
            }

            if (newPassword == "") {
                alert("New password must be filled out");
                return false;
            }

            if (newPassword.length < 6) {
                alert("New password must be at least 6 characters long");
                return false;
            }

            if (newPassword !== confirmPassword) {
                alert("New passwords do not match!");
                return false;
            }

            if (currentPassword === newPassword) {
                alert("New password must be different from current password");
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
                <h2>Change Password</h2>
                <p style="margin-bottom: 20px; color: #666; font-size: 14px;">
                    Enter your current password and choose a new one.
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

                <form name="changePasswordForm" action="/Sci-Bono_Clubhoue_LMS/change-password" method="post" onsubmit="return validateForm()">
                    <!-- CSRF Token -->
                    <input type="hidden" name="_csrf_token" value="<?php echo $csrf_token ?? ''; ?>">

                    <label for="current_password">Current Password:</label>
                    <input class="input_field"
                           type="password"
                           id="current_password"
                           name="current_password"
                           autocomplete="current-password"
                           required>
                    <br>

                    <label for="new_password">New Password:</label>
                    <input class="input_field"
                           type="password"
                           id="new_password"
                           name="new_password"
                           autocomplete="new-password"
                           required>
                    <br>

                    <label for="new_password_confirmation">Confirm New Password:</label>
                    <input class="input_field"
                           type="password"
                           id="new_password_confirmation"
                           name="new_password_confirmation"
                           autocomplete="new-password"
                           required>
                    <br>

                    <input class="btn" type="submit" value="Change Password">
                    <br>

                    <div class="form_links">
                        <a href="/Sci-Bono_Clubhoue_LMS/profile">Back to Profile</a>
                        <a href="/Sci-Bono_Clubhoue_LMS/dashboard">Go to Dashboard</a>
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
