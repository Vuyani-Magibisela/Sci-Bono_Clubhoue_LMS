// Enhanced Workshop Selection
$(document).ready(function() {
    // Initial setup
    const workshopLimit = 2; // Limit users to selecting only 2 workshops
    let workshopSelections = [];
    
    // Function to update the workshop selection UI
    function updateWorkshopSelectionUI() {
        // Clear all selections first
        $('input[name="workshop_preference[]"]').prop('disabled', false).parent().parent().removeClass('preferred backup disabled');
        
        // Apply styling and restrictions based on current selections
        workshopSelections.forEach((selection, index) => {
            const checkbox = $(`#workshop_${selection.id}`);
            const workshopOption = checkbox.closest('.workshop-option');
            
            if (index === 0) {
                workshopOption.addClass('preferred');
                workshopOption.find('.selection-label').text('(Preferred)');
            } else if (index === 1) {
                workshopOption.addClass('backup');
                workshopOption.find('.selection-label').text('(Backup)');
            }
        });
        
        // If we have reached the limit, disable unselected checkboxes
        if (workshopSelections.length >= workshopLimit) {
            $('input[name="workshop_preference[]"]:not(:checked)').prop('disabled', true)
                .closest('.workshop-option').addClass('disabled');
        }
        
        // Update the hidden input with the ordered preferences
        $('#workshop_preferences_ordered').val(JSON.stringify(workshopSelections));
        
        // Show or hide the selection info message
        if (workshopSelections.length > 0) {
            $('#selection-info').show();
        } else {
            $('#selection-info').hide();
        }
    }
    
    // Initialize capacity indicators
    function initializeCapacityIndicators() {
        $('.workshop-option').each(function() {
            const workshopId = $(this).find('input[type="checkbox"]').val();
            const capacityData = workshopCapacityData[workshopId];
            
            if (capacityData) {
                const capacityPercentage = (capacityData.enrolled / capacityData.max) * 100;
                const isFull = capacityPercentage >= 100;
                
                // Create capacity indicator
                const capacityIndicator = $(`
                    <div class="capacity-indicator">
                        <div class="capacity-bar">
                            <div class="capacity-fill" style="width: ${Math.min(capacityPercentage, 100)}%;"></div>
                        </div>
                        <div class="capacity-text">
                            ${capacityData.enrolled}/${capacityData.max} spots filled
                            ${isFull ? '<span class="capacity-full">FULL</span>' : ''}
                        </div>
                    </div>
                `);
                
                $(this).append(capacityIndicator);
                
                // If workshop is full, disable selection
                if (isFull) {
                    $(this).addClass('full-workshop');
                    $(this).find('input[type="checkbox"]').prop('disabled', true);
                }
            }
        });
    }
    
    // Add selection label spans to each workshop option
    $('.workshop-option').each(function() {
        $(this).find('.checkbox-group').append('<span class="selection-label"></span>');
    });
    
    // Handle checkbox changes for workshop preferences
    $('input[name="workshop_preference[]"]').change(function() {
        const workshopId = $(this).val();
        const workshopTitle = $(this).closest('.workshop-option').find('label').text().trim();
        
        if ($(this).is(':checked')) {
            // Add to selections if not already at limit
            if (workshopSelections.length < workshopLimit) {
                workshopSelections.push({
                    id: workshopId,
                    title: workshopTitle
                });
            } else {
                // If at limit, uncheck and return
                $(this).prop('checked', false);
                alert(`You can only select ${workshopLimit} workshops. Please unselect one first.`);
                return;
            }
        } else {
            // Remove from selections
            workshopSelections = workshopSelections.filter(selection => selection.id !== workshopId);
        }
        
        updateWorkshopSelectionUI();
    });
    
    // Add workshop selection info message
    $('<div id="selection-info" class="selection-info" style="display: none;">' +
      '<p>Selected workshops are prioritized in the order selected. Your first selection will be considered your preferred choice.</p>' +
      '<p>Click a selected workshop to deselect it if you want to change your preferences.</p>' +
      '</div>').insertBefore('.workshop-options');
    
    // Add reorder buttons for the workshops
    $('<div class="reorder-controls">' +
      '<button type="button" id="swap-preferences" class="secondary-button">Swap Preferences</button>' +
      '</div>').insertAfter('#selection-info');
    
    // Handle swapping preferences
    $('#swap-preferences').click(function() {
        if (workshopSelections.length === 2) {
            // Swap the order of selections
            [workshopSelections[0], workshopSelections[1]] = [workshopSelections[1], workshopSelections[0]];
            updateWorkshopSelectionUI();
        } else {
            alert('You need to select two workshops to swap preferences.');
        }
    });
    
    // Add workshop capacity data (to be replaced with actual data from PHP)
    const workshopCapacityData = {
        // This would be populated from PHP with actual data
        // Example format:
        // 1: { enrolled: 3, max: 5 },
        // 2: { enrolled: 8, max: 10 },
        // etc.
    };
    
    // If mentor registration, handle the workshop preference differently
    $('#mentor_registration').change(function() {
        if ($(this).is(':checked')) {
            // Hide the workshop selection interface for members
            $('#workshop_preferences_section').hide();
            
            // Show the mentor workshop preference dropdown
            $('#mentor_fields').show();
            
            // Update the workshop dropdown with capacity information
            $('#mentor_workshop_preference option').each(function() {
                const workshopId = $(this).val();
                if (workshopId && workshopCapacityData[workshopId]) {
                    const capacityData = workshopCapacityData[workshopId];
                    const isFull = (capacityData.enrolled >= capacityData.max);
                    
                    if (isFull) {
                        $(this).text($(this).text() + ' (FULL)');
                        // We don't disable options in the dropdown as mentors might still be assigned
                    } else {
                        $(this).text($(this).text() + ` (${capacityData.enrolled}/${capacityData.max})`);
                    }
                }
            });
        } else {
            // Show the workshop selection for members
            $('#workshop_preferences_section').show();
        }
    });
    
    // Initialize capacity indicators
    // This would be enabled once actual data is available from PHP
    // initializeCapacityIndicators();
    
    // Create a hidden input for the ordered preferences
    $('<input type="hidden" id="workshop_preferences_ordered" name="workshop_preferences_ordered">').appendTo('.workshop-options');
});