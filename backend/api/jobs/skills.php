<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$category_id = $_GET['category_id'] ?? null;
$search = $_GET['search'] ?? null;
$limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

try {
    $database = new Database();
    $db = $database->connect();
    
    // Build query
    $where_conditions = ["s.status = 'active'"];
    $params = [];
    
    if ($category_id) {
        $where_conditions[] = "s.category_id = ?";
        $params[] = $category_id;
    }
    
    if ($search) {
        $where_conditions[] = "s.name LIKE ?";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $stmt = $db->prepare("
        SELECT s.*, c.name as category_name
        FROM skills s
        JOIN categories c ON s.category_id = c.id
        WHERE $where_clause
        ORDER BY s.popularity_score DESC, s.name
        LIMIT ?
    ");
    
    $params[] = $limit;
    $stmt->execute($params);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    foreach ($skills as &$skill) {
        $skill['popularity_score'] = (int)$skill['popularity_score'];
    }
    
    jsonResponse([
        'success' => true,
        'data' => $skills
    ]);

} catch (Exception $e) {
    logError("Skills list failed", ['error' => $e->getMessage()]);
    jsonResponse(['error' => 'Internal server error'], 500);
}
?>
