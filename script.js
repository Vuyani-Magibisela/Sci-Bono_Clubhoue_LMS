// Add event listeners to all sign-in buttons on member cards
var signInButtons = document.querySelectorAll(".userSignin_card button");
signInButtons.forEach(function(button) {
    button.addEventListener("click", function() {
        // Retrieve the user ID associated with the clicked sign-in button
        var userId = button.dataset.userId;
        console.log("Clicked on sign-in button for user ID:", userId);
        
        // Verify if userId is retrieved correctly
        if (!userId) {
            console.error("Error: User ID not found for sign-in button.");
            return;
        }
        
        // Call signInPrompt function with the retrieved user ID
        signInPrompt(userId);
    });
});

// Function to prompt the user to enter their password and initiate sign-in process
function signInPrompt(userId) {
    console.log("Prompting for password for user ID:", userId);
    // Prompt the user to enter their password
    var password = prompt("Please enter your password:");
    if (password === null) {
        // User canceled the prompt, do nothing
        return;
    }
    
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
