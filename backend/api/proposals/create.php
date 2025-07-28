// api/proposals/create.php
<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();
$user = getCurrentUser();
if (!$user || $user['role'] !== 'freelancer') {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = getJSONInput();

// Validation
if (empty($data['job_id']) || empty($data['cover_letter']) || empty($data['proposed_budget'])) {
    jsonResponse(['error' => 'Missing required fields'], 400);
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Check if job exists and is open
    $stmt = $db->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'open'");
    $stmt->execute([$data['job_id']]);
    $job = $stmt->fetch();
    
    if (!$job) {
        jsonResponse(['error' => 'Job not found or not open'], 404);
    }
    
    // Check if already submitted proposal
    $stmt = $db->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
    $stmt->execute([$data['job_id'], $user['user_id']]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Proposal already submitted'], 400);
    }
    
    // Insert proposal
    $stmt = $db->prepare("
        INSERT INTO proposals (job_id, freelancer_id, cover_letter, proposed_budget, 
                              proposed_timeline, questions, attachments, submitted_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $data['job_id'],
        $user['user_id'],
        sanitizeInput($data['cover_letter']),
        $data['proposed_budget'],
        sanitizeInput($data['proposed_timeline'] ?? null),
        json_encode($data['questions'] ?? []),
        json_encode($data['attachments'] ?? [])
    ]);
    
    $proposal_id = $db->lastInsertId();
    
    // Update job proposal count
    updateJobProposalCount($data['job_id']);
    
    // Send notification to client
    sendNotification(
        $job['client_id'],
        'new_proposal',
        'New Proposal Received',
        'You received a new proposal for your job: ' . $job['title'],
        ['job_id' => $data['job_id'], 'proposal_id' => $proposal_id]
    );
    
    // Log activity
    logActivity($user['user_id'], 'proposal_submitted', 'proposal', $proposal_id);
    
    jsonResponse([
        'success' => true,
        'message' => 'Proposal submitted successfully',
        'proposal_id' => $proposal_id
    ]);

} catch (Exception $e) {
    logError("Proposal creation failed", ['error' => $e->getMessage()]);
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>