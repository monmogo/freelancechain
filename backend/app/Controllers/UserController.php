<?php
class UserController extends Controller {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }
    
    public function getProfile() {
        $this->requireAuth();
        
        try {
            // Get comprehensive user profile
            $stmt = $this->userModel->db->prepare("
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
                       us.response_rate_percentage, us.total_contracts, us.successful_contracts,
                       
                       c.name as country_name, c.code as country_code, c.currency_code,
                       c.currency_symbol, c.timezone as country_timezone
                       
                FROM users u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                LEFT JOIN user_stats us ON u.id = us.user_id
                LEFT JOIN countries c ON u.country_id = c.id
                WHERE u.id = ?
            ");
            $stmt->execute([$this->user['user_id']]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profile) {
                $this->error('Profile not found', 404);
            }
            
            // Get user skills
            $stmt = $this->userModel->db->prepare("
                SELECT s.id, s.name, s.slug, usk.proficiency_level, usk.years_experience,
                       usk.verified, c.name as category_name, c.slug as category_slug,
                       s.popularity_score
                FROM user_skills usk
                JOIN skills s ON usk.skill_id = s.id
                JOIN categories c ON s.category_id = c.id
                WHERE usk.user_id = ?
                ORDER BY s.popularity_score DESC
            ");
            $stmt->execute([$this->user['user_id']]);
            $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get user languages
            $stmt = $this->userModel->db->prepare("
                SELECT l.code, l.name, l.native_name, ul.proficiency
                FROM user_languages ul
                JOIN languages l ON ul.language_code = l.code
                WHERE ul.user_id = ?
                ORDER BY ul.proficiency DESC
            ");
            $stmt->execute([$this->user['user_id']]);
            $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate profile completeness
            $completeness = $this->calculateProfileCompleteness($profile, $skills, $languages);
            
            // Format response
            $this->success([
                'profile' => [
                    'id' => (int)$profile['id'],
                    'email' => $profile['email'],
                    'role' => $profile['role'],
                    'status' => $profile['status'],
                    'first_name' => $profile['first_name'],
                    'last_name' => $profile['last_name'],
                    'display_name' => $profile['display_name'],
                    'avatar' => $profile['avatar'],
                    'bio' => $profile['bio'],
                    'title' => $profile['title'],
                    'hourly_rate' => $profile['hourly_rate'] ? (float)$profile['hourly_rate'] : null,
                    'experience_years' => $profile['experience_years'] ? (int)$profile['experience_years'] : null,
                    'available_for_work' => (bool)$profile['available_for_work'],
                    'stats' => [
                        'total_jobs_posted' => (int)($profile['total_jobs_posted'] ?? 0),
                        'total_jobs_completed' => (int)($profile['total_jobs_completed'] ?? 0),
                        'total_earnings' => (float)($profile['total_earnings'] ?? 0),
                        'total_spent' => (float)($profile['total_spent'] ?? 0),
                        'avg_rating' => $profile['avg_rating'] ? round((float)$profile['avg_rating'], 2) : null,
                        'total_reviews' => (int)($profile['total_reviews'] ?? 0),
                        'success_rate' => $this->calculateSuccessRate($profile['total_contracts'], $profile['successful_contracts'])
                    ],
                    'skills' => $skills,
                    'languages' => $languages,
                    'profile_completeness' => $completeness,
                    'country' => [
                        'name' => $profile['country_name'],
                        'code' => $profile['country_code']
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            logError("Get profile failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
    
    public function updateProfile() {
        $this->requireAuth();
        
        $data = $this->getRequestData();
        
        try {
            $this->userModel->db->beginTransaction();
            
            // Update user table fields
            $userFields = ['country_id', 'preferred_language', 'timezone', 'currency_preference'];
            $userUpdates = [];
            foreach ($userFields as $field) {
                if (isset($data[$field])) {
                    $userUpdates[$field] = $data[$field];
                }
            }
            
            if (!empty($userUpdates)) {
                $this->userModel->update($this->user['user_id'], $userUpdates);
            }
            
            // Update profile fields
            $profileFields = [
                'first_name', 'last_name', 'display_name', 'bio', 'title',
                'hourly_rate', 'experience_years', 'phone', 'website', 
                'linkedin', 'github', 'portfolio_url', 'available_for_work',
                'city', 'state', 'postal_code'
            ];
            
            $profileUpdates = [];
            foreach ($profileFields as $field) {
                if (isset($data[$field])) {
                    $profileUpdates[] = "$field = ?";
                    $profileValues[] = $data[$field];
                }
            }
            
            if (!empty($profileUpdates)) {
                $profileValues[] = $this->user['user_id'];
                $stmt = $this->userModel->db->prepare("
                    UPDATE user_profiles SET " . implode(', ', $profileUpdates) . ", updated_at = NOW() 
                    WHERE user_id = ?
                ");
                $stmt->execute($profileValues);
            }
            
            // Update skills if provided
            if (isset($data['skills']) && is_array($data['skills'])) {
                $stmt = $this->userModel->db->prepare("DELETE FROM user_skills WHERE user_id = ?");
                $stmt->execute([$this->user['user_id']]);
                
                $stmt = $this->userModel->db->prepare("
                    INSERT INTO user_skills (user_id, skill_id, proficiency_level, years_experience) 
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($data['skills'] as $skill) {
                    $stmt->execute([
                        $this->user['user_id'],
                        $skill['skill_id'],
                        $skill['proficiency_level'] ?? 'intermediate',
                        $skill['years_experience'] ?? null
                    ]);
                }
            }
            
            $this->userModel->db->commit();
            
            // Update derived profiles
            updateUserStats($this->user['user_id']);
            if ($this->user['role'] === 'freelancer') {
                updateFreelancerProfile($this->user['user_id']);
            }
            
            // Log activity
            logActivity($this->user['user_id'], 'profile_update', 'user_profile', $this->user['user_id']);
            
            $this->success(null, 'Profile updated successfully');
            
        } catch (Exception $e) {
            $this->userModel->db->rollBack();
            logError("Profile update failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
    
    private function calculateProfileCompleteness($profile, $skills, $languages) {
        $completeness = 0;
        
        if (!empty($profile['display_name'])) $completeness += 10;
        if (!empty($profile['bio'])) $completeness += 15;
        if (!empty($profile['avatar'])) $completeness += 10;
        if (!empty($profile['title'])) $completeness += 10;
        if (!empty($profile['hourly_rate'])) $completeness += 10;
        if (count($skills) >= 3) $completeness += 15;
        if (count($languages) >= 1) $completeness += 5;
        
        $socialLinks = [$profile['website'], $profile['linkedin'], $profile['github'], $profile['portfolio_url']];
        $socialCount = count(array_filter($socialLinks));
        $completeness += min(25, $socialCount * 6);
        
        return min(100, $completeness);
    }
    
    private function calculateSuccessRate($total, $successful) {
        if (!$total || $total == 0) return null;
        return round(($successful / $total) * 100, 1);
    }
}