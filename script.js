//Handling signIn model
// Get the modal
var modal = document.getElementById("signin-modal");

// Get the button that opens the modal
var btn = document.getElementsByClassName("signInBtn");

// Get the <span> element that closes the modal
var span = document.getElementById("close-signin-modal");

// When the user clicks the button, open the modal
btn.onclick = function() {
    modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
    console.log("span clicked!")
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

var SingOutmodal = document.getElementById("signOut-modal");
SingOutmodal.style.display = "none";


//_____________________________________End of SignIn Model_______________________


// Define the signIn function
function signIn(userId) {
    // Call the signInPrompt function with the retrieved user ID
    signInPrompt(userId);
}

// Function to prompt the user to enter their password and initiate sign-in process
function signInPrompt(userId) {
      // Get the modal
      var modal = document.getElementById("signin-modal");

      // Display the modal
      modal.style.display = "block";
  
      // Set the user ID in the form field
      document.getElementById("userId").value = userId; 
}

// Get the modal and close button
var modal = document.getElementById("signin-modal");
var closeBtn = document.getElementById("close-signin-modal");

// When the user clicks on the close button, close the modal
closeBtn.onclick = function() {
    modal.style.display = "none";
};

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
};


/*Update the form submission handling to call the 
sendSignInRequest function with the user ID and password:*/
// Handle form submission
document.getElementById("signin-form").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent default form submission

    // Get user ID and password from form fields
    var userId = document.getElementById("userId").value;
    var password = document.getElementById("password").value;

    // Encode the password before sending it to the server
    var encodedPassword = encodeURIComponent(password);

    // Perform sign-in process with the provided user ID and password
    sendSignInRequest(userId, password);

    // Close the modal
    var modal = document.getElementById("signin-modal");
    // modal.style.display = "none";

    
});


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
                console.log(xhr.responseText);
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

// Define the signOut function
function signOut(userId, password) {
    // Send an AJAX request to the server to mark the user as signed out
    var xhr = new XMLHttpRequest();
    var url = "validate_password.php"; // Update the URL to your PHP script for handling sign-out
    xhr.open("POST", url, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                // If sign-out is successful, remove the user card from the UI
                //console.log('This is the responseText', xhr.responseText);  
                var response = xhr.responseText;
                
                if (response === "success") {
                    // Remove the user card from the DOM
                    var userCard = document.getElementById("userCard" + userId);
                    if (userCard) {
                        userCard.parentNode.removeChild(userCard);
                    }
                    //debuging
                    console.log("User signed out successfully.");

                    SingOutmodal.style.display = "block";
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000); 
                } else {
                    console.error("Sign-out failed:", response);
                }
            } else {
                console.error("Failed to send sign-out request:", xhr.statusText);
            }   
        }
    };
    xhr.send("userId=" + encodeURIComponent(userId) + "&password=" + encodeURIComponent(password) + "&action=signOut"); // Pass user ID and action to the server
}

// Function to handle the server response after signing in
function handleSignInResponse(response) {
    var modalContent = document.querySelector('.modal-content');
    var modalText = document.querySelector('.incorrectPassword');
    if (response.trim() === "valid") {
        // User has been successfully signed in
        console.log("Sign-in successful!");
        window.location.reload();
        // Perform any additional actions as needed (e.g., update UI)
    } else {
        // Sign-in failed due to invalid password
        console.log("Sign-in failed: Invalid password.");
        // Add the error class to change background color to red
        modalContent.classList.add('error');
        //Add the incorrectPassoword class to show text
        modalText.classList.add('show');
        // Add the shake class to shake the div
        modalContent.classList.add('shake');
        // Remove the classes after the animation ends
        setTimeout(function() {
            modalContent.classList.remove('error', 'shake');
            modalText.classList.remove('show')
        }, 5000); // The timeout duration should match the animation duration
    }
}
