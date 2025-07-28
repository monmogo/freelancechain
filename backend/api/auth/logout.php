<?php
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../services/SimpleJWTService.php';

try {
    $database = new Database();
    $db = $database->connect();
    $jwtService = new SimpleJWTService();
    
    $user = $jwtService->getCurrentUser();
    
    if ($user) {
        logActivity($user['sub'], 'logout', 'user', $user['sub']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => true, 
        'message' => 'Logged out successfully'
    ]);
}
?>