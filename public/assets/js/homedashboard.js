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
    if (postInput) {
        postInput.addEventListener('focus', function() {
            // In a real implementation, this might expand to a full post creation form
            alert('Post creation form would expand here');
        });
    }
    
    // Post actions
    const postActions = document.querySelectorAll('.post-action');
    postActions.forEach(action => {
        action.addEventListener('click', function() {
            const actionType = this.querySelector('span').textContent;
            alert(`${actionType} functionality would open here`);
        });
    });
    
    // Sidebar menu navigation with loading animation
    const menuItems = document.querySelectorAll('.menu-item:not(.active)');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Check if this is actually an anchor tag (the new structure)
            if (this.tagName === 'A' && this.href && this.href !== '#' && !this.hasAttribute('onclick')) {
                // It's a real link, just show loader and let it navigate
                showLoader();
                return; // Let the link work normally
            }

            // For placeholder links with onclick (like "coming soon" alerts)
            if (this.hasAttribute('onclick')) {
                // Let the onclick handler run
                return;
            }

            // For buttons or non-link menu items
            if (this.tagName === 'BUTTON' || this.type === 'submit') {
                // Let forms submit normally
                showLoader();
                return;
            }

            // Only prevent default if it's not a functional link
            e.preventDefault();
        });
    });
    
    // Mobile menu functionality with real navigation
    const mobileMenuItems = document.querySelectorAll('.mobile-menu-item');
    mobileMenuItems.forEach(item => {
        // We'll only add event listeners to items that don't have the active class
        if (!item.classList.contains('active')) {
            item.addEventListener('click', function(e) {
                // Only show loading and handle navigation if it's a link
                const href = this.getAttribute('href');
                
                // If it's a valid href and not just "#"
                if (href && href !== '#') {
                    e.preventDefault(); // Prevent default link behavior
                    showLoader(); // Show loading animation
                    
                    // Navigate to the href after a short delay
                    setTimeout(() => {
                        window.location.href = href;
                    }, 500);
                }
            });
        }
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