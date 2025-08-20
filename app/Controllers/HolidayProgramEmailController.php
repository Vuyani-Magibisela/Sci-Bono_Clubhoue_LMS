<?php
require_once __DIR__ . '/../Models/HolidayProgramProfileModel.php';

class HolidayProgramEmailController {
    private $conn;
    private $profileModel;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->profileModel = new HolidayProgramProfileModel($conn);
    }
    
    /**
     * Generate and send profile access email
     */
    public function sendProfileAccessEmail($attendeeId) {
        $attendee = $this->profileModel->getAttendeeProfile($attendeeId);
        
        if (!$attendee) {
            return [
                'success' => false,
                'message' => 'Attendee not found'
            ];
        }
        
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store token in database
        $this->storeAccessToken($attendeeId, $token, $expiry);
        
        // Generate email content
        $emailData = $this->generateEmailContent($attendee, $token);
        
        // In a real implementation, send the email here
        // For now, return the email data for testing
        return [
            'success' => true,
            'message' => 'Profile access email generated successfully',
            'email_data' => $emailData
        ];
    }
    
    /**
     * Store access token in database
     */
    private function storeAccessToken($attendeeId, $token, $expiry) {
        $sql = "INSERT INTO holiday_program_access_tokens (attendee_id, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $attendeeId, $token, $expiry);
        $stmt->execute();
    }
    
    /**
     * Generate email content
     */
    private function generateEmailContent($attendee, $token) {
        $baseUrl = $this->getBaseUrl();
        
        if (empty($attendee['password'])) {
            $profileLink = $baseUrl . "holiday-profile-verify-email.php?token=" . $token;
            $action = "set up your profile and create your password";
        } else {
            $profileLink = $baseUrl . "holidayProgramLogin.php?email=" . urlencode($attendee['email']);
            $action = "access your profile";
        }
        
        $subject = "Access Your Holiday Program Profile - " . $attendee['program_title'];
        
        $emailBody = $this->generateEmailTemplate($attendee, $profileLink, $action);
        
        return [
            'to' => $attendee['email'],
            'subject' => $subject,
            'body' => $emailBody,
            'profile_link' => $profileLink
        ];
    }
    
    /**
     * Generate email HTML template
     */
    private function generateEmailTemplate($attendee, $profileLink, $action) {
        return "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 10px; margin: 20px 0; }
                .button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 0.9rem; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Holiday Program Profile Access</h1>
                    <p>Welcome to " . htmlspecialchars($attendee['program_title']) . "</p>
                </div>
                
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($attendee['first_name']) . "!</h2>
                    
                    <p>We're excited to have you participate in our <strong>" . htmlspecialchars($attendee['program_title']) . "</strong> program.</p>
                    
                    <p>To " . $action . ", please click the button below:</p>
                    
                    <div style='text-align: center;'>
                        <a href='" . $profileLink . "' class='button'>Access My Profile</a>
                    </div>
                    
                    <p><strong>What you can do with your profile:</strong></p>
                    <ul>
                        <li>Update your personal information</li>
                        <li>Manage emergency contacts</li>
                        <li>Update medical and dietary information</li>
                        <li>View program details and schedule</li>
                        <li>Access your personalized dashboard</li>
                    </ul>
                    
                    <p><strong>Program Details:</strong><br>
                    " . htmlspecialchars($attendee['program_title']) . "<br>
                    " . htmlspecialchars($attendee['program_dates']) . "</p>
                    
                    <p>If you have any questions or need assistance, please contact our support team.</p>
                </div>
                
                <div class='footer'>
                    <p>This email was sent to " . htmlspecialchars($attendee['email']) . "</p>
                    <p>If you did not register for this program, please ignore this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get base URL for links
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['REQUEST_URI'] ?? '') . '/';
        
        return $protocol . $host . $path;
    }
}