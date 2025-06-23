$(document).ready(function() {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const isMentorParam = urlParams.get('mentor') === '1';
    const isMemberParam = urlParams.get('member') === '1';
    
    // Set initial state based on URL parameters
    if (isMentorParam) {
        $('#mentor_registration').prop('checked', true);
        $('#mentor_registration').trigger('change');
    }
    
    // Email check functionality
    $('#check-email-btn').click(function(e) {
        e.preventDefault();
        const email = $('#check-email').val();
        
        if (!email) {
            alert('Please enter an email address');
            return;
        }
        
        // Show loading indicator
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Checking...');
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                check_email: true,
                email: email
            },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    $('#email-check-result').removeClass('hidden').addClass('success');
                    $('#email-check-result p').text('Email found! Form will be pre-filled with your information.');
                    
                    // Fill the form with user data
                    const data = response.data;
                    $('#first_name').val(data.first_name || data.name);
                    $('#last_name').val(data.last_name || data.surname);
                    $('#email').val(data.email);
                    $('#phone').val(data.phone || data.leaner_number);
                    $('#dob').val(data.date_of_birth);
                    $('#gender').val(data.gender || data.Gender);
                    $('#school').val(data.school);
                    $('#grade').val(data.grade);
                    $('#address').val(data.address || data.address_street);
                    $('#city').val(data.city || data.address_city);
                    $('#province').val(data.province || data.address_province);
                    $('#postal_code').val(data.postal_code || data.address_postal_code);
                    
                    // Parent/Guardian information
                    $('#guardian_name').val(data.guardian_name || data.parent);
                    $('#guardian_relationship').val(data.guardian_relationship || data.Relationship);
                    $('#guardian_phone').val(data.guardian_phone || data.parent_number);
                    $('#guardian_email').val(data.guardian_email || data.parent_email);
                    
                    // Emergency contact information
                    $('#emergency_contact_name').val(data.emergency_contact_name || '');
                    $('#emergency_contact_relationship').val(data.emergency_contact_relationship || '');
                    $('#emergency_contact_phone').val(data.emergency_contact_phone || '');
                    
                    // Medical information and additional details if available
                    if (data.medical_conditions) $('#medical_conditions').val(data.medical_conditions);
                    if (data.allergies) $('#allergies').val(data.allergies);
                    if (data.dietary_restrictions) $('#dietary_restrictions').val(data.dietary_restrictions);
                    if (data.additional_notes) $('#additional_notes').val(data.additional_notes);
                    
                    // Workshop preferences - this would need to be handled specially for existing registrations
                    if (data.workshop_preference) {
                        try {
                            const workshops = JSON.parse(data.workshop_preference);
                            workshops.forEach(workshopId => {
                                $(`#workshop_${workshopId}`).prop('checked', true);
                            });
                        } catch (e) {
                            console.error('Error parsing workshop preferences:', e);
                        }
                    }
                    
                    // Scroll to form
                    $('html, body').animate({
                        scrollTop: $("#registration-form").offset().top - 100
                    }, 500);
                } else {
                    $('#email-check-result').removeClass('hidden').removeClass('success');
                    $('#email-check-result p').text('Email not found. Please fill out the form below.');
                }
            },
            error: function() {
                $('#email-check-result').removeClass('hidden').removeClass('success');
                $('#email-check-result p').text('Error checking email. Please try again or fill out the form manually.');
            },
            complete: function() {
                $('#check-email-btn').html('Check Email');
            }
        });
    });
    
    // Same as guardian checkbox
    $('#same_as_guardian').change(function() {
        if ($(this).is(':checked')) {
            // Copy parent/guardian info to emergency contact
            $('#emergency_contact_name').val($('#guardian_name').val());
            $('#emergency_contact_relationship').val($('#guardian_relationship').val());
            $('#emergency_contact_phone').val($('#guardian_phone').val());
            
            // Disable emergency contact fields
            $('#emergency_contact_fields input').prop('disabled', true);
        } else {
            // Enable emergency contact fields
            $('#emergency_contact_fields input').prop('disabled', false);
        }
    });

    // Mentor registration toggle functionality
    $('#mentor_registration').change(function() {
        if ($(this).is(':checked')) {
            // Show mentor-specific fields
            $('#mentor_fields').slideDown();
            $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', true);
            
            // Hide student-specific sections
            $('#school_section').slideUp();
            $('#guardian_section').slideUp();
            $('#workshop_preferences_section').slideUp();
            
            // Remove required attributes from student-specific fields
            $('#school, #grade').prop('required', false);
            $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', false);
            $('#why_interested, #experience_level').prop('required', false);
            $('input[name="workshop_preference[]"]').prop('required', false);
            
            // Add note about mentor workshop assignment
            $('#workshop_note').html('<div class="info-message"><i class="fas fa-info-circle"></i> As a mentor, you will be assigned to a specific workshop based on program needs and your expertise.</div>').slideDown();
        } else {
            // Hide mentor-specific fields
            $('#mentor_fields').slideUp();
            $('#mentor_experience, #mentor_availability, #mentor_workshop_preference').prop('required', false);
            
            // Show student-specific sections
            $('#school_section').slideDown();
            $('#guardian_section').slideDown();
            $('#workshop_preferences_section').slideDown();
            $('#workshop_note').slideUp();
            
            // Re-add required attributes to student-specific fields
            $('#school, #grade').prop('required', true);
            $('#guardian_name, #guardian_relationship, #guardian_phone, #guardian_email').prop('required', true);
            $('#why_interested, #experience_level').prop('required', true);
        }
    });
    
    // Trigger initial state
    $('#mentor_registration').trigger('change');
    
    // Form validation
    $('#registration-form').submit(function(e) {
        // First check if this is a mentor registration
        const isMentor = $('#mentor_registration').is(':checked');

        // Only validate workshop selection for non-mentors
        if (!isMentor && $('input[name="workshop_preference[]"]:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one workshop preference');
            return false;
        }
        
        // Check required checkboxes
        if (!$('#photo_permission').is(':checked') || !$('#data_permission').is(':checked')) {
            e.preventDefault();
            alert('Please agree to the required permissions');
            return false;
        }

        // Validate mentor fields if mentor registration
        if (isMentor) {
            if (!$('#mentor_experience').val().trim() || 
                !$('#mentor_availability').val() || 
                !$('#mentor_workshop_preference').val()) {
                e.preventDefault();
                alert('Please complete all required mentor fields');
                return false;
            }
        }

        // Age validation - only apply to non-mentors
        if (!isMentor) {
            const dobValue = $('#dob').val();
            if (dobValue) {
                const dob = new Date(dobValue);
                const today = new Date();
                
                // Calculate age
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                // Adjust age if birthday hasn't occurred yet this year
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                // Check program age restrictions
                const minAge = 13; // Minimum age
                const maxAge = 18; // Maximum age
                
                if (age < minAge || age > maxAge) {
                    e.preventDefault();
                    
                    // Custom error message based on age
                    let errorMessage = `You must be between ${minAge}-${maxAge} years old to register for this program.`;
                    if (age < minAge) {
                        errorMessage = `You must be at least ${minAge} years old to register for this program.`;
                    } else if (age > maxAge) {
                        errorMessage = `You must be ${maxAge} years old or younger to register for this program.`;
                    }
                    
                    // Show error message
                    $('.error-message').remove(); // Remove any existing error messages
                    $('<div class="error-message"><p><i class="fas fa-exclamation-circle"></i> ' + errorMessage + '</p></div>')
                        .insertBefore('#registration-form')
                        .hide()
                        .fadeIn(300);
                    
                    // Highlight the field and scroll to it
                    $('#dob').addClass('error-input');
                    $('html, body').animate({
                        scrollTop: $('#dob').offset().top - 100
                    }, 500);
                    
                    return false;
                } else {
                    $('#dob').removeClass('error-input');
                }
            }
        }

        return true;
    });
    
    // Handle show mentor form button for capacity full scenarios
    $('#show-mentor-form').click(function() {
        // Check the mentor registration checkbox
        $('#mentor_registration').prop('checked', true);
        
        // Trigger the change event to show mentor fields
        $('#mentor_registration').trigger('change');
        
        // Scroll to the form
        $('.registration-form-container').get(0).scrollIntoView({
            behavior: 'smooth'
        });
    });
});