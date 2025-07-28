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
    
    // Get job details with client info
    $stmt = $db->prepare("
        SELECT 
            j.*,
            js.client_name,
            js.client_avatar,
            js.client_rating,
            js.client_total_jobs,
            js.category_name,
            js.category_slug,
            c.country_name,
            c.timezone as client_timezone,
            cp.company_name,
            cp.industry,
            cp.total_spent as client_total_spent,
            cp.payment_verified,
            cp.identity_verified
        FROM jobs j
        JOIN job_summaries js ON j.id = js.job_id
        LEFT JOIN users u ON j.client_id = u.id
        LEFT JOIN countries c ON u.country_id = c.id
        LEFT JOIN client_profiles cp ON j.client_id = cp.user_id
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
    $stmt = $db->prepare("
        SELECT s.id, s.name, s.slug, js.required, js.proficiency_required
        FROM job_skills js
        JOIN skills s ON js.skill_id = s.id
        WHERE js.job_id = ?
        ORDER BY js.required DESC, s.popularity_score DESC
    ");
    $stmt->execute([$job_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get proposal statistics
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
    $proposal_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent proposals (if user is job owner or admin)
    $recent_proposals = [];
    if ($current_user && ($current_user['user_id'] == $job['client_id'] || $current_user['role'] === 'admin')) {
        $stmt = $db->prepare("
            SELECT 
                p.id, p.proposed_budget, p.proposed_timeline, p.submitted_at,
                fp.display_name as freelancer_name,
                fp.avatar as freelancer_avatar,
                fp.title as freelancer_title,
                fp.avg_rating as freelancer_rating,
                fp.total_jobs_completed,
                fp.success_rate
            FROM proposals p
            JOIN freelancer_profiles fp ON p.freelancer_id = fp.user_id
            WHERE p.job_id = ? AND p.status = 'pending'
            ORDER BY p.submitted_at DESC
            LIMIT 5
        ");
        $stmt->execute([$job_id]);
        $recent_proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Increment view count (only for non-owners)
    if (!$current_user || $current_user['user_id'] != $job['client_id']) {
        $stmt = $db->prepare("UPDATE jobs SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$job_id]);
    }
    
    // Format response data
    $job['budget_min'] = (float)$job['budget_min'];
    $job['budget_max'] = (float)$job['budget_max'];
    $job['client_rating'] = (float)$job['client_rating'];
    $job['client_total_jobs'] = (int)$job['client_total_jobs'];
    $job['client_total_spent'] = (float)$job['client_total_spent'];
    $job['proposal_count'] = (int)$job['proposal_count'];
    $job['view_count'] = (int)$job['view_count'];
    $job['max_proposals'] = (int)$job['max_proposals'];
    $job['estimated_hours'] = (int)$job['estimated_hours'];
    $job['featured'] = (bool)$job['featured'];
    $job['urgent'] = (bool)$job['urgent'];
    $job['payment_verified'] = (bool)$job['payment_verified'];
    $job['identity_verified'] = (bool)$job['identity_verified'];
    
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
        } else {
            $apply_message = 'This job is no longer accepting proposals';
        }
    } elseif (!$current_user) {
        $apply_message = 'Please login to apply for this job';
    } elseif ($current_user['role'] !== 'freelancer') {
        $apply_message = 'Only freelancers can apply for jobs';
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
    logError("Job detail failed", ['job_id' => $job_id, 'error' => $e->getMessage()]);
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>
