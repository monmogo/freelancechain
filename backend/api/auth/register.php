<?php
header('Access-Control-Allow-Origin: http://localhost:5173'); // Vue dev server URL
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get input data
$data = getJSONInput();
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'freelancer';
$first_name = trim($data['first_name'] ?? '');
$last_name = trim($data['last_name'] ?? '');

// Validation
if (empty($email) || empty($password)) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

if (!isValidEmail($email)) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

if (strlen($password) < 6) {
    jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
}

if (!in_array($role, ['freelancer', 'client'])) {
    jsonResponse(['error' => 'Invalid role'], 400);
}

try {
    // Database connection
    $database = new Database();
    $db = $database->connect();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email already registered'], 409);
    }
    
    // Create user
    $password_hash = hashPassword($password);
    
    $stmt = $db->prepare("
        INSERT INTO users (email, password_hash, role, status, created_at) 
        VALUES (?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$email, $password_hash, $role]);
    
    $user_id = $db->lastInsertId();
    
    // Create user profile
    $display_name = trim($first_name . ' ' . $last_name);
    $stmt = $db->prepare("
        INSERT INTO user_profiles (user_id, first_name, last_name, display_name, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $first_name, $last_name, $display_name]);
    
    // Create user stats
    $stmt = $db->prepare("INSERT INTO user_stats (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    
    // Generate token
    $token = generateJWT($user_id, $email, $role);
    
    jsonResponse([
        'success' => true,
        'message' => 'User registered successfully',
        'token' => $token,
        'user' => [
            'id' => $user_id,
            'email' => $email,
            'role' => $role,
            'display_name' => $display_name
        ]
    ], 201);
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    jsonResponse(['error' => 'Registration failed'], 500);
}
?>