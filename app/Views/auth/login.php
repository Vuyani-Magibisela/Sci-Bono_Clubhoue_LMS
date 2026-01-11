<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Login'; ?> - Sci-Bono Clubhouse</title>

    <!-- CSRF Meta Tag -->
    <meta name="csrf-token" content="<?php echo $csrf_token ?? ''; ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/style.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/screenSizes.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/cssRest.css">
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/header.css">

    <script>
        function validateForm() {
            var identifier = document.forms["loginForm"]["identifier"].value;
            var password = document.forms["loginForm"]["password"].value;

            if (identifier == "") {
                alert("Username or Email must be filled out");
                return false;
            }
            if (password == "") {
                alert("Password must be filled out");
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
            <img src="/Sci-Bono_Clubhoue_LMS/public/assets/images/SignIn_Img.png" alt="Large image of a mobile phone, human standing next to it." width="301" height="303">
        </div>

        <div class="login_form">
            <div>
                <h2>Log In</h2>

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

                <form name="loginForm" action="/Sci-Bono_Clubhoue_LMS/login" method="post" onsubmit="return validateForm()">
                    <!-- CSRF Token -->
                    <input type="hidden" name="_csrf_token" value="<?php echo $csrf_token ?? ''; ?>">

                    <label for="identifier">Username or Email:</label>
                    <input class="input_field"
                           type="text"
                           id="identifier"
                           name="identifier"
                           value="<?php echo htmlspecialchars($old_input['identifier'] ?? ''); ?>"
                           autocomplete="username">
                    <br>

                    <label for="password">Password:</label>
                    <input class="input_field"
                           type="password"
                           id="password"
                           name="password"
                           autocomplete="current-password">
                    <br>

                    <input class="btn" type="submit" value="Login">
                    <br>

                    <div class="form_links">
                        <a href="/Sci-Bono_Clubhoue_LMS/signup">Don't have an account?</a>
                        <a href="/Sci-Bono_Clubhoue_LMS/forgot-password">Forgot Password?</a>
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
