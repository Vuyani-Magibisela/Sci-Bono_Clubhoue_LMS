<?php
/**
 * Validation Helper Functions - Common validation utilities
 * Phase 2 Implementation
 */

class ValidationHelpers {
    
    /**
     * Validate South African ID Number
     */
    public static function validateSAIdNumber($idNumber) {
        if (strlen($idNumber) !== 13) {
            return false;
        }
        
        if (!ctype_digit($idNumber)) {
            return false;
        }
        
        // Luhn algorithm check
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = intval($idNumber[$i]);
            
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = intval($digit / 10) + ($digit % 10);
                }
            }
            
            $sum += $digit;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return intval($idNumber[12]) === $checkDigit;
    }
    
    /**
     * Validate South African cell phone number
     */
    public static function validateSACellNumber($number) {
        // Remove spaces and common separators
        $number = preg_replace('/[\s\-\(\)]/', '', $number);
        
        // South African cell numbers: +27 or 0, followed by specific prefixes
        $pattern = '/^(?:\+27|0)(?:6[0-9]|7[0-9]|8[1-9])[0-9]{7}$/';
        
        return preg_match($pattern, $number);
    }
    
    /**
     * Check if input contains SQL injection patterns
     */
    public static function checkSQLInjection($input) {
        $sqlPatterns = [
            '/(\bunion\s+select)/i',
            '/(\bselect\s+.*\bfrom)/i',
            '/(\binsert\s+into)/i',
            '/(\bupdate\s+.*\bset)/i',
            '/(\bdelete\s+from)/i',
            '/(\bdrop\s+table)/i',
            '/(\btruncate\s+table)/i',
            '/(\balter\s+table)/i',
            '/(\'|\";?\s*(or|and)\s*\')/i',
            '/(\s*(or|and)\s+\d+\s*=\s*\d+)/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Sanitize HTML while preserving safe tags
     */
    public static function sanitizeHTML($html, $allowedTags = ['p', 'br', 'strong', 'em']) {
        $allowedString = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($html, $allowedString);
    }
}