<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="public/assets/css/signUpStyles.css">
    <link rel="stylesheet" href="public/assets/css/cssRest.css">
    <link rel="stylesheet" href="public/assets/css/header.css">
</head>
<body id="signup">
    <!-- Header with logos -->
     <div class="header">
     <?php include 'header.php'; ?>
     </div>
    <!-- Main content -->	
    <main id="container-signup">
        <div class="mobile-content">
            <div class="mobileHeader">
                <h1>Sci-Bono Clubhouse</h1>
            </div>
            <div class="signup_img">
                <!-- Mobile image -->
                <img class="mobile-image" src="public/assets/images/mobileSigninImg.svg" alt="Mobile phone illustration with a person" width="301" height="303">
                <!-- Desktop image -->
                <img class="desktop-image" src="public/assets/images/SignUp_img.svg" alt="Mobile phone illustration with a person and a form" width="301" height="303">
            </div>
        </div>
        <div class="signup_form">
            <h1>Clubhouse Registration</h1>
            <h3>Registration Details</h3>
            <form id="signup_form" method="POST" action="signup_process.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input class="input_field" type="text" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="surname">Surname:</label>
                        <input class="input_field" type="text" name="surname" id="surname" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input class="input_field" type="text" name="username" id="username" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select name="gender" id="gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="user_type">Member Type:</label>
                        <select name="user_type" id="user_type">
                            <option value="member">Member</option>
                            <option value="mentor">Mentor</option>
                            <option value="admin">Admin</option>
                            <option value="alumni">Alumni</option>
                            <option value="community">Community</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="center">Clubhouse Center:</label>
                        <select id="center" name="center" required>
                            <option value="Sci-Bono Clubhouse">Sci-Bono Clubhouse</option>
                            <option value="Waverly Girls Solar Lab">Waverly Girls Solar Lab</option>
                            <option value="Mapetla Solar Lab">Mapetla Solar Lab</option>
                            <option value="Emdeni Solar Lab">Emdeni Solar Lab</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input class="input_field" type="password" name="password" id="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input class="input_field" type="password" name="confirm_password" id="confirm_password" required>
                    </div>
                </div>
                
                <input class="btn_signup" type="submit" value="Sign Up">
                
                <div class="form_links">
                    <a href="login.php">Already have an account</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>