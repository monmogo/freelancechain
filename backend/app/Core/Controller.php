<?php
abstract class Controller {
    protected $user = null;
    protected SimpleJWTService $jwtService;
    
    public function __construct() {
        $this->jwtService = new SimpleJWTService();
    }
    
    protected function requireAuth() {
        $token = $this->jwtService->extractTokenFromHeader();
        
        if (!$token) {
            $this->error('Authentication required', 401);
        }
        
        try {
            $payload = $this->jwtService->verifyToken($token, 'access');
            
            // Set user data from JWT payload
            $this->user = [
                'user_id' => (int)$payload['sub'],
                'email' => $payload['email'],
                'role' => $payload['role'],
                'permissions' => $payload['permissions'] ?? []
            ];
            
            return $this->user;
            
        } catch (Exception $e) {
            logError("Authentication failed", [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);
            $this->error('Invalid or expired token', 401);
        }
    }
    
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);
            $value = $data[$field] ?? null;
            
            foreach ($ruleList as $singleRule) {
                if ($singleRule === 'required' && empty($value)) {
                    $errors[$field] = "$field is required";
                    break;
                }
                
                if (strpos($singleRule, 'min:') === 0 && strlen($value) < substr($singleRule, 4)) {
                    $errors[$field] = "$field must be at least " . substr($singleRule, 4) . " characters";
                    break;
                }
                
                if (strpos($singleRule, 'max:') === 0 && strlen($value) > substr($singleRule, 4)) {
                    $errors[$field] = "$field must not exceed " . substr($singleRule, 4) . " characters";
                    break;
                }
                
                if ($singleRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "$field must be a valid email";
                    break;
                }
            }
        }
        
        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
        }
        
        return $data;
    }

    protected function requireRole($requiredRole) {
        $this->requireAuth();
        
        if ($this->user['role'] !== $requiredRole) {
            $this->error('Insufficient permissions', 403);
        }
    }
    
    /**
     * Check if user has permission
     */
    protected function hasPermission($permission) {
        if (!$this->user) return false;
        
        $permissions = $this->user['permissions'] ?? [];
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
    
    /**
     * Get request data (JSON or form)
     */
    protected function getRequestData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            return $data ?? [];
        }
        
        return $_POST;
    }
    
    /**
     * Success response
     */
    protected function success($data = null, $message = null, $status = 200) {
        http_response_code($status);
        
        $response = [
            'success' => true,
            'timestamp' => time()
        ];
        
        if ($message) $response['message'] = $message;
        if ($data !== null) $response['data'] = $data;
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Error response
     */
    protected function error($message, $status = 400, $errors = null) {
        http_response_code($status);
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => time()
        ];
        
        if ($errors) $response['errors'] = $errors;
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}