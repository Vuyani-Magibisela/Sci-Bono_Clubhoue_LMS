<?php

namespace App\Services;

use App\Utils\Logger;
use Exception;

class ApiTokenService
{
    private static $algorithm = 'HS256';
    private static $expiration = 3600; // 1 hour in seconds
    private static $refreshExpiration = 86400; // 24 hours in seconds
    
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
            
            Logger::info('API token generated', [
                'user_id' => $userId,
                'role' => $userRole,
                'expires_at' => date('Y-m-d H:i:s', $expire)
            ]);
            
            return $token;
            
        } catch (Exception $e) {
            Logger::error('Failed to generate token: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            throw new Exception('Failed to generate authentication token');
        }
    }
    
    /**
     * Generate refresh token
     */
    public static function generateRefreshToken($userId, $userRole = 'user')
    {
        $expire = time() + self::$refreshExpiration;
        
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
            'aud' => $_SERVER['HTTP_HOST'] ?? 'sci-bono-lms',
            'iat' => time(),
            'exp' => $expire,
            'user_id' => (int)$userId,
            'role' => $userRole,
            'token_type' => 'refresh'
        ];
        
        return self::generateCustomToken($payload);
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
                Logger::warning('Invalid token algorithm', ['algorithm' => $header['alg']]);
                return false;
            }
            
            // Verify signature
            $secretKey = self::getSecretKey();
            $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secretKey, true);
            $expectedSignatureEncoded = self::base64UrlEncode($expectedSignature);
            
            if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
                Logger::warning('Invalid token signature', [
                    'user_id' => $payload['user_id'] ?? 'unknown'
                ]);
                return false;
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                Logger::info('Token expired', [
                    'user_id' => $payload['user_id'] ?? 'unknown',
                    'expired_at' => date('Y-m-d H:i:s', $payload['exp'])
                ]);
                return false;
            }
            
            // Check required fields
            if (!isset($payload['user_id'])) {
                return false;
            }
            
            Logger::debug('Token validated successfully', [
                'user_id' => $payload['user_id'],
                'role' => $payload['role'] ?? 'user'
            ]);
            
            return $payload;
            
        } catch (Exception $e) {
            Logger::error('Token validation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Refresh access token using refresh token
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
                Logger::warning('Invalid token type for refresh', [
                    'user_id' => $payload['user_id'],
                    'token_type' => $payload['token_type'] ?? 'access'
                ]);
                return false;
            }
            
            // Generate new access token
            return self::generate($payload['user_id'], $payload['role']);
            
        } catch (Exception $e) {
            Logger::error('Token refresh failed: ' . $e->getMessage());
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
            Logger::error('Failed to generate custom token: ' . $e->getMessage());
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
}