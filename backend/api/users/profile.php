<?php
/**
 * User Profile API Endpoint
 * Updated for Simple JWT System
 */

// CORS Headers
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
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
require_once '../../services/SimpleJWTService.php';
require_once '../../middleware/SimpleAuthMiddleware.php';

try {
    $database = new Database();
    $db = $database->connect();
    $auth = new SimpleAuthMiddleware($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // ============================================================================
        // GET PROFILE - Require authentication
        // ============================================================================
        
        $current_user = $auth->requireAuth();
        
        // Get comprehensive user profile with stats
        $stmt = $db->prepare("
            SELECT u.id, u.email, u.role, u.status, u.country_id, u.preferred_language,
                   u.timezone, u.currency_preference, u.email_verified, u.kyc_verified,
                   u.wallet_address, u.last_login, u.created_at,
                   
                   up.first_name, up.last_name, up.display_name, up.avatar, up.bio,
                   up.title, up.hourly_rate, up.hourly_rate_currency, up.experience_years,
                   up.phone, up.website, up.linkedin, up.github, up.portfolio_url,
                   up.available_for_work, up.profile_visibility, up.address, up.city,
                   up.state, up.postal_code,
                   
                   us.total_jobs_posted, us.total_jobs_completed, us.total_jobs_in_progress,
                   us.total_earnings, us.total_spent, us.avg_rating, us.total_reviews,
                   us.total_5_star_reviews, us.total_4_star_reviews, us.total_3_star_reviews,
                   us.total_2_star_reviews, us.total_1_star_reviews, us.response_rate_percentage,
                   us.total_contracts, us.successful_contracts, us.last_calculated,
                   
                   c.name as country_name, c.code as country_code, c.currency_code,
                   c.currency_symbol, c.timezone as country_timezone
                   
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN user_stats us ON u.id = us.user_id
            LEFT JOIN countries c ON u.country_id = c.id
            WHERE u.id = ?
        ");
        $stmt->execute([$current_user['sub']]);
        $profile = $stmt->fetch();
        
        if (!$profile) {
            http_response_code(404);
            echo json_encode(['error' => 'Profile not found']);
            exit();
        }
        
        // Get user skills with categories
        $stmt = $db->prepare("
            SELECT s.id, s.name, s.slug, usk.proficiency_level, usk.years_experience,
                   usk.verified, c.name as category_name, c.slug as category_slug,
                   s.popularity_score
            FROM user_skills usk
            JOIN skills s ON usk.skill_id = s.id
            JOIN categories c ON s.category_id = c.id
            WHERE usk.user_id = ?
            ORDER BY s.popularity_score DESC, usk.proficiency_level DESC
        ");
        $stmt->execute([$current_user['sub']]);
        $skills = $stmt->fetchAll();
        
        // Get user languages
        $stmt = $db->prepare("
            SELECT l.code, l.name, l.native_name, ul.proficiency
            FROM user_languages ul
            JOIN languages l ON ul.language_code = l.code
            WHERE ul.user_id = ?
            ORDER BY ul.proficiency DESC
        ");
        $stmt->execute([$current_user['sub']]);
        $languages = $stmt->fetchAll();
        
        // Get role-specific profile data
        $role_profile = null;
        if ($profile['role'] === 'freelancer') {
            $stmt = $db->prepare("
                SELECT fp.*, 
                       ROUND((fp.total_earnings / NULLIF(fp.total_jobs_completed, 0)), 2) as avg_earnings_per_job,
                       fp.profile_completeness
                FROM freelancer_profiles fp
                WHERE fp.user_id = ?
            ");
            $stmt->execute([$current_user['sub']]);
            $role_profile = $stmt->fetch();
            
        } elseif ($profile['role'] === 'client') {
            $stmt = $db->prepare("
                SELECT cp.*,
                       ROUND((cp.total_spent / NULLIF(cp.total_jobs_posted, 0)), 2) as avg_budget_per_job
                FROM client_profiles cp
                WHERE cp.user_id = ?
            ");
            $stmt->execute([$current_user['sub']]);
            $role_profile = $stmt->fetch();
        }
        
        // Calculate profile completeness if not available
        $completeness = $role_profile['profile_completeness'] ?? calculateProfileCompleteness($profile, $skills, $languages);
        
        // Format response
        $response_data = [
            'success' => true,
            'profile' => [
                // Basic Info
                'id' => (int)$profile['id'],
                'email' => $profile['email'],
                'role' => $profile['role'],
                'status' => $profile['status'],
                'email_verified' => (bool)$profile['email_verified'],
                'kyc_verified' => (bool)$profile['kyc_verified'],
                'created_at' => $profile['created_at'],
                'last_login' => $profile['last_login'],
                
                // Personal Info
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'display_name' => $profile['display_name'],
                'avatar' => $profile['avatar'],
                'bio' => $profile['bio'],
                'title' => $profile['title'],
                'phone' => $profile['phone'],
                
                // Professional Info
                'hourly_rate' => $profile['hourly_rate'] ? (float)$profile['hourly_rate'] : null,
                'hourly_rate_currency' => $profile['hourly_rate_currency'],
                'experience_years' => $profile['experience_years'] ? (int)$profile['experience_years'] : null,
                'available_for_work' => (bool)$profile['available_for_work'],
                
                // Social Links
                'website' => $profile['website'],
                'linkedin' => $profile['linkedin'],
                'github' => $profile['github'],
                'portfolio_url' => $profile['portfolio_url'],
                
                // Location
                'country_id' => $profile['country_id'] ? (int)$profile['country_id'] : null,
                'country_name' => $profile['country_name'],
                'country_code' => $profile['country_code'],
                'city' => $profile['city'],
                'state' => $profile['state'],
                'timezone' => $profile['timezone'] ?? $profile['country_timezone'],
                
                // Preferences
                'preferred_language' => $profile['preferred_language'],
                'currency_preference' => $profile['currency_preference'],
                'profile_visibility' => $profile['profile_visibility'],
                
                // Blockchain
                'wallet_address' => $profile['wallet_address'],
                
                // Statistics
                'stats' => [
                    'total_jobs_posted' => (int)($profile['total_jobs_posted'] ?? 0),
                    'total_jobs_completed' => (int)($profile['total_jobs_completed'] ?? 0),
                    'total_jobs_in_progress' => (int)($profile['total_jobs_in_progress'] ?? 0),
                    'total_earnings' => (float)($profile['total_earnings'] ?? 0),
                    'total_spent' => (float)($profile['total_spent'] ?? 0),
                    'avg_rating' => $profile['avg_rating'] ? round((float)$profile['avg_rating'], 2) : null,
                    'total_reviews' => (int)($profile['total_reviews'] ?? 0),
                    'response_rate_percentage' => $profile['response_rate_percentage'] ? (float)$profile['response_rate_percentage'] : null,
                    'total_contracts' => (int)($profile['total_contracts'] ?? 0),
                    'successful_contracts' => (int)($profile['successful_contracts'] ?? 0),
                    'success_rate' => calculateSuccessRate($profile['total_contracts'], $profile['successful_contracts']),
                    'rating_breakdown' => [
                        '5_star' => (int)($profile['total_5_star_reviews'] ?? 0),
                        '4_star' => (int)($profile['total_4_star_reviews'] ?? 0),
                        '3_star' => (int)($profile['total_3_star_reviews'] ?? 0),
                        '2_star' => (int)($profile['total_2_star_reviews'] ?? 0),
                        '1_star' => (int)($profile['total_1_star_reviews'] ?? 0),
                    ],
                    'last_updated' => $profile['last_calculated']
                ],
                
                // Skills & Languages
                'skills' => array_map(function($skill) {
                    return [
                        'id' => (int)$skill['id'],
                        'name' => $skill['name'],
                        'slug' => $skill['slug'],
                        'proficiency_level' => $skill['proficiency_level'],
                        'years_experience' => $skill['years_experience'] ? (int)$skill['years_experience'] : null,
                        'verified' => (bool)$skill['verified'],
                        'category' => [
                            'name' => $skill['category_name'],
                            'slug' => $skill['category_slug']
                        ],
                        'popularity_score' => (int)$skill['popularity_score']
                    ];
                }, $skills),
                
                'languages' => array_map(function($lang) {
                    return [
                        'code' => $lang['code'],
                        'name' => $lang['name'],
                        'native_name' => $lang['native_name'],
                        'proficiency' => $lang['proficiency']
                    ];
                }, $languages),
                
                // Profile Health
                'profile_completeness' => (int)$completeness,
                'missing_fields' => getMissingProfileFields($profile, $skills, $languages),
                
                // Role-specific data
                'role_profile' => $role_profile
            ]
        ];
        
        echo json_encode($response_data);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // ============================================================================
        // UPDATE PROFILE - Require authentication
        // ============================================================================
        
        $current_user = $auth->requireAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            exit();
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Update users table if needed
            $user_fields = ['country_id', 'preferred_language', 'timezone', 'currency_preference'];
            $user_updates = [];
            $user_values = [];
            
            foreach ($user_fields as $field) {
                if (isset($data[$field])) {
                    $user_updates[] = "$field = ?";
                    $user_values[] = $data[$field];
                }
            }
            
            if (!empty($user_updates)) {
                $user_values[] = $current_user['sub'];
                $stmt = $db->prepare("UPDATE users SET " . implode(', ', $user_updates) . " WHERE id = ?");
                $stmt->execute($user_values);
            }
            
            // Update user_profiles table
            $profile_fields = [
                'first_name', 'last_name', 'display_name', 'bio', 'title',
                'hourly_rate', 'hourly_rate_currency', 'experience_years',
                'phone', 'website', 'linkedin', 'github', 'portfolio_url',
                'available_for_work', 'profile_visibility', 'address',
                'city', 'state', 'postal_code'
            ];
            
            $profile_updates = [];
            $profile_values = [];
            
            foreach ($profile_fields as $field) {
                if (isset($data[$field])) {
                    $profile_updates[] = "$field = ?";
                    $profile_values[] = $data[$field];
                }
            }
            
            if (!empty($profile_updates)) {
                $profile_updates[] = "updated_at = NOW()";
                $profile_values[] = $current_user['sub'];
                
                $stmt = $db->prepare("UPDATE user_profiles SET " . implode(', ', $profile_updates) . " WHERE user_id = ?");
                $stmt->execute($profile_values);
            }
            
            // Update skills if provided
            if (isset($data['skills']) && is_array($data['skills'])) {
                // Delete existing skills
                $stmt = $db->prepare("DELETE FROM user_skills WHERE user_id = ?");
                $stmt->execute([$current_user['sub']]);
                
                // Insert new skills
                $stmt = $db->prepare("
                    INSERT INTO user_skills (user_id, skill_id, proficiency_level, years_experience) 
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($data['skills'] as $skill) {
                    $stmt->execute([
                        $current_user['sub'],
                        $skill['skill_id'],
                        $skill['proficiency_level'] ?? 'intermediate',
                        $skill['years_experience'] ?? null
                    ]);
                }
            }
            
            // Update languages if provided
            if (isset($data['languages']) && is_array($data['languages'])) {
                // Delete existing languages
                $stmt = $db->prepare("DELETE FROM user_languages WHERE user_id = ?");
                $stmt->execute([$current_user['sub']]);
                
                // Insert new languages
                $stmt = $db->prepare("INSERT INTO user_languages (user_id, language_code, proficiency) VALUES (?, ?, ?)");
                
                foreach ($data['languages'] as $language) {
                    $stmt->execute([
                        $current_user['sub'],
                        $language['language_code'],
                        $language['proficiency']
                    ]);
                }
            }
            
            $db->commit();
            
            // Update role-specific profiles and stats
            updateUserStats($current_user['sub']);
            if ($current_user['role'] === 'freelancer') {
                updateFreelancerProfile($current_user['sub']);
            }
            
            // Log activity
            logActivity($current_user['sub'], 'profile_update', 'user_profile', $current_user['sub'], [
                'updated_fields' => array_keys($data)
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Profile API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Calculate profile completeness percentage
 */
function calculateProfileCompleteness($profile, $skills, $languages) {
    $completeness = 0;
    $total_points = 100;
    
    // Basic info (40 points)
    if (!empty($profile['display_name'])) $completeness += 10;
    if (!empty($profile['bio'])) $completeness += 15;
    if (!empty($profile['avatar'])) $completeness += 10;
    if (!empty($profile['phone'])) $completeness += 5;
    
    // Professional info (30 points)
    if (!empty($profile['title'])) $completeness += 10;
    if (!empty($profile['hourly_rate'])) $completeness += 10;
    if (!empty($profile['experience_years'])) $completeness += 10;
    
    // Skills and languages (20 points)
    if (count($skills) >= 3) $completeness += 15;
    if (count($languages) >= 1) $completeness += 5;
    
    // Social/portfolio (10 points)
    $social_links = [$profile['website'], $profile['linkedin'], $profile['github'], $profile['portfolio_url']];
    $social_count = count(array_filter($social_links));
    $completeness += min(10, $social_count * 2.5);
    
    return min(100, $completeness);
}

/**
 * Get missing profile fields for suggestions
 */
function getMissingProfileFields($profile, $skills, $languages) {
    $missing = [];
    
    if (empty($profile['avatar'])) $missing[] = 'avatar';
    if (empty($profile['bio'])) $missing[] = 'bio';
    if (empty($profile['title'])) $missing[] = 'title';
    if (empty($profile['hourly_rate'])) $missing[] = 'hourly_rate';
    if (empty($profile['phone'])) $missing[] = 'phone';
    if (count($skills) < 3) $missing[] = 'skills';
    if (count($languages) < 1) $missing[] = 'languages';
    if (empty($profile['portfolio_url'])) $missing[] = 'portfolio_url';
    
    return $missing;
}

/**
 * Calculate success rate percentage
 */
function calculateSuccessRate($total_contracts, $successful_contracts) {
    if (!$total_contracts || $total_contracts == 0) return null;
    return round(($successful_contracts / $total_contracts) * 100, 1);
}
?>