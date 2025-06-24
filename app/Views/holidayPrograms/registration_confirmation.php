<?php
session_start();
require_once '../../../server.php';

// Check if registration was successful
if (!isset($_SESSION['registration_success']) || !$_SESSION['registration_success']) {
    header('Location: holidayProgramRegistration.php');
    exit();
}

$confirmationCode = $_SESSION['confirmation_code'] ?? '';
$attendeeName = $_SESSION['attendee_name'] ?? '';
$workshopAssignments = $_SESSION['workshop_assignments'] ?? [];

// Clear session variables
unset($_SESSION['registration_success']);
unset($_SESSION['confirmation_code']);
unset($_SESSION['attendee_name']);
unset($_SESSION['workshop_assignments']);

// Get full registration details from database
$sql = "SELECT 
            a.*,
            p.title as program_title,
            p.dates as program_dates,
            c.name as cohort_name,
            c.start_date as cohort_start_date,
            c.end_date as cohort_end_date
        FROM holiday_program_attendees a
        JOIN holiday_programs p ON a.program_id = p.id
        LEFT JOIN holiday_program_cohorts c ON a.cohort_id = c.id
        WHERE a.confirmation_code = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $confirmationCode);
$stmt->execute();
$result = $stmt->get_result();
$registration = $result->fetch_assoc();

if (!$registration) {
    header('Location: holidayProgramRegistration.php');
    exit();
}

// Get selected workshops
$workshopPreferences = json_decode($registration['workshop_preference'], true) ?? [];
$selectedWorkshops = [];

if (!empty($workshopPreferences)) {
    $placeholders = str_repeat('?,', count($workshopPreferences) - 1) . '?';
    $workshopSql = "SELECT * FROM holiday_program_workshops WHERE id IN ($placeholders)";
    $workshopStmt = $conn->prepare($workshopSql);
    $workshopStmt->bind_param(str_repeat('i', count($workshopPreferences)), ...$workshopPreferences);
    $workshopStmt->execute();
    $workshopResult = $workshopStmt->get_result();
    
    while ($workshop = $workshopResult->fetch_assoc()) {
        $selectedWorkshops[] = $workshop;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmed - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="../../../public/assets/css/holidayProgramStyles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .success-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 12px;
        }
        
        .success-header i {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
        }
        
        .success-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5rem;
        }
        
        .confirmation-details {
            display: grid;
            gap: 25px;
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #6c63ff;
        }
        
        .detail-section h3 {
            margin-top: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e1e8ed;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #333;
            font-size: 1rem;
        }
        
        .workshop-list {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }
        
        .workshop-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #e1e8ed;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .preference-badge {
            background: #6c63ff;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .preference-badge.second {
            background: #8e8af7;
        }
        
        .next-steps {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
        }
        
        .next-steps h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .next-steps ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        
        .next-steps li {
            margin: 10px 0;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #6c63ff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a52d5;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        
        .alert-icon {
            color: #856404;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px;
                padding: 20px;
            }
            
            .success-header h1 {
                font-size: 2rem;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <?php include './holidayPrograms-header.php'; ?>
    
    <div class="confirmation-container">
        <div class="success-header">
            <i class="fas fa-check-circle"></i>
            <h1>Registration Confirmed!</h1>
            <p>Thank you for registering for our holiday program</p>
        </div>
        
        <div class="confirmation-details">
            <!-- Confirmation Information -->
            <div class="detail-section">
                <h3><i class="fas fa-ticket-alt"></i> Confirmation Details</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Confirmation Code</div>
                        <div class="detail-value"><strong><?php echo htmlspecialchars($confirmationCode); ?></strong></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Registration Status</div>
                        <div class="detail-value">
                            <span style="color: #ffc107;">
                                <i class="fas fa-clock"></i> Pending Review
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Program Information -->
            <div class="detail-section">
                <h3><i class="fas fa-calendar-alt"></i> Program Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Program</div>
                        <div class="detail-value"><?php echo htmlspecialchars($registration['program_title']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Dates</div>
                        <div class="detail-value"><?php echo htmlspecialchars($registration['program_dates']); ?></div>
                    </div>
                    <?php if (!empty($registration['cohort_name'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Cohort</div>
                        <div class="detail-value"><?php echo htmlspecialchars($registration['cohort_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Cohort Dates</div>
                        <div class="detail-value">
                            <?php echo date('M j', strtotime($registration['cohort_start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($registration['cohort_end_date'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Personal Information -->
            <div class="detail-section">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($registration['name'] . ' ' . $registration['surname']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($registration['email']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value"><?php echo htmlspecialchars($registration['phone']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Date of Birth</div>
                        <div class="detail-value"><?php echo date('F j, Y', strtotime($registration['date_of_birth'])); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Workshop Preferences -->
            <div class="detail-section">
                <h3><i class="fas fa-laptop-code"></i> Workshop Preferences</h3>
                <?php if (!empty($selectedWorkshops)): ?>
                <ul class="workshop-list">
                    <?php foreach ($selectedWorkshops as $index => $workshop): ?>
                    <li class="workshop-item">
                        <div class="preference-badge <?php echo $index > 0 ? 'second' : ''; ?>">
                            <?php echo $index === 0 ? '1st Choice' : '2nd Choice'; ?>
                        </div>
                        <div class="workshop-details">
                            <h4 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($workshop['title']); ?></h4>
                            <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                <?php echo htmlspecialchars($workshop['description']); ?>
                            </p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p>No workshop preferences selected.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="alert">
            <i class="fas fa-info-circle alert-icon"></i>
            <strong>Important:</strong> Your registration is currently pending review. You will receive an email confirmation within 24-48 hours with further instructions and program details.
        </div>
        
        <div class="next-steps">
            <h3><i class="fas fa-route"></i> What Happens Next?</h3>
            <ul>
                <li><strong>Review Process:</strong> Our team will review your registration and workshop preferences</li>
                <li><strong>Workshop Assignment:</strong> You'll be assigned to workshops based on availability and prerequisites</li>
                <li><strong>Email Confirmation:</strong> You'll receive detailed program information via email</li>
                <li><strong>Preparation Materials:</strong> Any required materials or preparation instructions will be provided</li>
                <li><strong>Program Start:</strong> Arrive on time for your first session as specified in your cohort schedule</li>
            </ul>
        </div>
        
        <div class="action-buttons">
            <a href="holiday-dashboard.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
            <a href="holidayProgramRegistration.php" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Register for Another Program
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Confirmation
            </button>
        </div>
    </div>
    
    <script>
        // Auto-save confirmation code to localStorage for reference
        localStorage.setItem('last_confirmation_code', '<?php echo htmlspecialchars($confirmationCode); ?>');
        
        // Show success message
        console.log('Registration successful! Confirmation code: <?php echo htmlspecialchars($confirmationCode); ?>');
    </script>
</body>
</html>