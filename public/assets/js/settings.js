// Modern Settings Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar expansion
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    
    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-expanded');
            mainContent.classList.toggle('main-expanded');
        });
    }
    
    // Mobile sidebar toggle
    const mobileToggle = document.getElementById('mobile-nav-toggle');
    
    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-visible');
        });
    }
    
    // Close sidebar when clicking outside (mobile)
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768 && 
            sidebar && 
            sidebar.classList.contains('sidebar-visible') && 
            !sidebar.contains(event.target) && 
            mobileToggle && 
            !mobileToggle.contains(event.target)) {
            sidebar.classList.remove('sidebar-visible');
        }
    });
    
    // Profile image upload functionality
    const profileImageInput = document.getElementById('profile-image-input');
    const profileImage = document.getElementById('profile-image');
    const changeImageBtn = document.getElementById('change-image-btn');
    
    if (profileImageInput && profileImage && changeImageBtn) {
        // Open file dialog when clicking on the change image button
        changeImageBtn.addEventListener('click', function() {
            profileImageInput.click();
        });
        
        // Handle image selection
        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                    profileImage.style.display = 'block';
                    document.querySelector('.profile-image-placeholder').style.display = 'none';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Custom Select Functionality (for Nationality, Language, etc.)
    const customSelects = document.querySelectorAll('.custom-select');
    
    customSelects.forEach(select => {
        const selectValue = select.querySelector('.custom-select-value');
        const dropdown = select.nextElementSibling;
        const options = dropdown.querySelectorAll('.custom-select-option');
        const searchInput = dropdown.querySelector('.custom-select-search input');
        const hiddenInput = select.parentElement.querySelector('input[type="hidden"]');
        
        // Toggle dropdown
        select.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            
            if (dropdown.style.display === 'block' && searchInput) {
                searchInput.focus();
            }
            
            // Rotate arrow
            const arrow = select.querySelector('.custom-select-arrow');
            if (arrow) {
                arrow.style.transform = dropdown.style.display === 'block' ? 'rotate(180deg)' : 'rotate(0)';
            }
        });
        
        // Filter options when typing in the search input
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchValue = this.value.toLowerCase();
                
                options.forEach(option => {
                    const optionText = option.textContent.toLowerCase();
                    if (optionText.includes(searchValue)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
            });
            
            // Prevent dropdown from closing when clicking on search input
            searchInput.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Handle option selection
        options.forEach(option => {
            option.addEventListener('click', function() {
                selectValue.textContent = this.textContent;
                
                if (hiddenInput) {
                    hiddenInput.value = this.dataset.value;
                }
                
                // Handle "Other" option
                const otherField = select.parentElement.nextElementSibling;
                if (otherField && otherField.classList.contains('other-field')) {
                    if (this.dataset.value === 'other') {
                        otherField.style.display = 'block';
                    } else {
                        otherField.style.display = 'none';
                    }
                }
                
                // Update selected state
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                dropdown.style.display = 'none';
                
                // Reset arrow rotation
                const arrow = select.querySelector('.custom-select-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0)';
                }
            });
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        const dropdowns = document.querySelectorAll('.custom-select-dropdown');
        dropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
            
            // Reset arrow rotation
            const select = dropdown.previousElementSibling;
            if (select) {
                const arrow = select.querySelector('.custom-select-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0)';
                }
            }
        });
    });
    
    // Form validation
    const settingsForm = document.getElementById('settings-form');
    
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate required fields
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.parentElement.classList.add('has-error');
                    
                    // Show error message
                    const errorMsg = field.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.textContent = 'This field is required';
                    }
                } else {
                    field.parentElement.classList.remove('has-error');
                }
            });
            
            // Validate email format
            const emailField = this.querySelector('input[type="email"]');
            if (emailField && emailField.value.trim()) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailField.value.trim())) {
                    isValid = false;
                    emailField.parentElement.classList.add('has-error');
                    
                    // Show error message
                    const errorMsg = emailField.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.textContent = 'Please enter a valid email address';
                    }
                }
            }
            
            // Validate ID number (if applicable)
            const idNumberField = this.querySelector('#sa-id-number');
            if (idNumberField && idNumberField.value.trim()) {
                // South African ID number validation logic
                // This is a simplified version - in a real implementation, you would include more robust validation
                if (!/^\d{13}$/.test(idNumberField.value.trim())) {
                    isValid = false;
                    idNumberField.parentElement.classList.add('has-error');
                    
                    // Show error message
                    const errorMsg = idNumberField.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.textContent = 'ID number should be 13 digits';
                    }
                }
            }
            
            // Validate password strength if changing password
            const passwordField = this.querySelector('#password');
            if (passwordField && passwordField.value.trim()) {
                const passwordStrengthRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                
                if (!passwordStrengthRegex.test(passwordField.value)) {
                    isValid = false;
                    passwordField.parentElement.classList.add('has-error');
                    
                    // Show error message
                    const errorMsg = passwordField.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.textContent = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character';
                    }
                }
            }
            
            // If form is not valid, prevent submission
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to the first error
                const firstError = this.querySelector('.has-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Real-time validation for fields
        const formInputs = settingsForm.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('blur', function() {
                // Reset error state
                this.parentElement.classList.remove('has-error');
                
                // Required field validation
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.parentElement.classList.add('has-error');
                    
                    // Show error message
                    const errorMsg = this.parentElement.querySelector('.error-message');
                    if (errorMsg) {
                        errorMsg.textContent = 'This field is required';
                    }
                    return;
                }
                
                // Email validation
                if (this.type === 'email' && this.value.trim()) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(this.value.trim())) {
                        this.parentElement.classList.add('has-error');
                        
                        // Show error message
                        const errorMsg = this.parentElement.querySelector('.error-message');
                        if (errorMsg) {
                            errorMsg.textContent = 'Please enter a valid email address';
                        }
                        return;
                    }
                }
                
                // ID number validation
                if (this.id === 'sa-id-number' && this.value.trim()) {
                    if (!/^\d{13}$/.test(this.value.trim())) {
                        this.parentElement.classList.add('has-error');
                        
                        // Show error message
                        const errorMsg = this.parentElement.querySelector('.error-message');
                        if (errorMsg) {
                            errorMsg.textContent = 'ID number should be 13 digits';
                        }
                        return;
                    }
                }
                
                // Password strength validation
                if (this.id === 'password' && this.value.trim()) {
                    const passwordStrengthRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                    
                    if (!passwordStrengthRegex.test(this.value)) {
                        this.parentElement.classList.add('has-error');
                        
                        // Show error message
                        const errorMsg = this.parentElement.querySelector('.error-message');
                        if (errorMsg) {
                            errorMsg.textContent = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character';
                        }
                        return;
                    }
                }
            });
        });
    }
    
    // Initialize form input masks
    const phoneInputs = document.querySelectorAll('.phone-input');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            // Remove non-numeric characters
            let value = this.value.replace(/\D/g, '');
            
            // Limit to appropriate length
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            // Format the number
            if (value.length > 6) {
                this.value = value.substring(0, 3) + ' ' + value.substring(3, 6) + ' ' + value.substring(6);
            } else if (value.length > 3) {
                this.value = value.substring(0, 3) + ' ' + value.substring(3);
            } else {
                this.value = value;
            }
        });
    });
    
    // Date picker enhancements
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set max date to today for date of birth fields
        if (input.id === 'dob') {
            const today = new Date().toISOString().split('T')[0];
            input.setAttribute('max', today);
        }
    });
    
    // Handle the "Other" options for select fields
    document.querySelectorAll('select').forEach(select => {
        const otherFieldId = select.getAttribute('data-other-field');
        if (otherFieldId) {
            const otherField = document.getElementById(otherFieldId);
            
            if (otherField) {
                // Set initial state
                otherField.style.display = select.value === 'Other' ? 'block' : 'none';
                
                // Add change listener
                select.addEventListener('change', function() {
                    otherField.style.display = this.value === 'Other' ? 'block' : 'none';
                    
                    if (this.value === 'Other') {
                        otherField.focus();
                    }
                });
            }
        }
    });
});