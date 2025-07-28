<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get current user from token
$current_user = getCurrentUser();
if (!$current_user) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Get user profile with stats
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.role, u.status, u.country_id, u.preferred_language,
               up.first_name, up.last_name, up.display_name, up.avatar, up.bio,
               up.title, up.hourly_rate, up.hourly_rate_currency, up.experience_years,
               up.phone, up.website, up.linkedin, up.github, up.portfolio_url,
               up.available_for_work, up.profile_visibility,
               us.total_jobs_completed, us.total_earnings, us.avg_rating, us.total_reviews,
               c.name as country_name
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN user_stats us ON u.id = us.user_id
        LEFT JOIN countries c ON u.country_id = c.id
        WHERE u.id = ?
    ");
    $stmt->execute([$current_user['user_id']]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        jsonResponse(['error' => 'Profile not found'], 404);
    }
    
    // Get user skills
    $stmt = $db->prepare("
        SELECT s.id, s.name, s.slug, us.proficiency_level, us.years_experience
        FROM user_skills us
        JOIN skills s ON us.skill_id = s.id
        WHERE us.user_id = ?
        ORDER BY s.popularity_score DESC
    ");
    $stmt->execute([$current_user['user_id']]);
    $skills = $stmt->fetchAll();
    
    $profile['skills'] = $skills;
    
    jsonResponse([
        'success' => true,
        'profile' => $profile
    ]);
    
} catch (PDOException $e) {
    error_log("Profile error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to load profile'], 500);
}
?>