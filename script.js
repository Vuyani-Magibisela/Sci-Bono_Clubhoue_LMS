// JavaScript code for handling sign in/out buttons
document.querySelectorAll('.signBtn button').forEach(button => {
    button.addEventListener('click', function() {
        // Get the user card container
        alert("Clicked");
        const card = this.closest('.userSignin_card');
        // Move the user card to the signed-in container
        document.querySelector('.userSignedin-box').appendChild(card);
    });
});

document.querySelectorAll('.signBtnOut button').forEach(button => {
    button.addEventListener('click', function() {
        // Get the user card container
        const card = this.closest('.userSignin_card');
        // Move the user card to the signed-out container
        document.querySelector('.userSignin-box').appendChild(card);
    });
});
