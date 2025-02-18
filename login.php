<?php
	session_start();

	if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
		echo '<p class="error">You have been logged out due to inactivity.</p>';
	}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login Page</title>
	<link rel="stylesheet" href="style.css">
	<link rel="stylesheet" href="public/assets/css/screenSizes.css">
	<link rel="stylesheet" href="public/assets/css/style">
	<script>
		function validateForm() {
			var username = document.forms["loginForm"]["username"].value;
			var password = document.forms["loginForm"]["password"].value;

			if (username == "") {
				alert("Username must be filled out");
				return false;
			}
			if (password == "") {
				alert("Password must be filled out");
				return false;
			}
		}
	</script>
</head>
<body>
	<?php 
		include 'header.php';
	?>
	<main id="container-login">
		<div class="login_img">
			<img src="public/assets/images/SignIn_Img.png" alt="Large image of a mobile phone, human standing next to it."  width="301" height="303">
		</div>
		<div class="login_form">
			<div>
			<h2>Log In</h2>
			<form name="loginForm" action="login_process.php" method="post" onsubmit="return validateForm()">
				<label for="username">Username:</label>
				<input class="input_field" type="text" id="username" name="username"><br>
				<label for="password">Password:</label>
				<input class="input_field" type="password" id="password" name="password"><br>
				<input class="btn" type="submit" value="Login"><br>
				<a href="signup.php">Dont have an account</a>
			</form>
			</div>
		</div>
	</main>
</body>
</html>
