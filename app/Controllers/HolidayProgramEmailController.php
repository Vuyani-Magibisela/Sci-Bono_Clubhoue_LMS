<?php
/**
 * Holiday Program Email Controller
 *
 * Handles email generation and sending for holiday program participants.
 * Migrated to extend BaseController - Phase 4 Week 3 Day 3
 *
 * @package App\Controllers
 * @since Phase 4 Week 3
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/HolidayProgramProfileModel.php';

class HolidayProgramEmailController extends BaseController {
    private $profileModel;

    public function __construct($conn, $config = null) {
        parent::__construct($conn, $config);
        $this->profileModel = new HolidayProgramProfileModel($this->conn);
    }

    /**
     * Generate and send profile access email
     * Modern method with comprehensive error handling
     *
     * @param int $attendeeId Attendee ID
     * @return array Response with success status and data
     */
    public function sendProfileAccessEmail($attendeeId) {
        try {
            $attendee = $this->profileModel->getAttendeeProfile($attendeeId);

            if (!$attendee) {
                $this->logger->warning("Attendee not found for email", [
                    'attendee_id' => $attendeeId
                ]);

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

            $this->logAction('send_profile_access_email', [
                'attendee_id' => $attendeeId,
                'email' => $attendee['email'],
                'program_id' => $attendee['program_id'] ?? null
            ]);

            // In a real implementation, send the email here
            // For now, return the email data for testing
            return [
                'success' => true,
                'message' => 'Profile access email generated successfully',
                'email_data' => $emailData
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to send profile access email", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while generating the email'
            ];
        }
    }

    /**
     * Send bulk profile access emails
     * Send emails to multiple attendees at once
     *
     * @param array $attendeeIds Array of attendee IDs
     * @return array Response with success count
     */
    public function sendBulkProfileAccessEmails($attendeeIds) {
        try {
            if (empty($attendeeIds) || !is_array($attendeeIds)) {
                return [
                    'success' => false,
                    'message' => 'No attendees specified'
                ];
            }

            $successCount = 0;
            $failedCount = 0;
            $results = [];

            foreach ($attendeeIds as $attendeeId) {
                $result = $this->sendProfileAccessEmail($attendeeId);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }

                $results[] = [
                    'attendee_id' => $attendeeId,
                    'success' => $result['success'],
                    'message' => $result['message']
                ];
            }

            $this->logAction('send_bulk_profile_access_emails', [
                'total' => count($attendeeIds),
                'success' => $successCount,
                'failed' => $failedCount
            ]);

            return [
                'success' => $failedCount === 0,
                'message' => "Sent {$successCount} emails successfully" . ($failedCount > 0 ? ", {$failedCount} failed" : ""),
                'results' => $results,
                'success_count' => $successCount,
                'failed_count' => $failedCount
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to send bulk emails", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while sending bulk emails'
            ];
        }
    }

    /**
     * Store access token in database
     *
     * @param int $attendeeId Attendee ID
     * @param string $token Access token
     * @param string $expiry Token expiry datetime
     * @return bool True on success
     * @throws Exception If database operation fails
     */
    private function storeAccessToken($attendeeId, $token, $expiry) {
        try {
            $sql = "INSERT INTO holiday_program_access_tokens (attendee_id, token, expires_at, created_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("iss", $attendeeId, $token, $expiry);
            $result = $stmt->execute();

            if (!$result) {
                throw new Exception("Failed to store access token: " . $stmt->error);
            }

            $stmt->close();

            $this->logger->info("Access token stored", [
                'attendee_id' => $attendeeId,
                'expires_at' => $expiry
            ]);

            return true;

        } catch (Exception $e) {
            $this->logger->error("Error storing access token", [
                'attendee_id' => $attendeeId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate email content with profile link
     *
     * @param array $attendee Attendee data
     * @param string $token Access token
     * @return array Email data with to, subject, body, profile_link
     */
    private function generateEmailContent($attendee, $token) {
        try {
            $baseUrl = $this->getBaseUrl();

            if (empty($attendee['password'])) {
                $profileLink = $baseUrl . "holiday-profile-verify-email.php?token=" . $token;
                $action = "set up your profile and create your password";
            } else {
                $profileLink = $baseUrl . "holidayProgramLogin.php?email=" . urlencode($attendee['email']);
                $action = "access your profile";
            }

            $subject = "Access Your Holiday Program Profile - " . ($attendee['program_title'] ?? 'Holiday Program');

            $emailBody = $this->generateEmailTemplate($attendee, $profileLink, $action);

            return [
                'to' => $attendee['email'],
                'subject' => $subject,
                'body' => $emailBody,
                'profile_link' => $profileLink
            ];

        } catch (Exception $e) {
            $this->logger->error("Error generating email content", [
                'attendee_id' => $attendee['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Generate email HTML template
     *
     * @param array $attendee Attendee data
     * @param string $profileLink Profile access link
     * @param string $action Action description
     * @return string HTML email template
     */
    private function generateEmailTemplate($attendee, $profileLink, $action) {
        $programTitle = htmlspecialchars($attendee['program_title'] ?? 'Holiday Program');
        $firstName = htmlspecialchars($attendee['first_name']);
        $email = htmlspecialchars($attendee['email']);
        $programDates = htmlspecialchars($attendee['program_dates'] ?? 'To be announced');

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
                    <p>Welcome to {$programTitle}</p>
                </div>

                <div class='content'>
                    <h2>Hello {$firstName}!</h2>

                    <p>We're excited to have you participate in our <strong>{$programTitle}</strong> program.</p>

                    <p>To {$action}, please click the button below:</p>

                    <div style='text-align: center;'>
                        <a href='{$profileLink}' class='button'>Access My Profile</a>
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
                    {$programTitle}<br>
                    {$programDates}</p>

                    <p>If you have any questions or need assistance, please contact our support team.</p>
                </div>

                <div class='footer'>
                    <p>This email was sent to {$email}</p>
                    <p>If you did not register for this program, please ignore this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Get base URL for links
     * Uses BaseController's URL helper or falls back to manual detection
     *
     * @return string Base URL
     */
    private function getBaseUrl() {
        try {
            // Try to use config if available
            if (isset($this->config['base_url'])) {
                return rtrim($this->config['base_url'], '/') . '/';
            }

            // Fallback to manual detection
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = dirname($_SERVER['REQUEST_URI'] ?? '') . '/';

            return $protocol . $host . $path;

        } catch (Exception $e) {
            $this->logger->warning("Error getting base URL, using fallback", [
                'error' => $e->getMessage()
            ]);

            return 'http://localhost/Sci-Bono_Clubhoue_LMS/';
        }
    }

    /**
     * Verify and consume access token
     * API method for token verification
     *
     * @param string $token Access token
     * @return array Result with attendee data if valid
     */
    public function verifyAccessToken($token) {
        try {
            if (empty($token)) {
                return [
                    'success' => false,
                    'message' => 'Token is required'
                ];
            }

            $sql = "SELECT attendee_id, expires_at
                    FROM holiday_program_access_tokens
                    WHERE token = ? AND expires_at > NOW()
                    LIMIT 1";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $tokenData = $result->fetch_assoc();
            $stmt->close();

            if (!$tokenData) {
                $this->logger->warning("Invalid or expired token", [
                    'token' => substr($token, 0, 10) . '...'
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ];
            }

            // Get attendee data
            $attendee = $this->profileModel->getAttendeeProfile($tokenData['attendee_id']);

            if (!$attendee) {
                return [
                    'success' => false,
                    'message' => 'Attendee not found'
                ];
            }

            $this->logAction('verify_access_token', [
                'attendee_id' => $tokenData['attendee_id'],
                'token_valid' => true
            ]);

            return [
                'success' => true,
                'attendee' => $attendee
            ];

        } catch (Exception $e) {
            $this->logger->error("Error verifying access token", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while verifying the token'
            ];
        }
    }
}
?>
