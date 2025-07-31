<?php
require_once __DIR__ . '/../../services/SimpleJWTService.php';

class AuthController extends Controller {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }
    
    public function register() {
        $data = $this->getRequestData();
        
        // Validation
        $this->validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'role' => 'required',
            'first_name' => 'required',
            'last_name' => 'required'
        ]);
        
        // Additional validation
        if (!in_array($data['role'], ['freelancer', 'client'])) {
            $this->error('Invalid role');
        }
        
        try {
            // Check if email exists
            if ($this->userModel->findBy('email', $data['email'])) {
                $this->error('Email already registered', 409);
            }
            
            // Create user with profile
            $userData = [
                'email' => $data['email'],
                'password_hash' => hashPassword($data['password']),
                'role' => $data['role'],
                'status' => 'active'
            ];
            
            $profileData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']
            ];
            
            $user = $this->userModel->createWithProfile($userData, $profileData);
            
            // Generate token
            $permissions = $this->userModel->getUserPermissions($user['role']);
            $token = $this->jwtService->generateAccessToken(
                $user['id'], 
                $user['email'], 
                $user['role'], 
                $permissions
            );
            
            $this->success([
                'token' => $token,
                'user' => [
                    'id' => (int)$user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'display_name' => trim($data['first_name'] . ' ' . $data['last_name'])
                ]
            ], 'User registered successfully', 201);
            
        } catch (Exception $e) {
            logError("Registration failed", ['error' => $e->getMessage()]);
            $this->error('Registration failed', 500);
        }
    }
    
    public function login() {
        $data = $this->getRequestData();

        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            $this->error('Email and password are required', 400);
        }
        
        $this->validate($data, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        try {
            $user = $this->userModel->getWithProfile($this->userModel->findBy('email', $data['email'])['id'] ?? 0);
            
            if (!$user) {
                $this->error('Invalid credentials', 401);
            }
            
            // Check account lockout
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $this->error('Account temporarily locked', 423);
            }
            
            // Verify password
            if (!password_verify($data['password'], $user['password_hash'])) {
                $this->userModel->incrementLoginAttempts($user['id']);
                $this->error('Invalid credentials', 401);
            }
            
            if ($user['status'] !== 'active') {
                $this->error('Account is not active', 403);
            }
            
            // Generate tokens
            $permissions = $this->userModel->getUserPermissions($user['role']);
            $access_token = $this->jwtService->generateAccessToken($user['id'], $user['email'], $user['role'], $permissions);
            $refresh_token = $this->jwtService->generateRefreshToken($user['id']);
            
            // Reset login attempts
            $this->userModel->resetLoginAttempts($user['id']);
            
            // Log activity
            logActivity($user['id'], 'login', 'user', $user['id']);
            
            $this->success([
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'user' => [
                    'id' => (int)$user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'display_name' => $user['display_name'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'avatar' => $user['avatar'],
                    'permissions' => $permissions
                ]
            ], 'Login successful');
            
        } catch (Exception $e) {
            logError("Login failed", ['error' => $e->getMessage()]);
            $this->error('Login failed', 500);
        }
    }
    
    public function refresh() {
        $data = $this->getRequestData();
        
        if (empty($data['refresh_token'])) {
            $this->error('Refresh token is required', 400);
        }
        
        try {
            $result = $this->jwtService->refreshAccessToken($data['refresh_token'], $this->userModel->db);
            $this->success($result);
            
        } catch (JWTException $e) {
            $this->error($e->getMessage(), 401);
        } catch (Exception $e) {
            logError("Token refresh failed", ['error' => $e->getMessage()]);
            $this->error('Token refresh failed', 500);
        }
    }
    
    public function logout() {
        try {
            if ($this->user) {
                logActivity($this->user['user_id'], 'logout', 'user', $this->user['user_id']);
            }
            
            $this->success(null, 'Logged out successfully');
            
        } catch (Exception $e) {
            // Even if logging fails, logout should succeed
            $this->success(null, 'Logged out successfully');
        }
    }
}