<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $database = new Database();
    $db = $database->connect();
    
    // Get categories with stats
    $stmt = $db->prepare("
        SELECT 
            c.*,
            cs.total_jobs,
            cs.active_jobs,
            cs.avg_job_budget,
            cs.total_freelancers,
            cs.hourly_rate_min,
            cs.hourly_rate_max
        FROM categories c
        LEFT JOIN category_stats cs ON c.id = cs.category_id
        WHERE c.status = 'active'
        ORDER BY c.sort_order, c.name
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    foreach ($categories as &$category) {
        $category['total_jobs'] = (int)($category['total_jobs'] ?? 0);
        $category['active_jobs'] = (int)($category['active_jobs'] ?? 0);
        $category['avg_job_budget'] = (float)($category['avg_job_budget'] ?? 0);
        $category['total_freelancers'] = (int)($category['total_freelancers'] ?? 0);
        $category['hourly_rate_min'] = (float)($category['hourly_rate_min'] ?? 0);
        $category['hourly_rate_max'] = (float)($category['hourly_rate_max'] ?? 0);
    }
    
    jsonResponse([
        'success' => true,
        'data' => $categories
    ]);

} catch (Exception $e) {
    logError("Categories list failed", ['error' => $e->getMessage()]);
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>