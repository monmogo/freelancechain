<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get query parameters
$category_id = $_GET['category_id'] ?? null;
$budget_min = $_GET['budget_min'] ?? null;
$budget_max = $_GET['budget_max'] ?? null;
$budget_type = $_GET['budget_type'] ?? null;
$experience_level = $_GET['experience_level'] ?? null;
$location_requirement = $_GET['location_requirement'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? 'newest';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 20;
$status = $_GET['status'] ?? 'open';

// Pagination
$pagination = getPaginationData($page, $limit);

try {
    $database = new Database();
    $db = $database->connect();
    
    // Build WHERE clause
    $where_conditions = ["js.status = ?"];
    $params = [$status];
    
    if ($category_id) {
        $where_conditions[] = "js.category_id = ?";
        $params[] = $category_id;
    }
    
    if ($budget_type) {
        $where_conditions[] = "j.budget_type = ?";
        $params[] = $budget_type;
    }
    
    if ($budget_min && $budget_max) {
        $where_conditions[] = "(js.budget_min >= ? AND js.budget_max <= ?)";
        $params[] = $budget_min;
        $params[] = $budget_max;
    } elseif ($budget_min) {
        $where_conditions[] = "js.budget_min >= ?";
        $params[] = $budget_min;
    } elseif ($budget_max) {
        $where_conditions[] = "js.budget_max <= ?";
        $params[] = $budget_max;
    }
    
    if ($experience_level) {
        $where_conditions[] = "j.experience_level = ?";
        $params[] = $experience_level;
    }
    
    if ($location_requirement) {
        $where_conditions[] = "j.location_requirement = ?";
        $params[] = $location_requirement;
    }
    
    // Search in title and description
    if ($search) {
        $where_conditions[] = "(js.title LIKE ? OR j.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Sort options
    $sort_options = [
        'newest' => 'js.published_at DESC',
        'oldest' => 'js.published_at ASC',
        'budget_high' => 'js.budget_max DESC',
        'budget_low' => 'js.budget_min ASC',
        'proposals' => 'js.proposal_count DESC'
    ];
    $order_by = $sort_options[$sort] ?? $sort_options['newest'];
    
    // Get total count
    $count_query = "
        SELECT COUNT(*) as total 
        FROM job_summaries js 
        JOIN jobs j ON js.job_id = j.id 
        WHERE $where_clause
    ";
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get jobs
    $query = "
        SELECT 
            js.*,
            j.description,
            j.requirements,
            j.estimated_hours,
            j.deadline,
            j.experience_level,
            j.project_length,
            j.location_requirement,
            j.timezone_preference,
            j.urgent,
            j.featured,
            (SELECT GROUP_CONCAT(s.name) 
             FROM job_skills jsk 
             JOIN skills s ON jsk.skill_id = s.id 
             WHERE jsk.job_id = j.id) as required_skills
        FROM job_summaries js
        JOIN jobs j ON js.job_id = j.id
        WHERE $where_clause
        ORDER BY j.featured DESC, j.urgent DESC, $order_by
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $pagination['limit'];
    $params[] = $pagination['offset'];
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format jobs data
    foreach ($jobs as &$job) {
        $job['budget_min'] = (float)$job['budget_min'];
        $job['budget_max'] = (float)$job['budget_max'];
        $job['client_rating'] = (float)$job['client_rating'];
        $job['proposal_count'] = (int)$job['proposal_count'];
        $job['view_count'] = (int)$job['view_count'];
        $job['client_total_jobs'] = (int)$job['client_total_jobs'];
        $job['required_skills'] = $job['required_skills'] ? explode(',', $job['required_skills']) : [];
        
        // Time calculations
        if ($job['published_at']) {
            $job['published_ago'] = time() - strtotime($job['published_at']);
        }
        if ($job['deadline']) {
            $job['days_until_deadline'] = ceil((strtotime($job['deadline']) - time()) / 86400);
        }
    }
    
    $total_pages = ceil($total / $pagination['limit']);
    
    jsonResponse([
        'success' => true,
        'data' => $jobs,
        'pagination' => [
            'current_page' => $pagination['page'],
            'total_pages' => $total_pages,
            'total_items' => (int)$total,
            'items_per_page' => $pagination['limit'],
            'has_next' => $pagination['page'] < $total_pages,
            'has_prev' => $pagination['page'] > 1
        ],
        'filters' => [
            'category_id' => $category_id,
            'budget_min' => $budget_min,
            'budget_max' => $budget_max,
            'budget_type' => $budget_type,
            'experience_level' => $experience_level,
            'location_requirement' => $location_requirement,
            'search' => $search,
            'sort' => $sort
        ]
    ]);

} catch (Exception $e) {
    logError("Jobs list failed", ['error' => $e->getMessage()]);
    jsonResponse(['error' => 'Internal server error'], 500);
}

// Helper function - add to functions.php
function getPaginationData($page, $limit = 20) {
    $page = max(1, (int)$page);
    $limit = min(100, max(1, (int)$limit));
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}
?>