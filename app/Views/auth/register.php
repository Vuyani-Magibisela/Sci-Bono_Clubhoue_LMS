<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Register'; ?> - Sci-Bono Clubhouse</title>

    <!-- CSRF Meta Tag -->
    <meta name="csrf-token" content="<?php echo $csrf_token ?? ''; ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/signUpStyles.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/cssRest.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/header.css">

    <script>
        function validateForm() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("password_confirmation").value;

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return false;
            }

            if (password.length < 6) {
                alert("Password must be at least 6 characters long");
                return false;
            }

            return true;
        }
    </script>
</head>
<body id="signup">
    <!-- Header with logos -->
    <div class="header">
        <?php include __DIR__ . '/../../../header.php'; ?>
    </div>

    <!-- Main content -->
    <main id="container-signup">
        <div class="mobile-content">
            <div class="mobileHeader">
                <h1>Sci-Bono Clubhouse</h1>
            </div>
            <div class="signup_img">
                <!-- Mobile image -->
                <img class="mobile-image" src="/Sci-Bono_Clubhoue_LMS/public/assets/images/mobileSigninImg.svg" alt="Mobile phone illustration with a person" width="301" height="303">
                <!-- Desktop image -->
                <img class="desktop-image" src="/Sci-Bono_Clubhoue_LMS/public/assets/images/SignUp_img.svg" alt="Mobile phone illustration with a person and a form" width="301" height="303">
            </div>
        </div>

        <div class="signup_form">
            <h1>Clubhouse Registration</h1>
            <h3>Registration Details</h3>

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

            <form id="signup_form" method="POST" action="/Sci-Bono_Clubhoue_LMS/signup" onsubmit="return validateForm()">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token ?? ''; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input class="input_field"
                               type="text"
                               name="name"
                               id="name"
                               value="<?php echo htmlspecialchars($old_input['name'] ?? ''); ?>"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="surname">Surname:</label>
                        <input class="input_field"
                               type="text"
                               name="surname"
                               id="surname"
                               value="<?php echo htmlspecialchars($old_input['surname'] ?? ''); ?>"
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input class="input_field"
                               type="text"
                               name="username"
                               id="username"
                               value="<?php echo htmlspecialchars($old_input['username'] ?? ''); ?>"
                               autocomplete="username"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input class="input_field"
                               type="email"
                               name="email"
                               id="email"
                               value="<?php echo htmlspecialchars($old_input['email'] ?? ''); ?>"
                               autocomplete="email"
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select name="gender" id="gender" required>
                            <option value="Male" <?php echo (($old_input['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (($old_input['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (($old_input['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="user_type">Member Type:</label>
                        <select name="user_type" id="user_type">
                            <option value="member" <?php echo (($old_input['user_type'] ?? 'member') === 'member') ? 'selected' : ''; ?>>Member</option>
                            <option value="student" <?php echo (($old_input['user_type'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="alumni" <?php echo (($old_input['user_type'] ?? '') === 'alumni') ? 'selected' : ''; ?>>Alumni</option>
                            <option value="community" <?php echo (($old_input['user_type'] ?? '') === 'community') ? 'selected' : ''; ?>>Community</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="center">Clubhouse Center:</label>
                        <select id="center" name="center" required>
                            <option value="Sci-Bono Clubhouse" <?php echo (($old_input['center'] ?? '') === 'Sci-Bono Clubhouse') ? 'selected' : ''; ?>>Sci-Bono Clubhouse</option>
                            <option value="Waverly Girls Solar Lab" <?php echo (($old_input['center'] ?? '') === 'Waverly Girls Solar Lab') ? 'selected' : ''; ?>>Waverly Girls Solar Lab</option>
                            <option value="Mapetla Solar Lab" <?php echo (($old_input['center'] ?? '') === 'Mapetla Solar Lab') ? 'selected' : ''; ?>>Mapetla Solar Lab</option>
                            <option value="Emdeni Solar Lab" <?php echo (($old_input['center'] ?? '') === 'Emdeni Solar Lab') ? 'selected' : ''; ?>>Emdeni Solar Lab</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input class="input_field"
                               type="password"
                               name="password"
                               id="password"
                               autocomplete="new-password"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password:</label>
                        <input class="input_field"
                               type="password"
                               name="password_confirmation"
                               id="password_confirmation"
                               autocomplete="new-password"
                               required>
                    </div>
                </div>

                <input class="btn_signup" type="submit" value="Sign Up">

                <div class="form_links">
                    <a href="/Sci-Bono_Clubhoue_LMS/login">Already have an account?</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
