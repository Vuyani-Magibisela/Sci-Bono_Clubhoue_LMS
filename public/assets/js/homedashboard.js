document.addEventListener('DOMContentLoaded', function() {
    // Show loading spinner
    function showLoader() {
        document.querySelector('.loading').style.display = 'flex';
    }
    
    // Hide loading spinner
    function hideLoader() {
        document.querySelector('.loading').style.display = 'none';
    }
    
    // Simulate loading for demo purposes
    function simulateLoading() {
        showLoader();
        setTimeout(hideLoader, 1000);
    }
    
    // Like button functionality
    const likeButtons = document.querySelectorAll('.like-btn');
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                icon.style.color = 'var(--primary)';
                
                // Update like count
                const postStats = this.closest('.post').querySelector('.post-stats');
                const likeCount = postStats.querySelector('.like-count span');
                likeCount.textContent = parseInt(likeCount.textContent) + 1;
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                icon.style.color = '';
                
                // Update like count
                const postStats = this.closest('.post').querySelector('.post-stats');
                const likeCount = postStats.querySelector('.like-count span');
                likeCount.textContent = parseInt(likeCount.textContent) - 1;
            }
        });
    });
    
    // Comment button functionality
    const commentButtons = document.querySelectorAll('.comment-btn');
    commentButtons.forEach(button => {
        button.addEventListener('click', function() {
            // In a real implementation, this would show a comment input field
            alert('Comment functionality would open here');
        });
    });
    
    // Share button functionality
    const shareButtons = document.querySelectorAll('.share-btn');
    shareButtons.forEach(button => {
        button.addEventListener('click', function() {
            // In a real implementation, this would show sharing options
            alert('Share functionality would open here');
        });
    });
    
    // Post creation
    const postInput = document.querySelector('.post-input input');
    postInput.addEventListener('focus', function() {
        // In a real implementation, this might expand to a full post creation form
        alert('Post creation form would expand here');
    });
    
    // Post actions
    const postActions = document.querySelectorAll('.post-action');
    postActions.forEach(action => {
        action.addEventListener('click', function() {
            const actionType = this.querySelector('span').textContent;
            alert(`${actionType} functionality would open here`);
        });
    });
    
    // Simulate page transition when clicking on menu items
    const menuItems = document.querySelectorAll('.menu-item:not(.active)');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            simulateLoading();
            // In a real implementation, this would navigate to the page
            // Here we just simulate page load
            setTimeout(() => {
                const menuText = this.querySelector('.menu-text');
                if (menuText) {
                    alert(`Navigating to ${menuText.textContent} page`);
                }
            }, 1000);
        });
    });
    
    // Mobile menu functionality
    const mobileMenuItems = document.querySelectorAll('.mobile-menu-item:not(.active)');
    mobileMenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            simulateLoading();
            // In a real implementation, this would navigate to the page
            // Here we just simulate page load
            setTimeout(() => {
                alert(`Navigating to ${this.querySelector('span').textContent} page`);
            }, 1000);
        });
    });
    
    // Post options menu
    const postOptions = document.querySelectorAll('.post-options');
    postOptions.forEach(option => {
        option.addEventListener('click', function() {
            // In a real implementation, this would show a dropdown menu
            alert('Options: Save post, Report post, Hide post, etc.');
        });
    });
    
    // "See All" buttons
    const seeAllButtons = document.querySelectorAll('.section-more');
    seeAllButtons.forEach(button => {
        button.addEventListener('click', function() {
            simulateLoading();
            const sectionTitle = this.closest('.section-header').querySelector('.section-title').textContent;
            setTimeout(() => {
                alert(`Viewing all ${sectionTitle}`);
            }, 1000);
        });
    });
    
    // Animation for progress bars
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const target = parseFloat(bar.style.width);
        bar.style.width = '0%';
        
        setTimeout(() => {
            bar.style.transition = 'width 1s ease';
            bar.style.width = target + '%';
        }, 200);
    });
});