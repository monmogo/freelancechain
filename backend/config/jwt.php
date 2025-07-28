<?php
/**
 * Simplified JWT Implementation for FreelanceChain
 * Secure but without blacklist complexity
 */

// ============================================================================
// 1. SIMPLIFIED JWT CONFIGURATION
// ============================================================================

class JWTConfig {
    private static $config = [
        'access_token_secret' => null,
        'refresh_token_secret' => null,
        'access_token_expiry' => 900,      // 15 minutes
        'refresh_token_expiry' => 604800,  // 7 days
        'issuer' => 'FreelanceChain',
        'algorithm' => 'HS256',
        'leeway' => 60,                    // 1 minute clock skew tolerance
    ];
    
    public static function init() {
        // Load from environment or generate defaults
        self::$config['access_token_secret'] = $_ENV['JWT_ACCESS_SECRET'] ?? 
            'CHANGE_THIS_' . bin2hex(random_bytes(32));
        self::$config['refresh_token_secret'] = $_ENV['JWT_REFRESH_SECRET'] ?? 
            'CHANGE_THIS_' . bin2hex(random_bytes(32));
            
        // Override with environment values
        self::$config['access_token_expiry'] = $_ENV['JWT_ACCESS_EXPIRY'] ?? self::$config['access_token_expiry'];
        self::$config['refresh_token_expiry'] = $_ENV['JWT_REFRESH_EXPIRY'] ?? self::$config['refresh_token_expiry'];
        self::$config['issuer'] = $_ENV['JWT_ISSUER'] ?? self::$config['issuer'];
    }
    
    public static function get($key) {
        return self::$config[$key] ?? null;
    }
}

class JWTException extends Exception {}
class JWTExpiredException extends JWTException {}
class JWTInvalidException extends JWTException {}
?>