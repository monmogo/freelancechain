<?php
// backend/api/auth/login.php - Updated with debug and CORS

// CORS headers FIRST
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Debug logging
error_log('=== LOGIN REQUEST DEBUG ===');
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log('Raw input: ' . file_get_contents('php://input'));

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed', 
        'received_method' => $_SERVER['REQUEST_METHOD'],
        'expected_method' => 'POST'
    ]);
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

$data = getJSONInput();
error_log('Parsed data: ' . json_encode($data));

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
    
    // Get user with profile
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.password_hash, u.role, u.status,
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
    
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit();
    }
    
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Account is not active']);
        exit();
    }
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Generate token (simple version for now)
    $token = 'token_' . $user['id'] . '_' . time();
    
    $response = [
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'display_name' => $user['display_name'] ?: ($user['first_name'] . ' ' . $user['last_name']),
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'avatar' => $user['avatar']
        ]
    ];
    
    error_log('Login success: ' . json_encode($response));
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Login failed', 'debug' => $e->getMessage()]);
}
?>