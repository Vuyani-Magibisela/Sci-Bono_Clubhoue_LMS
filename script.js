// JavaScript function to handle sign-in with password verification
function signIn(userId) {
    // Prompt the user to enter their password
    var password = prompt("Please enter your password:");

    // Validate password via AJAX
    if (password !== null) { // Check if the user clicked Cancel or OK in the prompt
        // Send AJAX request to validate password
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    // If password is validated, proceed with sign-in action
                    // You can implement this part based on the response from the server
                    var response = xhr.responseText;
                    if (response === "valid") {
                        // Proceed with sign-in action (send another AJAX request)
                        // Implement this part based on your server-side logic
                        // Example:
                        // sendSignInRequest(userId);
                    } else {
                        alert("Invalid password. Please try again.");
                    }
                } else {
                    // Handle AJAX error
                    console.error("AJAX request failed:", xhr.statusText);
                }
            }
        };
        xhr.open("POST", "validate_password.php"); // Adjust the PHP file name accordingly
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send("userId=" + userId + "&password=" + encodeURIComponent(password));
    }
}
