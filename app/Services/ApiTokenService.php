<?php

namespace App\Services;

use App\Utils\Logger;
use Exception;

class ApiTokenService
{
    private static $algorithm = 'HS256';
    private static $expiration = 3600; // 1 hour in seconds
    private static $refreshExpiration = 86400; // 24 hours in seconds
    private static $conn = null; // Database connection for blacklist operations

    /**
     * Set database connection for blacklist operations
     *
     * @param mixed $conn Database connection (MySQLi)
     */
    public static function setConnection($conn)
    {
        self::$conn = $conn;
    }

    /**
     * Get secret key for token signing
     */
    private static function getSecretKey()
    {
        // Try to get from environment first
        $secret = getenv('APP_SECRET_KEY');
        
        if (!$secret) {
            // Fallback to config or default (not recommended for production)
            $secret = defined('APP_SECRET_KEY') ? APP_SECRET_KEY : 'your-default-secret-key';
        }
        
        if (strlen($secret) < 32) {
            throw new Exception('Secret key must be at least 32 characters long');
        }
        
        return $secret;
    }
    
    /**
     * Generate JWT token for user
     */
    public static function generate($userId, $userRole = 'user', $expires = null)
    {
        try {
            $secretKey = self::getSecretKey();
            $issuedAt = time();
            $expire = $expires ?? ($issuedAt + self::$expiration);
            
            // Create header
            $header = [
                'typ' => 'JWT',
                'alg' => self::$algorithm
            ];
            
            // Create payload
            $payload = [
                'iss' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
                'aud' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
                'iat' => $issuedAt,
                'exp' => $expire,
                'jti' => bin2hex(random_bytes(16)), // Unique token ID for blacklist tracking
                'user_id' => (int)$userId,
                'role' => $userRole,
                'token_type' => 'access'
            ];
            
            // Encode header and payload
            $headerEncoded = self::base64UrlEncode(json_encode($header));
            $payloadEncoded = self::base64UrlEncode(json_encode($payload));
            
            // Create signature
            $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secretKey, true);
            $signatureEncoded = self::base64UrlEncode($signature);
            
            // Create token
            $token = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
return $token;
            
        } catch (Exception $e) {
throw new Exception('Failed to generate authentication token');
        }
    }
    
    /**
     * Generate refresh token with optional token family tracking
     *
     * @param int $userId User ID
     * @param string $userRole User role
     * @param string|null $familyId Token family ID (for rotation tracking)
     * @param string|null $parentJti Parent token JTI (for rotation tracking)
     * @return string Refresh token
     */
    public static function generateRefreshToken($userId, $userRole = 'user', $familyId = null, $parentJti = null)
    {
        $expire = time() + self::$refreshExpiration;

        // Generate new family ID if not provided
        if ($familyId === null) {
            $familyId = bin2hex(random_bytes(16));
        }

        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
            'iat' => time(),
            'exp' => $expire,
            'jti' => bin2hex(random_bytes(16)), // Unique token ID for blacklist tracking
            'user_id' => (int)$userId,
            'role' => $userRole,
            'token_type' => 'refresh',
            'family_id' => $familyId // Token family for rotation tracking
        ];

        $token = self::generateCustomToken($payload);

        // Store token family relationship
        self::storeTokenFamily($payload['jti'], $userId, $familyId, $parentJti);

        return $token;
    }
    
    /**
     * Validate and decode JWT token
     */
    public static function validate($token)
    {
        try {
            if (empty($token)) {
                return false;
            }
            
            // Split token into parts
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
            
            // Decode header and payload
            $header = json_decode(self::base64UrlDecode($headerEncoded), true);
            $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
            
            if (!$header || !$payload) {
                return false;
            }
            
            // Check algorithm
            if ($header['alg'] !== self::$algorithm) {
return false;
            }
            
            // Verify signature
            $secretKey = self::getSecretKey();
            $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secretKey, true);
            $expectedSignatureEncoded = self::base64UrlEncode($expectedSignature);
            
            if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
return false;
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
return false;
            }
            
            // Check required fields
            if (!isset($payload['user_id'])) {
                return false;
            }

            // Check if token is blacklisted (if database connection available)
            if (isset($payload['jti']) && self::isBlacklisted($payload['jti'])) {
                return false;
            }

            return $payload;
            
        } catch (Exception $e) {
return false;
        }
    }
    
    /**
     * Refresh access token using refresh token with rotation
     *
     * Implements refresh token rotation for enhanced security:
     * 1. Validates the refresh token
     * 2. Generates new access token
     * 3. Generates NEW refresh token (rotation)
     * 4. Blacklists old refresh token
     * 5. Detects token reuse (theft)
     *
     * @param string $refreshToken Current refresh token
     * @return array|false Array with ['access_token' => string, 'refresh_token' => string] or false
     */
    public static function refresh($refreshToken)
    {
        try {
            $payload = self::validate($refreshToken);

            if (!$payload) {
                return false;
            }

            // Check if it's a refresh token
            if (($payload['token_type'] ?? 'access') !== 'refresh') {
                return false;
            }

            // Check for token reuse (potential theft)
            if (self::detectTokenReuse($payload['jti'])) {
                // Token reuse detected - blacklist entire family
                self::blacklistTokenFamily($payload['family_id'] ?? null, $payload['user_id']);
                return false;
            }

            // Generate new access token
            $accessToken = self::generate($payload['user_id'], $payload['role']);

            // Generate NEW refresh token (rotation) - keep same family, set parent JTI
            $newRefreshToken = self::generateRefreshToken(
                $payload['user_id'],
                $payload['role'],
                $payload['family_id'] ?? null,
                $payload['jti'] // Old token JTI becomes parent
            );

            // Blacklist old refresh token (mark as used)
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            self::blacklistToken($refreshToken, $payload['user_id'], 'refresh_rotation', $ipAddress, $userAgent);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken
            ];

        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate custom token with specific payload
     */
    private static function generateCustomToken($payload)
    {
        try {
            $secretKey = self::getSecretKey();
            
            // Create header
            $header = [
                'typ' => 'JWT',
                'alg' => self::$algorithm
            ];
            
            // Encode header and payload
            $headerEncoded = self::base64UrlEncode(json_encode($header));
            $payloadEncoded = self::base64UrlEncode(json_encode($payload));
            
            // Create signature
            $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secretKey, true);
            $signatureEncoded = self::base64UrlEncode($signature);
            
            // Create token
            return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
            
        } catch (Exception $e) {
throw new Exception('Failed to generate token');
        }
    }
    
    /**
     * Get user information from token
     */
    public static function getUserFromToken($token)
    {
        $payload = self::validate($token);
        return $payload ? $payload['user_id'] : null;
    }
    
    /**
     * Get user role from token
     */
    public static function getRoleFromToken($token)
    {
        $payload = self::validate($token);
        return $payload ? ($payload['role'] ?? 'user') : null;
    }
    
    /**
     * Check if token is expired
     */
    public static function isExpired($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return true;
            }
            
            $payload = json_decode(self::base64UrlDecode($parts[1]), true);
            if (!$payload || !isset($payload['exp'])) {
                return true;
            }
            
            return $payload['exp'] < time();
            
        } catch (Exception $e) {
            return true;
        }
    }
    
    /**
     * Get token expiration time
     */
    public static function getTokenExpiration($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            $payload = json_decode(self::base64UrlDecode($parts[1]), true);
            return $payload['exp'] ?? null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Generate password reset token
     */
    public static function generatePasswordResetToken($userId, $email)
    {
        $expire = time() + 1800; // 30 minutes
        
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
            'iat' => time(),
            'exp' => $expire,
            'user_id' => (int)$userId,
            'email' => $email,
            'token_type' => 'password_reset'
        ];
        
        return self::generateCustomToken($payload);
    }
    
    /**
     * Generate email verification token
     */
    public static function generateEmailVerificationToken($userId, $email)
    {
        $expire = time() + 86400; // 24 hours
        
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
            'iat' => time(),
            'exp' => $expire,
            'user_id' => (int)$userId,
            'email' => $email,
            'token_type' => 'email_verification'
        ];
        
        return self::generateCustomToken($payload);
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Set token expiration time (in seconds)
     */
    public static function setExpiration($seconds)
    {
        self::$expiration = $seconds;
    }
    
    /**
     * Get current expiration time
     */
    public static function getExpiration()
    {
        return self::$expiration;
    }

    /**
     * Extract JTI (JWT ID) from token
     *
     * @param string $token JWT token
     * @return string|null Token JTI or null if not found
     */
    public static function getTokenJti($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(self::base64UrlDecode($parts[1]), true);
            return $payload['jti'] ?? null;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if token is blacklisted
     *
     * @param string $tokenJti Token JTI to check
     * @return bool True if blacklisted, false otherwise
     */
    public static function isBlacklisted($tokenJti)
    {
        // If no database connection, cannot check blacklist
        if (self::$conn === null) {
            return false;
        }

        try {
            $stmt = self::$conn->prepare("SELECT id FROM token_blacklist WHERE token_jti = ? LIMIT 1");
            $stmt->bind_param('s', $tokenJti);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result->num_rows > 0;

        } catch (Exception $e) {
            // Fail open: if blacklist check fails, allow token (logged for security review)
            return false;
        }
    }

    /**
     * Blacklist a token (prevents reuse)
     *
     * @param string $token JWT token to blacklist
     * @param int $userId User ID who owned the token
     * @param string $reason Reason for blacklisting (logout, password_change, etc)
     * @param string|null $ipAddress IP address when blacklisted
     * @param string|null $userAgent User agent when blacklisted
     * @return bool True on success, false on failure
     */
    public static function blacklistToken($token, $userId, $reason = 'logout', $ipAddress = null, $userAgent = null)
    {
        // If no database connection, cannot blacklist
        if (self::$conn === null) {
            return false;
        }

        try {
            // Extract JTI and expiration from token
            $jti = self::getTokenJti($token);
            $expiresAt = self::getTokenExpiration($token);

            if (!$jti || !$expiresAt) {
                return false;
            }

            // Convert timestamp to datetime
            $expiresAtDate = date('Y-m-d H:i:s', $expiresAt);

            // Insert into blacklist table
            $stmt = self::$conn->prepare("
                INSERT INTO token_blacklist
                (token_jti, user_id, expires_at, reason, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE blacklisted_at = CURRENT_TIMESTAMP
            ");

            $stmt->bind_param('sissss', $jti, $userId, $expiresAtDate, $reason, $ipAddress, $userAgent);
            $success = $stmt->execute();

            return $success;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clean up expired tokens from blacklist table
     *
     * @return int Number of tokens removed
     */
    public static function cleanupExpiredTokens()
    {
        // If no database connection, cannot cleanup
        if (self::$conn === null) {
            return 0;
        }

        try {
            $stmt = self::$conn->prepare("DELETE FROM token_blacklist WHERE expires_at < NOW()");
            $stmt->execute();
            $affectedRows = $stmt->affected_rows;

            return $affectedRows;

        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Rotate token (generate new token and blacklist old one)
     *
     * @param string $oldToken Current token to rotate
     * @param int $userId User ID
     * @param string $userRole User role
     * @param string $reason Reason for rotation (password_change, security_refresh, etc)
     * @return string|false New token on success, false on failure
     */
    public static function rotateToken($oldToken, $userId, $userRole = 'user', $reason = 'security_refresh')
    {
        try {
            // Blacklist old token
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            self::blacklistToken($oldToken, $userId, $reason, $ipAddress, $userAgent);

            // Generate new token
            $newToken = self::generate($userId, $userRole);

            return $newToken;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Generate device fingerprint for token security
     *
     * Combines User-Agent and IP address to create a unique fingerprint.
     * This can be stored with the token and validated on each request.
     *
     * @param array $request Request data (should include 'user_agent' and 'ip_address')
     * @return string Device fingerprint hash
     */
    public static function generateFingerprint($request = null)
    {
        // Get from request array or fallback to $_SERVER
        $userAgent = $request['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $ipAddress = $request['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        // Create fingerprint: hash of User-Agent + IP
        $fingerprint = hash('sha256', $userAgent . '|' . $ipAddress);

        return $fingerprint;
    }

    /**
     * Validate device fingerprint
     *
     * @param string $storedFingerprint Previously generated fingerprint
     * @param array $request Current request data
     * @return bool True if fingerprint matches, false otherwise
     */
    public static function validateFingerprint($storedFingerprint, $request = null)
    {
        $currentFingerprint = self::generateFingerprint($request);
        return hash_equals($storedFingerprint, $currentFingerprint);
    }

    /**
     * Store token family relationship for rotation tracking
     *
     * Creates or updates token family records in the database.
     * Token families allow tracking of token lineage to detect theft/reuse.
     *
     * @param string $jti Current token JTI (unique identifier)
     * @param int $userId User ID who owns the token
     * @param string $familyId Family ID (shared across rotated tokens)
     * @param string|null $parentJti Parent token JTI (null for first token in family)
     * @return bool True on success, false on failure
     */
    public static function storeTokenFamily($jti, $userId, $familyId, $parentJti = null)
    {
        // If no database connection, cannot store family
        if (self::$conn === null) {
            return false;
        }

        try {
            // Create token_families table if it doesn't exist
            self::createTokenFamiliesTable();

            // Insert token family record
            $stmt = self::$conn->prepare("
                INSERT INTO token_families
                (jti, user_id, family_id, parent_jti, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param('siss', $jti, $userId, $familyId, $parentJti);
            $success = $stmt->execute();

            return $success;

        } catch (Exception $e) {
            error_log("ApiTokenService::storeTokenFamily() error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Detect token reuse (potential theft)
     *
     * Checks if the token JTI is already blacklisted. If a blacklisted token
     * is used again, it indicates potential token theft.
     *
     * @param string $jti Token JTI to check
     * @return bool True if reuse detected, false otherwise
     */
    public static function detectTokenReuse($jti)
    {
        // Token reuse is detected by checking if it's already blacklisted
        // If a token is blacklisted (from previous rotation) and is being
        // used again, it means someone is reusing an old token
        return self::isBlacklisted($jti);
    }

    /**
     * Blacklist entire token family (on theft detection)
     *
     * When token reuse is detected, blacklist all tokens in the same family
     * to prevent the attacker from using any tokens in the chain.
     *
     * @param string|null $familyId Token family ID
     * @param int $userId User ID (for logging)
     * @return bool True on success, false on failure
     */
    public static function blacklistTokenFamily($familyId, $userId)
    {
        // If no database connection or no family ID, cannot blacklist family
        if (self::$conn === null || $familyId === null) {
            return false;
        }

        try {
            // Get all tokens in the family
            $stmt = self::$conn->prepare("
                SELECT jti FROM token_families
                WHERE family_id = ? AND user_id = ?
            ");

            $stmt->bind_param('si', $familyId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            $blacklistedCount = 0;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // Blacklist each token in the family
            while ($row = $result->fetch_assoc()) {
                $jti = $row['jti'];

                // Insert into blacklist with future expiration (1 year from now)
                $expiresAt = date('Y-m-d H:i:s', time() + 31536000); // 1 year

                $insertStmt = self::$conn->prepare("
                    INSERT INTO token_blacklist
                    (token_jti, user_id, expires_at, reason, ip_address, user_agent)
                    VALUES (?, ?, ?, 'token_theft_detected', ?, ?)
                    ON DUPLICATE KEY UPDATE blacklisted_at = CURRENT_TIMESTAMP
                ");

                $insertStmt->bind_param('sisss', $jti, $userId, $expiresAt, $ipAddress, $userAgent);
                if ($insertStmt->execute()) {
                    $blacklistedCount++;
                }
            }

            // Log the security event
            error_log("SECURITY WARNING: Token theft detected for user {$userId}. Blacklisted {$blacklistedCount} tokens in family {$familyId}");

            return $blacklistedCount > 0;

        } catch (Exception $e) {
            error_log("ApiTokenService::blacklistTokenFamily() error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create token_families table if it doesn't exist
     *
     * @return bool True on success, false on failure
     */
    private static function createTokenFamiliesTable()
    {
        if (self::$conn === null) {
            return false;
        }

        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS token_families (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    jti VARCHAR(64) NOT NULL UNIQUE,
                    user_id INT NOT NULL,
                    family_id VARCHAR(64) NOT NULL,
                    parent_jti VARCHAR(64) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_family_id (family_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_jti (jti)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";

            return self::$conn->query($sql);

        } catch (Exception $e) {
            error_log("ApiTokenService::createTokenFamiliesTable() error: " . $e->getMessage());
            return false;
        }
    }
}