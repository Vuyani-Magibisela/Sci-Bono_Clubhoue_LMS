<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sign Up</title>
	<link rel="stylesheet" href="style.css">
</head>
<body>
	<?php 
		include 'header.php';
	?>
	<main id="contianer-signup">
		<div class="signup_img">
			<img src="public/images/SignIn_Img.png" alt="Large image of a mobile phone, human standing next to it."  width="301" height="303">
		</div>

		<div class="signup_form">
			<div>
				<h1>Clubhouse Registration</h1>
				<h2>Registration Details</h2>
				<form id="signup_form" method="POST" action="signup_process.php">
					<label for="name">Name:</label><br><br>
					<input class="input_field" type="text" name="name" id="name" required><br><br>

					<label for="surname">Surname:</label><br><br>
					<input class="input_field" type="text" name="surname" id="surname" required><br><br>
					
					<label for="username">Username:</label><br><br>
					<input class="input_field" type="text" name="username" id="username" required><br><br>
					
					<label for="uesr_type">Member Type</label><br><br>
					<select name="user_type">
						<option value="member">Member</option>
						<option value="mentor">Mentor</option>
						<option value="admin">Admin</option>
					</select><br><br>

					<label for="password">Password:</label><br><br>
					<input class="input_field" type="password" name="password" id="password" required><br><br>
					
					<label for="confirm_password">Confirm Password:</label><br><br>
					<input class="input_field" type="password" name="confirm_password" id="confirm_password" required><br><br>
					
					<input class="btn_signup" type="submit" value="Sign Up"><br><br>
					<a href="login.php">already have an account</a>

				</form>
			</div>
		
		</div>
	</main>
	
	
</body>
</html>
