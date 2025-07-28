<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'client') {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$job_id = $_GET['id'] ?? null;
if (!$job_id) {
    jsonResponse(['error' => 'Job ID is required'], 400);
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Check if job exists and belongs to user
    $stmt = $db->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ?");
    $stmt->execute([$job_id, $user['user_id']]);
    $job = $stmt->fetch();
    
    if (!$job) {
        jsonResponse(['error' => 'Job not found or access denied'], 404);
    }
    
    if ($job['status'] !== 'draft') {
        jsonResponse(['error' => 'Only draft jobs can be published'], 400);
    }
    
    // Validate required fields for publishing
    $required_for_publish = ['title', 'description', 'category_id', 'budget_type'];
    foreach ($required_for_publish as $field) {
        if (empty($job[$field])) {
            jsonResponse(['error' => "Field '$field' is required for publishing"], 400);
        }
    }
    
    if ($job['budget_type'] === 'fixed' && (empty($job['budget_min']) || empty($job['budget_max']))) {
        jsonResponse(['error' => 'Budget range is required for fixed budget jobs'], 400);
    }
    
    // Check client verification for high-budget jobs
    if ($job['budget_max'] > 5000) {
        $stmt = $db->prepare("SELECT payment_verified FROM client_profiles WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        $client = $stmt->fetch();
        
        if (!$client || !$client['payment_verified']) {
            jsonResponse(['error' => 'Payment verification required for high-budget jobs'], 400);
        }
    }
    
    // Publish job
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 days')); // Jobs expire after 30 days
    
    $stmt = $db->prepare("
        UPDATE jobs SET 
        status = 'open', 
        published_at = NOW(), 
        expires_at = ?,
        updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$expires_at, $job_id]);
    
    // Update job summary
    updateJobSummary($job_id);
    
    // Update client stats
    $stmt = $db->prepare("
        UPDATE client_profiles SET 
        total_jobs_posted = total_jobs_posted + 1,
        last_job_posted = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([$user['user_id']]);
    
    // Log activity
    logActivity($user['user_id'], 'job_published', 'job', $job_id, [
        'title' => $job['title'],
        'budget_max' => $job['budget_max']
    ]);
    
    // Send notification to relevant freelancers (simplified)
    // In production, you might want to implement a more sophisticated matching system
    
    jsonResponse([
        'success' => true,
        'message' => 'Job published successfully',
        'job_id' => $job_id,
        'expires_at' => $expires_at
    ]);

} catch (Exception $e) {
    logError("Job publish failed", [
        'job_id' => $job_id,
        'user_id' => $user['user_id'],
        'error' => $e->getMessage()
    ]);
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>