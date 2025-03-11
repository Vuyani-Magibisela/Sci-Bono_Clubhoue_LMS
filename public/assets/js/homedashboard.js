document.addEventListener('DOMContentLoaded', function() {
    // Initialize learning progress
    initLearningProgress();
    
    // Initialize badges tooltips
    initBadgesTooltips();
});

function initLearningProgress() {
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
}

function initBadgesTooltips() {
    // Add tooltips to locked badges
    const lockedBadges = document.querySelectorAll('.badge-item.locked');
    lockedBadges.forEach(badge => {
        const badgeName = badge.querySelector('.badge-name').textContent;
        badge.title = `Complete requirements to unlock: ${badgeName}`;
    });
}