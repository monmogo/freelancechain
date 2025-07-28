<?php
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../services/SimpleJWTService.php';

$data = getJSONInput();
$refresh_token = $data['refresh_token'] ?? '';

if (empty($refresh_token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Refresh token is required']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $jwtService = new SimpleJWTService();
    
    $result = $jwtService->refreshAccessToken($refresh_token, $db);
    echo json_encode($result);
    
} catch (JWTException $e) {
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("Token refresh error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Token refresh failed']);
}
?>