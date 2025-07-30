<?php
/**
 * FreelanceChain API Router - Clean Version
 * Only includes implemented endpoints
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

// Include core files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if core files exist
if (!file_exists('app/Core/Model.php')) {
    jsonResponse(['error' => 'Core Model class not found'], 500);
    exit;
}

if (!file_exists('app/Core/Controller.php')) {
    jsonResponse(['error' => 'Core Controller class not found'], 500);
    exit;
}

require_once 'app/Core/Model.php';
require_once 'app/Core/Controller.php';

// Auto-loader for existing classes only
function autoload($className) {
    $paths = [
        'app/Models/' . $className . '.php',
        'app/Controllers/' . $className . '.php',
        'services/' . $className . '.php',
        'middleware/' . $className . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}
spl_autoload_register('autoload');

// Set CORS headers
setCORSHeaders();

// Get request info
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse URI with better handling
$uri = parse_url($requestUri, PHP_URL_PATH);

// Remove script name if accessing via index.php
if (strpos($uri, '/index.php') !== false) {
    $uri = str_replace('/index.php', '', $uri);
}

// Remove base path
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

$uri = rtrim($uri, '/');
if (empty($uri)) {
    $uri = '/';
}

/**
 * =============================================================================
 * ROUTES - ONLY IMPLEMENTED ENDPOINTS
 * =============================================================================
 */

$routes = [
    
    // ==========================================================================
    // SYSTEM & TEST ROUTES
    // ==========================================================================
    'GET /' => function() {
        jsonResponse([
            'message' => 'Welcome to FreelanceChain API v2.0',
            'status' => 'operational',
            'version' => '2.0.0',
            'timestamp' => time(),
            'available_endpoints' => [
                'health' => '/api/health',
                'test' => '/api/test',
                'routes' => '/api/routes',
                'auth' => '/api/auth/*',
                'jobs' => '/api/jobs',
                'categories' => '/api/categories',
                'users' => '/api/users/profile'
            ]
        ]);
    },
    
    'GET /api/health' => function() {
        try {
            $database = new Database();
            $db = $database->connect();
            
            $dbStatus = 'connected';
            $dbInfo = 'Connection successful';
            
            if ($db) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM users LIMIT 1");
                    $result = $stmt->fetch();
                    $dbInfo = "Database accessible - Users: " . ($result['count'] ?? 0);
                } catch (Exception $e) {
                    $dbInfo = "Connected but table error: " . $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            $dbStatus = 'error';
            $dbInfo = $e->getMessage();
        }
        
        jsonResponse([
            'status' => 'healthy',
            'api_version' => '2.0.0',
            'database' => $dbStatus,
            'database_info' => $dbInfo,
            'server_time' => date('Y-m-d H:i:s'),
            'timestamp' => time(),
            'php_version' => PHP_VERSION
        ]);
    },
    
    'GET /api/test' => function() {
        jsonResponse([
            'message' => 'FreelanceChain API Test Successful! 🚀',
            'version' => '2.0.0',
            'timestamp' => time(),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'server_time' => date('Y-m-d H:i:s'),
                'timezone' => date_default_timezone_get()
            ],
            'request_info' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ],
            'next_steps' => [
                'Try login: POST /api/auth/login',
                'Try categories: GET /api/categories', 
                'Try jobs: GET /api/jobs'
            ]
        ]);
    },
    
    'GET /api/routes' => function() {
        global $routes;
        $routeList = [];
        foreach ($routes as $pattern => $handler) {
            list($method, $path) = explode(' ', $pattern, 2);
            $routeList[] = [
                'method' => $method,
                'path' => $path,
                'handler' => is_string($handler) ? $handler : 'Function',
                'implemented' => true
            ];
        }
        
        jsonResponse([
            'total_routes' => count($routeList),
            'api_version' => '2.0.0',
            'base_url' => 'http://localhost/freelancechain/backend',
            'routes' => $routeList
        ]);
    },

    // ==========================================================================
    // AUTHENTICATION ROUTES (if AuthController exists)
    // ==========================================================================
    'POST /api/auth/register' => 'AuthController@register',
    'POST /api/auth/login' => 'AuthController@login',
    'POST /api/auth/logout' => 'AuthController@logout',
    'POST /api/auth/refresh' => 'AuthController@refresh',
    
    // ==========================================================================
    // USER ROUTES (if UserController exists)
    // ==========================================================================
    'GET /api/users/profile' => 'UserController@getProfile',
    'PUT /api/users/profile' => 'UserController@updateProfile',
    
    // ==========================================================================
    // JOB ROUTES (if JobController exists)
    // ==========================================================================
    'GET /api/jobs' => 'JobController@index',
    'POST /api/jobs' => 'JobController@create',
    'GET /api/jobs/(\d+)' => 'JobController@show',
    'PUT /api/jobs/(\d+)' => 'JobController@update',
    'DELETE /api/jobs/(\d+)' => 'JobController@delete',
    'POST /api/jobs/(\d+)/publish' => 'JobController@publish',
    
    // ==========================================================================
    // CATEGORY ROUTES (if CategoryController exists)
    // ==========================================================================
    'GET /api/categories' => 'CategoryController@index',
    'GET /api/categories/(\d+)' => 'CategoryController@show',
    'GET /api/categories/search' => 'CategoryController@search',
    'GET /api/categories/(\d+)/jobs' => 'CategoryController@getJobs',
    'GET /api/categories/(\d+)/freelancers' => 'CategoryController@getFreelancers',
    
    // ==========================================================================
    // SKILLS ROUTES (if SkillController exists)
    // ==========================================================================
    'GET /api/skills' => 'SkillController@index',
    'GET /api/skills/search' => 'SkillController@search',
    
    // ==========================================================================
    // PROPOSAL ROUTES (if ProposalController exists)
    // ==========================================================================
    'POST /api/proposals' => 'ProposalController@create',
    'GET /api/jobs/(\d+)/proposals' => 'ProposalController@getForJob',
    'GET /api/my/proposals' => 'ProposalController@getMyProposals'
];

/**
 * =============================================================================
 * ROUTE PROCESSING
 * =============================================================================
 */

try {
    $matched = false;
    
    foreach ($routes as $pattern => $handler) {
        list($routeMethod, $routePath) = explode(' ', $pattern, 2);
        
        // Skip if HTTP method doesn't match
        if ($requestMethod !== $routeMethod) {
            continue;
        }
        
        // Convert route pattern to regex
        $regex = '#^' . str_replace('(\d+)', '(\d+)', $routePath) . '$#';
        
        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches); // Remove full match
            
            if (is_callable($handler)) {
                // Direct function call
                call_user_func($handler);
            } else {
                // Controller method call
                list($controller, $method) = explode('@', $handler);
                
                // Check if controller file exists
                if (!class_exists($controller)) {
                    jsonResponse([
                        'error' => 'Controller not implemented',
                        'controller' => $controller,
                        'message' => "Controller $controller is not yet implemented",
                        'endpoint' => $routeMethod . ' ' . $routePath,
                        'available_endpoints' => '/api/routes'
                    ], 501);
                    break;
                }
                
                $instance = new $controller();
                
                if (!method_exists($instance, $method)) {
                    jsonResponse([
                        'error' => 'Method not implemented',
                        'controller' => $controller,
                        'method' => $method,
                        'message' => "Method $method not found in $controller"
                    ], 501);
                    break;
                }
                
                // Execute the method
                call_user_func_array([$instance, $method], $matches);
            }
            
            $matched = true;
            break;
        }
    }
    
    if (!$matched) {
        jsonResponse([
            'error' => 'Endpoint not found',
            'method' => $requestMethod,
            'uri' => $uri,
            'message' => 'The requested endpoint does not exist',
            'debug_info' => [
                'original_uri' => $requestUri,
                'processed_uri' => $uri,
                'method' => $requestMethod
            ],
            'available_endpoints' => '/api/routes'
        ], 404);
    }
    
} catch (Exception $e) {
    // Enhanced error logging
    $errorDetails = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    
    error_log("FreelanceChain API Error: " . json_encode($errorDetails));
    
    jsonResponse([
        'error' => 'Internal server error',
        'message' => 'An error occurred while processing your request',
        'details' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ],
        'timestamp' => time()
    ], 500);
}

ob_end_flush();
?>