<?php

// ============================================================================
// FILE 2: middleware/SimpleAuthMiddleware.php - FIXED VERSION
// ============================================================================

/**
 * Simple Auth Middleware - Works with SimpleJWTService
 * Paste this into: middleware/SimpleAuthMiddleware.php
 */

// Make sure to include SimpleJWTService
if (!class_exists('SimpleJWTService')) {
    require_once __DIR__ . '/../services/SimpleJWTService.php';
}

class SimpleAuthMiddleware {
    private $jwtService;
    private $database;
    
    public function __construct($database) {
        $this->database = $database;
        $this->jwtService = new SimpleJWTService();
    }
    
    /**
     * Require authentication
     */
    public function requireAuth($required_roles = null) {
        $user = $this->jwtService->getCurrentUser();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        
        // Double-check user is still active in database
        $stmt = $this->database->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$user['sub']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dbUser || $dbUser['status'] !== 'active') {
            http_response_code(401);
            echo json_encode(['error' => 'Account is not active']);
            exit;
        }
        
        // Check role permissions
        if ($required_roles && !in_array($user['role'], (array)$required_roles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
        
        return $user;
    }
    
    /**
     * Optional authentication
     */
    public function optionalAuth() {
        return $this->jwtService->getCurrentUser();
    }
}

?>