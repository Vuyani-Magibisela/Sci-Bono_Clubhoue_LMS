<?php
/**
 * Registration Confirmation View
 *
 * Displays confirmation after successful registration
 * Data passed from ProgramController@registrationConfirmation():
 * - $program: Program details
 * - $email: Registered email address
 * - $verificationToken: Email verification token
 *
 * Phase 3 Week 3: Updated to use ProgramController
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmed - Sci-Bono Clubhouse</title>
    <link rel="stylesheet" href="/Sci-Bono_Clubhoue_LMS/public/assets/css/holidayProgramStyles.css">
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

        .success-header p {
            margin: 0;
            font-size: 1.2rem;
            opacity: 0.95;
        }

        .info-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .info-section h2 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5rem;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }

        .info-item {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .info-item i {
            color: #28a745;
            margin-right: 15px;
            margin-top: 3px;
            min-width: 20px;
        }

        .info-item-content {
            flex: 1;
        }

        .info-item-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 3px;
        }

        .info-item-value {
            color: #6c757d;
        }

        .next-steps {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }

        .next-steps h3 {
            margin-top: 0;
            color: #856404;
        }

        .next-steps ol {
            margin: 15px 0 0 20px;
            color: #856404;
        }

        .next-steps li {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-primary {
            background: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: white;
            color: #28a745;
            border: 2px solid #28a745;
        }

        .btn-outline:hover {
            background: #28a745;
            color: white;
        }

        .verification-notice {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .verification-notice i {
            color: #0c5460;
            margin-right: 10px;
        }

        .verification-notice p {
            margin: 0;
            color: #0c5460;
        }

        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px;
                padding: 20px;
            }

            .success-header h1 {
                font-size: 1.8rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <!-- Success Header -->
        <div class="success-header">
            <i class="fas fa-check-circle"></i>
            <h1>Registration Successful!</h1>
            <p>You're all set for <?php echo htmlspecialchars($program['title']); ?></p>
        </div>

        <!-- Verification Notice -->
        <?php if (!empty($verificationToken)): ?>
        <div class="verification-notice">
            <i class="fas fa-envelope"></i>
            <p><strong>Email Verification Required:</strong> We've sent a verification email to <strong><?php echo htmlspecialchars($email); ?></strong>. Please check your inbox and click the verification link to complete your registration.</p>
        </div>
        <?php endif; ?>

        <!-- Program Information -->
        <div class="info-section">
            <h2><i class="fas fa-calendar-check"></i> Program Details</h2>
            <div class="info-item">
                <i class="fas fa-bookmark"></i>
                <div class="info-item-content">
                    <div class="info-item-label">Program</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($program['term']); ?>: <?php echo htmlspecialchars($program['title']); ?></div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-calendar-alt"></i>
                <div class="info-item-content">
                    <div class="info-item-label">Dates</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($program['dates'] ?? ($program['start_date'] . ' - ' . $program['end_date'])); ?></div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div class="info-item-content">
                    <div class="info-item-label">Time</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($program['time'] ?? 'TBA'); ?></div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div class="info-item-content">
                    <div class="info-item-label">Location</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($program['location'] ?? 'Sci-Bono Clubhouse'); ?></div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div class="info-item-content">
                    <div class="info-item-label">Your Email</div>
                    <div class="info-item-value"><?php echo htmlspecialchars($email); ?></div>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="next-steps">
            <h3><i class="fas fa-list-check"></i> What Happens Next?</h3>
            <ol>
                <li><strong>Verify Your Email:</strong> Check your inbox for a verification email and click the link to activate your account.</li>
                <li><strong>Create Your Password:</strong> After verification, you'll be able to create a password for your account.</li>
                <li><strong>Check Your Confirmation Email:</strong> You'll receive an email with all program details and important information.</li>
                <li><strong>Login to Your Dashboard:</strong> Once verified, login to view your program details, workshop selections, and important updates.</li>
                <li><strong>Prepare for the Program:</strong> Review any pre-program materials that may be sent to you via email.</li>
            </ol>
        </div>

        <!-- Contact Information -->
        <div class="info-section">
            <h2><i class="fas fa-question-circle"></i> Need Help?</h2>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <div class="info-item-content">
                    <div class="info-item-label">Phone</div>
                    <div class="info-item-value">+27 11 639 8400</div>
                </div>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div class="info-item-content">
                    <div class="info-item-label">Email</div>
                    <div class="info-item-value">info@sci-bono.co.za</div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/Sci-Bono_Clubhoue_LMS/programs" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Programs
            </a>
            <a href="/Sci-Bono_Clubhoue_LMS/holiday-login" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login to Dashboard
            </a>
        </div>

        <!-- Important Notice -->
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 6px; text-align: center; color: #6c757d;">
            <p style="margin: 0;"><i class="fas fa-info-circle"></i> Please save this page or take a screenshot for your records. You can also check your email for the confirmation message.</p>
        </div>
    </div>
</body>
</html>
