<?php
/**
 * QUICK FIX FILES - Replace these files to fix JWT errors
 */

// ============================================================================
// FILE 1: services/SimpleJWTService.php - FIXED VERSION
// ============================================================================

/**
 * Simple JWT Service - Self-contained, no external dependencies
 * Paste this into: services/SimpleJWTService.php
 */

class SimpleJWTService {
    private $access_secret;
    private $refresh_secret;
    private $access_expiry;
    private $refresh_expiry;
    private $issuer;
    
    public function __construct() {
        // Initialize config directly (no JWTConfig dependency)
        $this->access_secret = $_ENV['JWT_ACCESS_SECRET'] ?? 'freelancechain-access-secret-change-in-production';
        $this->refresh_secret = $_ENV['JWT_REFRESH_SECRET'] ?? 'freelancechain-refresh-secret-change-in-production';
        $this->access_expiry = $_ENV['JWT_ACCESS_EXPIRY'] ?? 900; // 15 minutes
        $this->refresh_expiry = $_ENV['JWT_REFRESH_EXPIRY'] ?? 604800; // 7 days
        $this->issuer = $_ENV['JWT_ISSUER'] ?? 'FreelanceChain';
    }
    
    /**
     * Generate cryptographically secure JTI
     */
    private function generateJTI() {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Create HMAC signature
     */
    private function createSignature($header, $payload, $secret) {
        $data = $header . '.' . $payload;
        return $this->base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }
    
    /**
     * Generate Access Token
     */
    public function generateAccessToken($user_id, $email, $role, $permissions = []) {
        $now = time();
        
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $payload = [
            'iss' => $this->issuer,
            'sub' => (string)$user_id,
            'aud' => 'freelancechain-api',
            'exp' => $now + $this->access_expiry,
            'nbf' => $now - 60,
            'iat' => $now,
            'jti' => $this->generateJTI(),
            'email' => $email,
            'role' => $role,
            'permissions' => $permissions,
            'token_type' => 'access'
        ];
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->createSignature($headerEncoded, $payloadEncoded, $this->access_secret);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }
    
    /**
     * Generate Refresh Token
     */
    public function generateRefreshToken($user_id) {
        $now = time();
        
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $payload = [
            'iss' => $this->issuer,
            'sub' => (string)$user_id,
            'aud' => 'freelancechain-refresh',
            'exp' => $now + $this->refresh_expiry,
            'nbf' => $now - 60,
            'iat' => $now,
            'jti' => $this->generateJTI(),
            'token_type' => 'refresh'
        ];
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->createSignature($headerEncoded, $payloadEncoded, $this->refresh_secret);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }
    
    /**
     * Verify JWT token
     */
    public function verifyToken($token, $token_type = 'access') {
        if (empty($token)) {
            throw new Exception('Token is empty');
        }
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        [$headerEncoded, $payloadEncoded, $signature] = $parts;
        
        // Decode header and payload
        $header = json_decode($this->base64UrlDecode($headerEncoded), true);
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        
        if (!$header || !$payload) {
            throw new Exception('Invalid token encoding');
        }
        
        // Verify token type
        if (($payload['token_type'] ?? '') !== $token_type) {
            throw new Exception('Invalid token type');
        }
        
        // Select appropriate secret
        $secret = $token_type === 'access' ? $this->access_secret : $this->refresh_secret;
        
        // Verify signature
        $expectedSignature = $this->createSignature($headerEncoded, $payloadEncoded, $secret);
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid token signature');
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token has expired');
        }
        
        // Check not before
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            throw new Exception('Token not yet valid');
        }
        
        return $payload;
    }
    
    /**
     * Extract token from Authorization header
     */
    public function extractTokenFromHeader() {
        $headers = getallheaders();
        if (!$headers) return null;
        
        $authHeader = null;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
        
        if (!$authHeader) return null;
        
        // Extract Bearer token
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        $token = $this->extractTokenFromHeader();
        if (!$token) return null;
        
        try {
            return $this->verifyToken($token, 'access');
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Refresh access token
     */
    public function refreshAccessToken($refresh_token, $database) {
        // Verify refresh token
        $refresh_payload = $this->verifyToken($refresh_token, 'refresh');
        $user_id = $refresh_payload['sub'];
        
        // Get fresh user data from database
        $stmt = $database->prepare("
            SELECT u.email, u.role, u.status
            FROM users u
            WHERE u.id = ? AND u.status = 'active'
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('User not found or inactive');
        }
        
        // Get permissions based on role
        $permissions = $this->getRolePermissions($user['role']);
        
        // Generate new access token
        $new_access_token = $this->generateAccessToken(
            $user_id,
            $user['email'],
            $user['role'],
            $permissions
        );
        
        return [
            'access_token' => $new_access_token,
            'token_type' => 'Bearer',
            'expires_in' => $this->access_expiry
        ];
    }
    
    /**
     * Get role-based permissions
     */
    private function getRolePermissions($role) {
        switch ($role) {
            case 'admin':
                return ['*'];
            case 'freelancer':
                return ['read_profile', 'write_profile', 'submit_proposals', 'manage_contracts'];
            case 'client':
                return ['read_profile', 'write_profile', 'post_jobs', 'manage_contracts'];
            default:
                return [];
        }
    }
}
?>