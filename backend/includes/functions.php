<?php

// ============================================================================
// FreelanceChain Backend Functions
// ============================================================================

function getStatusCodeMessage($status){
    $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    return (isset($codes[$status])) ? $codes[$status] : '';
}

// Enhanced CORS Headers
function setCORSHeaders() {
    // Allow specific origins
    $allowed_origins = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:3000',
        'http://127.0.0.1:3000'
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: http://localhost:5173");
    }
    
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400"); // 24 hours
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// JSON Response Helper with CORS
function jsonResponse($data, $status = 200) {
    // Set CORS headers first
    setCORSHeaders();
    
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Get JSON Input
function getJSONInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

// Validate Email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate Password Strength
function isValidPassword($password) {
    // At least 6 characters
    if (strlen($password) < 6) {
        return ['valid' => false, 'message' => 'Password must be at least 6 characters long'];
    }
    
    return ['valid' => true, 'message' => 'Password is valid'];
}

// Generate JWT Token (Enhanced)
function generateJWT($user_id, $email, $role) {
    $secret_key = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
    
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => (int)$user_id,
        'email' => $email,
        'role' => $role,
        'iat' => time(),
        'exp' => $_ENV['JWT_EXPIRY'] ?? (time() + (24 * 60 * 60)), // Default 24 hours
        'iss' => $_ENV['JWT_ISSUER'],
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret_key, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

// Verify JWT Token (Enhanced)
function verifyJWT($token) {
    if (empty($token)) return false;
    
    $secret_key = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
    
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    
    try {
        $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0]));
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
        $signature = $parts[2];
        
        // Verify signature
        $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret_key, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
        
        if ($signature !== $expectedSignature) return false;
        
        // Decode payload
        $data = json_decode($payload, true);
        if (!$data) return false;
        
        // Check expiration
        if (!isset($data['exp']) || $data['exp'] < time()) return false;
        
        // Check required fields
        if (!isset($data['user_id']) || !isset($data['email']) || !isset($data['role'])) return false;
        
        return $data;
    } catch (Exception $e) {
        return false;
    }
}

// Get Current User from Token
function getCurrentUser() {
    $headers = getallheaders();
    $token = null;
    
    // Check Authorization header (case insensitive)
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $token = str_replace(['Bearer ', 'bearer '], '', $value);
            break;
        }
    }
    
    if (!$token) return null;
    
    return verifyJWT($token);
}

// Hash Password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

// Verify Password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize Input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Generate Random String
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Log Error - Use system error log only
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    error_log("[$timestamp] FreelanceChain Error: $message$contextStr");
}

// Log Activity - Simplified version
function logActivity($user_id, $action, $entity_type = null, $entity_id = null, $metadata = []) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, metadata, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $description = "User performed action: $action";
        $metadata_json = !empty($metadata) ? json_encode($metadata) : null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->execute([
            $user_id,
            $action,
            $entity_type,
            $entity_id,
            $description,
            $metadata_json,
            $ip_address,
            $user_agent
        ]);
    } catch (Exception $e) {
        // Silently fail - don't break the main flow
    }
}

// Send Notification
function sendNotification($user_id, $type, $title, $message, $related_data = null, $action_url = null) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_data, action_url, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $related_data_json = !empty($related_data) ? json_encode($related_data) : null;
        
        $stmt->execute([
            $user_id,
            $type,
            $title,
            $message,
            $related_data_json,
            $action_url
        ]);
        
        return $db->lastInsertId();
    } catch (Exception $e) {
        return false;
    }
}

// Cache update functions
function updateJobSummary($job_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Get job with client and category info
        $stmt = $db->prepare("
            SELECT j.*, up.display_name as client_name, up.avatar as client_avatar,
                   c.name as category_name, c.slug as category_slug,
                   us.avg_rating as client_rating, us.total_jobs_completed as client_total_jobs
            FROM jobs j
            JOIN users u ON j.client_id = u.id
            JOIN user_profiles up ON u.id = up.user_id
            JOIN categories c ON j.category_id = c.id
            LEFT JOIN user_stats us ON u.id = us.user_id
            WHERE j.id = ?
        ");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();
        
        if (!$job) return false;
        
        // Insert or update job summary
        $stmt = $db->prepare("
            INSERT INTO job_summaries 
            (job_id, client_id, title, budget_min, budget_max, currency, category_id, status, 
             client_name, client_avatar, client_rating, client_total_jobs, category_name, category_slug,
             proposal_count, view_count, published_at, expires_at, last_updated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            budget_min = VALUES(budget_min),
            budget_max = VALUES(budget_max),
            status = VALUES(status),
            client_name = VALUES(client_name),
            client_avatar = VALUES(client_avatar),
            client_rating = VALUES(client_rating),
            client_total_jobs = VALUES(client_total_jobs),
            category_name = VALUES(category_name),
            category_slug = VALUES(category_slug),
            published_at = VALUES(published_at),
            expires_at = VALUES(expires_at),
            last_updated = NOW()
        ");
        
        $stmt->execute([
            $job['id'], $job['client_id'], $job['title'], $job['budget_min'], $job['budget_max'],
            $job['currency'], $job['category_id'], $job['status'], $job['client_name'], 
            $job['client_avatar'], $job['client_rating'], $job['client_total_jobs'],
            $job['category_name'], $job['category_slug'], $job['proposal_count'], 
            $job['view_count'], $job['published_at'], $job['expires_at']
        ]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function updateUserStats($user_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Calculate stats for freelancers
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as total_jobs_completed,
                COUNT(CASE WHEN c.status = 'active' THEN 1 END) as total_jobs_in_progress,
                SUM(CASE WHEN c.status = 'completed' THEN c.total_amount ELSE 0 END) as total_earnings,
                AVG(CASE WHEN r.overall_rating IS NOT NULL THEN r.overall_rating END) as avg_rating,
                COUNT(CASE WHEN r.overall_rating IS NOT NULL THEN 1 END) as total_reviews,
                COUNT(CASE WHEN r.overall_rating = 5 THEN 1 END) as total_5_star_reviews,
                COUNT(CASE WHEN r.overall_rating = 4 THEN 1 END) as total_4_star_reviews,
                COUNT(CASE WHEN r.overall_rating = 3 THEN 1 END) as total_3_star_reviews,
                COUNT(CASE WHEN r.overall_rating = 2 THEN 1 END) as total_2_star_reviews,
                COUNT(CASE WHEN r.overall_rating = 1 THEN 1 END) as total_1_star_reviews,
                COUNT(c.id) as total_contracts,
                COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as successful_contracts,
                COUNT(CASE WHEN c.status = 'cancelled' THEN 1 END) as cancelled_contracts,
                COUNT(CASE WHEN c.status = 'disputed' THEN 1 END) as disputed_contracts
            FROM contracts c
            LEFT JOIN reviews r ON c.id = r.contract_id AND r.reviewee_id = ?
            WHERE c.freelancer_id = ?
        ");
        $stmt->execute([$user_id, $user_id]);
        $freelancer_stats = $stmt->fetch();
        
        // Calculate stats for clients
        $stmt = $db->prepare("
            SELECT 
                COUNT(j.id) as total_jobs_posted,
                SUM(CASE WHEN c.status = 'completed' THEN c.total_amount ELSE 0 END) as total_spent,
                AVG(CASE WHEN r.overall_rating IS NOT NULL THEN r.overall_rating END) as avg_rating,
                COUNT(CASE WHEN r.overall_rating IS NOT NULL THEN 1 END) as total_reviews
            FROM jobs j
            LEFT JOIN contracts c ON j.id = c.job_id
            LEFT JOIN reviews r ON c.id = r.contract_id AND r.reviewee_id = ?
            WHERE j.client_id = ?
        ");
        $stmt->execute([$user_id, $user_id]);
        $client_stats = $stmt->fetch();
        
        // Update or insert user stats
        $stmt = $db->prepare("
            INSERT INTO user_stats 
            (user_id, total_jobs_posted, total_jobs_completed, total_jobs_in_progress,
             total_earnings, total_spent, avg_rating, total_reviews,
             total_5_star_reviews, total_4_star_reviews, total_3_star_reviews, 
             total_2_star_reviews, total_1_star_reviews, total_contracts, 
             successful_contracts, cancelled_contracts, disputed_contracts, last_calculated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            total_jobs_posted = VALUES(total_jobs_posted),
            total_jobs_completed = VALUES(total_jobs_completed),
            total_jobs_in_progress = VALUES(total_jobs_in_progress),
            total_earnings = VALUES(total_earnings),
            total_spent = VALUES(total_spent),
            avg_rating = VALUES(avg_rating),
            total_reviews = VALUES(total_reviews),
            total_5_star_reviews = VALUES(total_5_star_reviews),
            total_4_star_reviews = VALUES(total_4_star_reviews),
            total_3_star_reviews = VALUES(total_3_star_reviews),
            total_2_star_reviews = VALUES(total_2_star_reviews),
            total_1_star_reviews = VALUES(total_1_star_reviews),
            total_contracts = VALUES(total_contracts),
            successful_contracts = VALUES(successful_contracts),
            cancelled_contracts = VALUES(cancelled_contracts),
            disputed_contracts = VALUES(disputed_contracts),
            last_calculated = NOW()
        ");
        
        $stmt->execute([
            $user_id,
            $client_stats['total_jobs_posted'] ?? 0,
            $freelancer_stats['total_jobs_completed'] ?? 0,
            $freelancer_stats['total_jobs_in_progress'] ?? 0,
            $freelancer_stats['total_earnings'] ?? 0,
            $client_stats['total_spent'] ?? 0,
            $freelancer_stats['avg_rating'] ?? $client_stats['avg_rating'] ?? 0,
            $freelancer_stats['total_reviews'] ?? $client_stats['total_reviews'] ?? 0,
            $freelancer_stats['total_5_star_reviews'] ?? 0,
            $freelancer_stats['total_4_star_reviews'] ?? 0,
            $freelancer_stats['total_3_star_reviews'] ?? 0,
            $freelancer_stats['total_2_star_reviews'] ?? 0,
            $freelancer_stats['total_1_star_reviews'] ?? 0,
            $freelancer_stats['total_contracts'] ?? 0,
            $freelancer_stats['successful_contracts'] ?? 0,
            $freelancer_stats['cancelled_contracts'] ?? 0,
            $freelancer_stats['disputed_contracts'] ?? 0
        ]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function updateFreelancerProfile($user_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Get user profile and stats
        $stmt = $db->prepare("
            SELECT up.*, u.country_id, u.timezone, us.*
            FROM user_profiles up
            JOIN users u ON up.user_id = u.id
            LEFT JOIN user_stats us ON u.id = us.user_id
            WHERE up.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        if (!$profile) return false;
        
        // Get user skills
        $stmt = $db->prepare("
            SELECT s.id, s.name, s.slug, usk.proficiency_level, usk.years_experience
            FROM user_skills usk
            JOIN skills s ON usk.skill_id = s.id
            WHERE usk.user_id = ?
            ORDER BY s.popularity_score DESC
        ");
        $stmt->execute([$user_id]);
        $skills = $stmt->fetchAll();
        
        // Generate search keywords
        $keywords = [];
        $keywords[] = $profile['display_name'];
        $keywords[] = $profile['title'];
        foreach ($skills as $skill) {
            $keywords[] = $skill['name'];
        }
        $search_keywords = implode(' ', array_filter($keywords));
        $primary_skills = implode(', ', array_column($skills, 'name'));
        
        // Calculate profile completeness
        $completeness = 0;
        if (!empty($profile['display_name'])) $completeness += 15;
        if (!empty($profile['title'])) $completeness += 15;
        if (!empty($profile['bio'])) $completeness += 20;
        if (!empty($profile['avatar'])) $completeness += 10;
        if (!empty($profile['hourly_rate'])) $completeness += 10;
        if (count($skills) > 0) $completeness += 20;
        if (!empty($profile['portfolio_url'])) $completeness += 10;
        
        // Calculate success rate
        $success_rate = 0;
        if ($profile['total_contracts'] > 0) {
            $success_rate = ($profile['successful_contracts'] / $profile['total_contracts']) * 100;
        }
        
        // Get user languages
        $stmt = $db->prepare("
            SELECT language_code, proficiency FROM user_languages WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $languages = $stmt->fetchAll();
        
        // Update or insert freelancer profile
        $stmt = $db->prepare("
            INSERT INTO freelancer_profiles 
            (user_id, display_name, title, bio, avatar, country_id, hourly_rate, 
             hourly_rate_currency, experience_years, available_for_work, skills_json,
             primary_skills, avg_rating, total_reviews, total_jobs_completed, 
             total_earnings, success_rate, timezone, preferred_languages, 
             search_keywords, profile_completeness, last_active, profile_visibility, last_updated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())
            ON DUPLICATE KEY UPDATE
            display_name = VALUES(display_name),
            title = VALUES(title),
            bio = VALUES(bio),
            avatar = VALUES(avatar),
            country_id = VALUES(country_id),
            hourly_rate = VALUES(hourly_rate),
            hourly_rate_currency = VALUES(hourly_rate_currency),
            experience_years = VALUES(experience_years),
            available_for_work = VALUES(available_for_work),
            skills_json = VALUES(skills_json),
            primary_skills = VALUES(primary_skills),
            avg_rating = VALUES(avg_rating),
            total_reviews = VALUES(total_reviews),
            total_jobs_completed = VALUES(total_jobs_completed),
            total_earnings = VALUES(total_earnings),
            success_rate = VALUES(success_rate),
            timezone = VALUES(timezone),
            preferred_languages = VALUES(preferred_languages),
            search_keywords = VALUES(search_keywords),
            profile_completeness = VALUES(profile_completeness),
            profile_visibility = VALUES(profile_visibility),
            last_updated = NOW()
        ");
        
        $stmt->execute([
            $user_id,
            $profile['display_name'],
            $profile['title'],
            $profile['bio'],
            $profile['avatar'],
            $profile['country_id'],
            $profile['hourly_rate'],
            $profile['hourly_rate_currency'],
            $profile['experience_years'],
            $profile['available_for_work'],
            json_encode($skills),
            $primary_skills,
            $profile['avg_rating'] ?? 0,
            $profile['total_reviews'] ?? 0,
            $profile['total_jobs_completed'] ?? 0,
            $profile['total_earnings'] ?? 0,
            $success_rate,
            $profile['timezone'],
            json_encode(array_column($languages, 'language_code')),
            $search_keywords,
            $completeness,
            $profile['profile_visibility'] ?? 'public'
        ]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Helper function to update job proposal count
function updateJobProposalCount($job_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM proposals WHERE job_id = ? AND status != 'withdrawn'");
        $stmt->execute([$job_id]);
        $result = $stmt->fetch();
        
        $stmt = $db->prepare("UPDATE jobs SET proposal_count = ? WHERE id = ?");
        $stmt->execute([$result['count'], $job_id]);
        
        // Also update job summary
        updateJobSummary($job_id);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Helper function to update conversation counters
function updateConversationCounters($conversation_id, $sender_id) {
    try {
        $database = new Database();
        $db = $database->connect();
        
        // Get conversation participants
        $stmt = $db->prepare("SELECT client_id, freelancer_id FROM conversations WHERE id = ?");
        $stmt->execute([$conversation_id]);
        $conversation = $stmt->fetch();
        
        if (!$conversation) return false;
        
        // Count total messages
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE conversation_id = ?");
        $stmt->execute([$conversation_id]);
        $total_messages = $stmt->fetch()['count'];
        
        // Count unread messages for each participant
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM messages 
            WHERE conversation_id = ? AND sender_id != ? AND is_read = FALSE
        ");
        $stmt->execute([$conversation_id, $conversation['client_id']]);
        $unread_by_client = $stmt->fetch()['count'];
        
        $stmt->execute([$conversation_id, $conversation['freelancer_id']]);
        $unread_by_freelancer = $stmt->fetch()['count'];
        
        // Get last message info
        $stmt = $db->prepare("
            SELECT message, sender_id, created_at 
            FROM messages 
            WHERE conversation_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$conversation_id]);
        $last_message = $stmt->fetch();
        
        // Update conversation counters
        $stmt = $db->prepare("
            UPDATE conversations SET 
            total_messages = ?,
            unread_by_client = ?,
            unread_by_freelancer = ?,
            last_message_text = ?,
            last_message_sender_id = ?,
            last_message_at = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $total_messages,
            $unread_by_client,
            $unread_by_freelancer,
            $last_message['message'] ?? null,
            $last_message['sender_id'] ?? null,
            $last_message['created_at'] ?? null,
            $conversation_id
        ]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Load environment variables function
function loadEnvironment() {
    $env_file = __DIR__ . '/../.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
}

// Call loadEnvironment when functions.php is included
loadEnvironment();
?>