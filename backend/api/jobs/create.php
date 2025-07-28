<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();

// Authentication check
$user = getCurrentUser();
if (!$user || $user['role'] !== 'client') {
    jsonResponse(['error' => 'Unauthorized. Only clients can create jobs.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = getJSONInput();

// Validation
$required_fields = ['title', 'description', 'category_id', 'budget_type'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        jsonResponse(['error' => "Field '$field' is required"], 400);
    }
}

// Validate budget type and amounts
if (!in_array($data['budget_type'], ['fixed', 'hourly'])) {
    jsonResponse(['error' => 'Budget type must be "fixed" or "hourly"'], 400);
}

if ($data['budget_type'] === 'fixed') {
    if (empty($data['budget_min']) || empty($data['budget_max'])) {
        jsonResponse(['error' => 'Budget min and max are required for fixed budget'], 400);
    }
    if ($data['budget_min'] > $data['budget_max']) {
        jsonResponse(['error' => 'Budget min cannot be greater than max'], 400);
    }
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Verify category exists
    $stmt = $db->prepare("SELECT id FROM categories WHERE id = ? AND status = 'active'");
    $stmt->execute([$data['category_id']]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'Invalid category'], 400);
    }
    
    // Insert job
    $stmt = $db->prepare("
        INSERT INTO jobs (
            client_id, title, description, requirements, budget_type, 
            budget_min, budget_max, currency, estimated_hours, deadline,
            category_id, experience_level, project_length, location_requirement,
            timezone_preference, status, visibility, featured, urgent,
            max_proposals, proposal_deadline, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $deadline = !empty($data['deadline']) ? date('Y-m-d', strtotime($data['deadline'])) : null;
    $proposal_deadline = !empty($data['proposal_deadline']) ? date('Y-m-d H:i:s', strtotime($data['proposal_deadline'])) : null;
    
    $stmt->execute([
        $user['user_id'],
        sanitizeInput($data['title']),
        sanitizeInput($data['description']),
        sanitizeInput($data['requirements'] ?? null),
        $data['budget_type'],
        $data['budget_min'] ?? null,
        $data['budget_max'] ?? null,
        $data['currency'] ?? 'USD',
        $data['estimated_hours'] ?? null,
        $deadline,
        $data['category_id'],
        $data['experience_level'] ?? 'intermediate',
        $data['project_length'] ?? 'medium',
        $data['location_requirement'] ?? 'remote',
        sanitizeInput($data['timezone_preference'] ?? null),
        'draft', // Always start as draft
        $data['visibility'] ?? 'public',
        $data['featured'] ?? 0,
        $data['urgent'] ?? 0,
        $data['max_proposals'] ?? 50,
        $proposal_deadline
    ]);
    
    $job_id = $db->lastInsertId();
    
    // Add job skills if provided
    if (!empty($data['skills']) && is_array($data['skills'])) {
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
    logActivity($user['user_id'], 'job_created', 'job', $job_id, [
        'title' => $data['title'],
        'budget_type' => $data['budget_type']
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Job created successfully',
        'job_id' => $job_id,
        'status' => 'draft'
    ]);

} catch (Exception $e) {
    logError("Job creation failed", [
        'user_id' => $user['user_id'],
        'error' => $e->getMessage(),
        'data' => $data
    ]);
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>