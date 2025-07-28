<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$job_id = $_GET['id'] ?? null;
if (!$job_id) {
    jsonResponse(['error' => 'Job ID is required'], 400);
}

try {
    $database = new Database();
    $db = $database->connect();
    
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }
    
    // Get job details with client info
    $stmt = $db->prepare("
        SELECT 
            j.*,
            up.display_name as client_name,
            up.avatar as client_avatar,
            c.name as category_name,
            c.slug as category_slug,
            co.name as country_name,
            co.timezone as client_timezone
        FROM jobs j
        LEFT JOIN user_profiles up ON j.client_id = up.user_id
        LEFT JOIN categories c ON j.category_id = c.id
        LEFT JOIN users u ON j.client_id = u.id
        LEFT JOIN countries co ON u.country_id = co.id
        WHERE j.id = ?
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        jsonResponse(['error' => 'Job not found'], 404);
    }
    
    // Check if job is public or user has access
    $current_user = getCurrentUser();
    $can_view = true;
    
    if ($job['visibility'] === 'private' || $job['status'] === 'draft') {
        if (!$current_user || $current_user['user_id'] != $job['client_id']) {
            $can_view = false;
        }
    }
    
    if (!$can_view) {
        jsonResponse(['error' => 'Access denied'], 403);
    }
    
    // Get job skills
    $skills = [];
    try {
        $stmt = $db->prepare("
            SELECT s.id, s.name, s.slug, js.required, js.proficiency_required
            FROM job_skills js
            JOIN skills s ON js.skill_id = s.id
            WHERE js.job_id = ?
            ORDER BY js.required DESC, s.popularity_score DESC
        ");
        $stmt->execute([$job_id]);
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format skills
        foreach ($skills as &$skill) {
            $skill['required'] = (bool)$skill['required'];
        }
    } catch (Exception $e) {
        // Skills query failed, continue with empty array
        $skills = [];
    }
    
    // Get proposal statistics
    $proposal_stats = [
        'total_proposals' => 0,
        'pending_proposals' => 0,
        'accepted_proposals' => 0,
        'avg_proposal_budget' => 0,
        'min_proposal_budget' => 0,
        'max_proposal_budget' => 0
    ];
    
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_proposals,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_proposals,
                COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_proposals,
                AVG(proposed_budget) as avg_proposal_budget,
                MIN(proposed_budget) as min_proposal_budget,
                MAX(proposed_budget) as max_proposal_budget
            FROM proposals
            WHERE job_id = ? AND status != 'withdrawn'
        ");
        $stmt->execute([$job_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            $proposal_stats = [
                'total_proposals' => (int)($stats['total_proposals'] ?? 0),
                'pending_proposals' => (int)($stats['pending_proposals'] ?? 0),
                'accepted_proposals' => (int)($stats['accepted_proposals'] ?? 0),
                'avg_proposal_budget' => (float)($stats['avg_proposal_budget'] ?? 0),
                'min_proposal_budget' => (float)($stats['min_proposal_budget'] ?? 0),
                'max_proposal_budget' => (float)($stats['max_proposal_budget'] ?? 0)
            ];
        }
    } catch (Exception $e) {
        // Proposal stats query failed, continue with defaults
    }
    
    // Get recent proposals (if user is job owner or admin)
    $recent_proposals = [];
    if ($current_user && ($current_user['user_id'] == $job['client_id'] || $current_user['role'] === 'admin')) {
        try {
            $stmt = $db->prepare("
                SELECT 
                    p.id, p.proposed_budget, p.proposed_timeline, p.submitted_at,
                    up.display_name as freelancer_name,
                    up.avatar as freelancer_avatar
                FROM proposals p
                JOIN user_profiles up ON p.freelancer_id = up.user_id
                WHERE p.job_id = ? AND p.status = 'pending'
                ORDER BY p.submitted_at DESC
                LIMIT 5
            ");
            $stmt->execute([$job_id]);
            $recent_proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format proposals
            foreach ($recent_proposals as &$proposal) {
                $proposal['proposed_budget'] = (float)$proposal['proposed_budget'];
            }
        } catch (Exception $e) {
            // Recent proposals query failed, continue with empty array
            $recent_proposals = [];
        }
    }
    
    // Increment view count (only for non-owners)
    if (!$current_user || $current_user['user_id'] != $job['client_id']) {
        try {
            $stmt = $db->prepare("UPDATE jobs SET view_count = view_count + 1 WHERE id = ?");
            $stmt->execute([$job_id]);
        } catch (Exception $e) {
            // View count update failed, but don't break the response
        }
    }
    
    // Format response data
    $job['budget_min'] = (float)($job['budget_min'] ?? 0);
    $job['budget_max'] = (float)($job['budget_max'] ?? 0);
    $job['proposal_count'] = (int)($job['proposal_count'] ?? 0);
    $job['view_count'] = (int)($job['view_count'] ?? 0);
    $job['max_proposals'] = (int)($job['max_proposals'] ?? 50);
    $job['estimated_hours'] = $job['estimated_hours'] ? (int)$job['estimated_hours'] : null;
    $job['featured'] = (bool)($job['featured'] ?? false);
    $job['urgent'] = (bool)($job['urgent'] ?? false);
    
    // Get client profile info
    $client_info = [
        'payment_verified' => false,
        'identity_verified' => false,
        'avg_rating' => 0,
        'total_jobs_posted' => 0,
        'total_spent' => 0,
        'company_name' => null,
        'industry' => null
    ];
    
    try {
        $stmt = $db->prepare("
            SELECT 
                cp.payment_verified, cp.identity_verified, cp.avg_rating,
                cp.total_jobs_posted, cp.total_spent, cp.company_name, cp.industry
            FROM client_profiles cp
            WHERE cp.user_id = ?
        ");
        $stmt->execute([$job['client_id']]);
        $client_profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client_profile) {
            $client_info = [
                'payment_verified' => (bool)$client_profile['payment_verified'],
                'identity_verified' => (bool)$client_profile['identity_verified'],
                'avg_rating' => (float)($client_profile['avg_rating'] ?? 0),
                'total_jobs_posted' => (int)($client_profile['total_jobs_posted'] ?? 0),
                'total_spent' => (float)($client_profile['total_spent'] ?? 0),
                'company_name' => $client_profile['company_name'],
                'industry' => $client_profile['industry']
            ];
        }
    } catch (Exception $e) {
        // Client profile query failed, continue with defaults
    }
    
    // Merge client info into job
    $job = array_merge($job, $client_info);
    
    // Time calculations
    if ($job['published_at']) {
        $job['published_ago'] = time() - strtotime($job['published_at']);
    }
    if ($job['deadline']) {
        $job['days_until_deadline'] = ceil((strtotime($job['deadline']) - time()) / 86400);
    }
    if ($job['proposal_deadline']) {
        $job['proposal_deadline_remaining'] = strtotime($job['proposal_deadline']) - time();
    }
    
    // Check if current user can apply
    $can_apply = false;
    $apply_message = '';
    
    if ($current_user && $current_user['role'] === 'freelancer' && $current_user['user_id'] != $job['client_id']) {
        if ($job['status'] === 'open') {
            // Check if already applied
            try {
                $stmt = $db->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
                $stmt->execute([$job_id, $current_user['user_id']]);
                if ($stmt->fetch()) {
                    $apply_message = 'You have already submitted a proposal for this job';
                } elseif ($job['proposal_count'] >= $job['max_proposals']) {
                    $apply_message = 'Maximum number of proposals reached';
                } elseif ($job['proposal_deadline'] && strtotime($job['proposal_deadline']) < time()) {
                    $apply_message = 'Proposal deadline has passed';
                } else {
                    $can_apply = true;
                }
            } catch (Exception $e) {
                $apply_message = 'Unable to check application status';
            }
        } else {
            $apply_message = 'This job is no longer accepting proposals';
        }
    } elseif (!$current_user) {
        $apply_message = 'Please login to apply for this job';
    } elseif ($current_user && $current_user['role'] !== 'freelancer') {
        $apply_message = 'Only freelancers can apply for jobs';
    } elseif ($current_user && $current_user['user_id'] == $job['client_id']) {
        $apply_message = 'You cannot apply to your own job';
    }
    
    jsonResponse([
        'success' => true,
        'data' => [
            'job' => $job,
            'skills' => $skills,
            'proposal_stats' => $proposal_stats,
            'recent_proposals' => $recent_proposals,
            'can_apply' => $can_apply,
            'apply_message' => $apply_message
        ]
    ]);

} catch (Exception $e) {
    // Log error to system log instead of file
    error_log("Job detail failed: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>