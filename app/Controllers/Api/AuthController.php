<?php
/**
 * Api\AuthController
 *
 * Handles API authentication with JWT tokens
 *
 * Phase 5 Week 1 Day 4: Full Implementation
 *
 * @package App\Controllers\Api
 * @since Phase 5
 */

namespace App\Controllers\Api;

require_once __DIR__ . '/../../API/BaseApiController.php';
require_once __DIR__ . '/../../Services/ApiTokenService.php';

use App\API\BaseApiController;
use App\Services\ApiTokenService;

class AuthController extends BaseApiController
{
    /**
     * Login endpoint
     *
     * POST /api/v1/auth/login
     * Request: { "email": "user@example.com", "password": "password123" }
     * Response: { "access_token": "...", "refresh_token": "...", "user": {...} }
     */
    public function login()
    {
        try {
            // Validate required fields
            $email = $this->requestData['email'] ?? null;
            $password = $this->requestData['password'] ?? null;

            if (!$email || !$password) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email and password are required',
                    'errors' => [
                        'email' => !$email ? 'Email is required' : null,
                        'password' => !$password ? 'Password is required' : null
                    ]
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Find user by email
            $stmt = $this->db->prepare("SELECT id, email, password, name, surname, user_type, active FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            $user = $result->fetch_assoc();

            // Check if user is active
            if (!$user['active']) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Account is deactivated'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Set database connection for ApiTokenService
            ApiTokenService::setConnection($this->db);

            // Generate access and refresh tokens
            $accessToken = ApiTokenService::generate($user['id'], $user['user_type']);
            $refreshToken = ApiTokenService::generateRefreshToken($user['id'], $user['user_type']);

            // Remove sensitive data
            unset($user['password']);

            // Log successful login
            $this->logAction('api_login', ['user_id' => $user['id'], 'email' => $user['email']]);

            // Update last login timestamp
            $updateStmt = $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $updateStmt->bind_param('i', $user['id']);
            $updateStmt->execute();

            // Return tokens and user data
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => ApiTokenService::getExpiration(),
                    'user' => $user
                ]
            ], JSON_PRETTY_PRINT);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred during login',
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }

    /**
     * Logout endpoint
     *
     * POST /api/v1/auth/logout
     * Headers: Authorization: Bearer {token}
     * Response: { "success": true, "message": "Logged out successfully" }
     */
    public function logout()
    {
        try {
            // Get token from Authorization header
            $token = $this->getBearerToken();

            if (!$token) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Authorization token required'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Set database connection for ApiTokenService
            ApiTokenService::setConnection($this->db);

            // Validate token and get user
            $payload = ApiTokenService::validate($token);

            if (!$payload) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid or expired token'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Blacklist the token
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $success = ApiTokenService::blacklistToken($token, $payload['user_id'], 'logout', $ipAddress, $userAgent);

            if (!$success) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to logout'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Log logout action
            $this->logAction('api_logout', ['user_id' => $payload['user_id']]);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully'
            ], JSON_PRETTY_PRINT);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred during logout',
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }

    /**
     * Refresh token endpoint with token rotation
     *
     * POST /api/v1/auth/refresh
     * Request: { "refresh_token": "..." }
     * Response: { "access_token": "...", "refresh_token": "...", "expires_in": 3600 }
     *
     * Implements refresh token rotation for enhanced security:
     * - Old refresh token is blacklisted
     * - New refresh token is generated
     * - Token reuse detection prevents theft
     */
    public function refresh()
    {
        try {
            // Get refresh token from request
            $refreshToken = $this->requestData['refresh_token'] ?? null;

            if (!$refreshToken) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Refresh token is required'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Set database connection for ApiTokenService
            ApiTokenService::setConnection($this->db);

            // Validate and refresh token (with rotation)
            // Returns: ['access_token' => string, 'refresh_token' => string] or false
            $tokens = ApiTokenService::refresh($refreshToken);

            if (!$tokens) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid or expired refresh token. Token reuse detected or token blacklisted.'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Log token refresh
            $payload = ApiTokenService::validate($tokens['access_token']);
            if ($payload) {
                $this->logAction('api_token_refresh', [
                    'user_id' => $payload['user_id'],
                    'rotation' => true
                ]);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'token_type' => 'Bearer',
                    'expires_in' => ApiTokenService::getExpiration()
                ]
            ], JSON_PRETTY_PRINT);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred during token refresh',
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }

    /**
     * Forgot password endpoint
     *
     * POST /api/v1/auth/forgot-password
     * Request: { "email": "user@example.com" }
     * Response: { "success": true, "message": "Password reset instructions sent to your email" }
     */
    public function forgotPassword()
    {
        try {
            // Validate email
            $email = $this->requestData['email'] ?? null;

            if (!$email) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email is required',
                    'errors' => ['email' => 'Email is required']
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email format',
                    'errors' => ['email' => 'Invalid email format']
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Find user by email
            $stmt = $this->db->prepare("SELECT id, email, name, surname FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // Always return success to prevent email enumeration attacks
            if ($result->num_rows === 0) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'If an account exists with this email, password reset instructions have been sent'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            $user = $result->fetch_assoc();

            // Set database connection for ApiTokenService
            ApiTokenService::setConnection($this->db);

            // Generate password reset token (30-minute expiry)
            $resetToken = ApiTokenService::generatePasswordResetToken($user['id'], $user['email']);
            $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 minutes

            // Store token in database
            $updateStmt = $this->db->prepare("
                UPDATE users
                SET password_reset_token = ?,
                    password_reset_expires = ?
                WHERE id = ?
            ");
            $updateStmt->bind_param('ssi', $resetToken, $expiresAt, $user['id']);
            $updateStmt->execute();

            // Log password reset request
            $this->logAction('password_reset_requested', ['user_id' => $user['id'], 'email' => $user['email']]);

            // TODO: Send email with reset link
            // For now, we'll return the token in the response (DEVELOPMENT ONLY)
            // In production, this should be sent via email
            $resetLink = $_SERVER['HTTP_HOST'] . "/reset-password?token=" . urlencode($resetToken);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'If an account exists with this email, password reset instructions have been sent',
                // DEVELOPMENT ONLY - Remove in production
                'dev_only' => [
                    'reset_token' => $resetToken,
                    'reset_link' => $resetLink,
                    'expires_at' => $expiresAt
                ]
            ], JSON_PRETTY_PRINT);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred processing your request',
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }

    /**
     * Reset password endpoint
     *
     * POST /api/v1/auth/reset-password
     * Request: { "token": "...", "password": "...", "password_confirmation": "..." }
     * Response: { "success": true, "message": "Password reset successfully" }
     */
    public function resetPassword()
    {
        try {
            // Validate required fields
            $token = $this->requestData['token'] ?? null;
            $password = $this->requestData['password'] ?? null;
            $passwordConfirmation = $this->requestData['password_confirmation'] ?? null;

            if (!$token || !$password || !$passwordConfirmation) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'All fields are required',
                    'errors' => [
                        'token' => !$token ? 'Reset token is required' : null,
                        'password' => !$password ? 'Password is required' : null,
                        'password_confirmation' => !$passwordConfirmation ? 'Password confirmation is required' : null
                    ]
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Validate password match
            if ($password !== $passwordConfirmation) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Passwords do not match',
                    'errors' => ['password_confirmation' => 'Passwords do not match']
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Validate password strength (min 8 characters)
            if (strlen($password) < 8) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Password must be at least 8 characters',
                    'errors' => ['password' => 'Password must be at least 8 characters']
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Set database connection for ApiTokenService
            ApiTokenService::setConnection($this->db);

            // Validate reset token
            $tokenPayload = ApiTokenService::validate($token);

            if (!$tokenPayload) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Verify token type is password_reset
            if (($tokenPayload['token_type'] ?? '') !== 'password_reset') {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid reset token'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            $userId = $tokenPayload['user_id'];
            $email = $tokenPayload['email'];

            // Verify token matches database record
            $stmt = $this->db->prepare("
                SELECT id, password_reset_token, password_reset_expires
                FROM users
                WHERE id = ? AND email = ?
                LIMIT 1
            ");
            $stmt->bind_param('is', $userId, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid reset token'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            $user = $result->fetch_assoc();

            // Verify token matches database
            if ($user['password_reset_token'] !== $token) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid reset token'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Check if token has expired
            if ($user['password_reset_expires'] && strtotime($user['password_reset_expires']) < time()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Reset token has expired'
                ], JSON_PRETTY_PRINT);
                exit;
            }

            // Hash new password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Update password and clear reset token
            $updateStmt = $this->db->prepare("
                UPDATE users
                SET password = ?,
                    password_reset_token = NULL,
                    password_reset_expires = NULL
                WHERE id = ?
            ");
            $updateStmt->bind_param('si', $hashedPassword, $userId);
            $updateStmt->execute();

            // Log password reset
            $this->logAction('password_reset_completed', ['user_id' => $userId, 'email' => $email]);

            // Blacklist all existing tokens for this user (force re-login)
            // This is a security measure to invalidate all sessions after password change
            $blacklistStmt = $this->db->prepare("
                INSERT INTO token_blacklist (token_jti, user_id, expires_at, reason)
                SELECT token_jti, ?, DATE_ADD(NOW(), INTERVAL 1 DAY), 'password_reset'
                FROM (SELECT CONCAT('user_', ?, '_all_tokens') as token_jti) as dummy
            ");
            $blacklistStmt->bind_param('ii', $userId, $userId);
            $blacklistStmt->execute();

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Password reset successfully. Please login with your new password.'
            ], JSON_PRETTY_PRINT);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred resetting your password',
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
            exit;
        }
    }

    /**
     * Get Bearer token from Authorization header
     *
     * @return string|null
     */
    private function getBearerToken()
    {
        $authHeader = $this->headers['Authorization'] ?? $this->headers['authorization'] ?? null;

        if (!$authHeader) {
            return null;
        }

        if (strpos($authHeader, 'Bearer ') === 0) {
            return substr($authHeader, 7);
        }

        return null;
    }
}
