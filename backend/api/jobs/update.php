<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'client') {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$job_id = $_GET['id'] ?? null;
if (!$job_id) {
    jsonResponse(['error' => 'Job ID is required'], 400);
}

$data = getJSONInput();

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
    
    // Check if job can be updated
    if ($job['status'] === 'completed' || $job['status'] === 'cancelled') {
        jsonResponse(['error' => 'Cannot update completed or cancelled jobs'], 400);
    }
    
    // If job has accepted proposals, only allow limited updates
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM proposals WHERE job_id = ? AND status = 'accepted'");
    $stmt->execute([$job_id]);
    $has_accepted_proposals = $stmt->fetch()['count'] > 0;
    
    if ($has_accepted_proposals) {
        $allowed_fields = ['description', 'requirements'];
        $update_fields = array_intersect_key($data, array_flip($allowed_fields));
        if (empty($update_fields)) {
            jsonResponse(['error' => 'No updatable fields provided'], 400);
        }
    } else {
        // Full update allowed
        $allowed_fields = [
            'title', 'description', 'requirements', 'budget_min', 'budget_max',
            'estimated_hours', 'deadline', 'experience_level', 'project_length',
            'location_requirement', 'timezone_preference', 'max_proposals', 'proposal_deadline'
        ];
        $update_fields = array_intersect_key($data, array_flip($allowed_fields));
    }
    
    if (empty($update_fields)) {
        jsonResponse(['error' => 'No valid fields to update'], 400);
    }
    
    // Build update query
    $set_clauses = [];
    $params = [];
    
    foreach ($update_fields as $field => $value) {
        $set_clauses[] = "$field = ?";
        if (in_array($field, ['title', 'description', 'requirements', 'timezone_preference'])) {
            $params[] = sanitizeInput($value);
        } elseif (in_array($field, ['deadline', 'proposal_deadline'])) {
            $params[] = $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
        } else {
            $params[] = $value;
        }
    }
    
    $set_clauses[] = "updated_at = NOW()";
    $params[] = $job_id;
    
    $query = "UPDATE jobs SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    // Update skills if provided and allowed
    if (!$has_accepted_proposals && isset($data['skills']) && is_array($data['skills'])) {
        // Delete existing skills
        $stmt = $db->prepare("DELETE FROM job_skills WHERE job_id = ?");
        $stmt->execute([$job_id]);
        
        // Add new skills
        $skill_stmt = $db->prepare("
            INSERT INTO job_skills (job_id, skill_id, required, proficiency_required) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($data['skills'] as $skill) {
            $skill_stmt->execute([
                $job_id,
                $skill['skill_id'],
                $skill['required'] ?? 1,
                $skill['proficiency_required'] ?? 'intermediate'
            ]);
        }
    }
    
    // Update job summary
    updateJobSummary($job_id);
    
    // Log activity
    logActivity($user['user_id'], 'job_updated', 'job', $job_id, [
        'updated_fields' => array_keys($update_fields)
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Job updated successfully'
    ]);

} catch (Exception $e) {
    logError("Job update failed", [
        'job_id' => $job_id,
        'user_id' => $user['user_id'],
        'error' => $e->getMessage()
    ]);
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>