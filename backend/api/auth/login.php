<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// ============================================================================
// FILE 4: api/auth/login.php - WORKING VERSION
// ============================================================================

/**
 * Login API - Fixed version for XAMPP
 * Replace your login.php with this
 */

// CORS Headers FIRST
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Include required files
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../services/SimpleJWTService.php';

// Get input data
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

// Validation
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $jwtService = new SimpleJWTService();
    
    // Get user with profile
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.password_hash, u.role, u.status, u.login_attempts, u.locked_until,
               up.display_name, up.first_name, up.last_name, up.avatar
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit();
    }
    
    // Check account lockout
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        http_response_code(423);
        echo json_encode(['error' => 'Account is temporarily locked']);
        exit();
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Increment login attempts
        $attempts = $user['login_attempts'] + 1;
        $locked_until = null;
        
        if ($attempts >= 5) {
            $locked_until = date('Y-m-d H:i:s', time() + (30 * 60));
        }
        
        $stmt = $db->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
        $stmt->execute([$attempts, $locked_until, $user['id']]);
        
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit();
    }
    
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Account is not active']);
        exit();
    }
    
    // Get permissions
    $permissions = [];
    switch ($user['role']) {
        case 'admin':
            $permissions = ['*'];
            break;
        case 'freelancer':
            $permissions = ['read_profile', 'write_profile', 'submit_proposals'];
            break;
        case 'client':
            $permissions = ['read_profile', 'write_profile', 'post_jobs'];
            break;
    }
    
    // Generate tokens
    $access_token = $jwtService->generateAccessToken($user['id'], $user['email'], $user['role'], $permissions);
    $refresh_token = $jwtService->generateRefreshToken($user['id']);
    
    // Reset login attempts
    $stmt = $db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Log activity (safe - won't break if fails)
    logActivity($user['id'], 'login', 'user', $user['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'token_type' => 'Bearer',
        'expires_in' => 900,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'display_name' => $user['display_name'] ?: trim($user['first_name'] . ' ' . $user['last_name']),
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'avatar' => $user['avatar'],
            'permissions' => $permissions
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
}

?>