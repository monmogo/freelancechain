<?php
require_once 'includes/functions.php';

// Set CORS headers
setCORSHeaders();

// Get request info
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove query string and base path
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/freelancechain/backend', '', $path); // Adjust base path

// Route requests
switch (true) {
    // Auth routes
    case preg_match('#^/api/auth/register$#', $path) && $request_method === 'POST':
        require_once 'api/auth/register.php';
        break;
        
    case preg_match('#^/api/auth/login$#', $path) && $request_method === 'POST':
        require_once 'api/auth/login.php';
        break;
        
    case preg_match('#^/api/auth/logout$#', $path) && $request_method === 'POST':
        require_once 'api/auth/logout.php';
        break;
        
    // User routes
    case preg_match('#^/api/users/profile$#', $path) && $request_method === 'GET':
        require_once 'api/users/profile.php';
        break;
        
    case preg_match('#^/api/users/profile$#', $path) && $request_method === 'PUT':
        require_once 'api/users/update_profile.php';
        break;
        
    // Test route
    case preg_match('#^/api/test$#', $path) && $request_method === 'GET':
        jsonResponse(['message' => 'API is working!', 'timestamp' => time()]);
        break;
        
    default:
        jsonResponse(['error' => 'Endpoint not found'], 404);
}
?>