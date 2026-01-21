<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../../login.php?redirect=app/Views/holidayPrograms/holidayProgramCreationForm.php");
    exit;
}

// Include required files
require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../../core/CSRF.php';
require_once __DIR__ . '/../../../server.php';
require_once __DIR__ . '/../../Controllers/HolidayProgramCreationController.php';

// Initialize controller
$creationController = new HolidayProgramCreationController($conn);

// Handle form submission
$formSubmitted = false;
$programCreated = false;
$errorMessage = '';
$successMessage = '';
$newProgramId = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_program'])) {
    // Validate CSRF token
    if (!CSRF::validateToken()) {
        $errorMessage = 'Invalid security token. Please refresh the page and try again.';
        $formSubmitted = true;
        $programCreated = false;
        error_log("CSRF validation failed for holiday program creation: IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ", User: " . ($_SESSION['user_id'] ?? 'unknown'));
    } else {
        $formSubmitted = true;
        $result = $creationController->createProgram($_POST);
    
    if ($result['success']) {
        $programCreated = true;
        $newProgramId = $result['program_id'];
        $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    }
}

// Get editing program if edit mode
$editMode = isset($_GET['edit']) && isset($_GET['program_id']);
$editProgram = null;
if ($editMode) {
    $editProgram = $creationController->getProgramForEdit(intval($_GET['program_id']));
    if (!$editProgram) {
        $errorMessage = "Program not found.";
        $editMode = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Edit' : 'Create New'; ?> Holiday Program - Sci-Bono Clubhouse</title>
    <?php echo CSRF::metaTag(); ?>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramAdmin.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .creation-form-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .form-section {
            padding: 30px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .form-section:last-child {
            border-bottom: none;
        }
        
        .form-section h3 {
            margin: 0 0 20px 0;
            color: #495057;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .workshop-container {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
        
        .workshop-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .remove-workshop {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        
        .add-workshop-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .form-actions {
            background: #f8f9fa;
            padding: 30px;
            text-align: right;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        .success-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-card h2 {
            margin: 0 0 15px 0;
            font-size: 2rem;
        }
        
        .success-actions {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .form-actions {
                padding: 20px;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include './holidayPrograms-header.php'; ?>
    
    <div class="creation-form-container">
        <!-- Form Header -->
        <div class="form-header">
            <div class="container">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <a href="holidayProgramAdminDashboard.php" class="action-btn" style="background: rgba(255,255,255,0.2); color: white; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <div>
                        <h1><i class="fas fa-plus-circle"></i> <?php echo $editMode ? 'Edit Holiday Program' : 'Create New Holiday Program'; ?></h1>
                        <p><?php echo $editMode ? 'Update program details and settings' : 'Set up a new holiday program for participants'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container">
            <?php if ($programCreated): ?>
                <!-- Success Message -->
                <div class="success-card">
                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <h2>Program Created Successfully!</h2>
                    <p>Your holiday program has been created and is ready for registrations.</p>
                    <div class="success-actions">
                        <a href="holidayProgramAdminDashboard.php?program_id=<?php echo $newProgramId; ?>" class="action-btn primary">
                            <i class="fas fa-tachometer-alt"></i> View in Dashboard
                        </a>
                        <a href="holidayProgramCreationForm.php" class="action-btn" style="background: rgba(255,255,255,0.2); color: white; text-decoration: none;">
                            <i class="fas fa-plus"></i> Create Another Program
                        </a>
                        <a href="holiday-program-details-term1.php?id=<?php echo $newProgramId; ?>" class="action-btn" style="background: rgba(255,255,255,0.2); color: white; text-decoration: none;">
                            <i class="fas fa-eye"></i> Preview Program
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$programCreated): ?>
                <!-- Creation Form -->
                <form method="POST" action="" class="creation-form" id="program-form">
                    <?php echo CSRF::field(); ?>
                    <?php if ($editMode): ?>
                        <input type="hidden" name="program_id" value="<?php echo $editProgram['id']; ?>">
                        <input type="hidden" name="edit_mode" value="1">
                    <?php endif; ?>
                    
                    <!-- Basic Information -->
                    <div class="form-card">
                        <div class="form-section">
                            <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="term">Term <span class="required">*</span></label>
                                    <select id="term" name="term" class="form-select" required>
                                        <option value="">Select Term</option>
                                        <option value="Term 1" <?php echo ($editProgram && $editProgram['term'] === 'Term 1') ? 'selected' : ''; ?>>Term 1</option>
                                        <option value="Term 2" <?php echo ($editProgram && $editProgram['term'] === 'Term 2') ? 'selected' : ''; ?>>Term 2</option>
                                        <option value="Term 3" <?php echo ($editProgram && $editProgram['term'] === 'Term 3') ? 'selected' : ''; ?>>Term 3</option>
                                        <option value="Term 4" <?php echo ($editProgram && $editProgram['term'] === 'Term 4') ? 'selected' : ''; ?>>Term 4</option>
                                        <option value="Special Program" <?php echo ($editProgram && $editProgram['term'] === 'Special Program') ? 'selected' : ''; ?>>Special Program</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="title">Program Title <span class="required">*</span></label>
                                    <input type="text" id="title" name="title" class="form-input" 
                                           value="<?php echo htmlspecialchars($editProgram['title'] ?? ''); ?>" 
                                           placeholder="e.g., Multi-Media - Digital Design" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Program Description <span class="required">*</span></label>
                                <textarea id="description" name="description" class="form-textarea" 
                                          placeholder="Describe what participants will learn and do in this program..." required><?php echo htmlspecialchars($editProgram['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="program_goals">Program Goals</label>
                                <textarea id="program_goals" name="program_goals" class="form-textarea" 
                                          placeholder="What are the learning objectives and goals for this program?"><?php echo htmlspecialchars($editProgram['program_goals'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule & Logistics -->
                    <div class="form-card">
                        <div class="form-section">
                            <h3><i class="fas fa-calendar-alt"></i> Schedule & Logistics</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="start_date">Start Date <span class="required">*</span></label>
                                    <input type="date" id="start_date" name="start_date" class="form-input" 
                                           value="<?php echo $editProgram['start_date'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="end_date">End Date <span class="required">*</span></label>
                                    <input type="date" id="end_date" name="end_date" class="form-input" 
                                           value="<?php echo $editProgram['end_date'] ?? ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="time">Program Time</label>
                                    <input type="text" id="time" name="time" class="form-input" 
                                           value="<?php echo htmlspecialchars($editProgram['time'] ?? '9:00 AM - 4:00 PM'); ?>" 
                                           placeholder="e.g., 9:00 AM - 4:00 PM">
                                </div>
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input type="text" id="location" name="location" class="form-input" 
                                           value="<?php echo htmlspecialchars($editProgram['location'] ?? 'Sci-Bono Clubhouse'); ?>" 
                                           placeholder="Program location">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="age_range">Age Range</label>
                                    <input type="text" id="age_range" name="age_range" class="form-input" 
                                           value="<?php echo htmlspecialchars($editProgram['age_range'] ?? '13-18 years'); ?>" 
                                           placeholder="e.g., 13-18 years">
                                </div>
                                <div class="form-group">
                                    <label for="max_participants">Maximum Participants <span class="required">*</span></label>
                                    <input type="number" id="max_participants" name="max_participants" class="form-input" 
                                           value="<?php echo $editProgram['max_participants'] ?? '30'; ?>" 
                                           min="1" max="100" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="registration_deadline">Registration Deadline</label>
                                    <input type="text" id="registration_deadline" name="registration_deadline" class="form-input" 
                                           value="<?php echo htmlspecialchars($editProgram['registration_deadline'] ?? ''); ?>" 
                                           placeholder="e.g., March 24, 2025">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="lunch_included" value="1" 
                                               <?php echo ($editProgram && $editProgram['lunch_included']) ? 'checked' : 'checked'; ?>>
                                        Lunch included
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Registration Settings -->
                    <div class="form-card">
                        <div class="form-section">
                            <h3><i class="fas fa-cog"></i> Registration Settings</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="registration_status">Registration Status <span class="required">*</span></label>
                                    <select id="registration_status" name="registration_status" class="form-select" required>
                                        <option value="closed" <?php echo ($editProgram && !$editProgram['registration_open']) ? 'selected' : ''; ?>>Closed</option>
                                        <option value="open" <?php echo (!$editProgram || $editProgram['registration_open']) ? 'selected' : ''; ?>>Open</option>
                                        <option value="closing_soon">Closing Soon</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="registration_open" value="1" 
                                               <?php echo (!$editProgram || $editProgram['registration_open']) ? 'checked' : ''; ?>>
                                        Enable online registration
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Workshops -->
                    <div class="form-card">
                        <div class="form-section">
                            <h3><i class="fas fa-laptop-code"></i> Workshops</h3>
                            <p style="color: #666; margin-bottom: 20px;">Add the workshops that will be available for this program.</p>
                            
                            <button type="button" class="add-workshop-btn" onclick="addWorkshop()">
                                <i class="fas fa-plus"></i> Add Workshop
                            </button>
                            
                            <div id="workshops-container">
                                <?php if ($editMode && isset($editProgram['workshops'])): ?>
                                    <?php foreach ($editProgram['workshops'] as $index => $workshop): ?>
                                        <div class="workshop-container">
                                            <div class="workshop-header">
                                                <h4>Workshop <?php echo $index + 1; ?></h4>
                                                <button type="button" class="remove-workshop" onclick="removeWorkshop(this)">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Workshop Title <span class="required">*</span></label>
                                                    <input type="text" name="workshops[<?php echo $index; ?>][title]" class="form-input" 
                                                           value="<?php echo htmlspecialchars($workshop['title']); ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Instructor</label>
                                                    <input type="text" name="workshops[<?php echo $index; ?>][instructor]" class="form-input" 
                                                           value="<?php echo htmlspecialchars($workshop['instructor']); ?>">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Max Participants</label>
                                                    <input type="number" name="workshops[<?php echo $index; ?>][max_participants]" class="form-input" 
                                                           value="<?php echo $workshop['max_participants']; ?>" min="1" max="50">
                                                </div>
                                                <div class="form-group">
                                                    <label>Location</label>
                                                    <input type="text" name="workshops[<?php echo $index; ?>][location]" class="form-input" 
                                                           value="<?php echo htmlspecialchars($workshop['location'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Description</label>
                                                <textarea name="workshops[<?php echo $index; ?>][description]" class="form-textarea" rows="3"><?php echo htmlspecialchars($workshop['description']); ?></textarea>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Default empty workshop -->
                                    <div class="workshop-container">
                                        <div class="workshop-header">
                                            <h4>Workshop 1</h4>
                                            <button type="button" class="remove-workshop" onclick="removeWorkshop(this)">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Workshop Title <span class="required">*</span></label>
                                                <input type="text" name="workshops[0][title]" class="form-input" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Instructor</label>
                                                <input type="text" name="workshops[0][instructor]" class="form-input">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Max Participants</label>
                                                <input type="number" name="workshops[0][max_participants]" class="form-input" 
                                                       value="15" min="1" max="50">
                                            </div>
                                            <div class="form-group">
                                                <label>Location</label>
                                                <input type="text" name="workshops[0][location]" class="form-input">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="workshops[0][description]" class="form-textarea" rows="3"></textarea>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="holidayProgramAdminDashboard.php" class="action-btn">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="create_program" class="action-btn primary">
                            <i class="fas fa-save"></i> <?php echo $editMode ? 'Update Program' : 'Create Program'; ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let workshopIndex = <?php echo $editMode && isset($editProgram['workshops']) ? count($editProgram['workshops']) : 1; ?>;
        
        function addWorkshop() {
            const container = document.getElementById('workshops-container');
            const workshopHtml = `
                <div class="workshop-container">
                    <div class="workshop-header">
                        <h4>Workshop ${workshopIndex + 1}</h4>
                        <button type="button" class="remove-workshop" onclick="removeWorkshop(this)">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Workshop Title <span class="required">*</span></label>
                            <input type="text" name="workshops[${workshopIndex}][title]" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Instructor</label>
                            <input type="text" name="workshops[${workshopIndex}][instructor]" class="form-input">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Max Participants</label>
                            <input type="number" name="workshops[${workshopIndex}][max_participants]" class="form-input" 
                                   value="15" min="1" max="50">
                        </div>
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="workshops[${workshopIndex}][location]" class="form-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="workshops[${workshopIndex}][description]" class="form-textarea" rows="3"></textarea>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', workshopHtml);
            workshopIndex++;
        }
        
        function removeWorkshop(button) {
            const container = button.closest('.workshop-container');
            container.remove();
            
            // Renumber remaining workshops
            const workshops = document.querySelectorAll('.workshop-container');
            workshops.forEach((workshop, index) => {
                workshop.querySelector('h4').textContent = `Workshop ${index + 1}`;
            });
        }
        
        // Auto-generate dates string when start/end dates change
        function updateDatesString() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                const startFormatted = start.toLocaleDateString('en-US', { 
                    month: 'long', 
                    day: 'numeric' 
                });
                const endFormatted = end.toLocaleDateString('en-US', { 
                    month: 'long', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
                
                // Update the dates field if it exists (you might want to add this field)
                console.log(`${startFormatted} - ${endFormatted}`);
            }
        }
        
        document.getElementById('start_date').addEventListener('change', updateDatesString);
        document.getElementById('end_date').addEventListener('change', updateDatesString);
        
        // Form validation
        document.getElementById('program-form').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (startDate >= endDate) {
                e.preventDefault();
                alert('End date must be after start date.');
                return false;
            }
            
            // Check if at least one workshop is added
            const workshops = document.querySelectorAll('.workshop-container');
            if (workshops.length === 0) {
                e.preventDefault();
                alert('Please add at least one workshop.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>