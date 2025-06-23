// holidayProgramIndex.js - Dynamic Holiday Program Status Management

document.addEventListener('DOMContentLoaded', function() {
    // Initialize program status monitoring
    initializeProgramStatusMonitoring();
    
    // Initialize UI interactions
    initializeUIInteractions();
    
    // Check for status updates periodically
    startStatusPolling();
    
    // Initialize scroll animations
    initializeScrollAnimations();
    
    // Handle visibility changes
    handleVisibilityChanges();
});

/**
 * Initialize program status monitoring
 */
function initializeProgramStatusMonitoring() {
    const programCards = document.querySelectorAll('.program-card');
    
    programCards.forEach(card => {
        // Add loading state functionality
        addLoadingStateSupport(card);
        
        // Add hover effects for interactive elements
        addInteractiveEffects(card);
        
        // Monitor capacity changes
        monitorCapacityUpdates(card);
        
        // Add click tracking
        addClickTracking(card);
    });
}

/**
 * Add loading state support to program cards
 */
function addLoadingStateSupport(card) {
    const registerBtn = card.querySelector('.register-btn');
    const detailsLink = card.querySelector('.details-link');
    
    if (registerBtn && !registerBtn.classList.contains('disabled')) {
        registerBtn.addEventListener('click', function(e) {
            // Only add loading if it's an active link
            if (this.getAttribute('href') && !this.classList.contains('disabled')) {
                this.classList.add('loading');
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                
                // Restore original text if navigation fails
                setTimeout(() => {
                    if (this.classList.contains('loading')) {
                        this.classList.remove('loading');
                        this.innerHTML = originalText;
                    }
                }, 5000);
            }
        });
    }
    
    if (detailsLink) {
        detailsLink.addEventListener('click', function(e) {
            this.classList.add('loading');
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            
            // Restore original text if navigation fails
            setTimeout(() => {
                if (this.classList.contains('loading')) {
                    this.classList.remove('loading');
                    this.innerHTML = originalText;
                }
            }, 5000);
        });
    }
}

/**
 * Add interactive effects to program cards
 */
function addInteractiveEffects(card) {
    let hoverTimeout;
    
    // Add smooth hover transitions
    card.addEventListener('mouseenter', function() {
        clearTimeout(hoverTimeout);
        if (!this.classList.contains('loading')) {
            this.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
            this.style.transform = 'translateY(-8px)';
            this.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.15)';
        }
    });
    
    card.addEventListener('mouseleave', function() {
        clearTimeout(hoverTimeout);
        hoverTimeout = setTimeout(() => {
            if (!this.classList.contains('loading')) {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.1)';
            }
        }, 100);
    });
    
    // Add click effects to buttons
    const buttons = card.querySelectorAll('.register-btn, .details-link');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Create ripple effect only for active buttons
            if (!this.classList.contains('disabled')) {
                createRippleEffect(e, this);
            }
        });
    });
}

/**
 * Monitor capacity updates for programs
 */
function monitorCapacityUpdates(card) {
    const capacityBar = card.querySelector('.capacity-fill');
    const capacityText = card.querySelector('.capacity-text');
    
    if (capacityBar && capacityText) {
        // Store original capacity info
        const programData = {
            id: extractProgramId(card),
            originalWidth: capacityBar.style.width,
            originalText: capacityText.textContent
        };
        
        // Set up periodic capacity checking
        if (programData.id) {
            setInterval(() => {
                checkCapacityUpdates(card, capacityBar, capacityText, programData);
            }, 45000); // Check every 45 seconds
        }
    }
}

/**
 * Check for capacity updates via AJAX
 */
function checkCapacityUpdates(card, capacityBar, capacityText, programData) {
    if (!programData.id || document.hidden) return;
    
    fetch(`./api/get-program-capacity.php?program_id=${programData.id}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.capacity_info) {
                updateCapacityDisplay(capacityBar, capacityText, data.capacity_info);
                
                // Check if status has changed
                if (data.status_changed || data.registration_open !== getCurrentRegistrationStatus(card)) {
                    updateProgramStatus(card, {
                        registration_open: data.registration_open,
                        program_id: programData.id,
                        has_details: true
                    });
                }
            }
        })
        .catch(error => {
            console.log('Capacity check failed for program', programData.id, ':', error.message);
        });
}

/**
 * Get current registration status from card
 */
function getCurrentRegistrationStatus(card) {
    const registerBtn = card.querySelector('.register-btn');
    return registerBtn && registerBtn.classList.contains('active');
}

/**
 * Update capacity display
 */
function updateCapacityDisplay(capacityBar, capacityText, capacityInfo) {
    const percentage = capacityInfo.max > 0 ? (capacityInfo.current / capacityInfo.max) * 100 : 0;
    const newWidth = Math.min(percentage, 100) + '%';
    const newText = `${capacityInfo.current}/${capacityInfo.max} participants`;
    
    // Only update if values have changed
    if (capacityBar.style.width !== newWidth) {
        // Animate capacity bar
        capacityBar.style.transition = 'width 0.8s ease, background 0.3s ease';
        capacityBar.style.width = newWidth;
        
        // Update text with animation
        capacityText.style.transition = 'opacity 0.3s ease';
        capacityText.style.opacity = '0.5';
        
        setTimeout(() => {
            capacityText.textContent = newText;
            capacityText.style.opacity = '1';
        }, 150);
        
        // Change color based on capacity
        updateCapacityColor(capacityBar, percentage);
        
        // Show warning if approaching capacity
        if (percentage >= 90 && percentage < 100) {
            showCapacityWarning(capacityInfo);
        }
    }
}

/**
 * Update capacity bar color based on percentage
 */
function updateCapacityColor(capacityBar, percentage) {
    if (percentage >= 100) {
        capacityBar.style.background = 'linear-gradient(90deg, #dc3545, #e74c3c)';
        capacityBar.classList.add('capacity-full');
    } else if (percentage >= 90) {
        capacityBar.style.background = 'linear-gradient(90deg, #fd7e14, #dc3545)';
        capacityBar.classList.add('capacity-warning');
    } else if (percentage >= 75) {
        capacityBar.style.background = 'linear-gradient(90deg, #ffc107, #fd7e14)';
        capacityBar.classList.remove('capacity-warning', 'capacity-full');
    } else {
        capacityBar.style.background = 'linear-gradient(90deg, #28a745, #20c997)';
        capacityBar.classList.remove('capacity-warning', 'capacity-full');
    }
}

/**
 * Show capacity warning notification
 */
function showCapacityWarning(capacityInfo) {
    const warningKey = `capacity_warning_${capacityInfo.current}_${capacityInfo.max}`;
    
    // Don't show the same warning repeatedly
    if (sessionStorage.getItem(warningKey)) return;
    
    sessionStorage.setItem(warningKey, 'shown');
    showStatusChangeNotification(
        `Program is almost full! Only ${capacityInfo.max - capacityInfo.current} spots remaining.`,
        'warning'
    );
}

/**
 * Update program status dynamically
 */
function updateProgramStatus(card, newStatus) {
    const statusBadge = card.querySelector('.status-badge');
    const registerBtn = card.querySelector('.register-btn');
    const detailsLink = card.querySelector('.details-link');
    const programId = newStatus.program_id;
    
    // Add update animation
    card.classList.add('status-updating');
    
    setTimeout(() => {
        if (newStatus.registration_open) {
            // Registration opened
            if (statusBadge) {
                statusBadge.className = 'status-badge open';
                statusBadge.textContent = 'Registration Open';
            }
            
            if (registerBtn) {
                registerBtn.className = 'register-btn active';
                registerBtn.textContent = 'Register Now';
                registerBtn.href = `holidayProgramRegistration.php?program_id=${programId}`;
                registerBtn.removeAttribute('disabled');
            }
            
            card.classList.remove('registration-closed');
            card.classList.add('registration-open');
            
            // Show notification
            showStatusChangeNotification('Registration is now open! 🎉', 'success');
            
        } else {
            // Registration closed
            if (statusBadge) {
                statusBadge.className = 'status-badge closed';
                statusBadge.textContent = 'Registration Closed';
            }
            
            if (registerBtn) {
                registerBtn.className = 'register-btn disabled';
                registerBtn.textContent = 'Registration Closed';
                registerBtn.removeAttribute('href');
                registerBtn.setAttribute('disabled', 'true');
            }
            
            card.classList.remove('registration-open');
            card.classList.add('registration-closed');
            
            // Show notification
            showStatusChangeNotification('Registration has been closed', 'info');
        }
        
        // Control details button visibility
        if (detailsLink) {
            detailsLink.style.display = newStatus.has_details ? 'inline-block' : 'none';
        }
        
        // Remove update animation
        card.classList.remove('status-updating');
        
    }, 300);
}

/**
 * Show status change notification
 */
function showStatusChangeNotification(message, type) {
    // Remove existing notifications of the same type
    const existingNotifications = document.querySelectorAll(`.status-notification.${type}`);
    existingNotifications.forEach(notification => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    });
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `status-notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Style notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        padding: 15px 20px;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        min-width: 320px;
        max-width: 400px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        background: ${getNotificationColor(type)};
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 6 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.4s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 400);
        }
    }, 6000);
    
    // Add click to dismiss
    notification.addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    });
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'info': 'info-circle',
        'warning': 'exclamation-triangle',
        'error': 'exclamation-circle',
        'capacity': 'users'
    };
    return icons[type] || 'info-circle';
}

/**
 * Get notification color based on type
 */
function getNotificationColor(type) {
    const colors = {
        'success': 'linear-gradient(135deg, #28a745, #20c997)',
        'info': 'linear-gradient(135deg, #17a2b8, #138496)',
        'warning': 'linear-gradient(135deg, #ffc107, #fd7e14)',
        'error': 'linear-gradient(135deg, #dc3545, #c82333)',
        'capacity': 'linear-gradient(135deg, #6f42c1, #e83e8c)'
    };
    return colors[type] || colors.info;
}

/**
 * Create ripple effect on button click
 */
function createRippleEffect(event, element) {
    const ripple = document.createElement('span');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
        z-index: 1;
    `;
    
    // Ensure element has relative positioning
    const originalPosition = element.style.position;
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    
    element.appendChild(ripple);
    
    // Clean up
    setTimeout(() => {
        ripple.remove();
        if (!originalPosition) {
            element.style.position = '';
        }
    }, 600);
}

/**
 * Extract program ID from card element
 */
function extractProgramId(card) {
    // Try to get from register button href
    const registerBtn = card.querySelector('.register-btn[href]');
    if (registerBtn && registerBtn.href) {
        const url = new URL(registerBtn.href, window.location.origin);
        const programId = url.searchParams.get('program_id');
        if (programId) return programId;
    }
    
    // Try to get from details link href
    const detailsLink = card.querySelector('.details-link[href]');
    if (detailsLink && detailsLink.href) {
        const url = new URL(detailsLink.href, window.location.origin);
        return url.searchParams.get('id');
    }
    
    // Try to get from data attribute
    const programId = card.getAttribute('data-program-id');
    if (programId) return programId;
    
    return null;
}

/**
 * Add click tracking for analytics
 */
function addClickTracking(card) {
    const buttons = card.querySelectorAll('.register-btn, .details-link');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const programId = extractProgramId(card);
            const buttonType = this.classList.contains('register-btn') ? 'register' : 'details';
            
            // Track click event (you can integrate with your analytics)
            console.log(`Button clicked: ${buttonType} for program ${programId}`);
            
            // You can send this data to your analytics service
            // trackEvent('program_interaction', buttonType, programId);
        });
    });
}

/**
 * Initialize UI interactions
 */
function initializeUIInteractions() {
    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                const headerOffset = 80; // Account for fixed header
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add loading states to navigation links
    const navLinks = document.querySelectorAll('nav a:not([href^="#"]), .cta-button:not([href^="#"])');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (this.getAttribute('href') && !this.classList.contains('loading')) {
                this.classList.add('loading');
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                
                // Restore if navigation doesn't happen
                setTimeout(() => {
                    if (this.classList.contains('loading')) {
                        this.classList.remove('loading');
                        this.innerHTML = originalText;
                    }
                }, 3000);
            }
        });
    });
}

/**
 * Initialize scroll-based animations
 */
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                
                // Add staggered animation for program cards
                if (entry.target.classList.contains('program-card')) {
                    const cards = document.querySelectorAll('.program-card');
                    const index = Array.from(cards).indexOf(entry.target);
                    entry.target.style.animationDelay = `${index * 0.1}s`;
                }
            }
        });
    }, observerOptions);
    
    // Observe elements that should animate
    const animateElements = document.querySelectorAll('.program-card, .feature-card, .section-header');
    animateElements.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Handle visibility changes
 */
function handleVisibilityChanges() {
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            // Page became visible, check for updates immediately
            console.log('Page visible - checking for updates');
            checkForGlobalStatusUpdates();
        }
    });
}

/**
 * Start polling for status updates
 */
function startStatusPolling() {
    // Check immediately
    setTimeout(checkForGlobalStatusUpdates, 2000);
    
    // Then check every minute when page is visible
    setInterval(() => {
        if (!document.hidden) {
            checkForGlobalStatusUpdates();
        }
    }, 60000);
}

/**
 * Check for global status updates
 */
function checkForGlobalStatusUpdates() {
    const lastCheck = sessionStorage.getItem('last_status_check') || 
                     new Date(Date.now() - 3600000).toISOString(); // 1 hour ago
    
    fetch(`./api/get-all-program-status.php?last_check=${encodeURIComponent(lastCheck)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update last check timestamp
                sessionStorage.setItem('last_status_check', data.timestamp);
                
                // Process updates
                if (data.updates && data.updates.length > 0) {
                    data.updates.forEach(update => {
                        const card = findProgramCard(update.program_id);
                        if (card) {
                            updateProgramStatus(card, update);
                        }
                    });
                }
                
                // Handle capacity warnings
                if (data.capacity_warnings && data.capacity_warnings.length > 0) {
                    data.capacity_warnings.forEach(warning => {
                        showStatusChangeNotification(warning.message, 'capacity');
                    });
                }
            }
        })
        .catch(error => {
            console.log('Status check failed:', error.message);
        });
}

/**
 * Find program card by ID
 */
function findProgramCard(programId) {
    const cards = document.querySelectorAll('.program-card');
    for (let card of cards) {
        const cardProgramId = extractProgramId(card);
        if (cardProgramId == programId) {
            return card;
        }
    }
    return null;
}

/**
 * Handle network status changes
 */
window.addEventListener('online', () => {
    showStatusChangeNotification('Connection restored', 'success');
    // Check for updates immediately when coming back online
    setTimeout(checkForGlobalStatusUpdates, 1000);
});

window.addEventListener('offline', () => {
    showStatusChangeNotification('No internet connection - updates may be delayed', 'warning');
});

/**
 * Handle page unload
 */
window.addEventListener('beforeunload', () => {
    // Clean up any ongoing operations
    const loadingElements = document.querySelectorAll('.loading');
    loadingElements.forEach(element => {
        element.classList.remove('loading');
    });
});

// Add all required CSS animations and styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes animate-in {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse-warning {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    @keyframes status-update {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    
    /* Enhanced animations */
    .animate-in {
        animation: animate-in 0.6s ease forwards;
    }
    
    .loading {
        pointer-events: none;
        opacity: 0.7;
        cursor: wait !important;
    }
    
    .status-updating {
        animation: status-update 0.6s ease;
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .notification-message {
        flex: 1;
        line-height: 1.4;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 8px;
        margin: -4px;
        border-radius: 6px;
        transition: background 0.3s ease;
        opacity: 0.8;
    }
    
    .notification-close:hover {
        background: rgba(255, 255, 255, 0.2);
        opacity: 1;
    }
    
    /* Enhanced button states */
    .register-btn.loading,
    .details-link.loading {
        cursor: wait !important;
        transform: none !important;
        pointer-events: none;
    }
    
    .program-card.loading {
        pointer-events: none;
        filter: brightness(0.95);
    }
    
    /* Capacity states */
    .capacity-fill.capacity-warning {
        animation: pulse-warning 2s infinite;
    }
    
    .capacity-fill.capacity-full {
        animation: pulse-warning 1s infinite;
    }
    
    /* Status badge enhanced animations */
    .status-badge.open {
        animation: pulse-success 3s infinite;
    }
    
    @keyframes pulse-success {
        0% { 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 0 rgba(40, 167, 69, 0.7); 
        }
        70% { 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 10px rgba(40, 167, 69, 0); 
        }
        100% { 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 0 0 0 rgba(40, 167, 69, 0); 
        }
    }
    
    /* Improved hover effects */
    .program-card:hover .register-btn.active {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
    }
    
    .program-card:hover .details-link {
        transform: translateY(-1px);
        border-color: #495057;
    }
    
    /* Mobile optimizations */
    @media (max-width: 768px) {
        .status-notification {
            right: 10px !important;
            left: 10px !important;
            min-width: auto !important;
            max-width: none !important;
        }
        
        .notification-content {
            font-size: 14px;
            gap: 8px;
        }
        
        .notification-message {
            font-size: 13px;
        }
        
        /* Reduce animation intensity on mobile */
        .program-card:hover {
            transform: translateY(-4px) !important;
        }
    }
    
    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        .animate-in,
        .status-updating,
        .program-card,
        .capacity-fill,
        .status-badge {
            animation: none !important;
            transition: none !important;
        }
        
        .program-card:hover {
            transform: none !important;
        }
    }
    
    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .status-notification {
            border: 2px solid white;
        }
        
        .notification-close {
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
    }
`;

document.head.appendChild(style);

// Initialize error handling
window.addEventListener('error', (event) => {
    console.error('JavaScript error:', event.error);
});

// Initialize unhandled promise rejection handling
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
});

console.log('Holiday Program Index JS initialized successfully');