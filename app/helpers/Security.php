<?php
/**
 * Security helper class for CSRF protection and input validation
 */
class Security {
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get CSRF token for forms
     */
    public static function getCSRFToken(): string {
        return htmlspecialchars(self::generateCSRFToken(), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     */
    public static function validatePassword(string $password): bool {
        return mb_strlen($password) >= 6 && mb_strlen($password) <= 255;
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $input): string {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Validate integer input
     */
    public static function validateInteger($value): bool {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Get safe integer or return null
     */
    public static function getSafeInt($value): ?int {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        return $filtered !== false ? (int)$filtered : null;
    }
}
