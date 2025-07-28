<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();

$user = getCurrentUser();
if (!$user || $user['role'] !== 'client') {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
    
    // Only allow deletion of draft jobs or jobs without proposals
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM proposals WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $proposal_count = $stmt->fetch()['count'];
    
    if ($job['status'] !== 'draft' && $proposal_count > 0) {
        jsonResponse(['error' => 'Cannot delete published jobs with proposals'], 400);
    }
    
    // Delete job (CASCADE will handle related records)
    $stmt = $db->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    
    // Log activity
    logActivity($user['user_id'], 'job_deleted', 'job', $job_id, [
        'title' => $job['title']
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Job deleted successfully'
    ]);

} catch (Exception $e) {
    logError("Job deletion failed", [
        'job_id' => $job_id,
        'user_id' => $user['user_id'],
        'error' => $e->getMessage()
    ]);
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>