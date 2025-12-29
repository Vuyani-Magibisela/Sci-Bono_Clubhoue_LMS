// Fixed JavaScript for MVC Architecture
// Configuration with dynamic BASE_URL
const CONFIG = {
    // Feature flag for routing migration (set via PHP or default to modern routing)
    useModernRouting: window.USE_MODERN_ROUTING !== undefined ? window.USE_MODERN_ROUTING : true,

    endpoints: {
        // Modern ModernRouter endpoints (RESTful API)
        modern: {
            signin: (window.BASE_URL || '/Sci-Bono_Clubhoue_LMS/') + 'api/v1/attendance/signin',
            signout: (window.BASE_URL || '/Sci-Bono_Clubhoue_LMS/') + 'api/v1/attendance/signout',
            search: (window.BASE_URL || '/Sci-Bono_Clubhoue_LMS/') + 'api/v1/attendance/search',
            stats: (window.BASE_URL || '/Sci-Bono_Clubhoue_LMS/') + 'api/v1/attendance/stats'
        },

        // Legacy direct-file endpoints (backward compatibility)
        legacy: {
            signin: (window.BASE_URL || '') + 'app/Controllers/attendance_routes.php?action=signin',
            signout: (window.BASE_URL || '') + 'app/Controllers/attendance_routes.php?action=signout',
            search: (window.BASE_URL || '') + 'app/Controllers/attendance_routes.php?action=search',
            stats: (window.BASE_URL || '') + 'app/Controllers/attendance_routes.php?action=stats'
        }
    },

    autoRefreshInterval: 5 * 60 * 1000, // 5 minutes
    maxFailedAttempts: 5,
    searchDelay: 300 // milliseconds
};

// Helper function to get active endpoints based on feature flag
function getEndpoints() {
    return CONFIG.useModernRouting ? CONFIG.endpoints.modern : CONFIG.endpoints.legacy;
}

// State management
let currentUserId = null;
let searchTimeout = null;

// DOM elements - Initialize after DOM is loaded
let modal, closeBtn, signinForm, errorMessage, errorText, submitBtn, btnText, loadingSpinner;
let searchInput, clearBtn, noResults, signinCards, memberCount;
let signOutModal;

// Initialize DOM elements
function initializeDOMElements() {
    // Modal elements
    modal = document.getElementById("signin-modal");
    closeBtn = document.getElementById("close-signin-modal");
    signinForm = document.getElementById("signin-form");
    errorMessage = document.getElementById("error-message") || document.querySelector('.incorrectPassword');
    errorText = document.getElementById("error-text");
    submitBtn = document.querySelector('.submit-btn');
    btnText = document.querySelector('.btn-text');
    loadingSpinner = document.querySelector('.loading-spinner');
    
    // Search elements
    searchInput = document.getElementById("search-input") || document.getElementById("search");
    clearBtn = document.getElementById("clear-search");
    noResults = document.getElementById("no-results");
    signinCards = document.getElementById("signin-cards") || document.querySelector('.signInUserCards');
    memberCount = document.getElementById("member-count");
    
    // Sign out modal
    signOutModal = document.getElementById("signOut-modal");
    if (signOutModal) {
        signOutModal.style.display = "none";
    }
}

// Search functionality
function performSearch() {
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    const userCards = document.querySelectorAll('.userSignin_card, .user-card');
    let visibleCards = 0;

    userCards.forEach(card => {
        if (card.closest('#signout-cards') || card.closest('.signOutUserCards')) {
            return; // Skip signed-in users
        }
        
        const searchTerms = card.getAttribute('data-search-terms') || '';
        const username = card.querySelector('.userName h3, .user-name')?.textContent || '';
        const fullname = card.querySelector('.userName h6, .user-fullname')?.textContent || '';
        const role = card.querySelector('.userRole p, .user-role')?.textContent || '';
        
        const searchableText = (searchTerms + ' ' + username + ' ' + fullname + ' ' + role).toLowerCase();
        const isVisible = searchableText.includes(searchTerm) || searchTerm === '';
        
        card.style.display = isVisible ? 'flex' : 'none';
        if (isVisible) visibleCards++;
    });

    // Show/hide no results message
    if (noResults) {
        const shouldShowNoResults = visibleCards === 0 && searchTerm !== '';
        noResults.style.display = shouldShowNoResults ? 'flex' : 'none';
    }
    
    // Show/hide clear button
    if (clearBtn) {
        clearBtn.style.display = searchTerm ? 'flex' : 'none';
    }
}

function clearSearch() {
    if (!searchInput) return;
    
    searchInput.value = '';
    if (clearBtn) clearBtn.style.display = 'none';
    
    const userCards = document.querySelectorAll('.userSignin_card, .user-card');
    userCards.forEach(card => {
        if (!card.closest('#signout-cards') && !card.closest('.signOutUserCards')) {
            card.style.display = 'flex';
        }
    });
    
    if (noResults) noResults.style.display = 'none';
    searchInput.focus();
}

// Debounced search
function debouncedSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(performSearch, CONFIG.searchDelay);
}

// Modal functionality
function closeSigninModal() {
    if (!modal) return;
    
    modal.style.display = "none";
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    if (errorMessage) {
        errorMessage.style.display = 'none';
        errorMessage.classList.remove('show');
    }
    
    if (signinForm) signinForm.reset();
    
    // Reset modal user display to default
    const modalUserName = document.getElementById('modal-user-name');
    const modalUserRole = document.getElementById('modal-user-role');
    
    if (modalUserName) {
        modalUserName.textContent = 'Loading...';
    }
    
    if (modalUserRole) {
        modalUserRole.textContent = 'MEMBER';
        modalUserRole.className = 'modal-user-role member';
    }
    
    resetSubmitButton();
    currentUserId = null;
}

function showSigninModal(userId) {
    if (!modal) return;
    
    currentUserId = userId;
    
    // Find the user card that was clicked
    const userCard = document.querySelector(`[data-user-id="${userId}"]`);
    
    if (userCard) {
        // Extract user information from the clicked card
        const userName = userCard.querySelector('.user-name')?.textContent || 'Unknown User';
        const userRole = userCard.querySelector('.user-role')?.textContent || 'Member';
        const roleClass = userCard.querySelector('.user-role')?.className || '';
        
        // Update modal with user information
        const modalUserName = document.getElementById('modal-user-name');
        const modalUserRole = document.getElementById('modal-user-role');
        
        if (modalUserName) {
            modalUserName.textContent = userName;
        }
        
        if (modalUserRole) {
            modalUserRole.textContent = userRole;
            // Apply the same role class for color coding
            modalUserRole.className = 'modal-user-role';
            if (roleClass.includes('admin')) modalUserRole.classList.add('admin');
            else if (roleClass.includes('mentor')) modalUserRole.classList.add('mentor');
            else if (roleClass.includes('member')) modalUserRole.classList.add('member');
        }
    }
    
    modal.style.display = "block";
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Set user ID in form
    const userIdField = document.getElementById('userId');
    if (userIdField) userIdField.value = userId;
    
    // Focus on password field
    setTimeout(() => {
        const passwordField = document.getElementById('password');
        if (passwordField) passwordField.focus();
    }, 100);
}

function resetSubmitButton() {
    if (!submitBtn) return;
    
    submitBtn.disabled = false;
    submitBtn.classList.remove('loading');
    
    if (btnText) btnText.style.display = 'inline';
    if (loadingSpinner) loadingSpinner.style.display = 'none';
}

function showError(message) {
    console.error('Signin Error:', message);
    
    if (errorText) {
        errorText.textContent = message;
    }
    
    if (errorMessage) {
        errorMessage.style.display = 'flex';
        errorMessage.classList.add('show');
        
        // Add shake animation
        const modalContent = document.querySelector('.modal-content');
        if (modalContent) {
            modalContent.classList.add('error', 'shake');
            
            // Remove classes after animation
            setTimeout(() => {
                modalContent.classList.remove('error', 'shake');
            }, 500);
        }
    }
}

// Toast functionality
function showSuccessToast(message) {
    const toast = document.getElementById('success-toast');
    if (toast) {
        const span = toast.querySelector('span');
        if (span) span.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    } else {
        // Fallback for old modal
        if (signOutModal) {
            signOutModal.style.display = "block";
            setTimeout(() => {
                signOutModal.style.display = "none";
            }, 3000);
        }
    }
}

// Sign in function (called by user card buttons)
function signIn(userId) {
    showSigninModal(userId);
}

// Updated signInPrompt function for backward compatibility
function signInPrompt(userId) {
    showSigninModal(userId);
}

// Send sign-in request using modern fetch API with better error handling
function sendSignInRequest(userId, password) {
    if (!submitBtn) return;
    
    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    if (btnText) btnText.style.display = 'none';
    if (loadingSpinner) loadingSpinner.style.display = 'inline-block';
    if (errorMessage) errorMessage.style.display = 'none';

    const endpoints = getEndpoints();

    console.log('Attempting signin for user:', userId);
    console.log('Using endpoint:', endpoints.signin);
    console.log('Modern routing:', CONFIG.useModernRouting);

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('password', password);

    fetch(endpoints.signin, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Check if response is actually JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response');
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Signin response:', data);
        
        if (data.success) {
            closeSigninModal();
            showSuccessToast('Successfully signed in!');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showError(data.message || 'Sign in failed. Please try again.');
            resetSubmitButton();
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.value = '';
                passwordField.focus();
            }
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showError('Connection error. Please check your internet connection and try again.');
        resetSubmitButton();
    });
}

// Handle sign-in response (backward compatibility)
function handleSignInResponse(response) {
    if (response.trim() === "valid") {
        console.log("Sign-in successful!");
        closeSigninModal();
        showSuccessToast('Successfully signed in!');
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    } else {
        showError('Invalid password. Please try again.');
    }
}

// Sign out function
function signOut(userId) {
    if (!confirm('Are you sure you want to sign out?')) {
        return;
    }

    const endpoints = getEndpoints();

    console.log('Attempting signout for user:', userId);
    console.log('Using endpoint:', endpoints.signout);
    console.log('Modern routing:', CONFIG.useModernRouting);

    const formData = new FormData();
    formData.append('user_id', userId);

    fetch(endpoints.signout, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text);
                throw new Error('Server returned non-JSON response');
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showSuccessToast('Successfully signed out!');
            
            // Remove user card from DOM (for immediate feedback)
            const userCard = document.getElementById("userCard" + userId);
            if (userCard) {
                userCard.style.transition = 'opacity 0.3s ease';
                userCard.style.opacity = '0';
                setTimeout(() => {
                    userCard.remove();
                    updateMemberCount();
                }, 300);
            }
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert(data.message || 'Failed to sign out. Please try again.');
        }
    })
    .catch(error => {
        console.error('Signout error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Update member count
function updateMemberCount() {
    if (!memberCount) return;
    
    const signedInCards = document.querySelectorAll('#signout-cards .user-card:not(.no-members), .signOutUserCards .userSignin_card').length;
    memberCount.textContent = signedInCards;
}

// Event listeners setup
function setupEventListeners() {
    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', debouncedSearch);
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Escape') {
                clearSearch();
            }
        });
    }
    
    if (clearBtn) {
        clearBtn.addEventListener('click', clearSearch);
    }
    
    // Modal event listeners
    if (closeBtn) {
        closeBtn.addEventListener('click', closeSigninModal);
    }
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeSigninModal();
            }
        });
    }
    
    // Form submission
    if (signinForm) {
        signinForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!currentUserId) {
                showError('No user selected');
                return;
            }
            
            const password = document.getElementById('password')?.value;
            if (!password) {
                showError('Password is required');
                return;
            }
            
            sendSignInRequest(currentUserId, password);
        });
    }
    
    // Global keyboard events
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && (modal.style.display === 'block' || modal.classList.contains('active'))) {
            closeSigninModal();
        }
    });
    
    // Sign in buttons (for dynamically created buttons)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('signInBtn') || e.target.closest('.signInBtn')) {
            e.preventDefault();
            const button = e.target.classList.contains('signInBtn') ? e.target : e.target.closest('.signInBtn');
            const userId = button.getAttribute('data-user-id') || button.getAttribute('onclick')?.match(/\d+/)?.[0];
            if (userId) {
                signIn(parseInt(userId));
            }
        }
    });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing attendance system...');
    console.log('Endpoints configured:', CONFIG.endpoints);
    
    initializeDOMElements();
    setupEventListeners();
    updateMemberCount();
    
    // Set initial focus on search if available
    if (searchInput) {
        searchInput.focus();
    }
    
    console.log('Attendance system initialized successfully');
});

// Auto-refresh page every 5 minutes to keep data current
setInterval(() => {
    console.log('Auto-refreshing page...');
    window.location.reload();
}, CONFIG.autoRefreshInterval);

// Legacy browser compatibility
window.onclick = function(event) {
    if (modal && event.target == modal) {
        closeSigninModal();
    }
};

// Export functions for global access (backward compatibility)
window.signIn = signIn;
window.signOut = signOut;
window.signInPrompt = signInPrompt;
window.handleSignInResponse = handleSignInResponse;

// Debug function to test endpoints
window.testEndpoint = function() {
    console.log('Testing signin endpoint...');
    fetch(CONFIG.endpoints.signin)
        .then(response => {
            console.log('Endpoint test - Status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Endpoint test - Response:', text);
        })
        .catch(error => {
            console.error('Endpoint test - Error:', error);
        });
};