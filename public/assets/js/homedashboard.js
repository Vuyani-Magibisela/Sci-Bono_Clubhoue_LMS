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
            // Check if there's a link inside
            const link = this.querySelector('a');
            if (link) {
                // If there's a link, let it handle the navigation
                // We just show the loading animation
                showLoader();
                return; // Don't prevent default; let the link work
            }
            
            // If there's no link, prevent default behavior and handle it here
            e.preventDefault();
            simulateLoading();
            
            // Get the menu text
            const menuText = this.querySelector('.menu-text');
            if (menuText) {
                // Determine where to navigate based on the menu text
                let targetUrl = '';
                switch(menuText.textContent.toLowerCase()) {
                    case 'home':
                        targetUrl = 'home.php';
                        break;
                    case 'profile':
                        targetUrl = 'app/Views/profile.php';
                        break;
                    case 'messages':
                        targetUrl = 'app/Views/messages.php';
                        break;
                    case 'members':
                        targetUrl = 'members.php';
                        break;
                    case 'feed':
                        targetUrl = 'feed.php';
                        break;
                    case 'learn':
                        targetUrl = 'app/Views/learn.php';
                        break;
                    case 'daily register':
                        targetUrl = 'signin.php';
                        break;
                    case 'projects':
                        targetUrl = 'app/Views/projects.php';
                        break;
                    case 'reports':
                        targetUrl = 'app/Views/statsDashboard.php';
                        break;
                    case 'settings':
                        targetUrl = 'app/Views/settings.php';
                        break;
                    default:
                        targetUrl = '#';
                        break;
                }
                
                // Navigate to the target URL after a short delay to show the loading animation
                if (targetUrl !== '#') {
                    setTimeout(() => {
                        window.location.href = targetUrl;
                    }, 500);
                }
            }
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