/**
 * Daily Attendance Register
 * JavaScript for handling interactions and animations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize GSAP animations
    initAnimations();
    
    // Set up filter buttons
    setupFilterButtons();
    
    // Set up date picker
    setupDatePicker();
});

/**
 * Initialize GSAP animations for page elements
 */
function initAnimations() {
    // Main timeline
    const mainTimeline = gsap.timeline({
        defaults: { 
            ease: "power3.out",
            duration: 0.5
        }
    });
    
    // Animate page header
    mainTimeline.from('.page-header', { 
        y: -30, 
        opacity: 0
    });
    
    // Animate controls section
    mainTimeline.from('.attendance-controls', { 
        y: -20, 
        opacity: 0 
    }, "-=0.3");
    
    // Staggered animation for summary cards
    mainTimeline.from('.summary-card', { 
        opacity: 0, 
        y: 15, 
        stagger: 0.1
    }, "-=0.3");
    
    // Staggered animation for attendance sections
    const sections = document.querySelectorAll('.attendance-section');
    sections.forEach((section, index) => {
        // Create separate timeline for each section
        const sectionTimeline = gsap.timeline({
            scrollTrigger: {
                trigger: section,
                start: "top bottom-=100",
                toggleActions: "play none none none"
            }
        });
        
        // Animate section title
        sectionTimeline.from(section.querySelector('.section-title'), { 
            opacity: 0, 
            x: -20,
            duration: 0.4
        });
        
        // Staggered animation for table rows
        sectionTimeline.from(section.querySelectorAll('tbody tr'), { 
            opacity: 0, 
            y: 10, 
            stagger: 0.03,
            duration: 0.3
        }, "-=0.2");
    });
    
    // Handle empty state if no records
    if (document.querySelector('.empty-attendance')) {
        gsap.from('.empty-attendance', { 
            scale: 0.9, 
            opacity: 0, 
            duration: 0.6,
            ease: "back.out(1.7)" 
        });
    }
}

/**
 * Set up date picker with Flatpickr
 */
function setupDatePicker() {
    // Get active dates from data attribute
    const datePicker = document.getElementById('datePicker');
    const activeDatesStr = datePicker.getAttribute('data-active-dates');
    let activeDates = [];
    
    if (activeDatesStr) {
        try {
            activeDates = JSON.parse(activeDatesStr);
        } catch (e) {
            console.error('Error parsing active dates', e);
        }
    }
    
    // Initialize Flatpickr
    const pickr = flatpickr("#datePicker", {
        dateFormat: "Y-m-d",
        maxDate: "today",
        disableMobile: "true",
        onChange: function(selectedDates, dateStr) {
            // Get current filter
            const currentFilter = getCurrentFilter();
            
            // Show loading animation
            showLoadingAnimation();
            
            // Redirect to the same page with the selected date
            window.location.href = `dailyAttendanceRegister.php?date=${dateStr}&filter=${currentFilter}`;
        },
        // Enable only dates with attendance records if provided
        enable: activeDates.length > 0 ? activeDates : undefined
    });
}

/**
 * Set up filter buttons
 */
function setupFilterButtons() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get current date
            const currentDate = document.getElementById('datePicker').value;
            const filter = this.getAttribute('data-filter');
            
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Animate transition when filter changes
            animateFilterChange(filter);
            
            // Show loading animation
            showLoadingAnimation();
            
            // Redirect after short delay for animations
            setTimeout(() => {
                window.location.href = `dailyAttendanceRegister.php?date=${currentDate}&filter=${filter}`;
            }, 300);
        });
    });
}

/**
 * Animate filter change
 * @param {string} newFilter - The filter to change to
 */
function animateFilterChange(newFilter) {
    // Get all attendance sections
    const sections = document.querySelectorAll('.attendance-section');
    
    if (newFilter === 'all') {
        // For 'all' filter, animate all sections
        sections.forEach(section => {
            gsap.to(section, {
                opacity: 1,
                y: 0,
                duration: 0.3,
                ease: "power2.out"
            });
        });
    } else {
        // For specific filter, fade out non-matching sections
        sections.forEach(section => {
            const sectionId = section.getAttribute('id');
            const sectionType = sectionId.replace('section-', '');
            
            if (sectionType === newFilter) {
                // Highlight the section that matches the filter
                gsap.to(section, {
                    opacity: 1,
                    y: 0,
                    duration: 0.3,
                    ease: "power2.out"
                });
            } else {
                // Fade out other sections
                gsap.to(section, {
                    opacity: 0.5,
                    y: 10,
                    duration: 0.3,
                    ease: "power2.out"
                });
            }
        });
    }
}

/**
 * Get current filter from active button
 * @return {string} Current filter
 */
function getCurrentFilter() {
    const activeButton = document.querySelector('.filter-btn.active');
    return activeButton ? activeButton.getAttribute('data-filter') : 'all';
}

/**
 * Show loading animation when changing page
 */
function showLoadingAnimation() {
    // Create overlay
    const overlay = document.createElement('div');
    overlay.classList.add('loading-overlay');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.backgroundColor = 'rgba(255, 255, 255, 0.8)';
    overlay.style.display = 'flex';
    overlay.style.justifyContent = 'center';
    overlay.style.alignItems = 'center';
    overlay.style.zIndex = '9999';
    
    // Create spinner
    const spinner = document.createElement('div');
    spinner.classList.add('loading-spinner');
    spinner.style.width = '50px';
    spinner.style.height = '50px';
    spinner.style.border = '5px solid #f3f3f3';
    spinner.style.borderTop = '5px solid #3498db';
    spinner.style.borderRadius = '50%';
    
    // Add animation
    spinner.animate(
        [
            { transform: 'rotate(0deg)' },
            { transform: 'rotate(360deg)' }
        ],
        {
            duration: 1000,
            iterations: Infinity
        }
    );
    
    // Add spinner to overlay
    overlay.appendChild(spinner);
    
    // Add overlay to body
    document.body.appendChild(overlay);
}