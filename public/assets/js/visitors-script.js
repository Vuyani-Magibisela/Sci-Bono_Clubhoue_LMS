document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabs.forEach(tab => tab.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to current tab and content
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            });
        });
    });

    // Form validation functions
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    function validatePhone(phone) {
        const re = /^(\+\d{1,2}\s?)?\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/;
        return re.test(String(phone));
    }

    function showError(inputId, errorMessage) {
        const input = document.getElementById(inputId);
        const errorElement = document.getElementById(`${inputId}-error`);
        
        input.classList.add('error');
        errorElement.textContent = errorMessage;
        errorElement.style.display = 'block';
    }

    function hideError(inputId) {
        const input = document.getElementById(inputId);
        const errorElement = document.getElementById(`${inputId}-error`);
        
        input.classList.remove('error');
        errorElement.style.display = 'none';
    }

    function showNotification(type, message) {
        const notification = document.getElementById('notification');
        notification.className = 'notification';
        notification.classList.add(`notification-${type}`);
        notification.textContent = message;
        notification.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }

    // Registration form validation
    const registrationForm = document.getElementById('registration-form');
    
    registrationForm.addEventListener('submit', function(event) {
        event.preventDefault();
        let isValid = true;
        
        // Validate Name
        const name = document.getElementById('name').value.trim();
        if (name === '') {
            showError('name', 'Please enter your name');
            isValid = false;
        } else {
            hideError('name');
        }
        
        // Validate Surname
        const surname = document.getElementById('surname').value.trim();
        if (surname === '') {
            showError('surname', 'Please enter your surname');
            isValid = false;
        } else {
            hideError('surname');
        }
        
        // Validate Age
        const age = document.getElementById('age').value;
        if (age === '' || isNaN(age) || age < 5 || age > 100) {
            showError('age', 'Please enter a valid age (5-100)');
            isValid = false;
        } else {
            hideError('age');
        }
        
        // Validate Grade School
        const gradeSchool = document.getElementById('grade-school').value.trim();
        if (gradeSchool === '') {
            showError('grade-school', 'Please enter your grade school');
            isValid = false;
        } else {
            hideError('grade-school');
        }
        
        // Validate Parent Name
        const parentName = document.getElementById('parent-name').value.trim();
        if (parentName === '') {
            showError('parent-name', 'Please enter parent name');
            isValid = false;
        } else {
            hideError('parent-name');
        }
        
        // Validate Parent Surname
        const parentSurname = document.getElementById('parent-surname').value.trim();
        if (parentSurname === '') {
            showError('parent-surname', 'Please enter parent surname');
            isValid = false;
        } else {
            hideError('parent-surname');
        }
        
        // Validate Email
        const email = document.getElementById('email').value.trim();
        if (email === '' || !validateEmail(email)) {
            showError('email', 'Please enter a valid email address');
            isValid = false;
        } else {
            hideError('email');
        }
        
        // Validate Phone
        const phone = document.getElementById('phone').value.trim();
        if (phone === '' || !validatePhone(phone)) {
            showError('phone', 'Please enter a valid phone number');
            isValid = false;
        } else {
            hideError('phone');
        }
        
        // If form is valid, submit data
        if (isValid) {
            console.log("Form is valid, submitting data...");
            // Show loading state
            const registerBtn = document.getElementById('register-btn');
            registerBtn.classList.add('loading');
            registerBtn.disabled = true;
            
            // Gather form data
            const formData = new FormData(registrationForm);
            
            // Submit form data via AJAX
            fetch('../../handlers/visitors-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                registerBtn.classList.remove('loading');
                registerBtn.disabled = false;
                
                if (data.success) {
                    showNotification('success', 'Registration successful!');
                    registrationForm.reset();
                    loadVisitors(); // Reload the visitors list
                } else {
                    showNotification('error', data.message || 'Registration failed. Please try again.');
                }
            })
            .catch(error => {
                registerBtn.classList.remove('loading');
                registerBtn.disabled = false;
                showNotification('error', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
    });

    // Sign In form submission
    const signInForm = document.getElementById('signin-form');
    
    signInForm.addEventListener('submit', function(event) {
        event.preventDefault();
        let isValid = true;
        
        // Validate Email
        const email = document.getElementById('signin-email').value.trim();
        if (email === '' || !validateEmail(email)) {
            showError('signin-email', 'Please enter a valid email address');
            isValid = false;
        } else {
            hideError('signin-email');
        }
        
        // If form is valid, submit data
        if (isValid) {
            // Show loading state
            const signInBtn = document.getElementById('signin-btn');
            signInBtn.classList.add('loading');
            signInBtn.disabled = true;
            
            // Gather form data
            const formData = new FormData(signInForm);
            formData.append('action', 'signin');
            
            // Submit form data via AJAX
            fetch('../../handlers/visitors-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                signInBtn.classList.remove('loading');
                signInBtn.disabled = false;
                
                if (data.success) {
                    showNotification('success', 'Sign in successful!');
                    signInForm.reset();
                    loadVisitors(); // Reload the visitors list
                } else {
                    showNotification('error', data.message || 'Sign in failed. Please try again.');
                }
            })
            .catch(error => {
                signInBtn.classList.remove('loading');
                signInBtn.disabled = false;
                showNotification('error', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
    });

    // Sign Out form submission
    const signOutForm = document.getElementById('signout-form');
    
    signOutForm.addEventListener('submit', function(event) {
        event.preventDefault();
        let isValid = true;
        
        // Validate Email
        const email = document.getElementById('signout-email').value.trim();
        if (email === '' || !validateEmail(email)) {
            showError('signout-email', 'Please enter a valid email address');
            isValid = false;
        } else {
            hideError('signout-email');
        }
        
        // If form is valid, submit data
        if (isValid) {
            // Show loading state
            const signOutBtn = document.getElementById('signout-btn');
            signOutBtn.classList.add('loading');
            signOutBtn.disabled = true;
            
            // Gather form data
            const formData = new FormData(signOutForm);
            formData.append('action', 'signout');
            
            // Submit form data via AJAX
            fetch('../../handlers/visitors-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                signOutBtn.classList.remove('loading');
                signOutBtn.disabled = false;
                
                if (data.success) {
                    showNotification('success', 'Sign out successful!');
                    signOutForm.reset();
                    loadVisitors(); // Reload the visitors list
                } else {
                    showNotification('error', data.message || 'Sign out failed. Please try again.');
                }
            })
            .catch(error => {
                signOutBtn.classList.remove('loading');
                signOutBtn.disabled = false;
                showNotification('error', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }
    });

    // Load visitors data
    let currentPage = 1;
    const recordsPerPage = 10;
    
    function loadVisitors(page = 1, filter = 'all', searchTerm = '') {
        currentPage = page;
        
        // Show loading state
        const visitorsList = document.getElementById('visitors-list');
        visitorsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">Loading...</td></tr>';
        
        // Fetch visitors data via AJAX
        fetch(`visitors-handler.php?action=list&page=${page}&filter=${filter}&search=${searchTerm}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear the table
                    visitorsList.innerHTML = '';
                    
                    if (data.visitors.length === 0) {
                        visitorsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">No visitors found</td></tr>';
                    } else {
                        // Populate the table with visitor data
                        data.visitors.forEach(visitor => {
                            // Calculate duration if sign out time exists
                            let duration = 'N/A';
                            if (visitor.sign_out_time) {
                                const signInTime = new Date(visitor.sign_in_time);
                                const signOutTime = new Date(visitor.sign_out_time);
                                const durationMs = signOutTime - signInTime;
                                
                                // Format duration as hours and minutes
                                const hours = Math.floor(durationMs / (1000 * 60 * 60));
                                const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
                                duration = `${hours}h ${minutes}m`;
                            }
                            
                            // Format status badge
                            const status = visitor.sign_out_time ? 
                                '<span class="badge badge-completed">Completed</span>' : 
                                '<span class="badge badge-active">Active</span>';
                            
                            // Create table row
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${visitor.name} ${visitor.surname}</td>
                                <td>${visitor.email}</td>
                                <td>${formatDateTime(visitor.sign_in_time)}</td>
                                <td>${visitor.sign_out_time ? formatDateTime(visitor.sign_out_time) : 'Still Present'}</td>
                                <td>${duration}</td>
                                <td>${status}</td>
                            `;
                            
                            visitorsList.appendChild(row);
                        });
                        
                        // Generate pagination
                        generatePagination(data.totalPages, currentPage);
                    }
                } else {
                    visitorsList.innerHTML = `<tr><td colspan="6" style="text-align: center;">${data.message || 'Failed to load visitors'}</td></tr>`;
                }
            })
            .catch(error => {
                visitorsList.innerHTML = '<tr><td colspan="6" style="text-align: center;">Error loading visitors</td></tr>';
                console.error('Error:', error);
            });
    }
    
    // Helper function to format date and time
    function formatDateTime(dateTimeString) {
        const dateTime = new Date(dateTimeString);
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(dateTime);
    }
    
    // Generate pagination links
    function generatePagination(totalPages, currentPage) {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';
        
        // Previous button
        if (totalPages > 1) {
            const prevLink = document.createElement('a');
            prevLink.innerHTML = '&laquo;';
            prevLink.href = '#';
            if (currentPage > 1) {
                prevLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    loadVisitors(currentPage - 1, getCurrentFilter(), getCurrentSearch());
                });
            } else {
                prevLink.classList.add('disabled');
            }
            pagination.appendChild(prevLink);
        }
        
        // Page links
        for (let i = 1; i <= totalPages; i++) {
            const pageLink = document.createElement('a');
            pageLink.textContent = i;
            pageLink.href = '#';
            if (i === currentPage) {
                pageLink.classList.add('active');
            }
            pageLink.addEventListener('click', (e) => {
                e.preventDefault();
                if (i !== currentPage) {
                    loadVisitors(i, getCurrentFilter(), getCurrentSearch());
                }
            });
            pagination.appendChild(pageLink);
        }
        
        // Next button
        if (totalPages > 1) {
            const nextLink = document.createElement('a');
            nextLink.innerHTML = '&raquo;';
            nextLink.href = '#';
            if (currentPage < totalPages) {
                nextLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    loadVisitors(currentPage + 1, getCurrentFilter(), getCurrentSearch());
                });
            } else {
                nextLink.classList.add('disabled');
            }
            pagination.appendChild(nextLink);
        }
    }
    
    // Get current filter value
    function getCurrentFilter() {
        return document.getElementById('filter-status').value;
    }
    
    // Get current search term
    function getCurrentSearch() {
        return document.getElementById('search-input').value.trim();
    }
    
    // Initial loading of visitors data
    loadVisitors(1, getCurrentFilter(), getCurrentSearch());
    
    // Search and filter functionality
    const searchInput = document.getElementById('search-input');
    const filterStatus = document.getElementById('filter-status');
    
    // Debounce function to prevent too many API calls while typing
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    // Handle search input
    searchInput.addEventListener('input', debounce(function() {
        loadVisitors(1, getCurrentFilter(), getCurrentSearch());
    }, 500));
    
    // Handle filter change
    filterStatus.addEventListener('change', function() {
        loadVisitors(1, getCurrentFilter(), getCurrentSearch());
    });