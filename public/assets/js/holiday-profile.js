// Holiday Program Profile JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profile-form');
    const saveBtn = document.getElementById('save-btn');
    let formChanged = false;
    let autoSaveTimer;

    // Form submission with loading state
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            if (saveBtn) {
                const originalContent = saveBtn.innerHTML;
                
                // Show loading state
                saveBtn.innerHTML = '<div class="spinner"></div> Saving...';
                saveBtn.classList.add('saving');
                
                // Reset after form submission (success/error will reload page)
                setTimeout(() => {
                    saveBtn.innerHTML = originalContent;
                    saveBtn.classList.remove('saving');
                }, 3000);
            }
        });
    }

    // Track form changes
    const formInputs = document.querySelectorAll('#profile-form input, #profile-form select, #profile-form textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            formChanged = true;
            clearTimeout(autoSaveTimer);
            
            // Optional: Auto-save after 5 seconds of inactivity
            autoSaveTimer = setTimeout(() => {
                console.log('Auto-saving changes...');
                // Implement AJAX auto-save here if needed
            }, 5000);
        });

        input.addEventListener('change', function() {
            formChanged = true;
        });
    });

    // Warn before leaving if there are unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });

    // Reset form changed flag on successful submit
    if (profileForm) {
        profileForm.addEventListener('submit', function() {
            formChanged = false;
        });
    }

    // Password strength checker for password creation page
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (passwordInput && confirmPasswordInput) {
        function checkPasswordStrength() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            // Check password strength
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Check password match
            if (password && confirmPassword && password === confirmPassword) {
                confirmPasswordInput.style.borderColor = '#28a745';
            } else if (confirmPassword) {
                confirmPasswordInput.style.borderColor = '#dc3545';
            }
        }
        
        passwordInput.addEventListener('input', checkPasswordStrength);
        confirmPasswordInput.addEventListener('input', checkPasswordStrength);
    }

    // Form validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function validatePhone(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/\s/g, ''));
    }

    // Real-time validation
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            if (this.value && !validateEmail(this.value)) {
                this.style.borderColor = '#dc3545';
                showFieldError(this, 'Please enter a valid email address');
            } else {
                this.style.borderColor = '#28a745';
                hideFieldError(this);
            }
        });
    }
    
    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            if (this.value && !validatePhone(this.value)) {
                this.style.borderColor = '#dc3545';
                showFieldError(this, 'Please enter a valid phone number');
            } else if (this.value) {
                this.style.borderColor = '#28a745';
                hideFieldError(this);
            }
        });
    }

    function showFieldError(field, message) {
        hideFieldError(field); // Remove existing error
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#dc3545';
        errorDiv.style.fontSize = '0.8rem';
        errorDiv.style.marginTop = '5px';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }

    function hideFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // Smooth scroll to form errors
    const errorAlert = document.querySelector('.alert-danger');
    if (errorAlert) {
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});