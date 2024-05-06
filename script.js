// Define the signIn function
function signIn(userId) {
    // Call the signInPrompt function with the retrieved user ID
    signInPrompt(userId);
}

// Function to prompt the user to enter their password and initiate sign-in process
function signInPrompt(userId) {
    // Prompt the user to enter their password
    var password = prompt("Please enter your password:");
    if (password === null) {
        // User canceled the prompt, do nothing
        return;
    }

    // Debugging: Log the entered password
    console.log("Entered password:", password);


    // Encode the password before sending it to the server
    var encodedPassword = encodeURIComponent(password);

    console.log("encoded Password", encodedPassword);
    
    // Perform sign-in process with the provided user ID and password
    sendSignInRequest(userId, password);
}

// Function to send a sign-in request to the server
function sendSignInRequest(userId, password) {
    
    // Create a new XMLHttpRequest object
    var xhr = new XMLHttpRequest();
    
    // Define the AJAX request parameters
    xhr.open("POST", "validate_password.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    
    // Define the callback function to handle the AJAX response
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                // Handle the server response
                handleSignInResponse(xhr.responseText);
            } else {
                // Handle AJAX error
                console.error("AJAX request failed:", xhr.statusText);
            }
        }
    };
    
    // Prepare the data to send in the request body
    var formData = "userId=" + encodeURIComponent(userId) + "&password=" + encodeURIComponent(password);
    
    // Send the AJAX request with the prepared data
    xhr.send(formData);
}

// Function to handle the server response after signing in
function handleSignInResponse(response) {
    if (response === "valid") {
        // User has been successfully signed in
        console.log("Sign-in successful!");
        // Perform any additional actions as needed (e.g., update UI)
    } else {
        // Sign-in failed due to invalid password
        console.log("Sign-in failed: Invalid password.");
        // Display an error message to the user or take appropriate action
    }
}
