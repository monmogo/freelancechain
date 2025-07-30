<?php
class CategoryController extends Controller {
    private $categoryModel;
    
    public function __construct() {
        parent::__construct();
        $this->categoryModel = new Category();
    }
    
    // GET /api/categories - List all categories
    public function index() {
        try {
            $view = $_GET['view'] ?? 'all'; // all, hierarchy, popular
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            
            switch ($view) {
                case 'hierarchy':
                    $categories = $this->categoryModel->getCategoryHierarchy();
                    break;
                    
                case 'popular':
                    $categories = $this->categoryModel->getPopularCategories($limit);
                    break;
                    
                default:
                    $categories = $this->categoryModel->getAllWithStats();
                    break;
            }
            
            // Format data
            $categories = $this->formatCategoriesData($categories);
            
            $this->success([
                'categories' => $categories,
                'view' => $view,
                'count' => count($categories)
            ]);
            
        } catch (Exception $e) {
            logError("Categories list failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
    
    // GET /api/categories/{id} - Get category details
    public function show($id) {
        try {
            $category = $this->categoryModel->getCategoryDetails($id);
            
            if (!$category) {
                $this->error('Category not found', 404);
            }
            
            // Get additional data
            $subcategories = $this->categoryModel->getSubcategories($id);
            $recentJobs = $this->categoryModel->getRecentJobs($id, 8);
            $topFreelancers = $this->categoryModel->getTopFreelancers($id, 6);
            $skills = $this->categoryModel->getCategorySkills($id, 15);
            
            // Format category data
            $category = $this->formatCategoryData($category);
            
            // Format subcategories
            $subcategories = $this->formatCategoriesData($subcategories);
            
            // Format recent jobs
            foreach ($recentJobs as &$job) {
                $job['budget_min'] = (float)$job['budget_min'];
                $job['budget_max'] = (float)$job['budget_max'];
                $job['proposal_count'] = (int)$job['proposal_count'];
                $job['published_ago'] = time() - strtotime($job['published_at']);
            }
            
            // Format freelancers
            foreach ($topFreelancers as &$freelancer) {
                $freelancer['avg_rating'] = $freelancer['avg_rating'] ? (float)$freelancer['avg_rating'] : null;
                $freelancer['total_jobs_completed'] = (int)$freelancer['total_jobs_completed'];
                $freelancer['hourly_rate'] = $freelancer['hourly_rate'] ? (float)$freelancer['hourly_rate'] : null;
                $freelancer['success_rate'] = $freelancer['success_rate'] ? (float)$freelancer['success_rate'] : null;
            }
            
            // Format skills
            foreach ($skills as &$skill) {
                $skill['popularity_score'] = (int)$skill['popularity_score'];
                $skill['freelancer_count'] = (int)$skill['freelancer_count'];
            }
            
            $this->success([
                'category' => $category,
                'subcategories' => $subcategories,
                'recent_jobs' => $recentJobs,
                'top_freelancers' => $topFreelancers,
                'popular_skills' => $skills,
                'statistics' => [
                    'total_jobs' => (int)($category['total_jobs'] ?? 0),
                    'active_jobs' => (int)($category['active_jobs'] ?? 0),
                    'current_jobs' => (int)($category['current_jobs'] ?? 0),
                    'open_jobs' => (int)($category['open_jobs'] ?? 0),
                    'featured_jobs' => (int)($category['featured_jobs'] ?? 0),
                    'completed_jobs' => (int)($category['completed_jobs'] ?? 0),
                    'avg_job_budget' => (float)($category['avg_job_budget'] ?? 0),
                    'avg_current_budget' => (float)($category['avg_current_budget'] ?? 0),
                    'total_freelancers' => (int)($category['total_freelancers'] ?? 0),
                    'active_freelancers' => (int)($category['active_freelancers'] ?? 0),
                    'avg_freelancer_rate' => (float)($category['avg_freelancer_rate'] ?? 0),
                    'total_gmv' => (float)($category['total_gmv'] ?? 0),
                    'success_rate' => (float)($category['success_rate'] ?? 0),
                    'avg_project_duration' => (int)($category['avg_project_duration_days'] ?? 0),
                    'budget_range' => [
                        'min_popular' => (float)($category['budget_min_popular'] ?? 0),
                        'max_popular' => (float)($category['budget_max_popular'] ?? 0)
                    ],
                    'hourly_rate_range' => [
                        'min' => (float)($category['hourly_rate_min'] ?? 0),
                        'max' => (float)($category['hourly_rate_max'] ?? 0)
                    ],
                    'last_updated' => $category['last_calculated']
                ]
            ]);
            
        } catch (Exception $e) {
            logError("Category detail failed", [
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->error('Internal server error', 500);
        }
    }
    
    // GET /api/categories/search - Search categories
    public function search() {
        $query = $_GET['q'] ?? $_GET['query'] ?? '';
        $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
        
        if (empty($query) || strlen($query) < 2) {
            $this->error('Search query must be at least 2 characters', 400);
        }
        
        try {
            $categories = $this->categoryModel->searchCategories($query, $limit);
            $categories = $this->formatCategoriesData($categories);
            
            $this->success([
                'categories' => $categories,
                'query' => $query,
                'count' => count($categories)
            ]);
            
        } catch (Exception $e) {
            logError("Category search failed", [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            $this->error('Internal server error', 500);
        }
    }
    
    // GET /api/categories/{id}/jobs - Get jobs in category
    public function getJobs($id) {
        try {
            $category = $this->categoryModel->find($id);
            
            if (!$category) {
                $this->error('Category not found', 404);
            }
            
            // Use JobController logic but filter by category
            $jobModel = new Job();
            
            $filters = [
                'category_id' => $id,
                'status' => $_GET['status'] ?? 'open',
                'budget_min' => $_GET['budget_min'] ?? null,
                'budget_max' => $_GET['budget_max'] ?? null,
                'budget_type' => $_GET['budget_type'] ?? null,
                'experience_level' => $_GET['experience_level'] ?? null,
                'location_requirement' => $_GET['location_requirement'] ?? null,
                'search' => $_GET['search'] ?? null
            ];
            
            $sort = $_GET['sort'] ?? 'newest';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            
            $result = $jobModel->getJobsList($filters, $sort, $page, $limit);
            
            // Format jobs
            foreach ($result['jobs'] as &$job) {
                $job['budget_min'] = (float)$job['budget_min'];
                $job['budget_max'] = (float)$job['budget_max'];
                $job['proposal_count'] = (int)$job['proposal_count'];
                $job['view_count'] = (int)$job['view_count'];
                $job['required_skills'] = $job['required_skills'] ? explode(',', $job['required_skills']) : [];
                
                if ($job['published_at']) {
                    $job['published_ago'] = time() - strtotime($job['published_at']);
                }
                if ($job['deadline']) {
                    $job['days_until_deadline'] = ceil((strtotime($job['deadline']) - time()) / 86400);
                }
            }
            
            $this->success([
                'category' => [
                    'id' => (int)$category['id'],
                    'name' => $category['name'],
                    'slug' => $category['slug']
                ],
                'jobs' => $result['jobs'],
                'pagination' => [
                    'current_page' => $result['current_page'],
                    'total_pages' => $result['total_pages'],
                    'total_items' => $result['total'],
                    'items_per_page' => $result['items_per_page'],
                    'has_next' => $result['current_page'] < $result['total_pages'],
                    'has_prev' => $result['current_page'] > 1
                ],
                'filters' => $filters
            ]);
            
        } catch (Exception $e) {
            logError("Category jobs failed", [
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->error('Internal server error', 500);
        }
    }
    
    // GET /api/categories/{id}/freelancers - Get freelancers in category  
    public function getFreelancers($id) {
        try {
            $category = $this->categoryModel->find($id);
            
            if (!$category) {
                $this->error('Category not found', 404);
            }
            
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            $sort = $_GET['sort'] ?? 'rating'; // rating, completed, rate
            
            $orderBy = [
                'rating' => 'fp.avg_rating DESC, fp.total_jobs_completed DESC',
                'completed' => 'fp.total_jobs_completed DESC, fp.avg_rating DESC',
                'rate' => 'fp.hourly_rate ASC',
                'newest' => 'fp.last_updated DESC'
            ];
            
            $stmt = $this->categoryModel->db->prepare("
                SELECT DISTINCT 
                    fp.user_id, fp.display_name, fp.title, fp.avatar, fp.bio,
                    fp.avg_rating, fp.total_jobs_completed, fp.hourly_rate,
                    fp.success_rate, fp.total_earnings, fp.profile_completeness,
                    fp.country_id, c.name as country_name,
                    GROUP_CONCAT(DISTINCT s.name ORDER BY s.popularity_score DESC LIMIT 5) as top_skills
                FROM freelancer_profiles fp
                JOIN user_skills us ON fp.user_id = us.user_id
                JOIN skills s ON us.skill_id = s.id
                LEFT JOIN countries c ON fp.country_id = c.id
                WHERE s.category_id = ? 
                AND fp.available_for_work = 1 
                AND fp.profile_visibility = 'public'
                GROUP BY fp.user_id
                ORDER BY " . ($orderBy[$sort] ?? $orderBy['rating']) . "
                LIMIT ?
            ");
            $stmt->execute([$id, $limit]);
            $freelancers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format freelancers
            foreach ($freelancers as &$freelancer) {
                $freelancer['avg_rating'] = $freelancer['avg_rating'] ? (float)$freelancer['avg_rating'] : null;
                $freelancer['total_jobs_completed'] = (int)$freelancer['total_jobs_completed'];
                $freelancer['hourly_rate'] = $freelancer['hourly_rate'] ? (float)$freelancer['hourly_rate'] : null;
                $freelancer['success_rate'] = $freelancer['success_rate'] ? (float)$freelancer['success_rate'] : null;
                $freelancer['total_earnings'] = $freelancer['total_earnings'] ? (float)$freelancer['total_earnings'] : null;
                $freelancer['profile_completeness'] = (int)$freelancer['profile_completeness'];
                $freelancer['top_skills'] = $freelancer['top_skills'] ? explode(',', $freelancer['top_skills']) : [];
            }
            
            $this->success([
                'category' => [
                    'id' => (int)$category['id'],
                    'name' => $category['name'],
                    'slug' => $category['slug']
                ],
                'freelancers' => $freelancers,
                'count' => count($freelancers),
                'sort' => $sort
            ]);
            
        } catch (Exception $e) {
            logError("Category freelancers failed", [
                'category_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->error('Internal server error', 500);
        }
    }
    
    // GET /api/categories/stats - Get overall category statistics
    public function getStats() {
        try {
            $stmt = $this->categoryModel->db->prepare("
                SELECT 
                    COUNT(*) as total_categories,
                    COUNT(CASE WHEN parent_id IS NULL THEN 1 END) as parent_categories,
                    COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as subcategories,
                    SUM(COALESCE(cs.total_jobs, 0)) as total_jobs_all_categories,
                    SUM(COALESCE(cs.active_jobs, 0)) as active_jobs_all_categories,
                    AVG(COALESCE(cs.avg_job_budget, 0)) as avg_budget_across_categories,
                    SUM(COALESCE(cs.total_freelancers, 0)) as total_freelancers_all_categories
                FROM categories c
                LEFT JOIN category_stats cs ON c.id = cs.category_id
                WHERE c.status = 'active'
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get top performing categories
            $stmt = $this->categoryModel->db->prepare("
                SELECT c.name, c.slug, cs.active_jobs, cs.total_gmv
                FROM categories c
                JOIN category_stats cs ON c.id = cs.category_id
                WHERE c.status = 'active' AND c.parent_id IS NULL
                AND cs.active_jobs > 0
                ORDER BY cs.total_gmv DESC
                LIMIT 5
            ");
            $stmt->execute();
            $topCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($stats as $key => $value) {
                $stats[$key] = (int)$value;
            }
            
            foreach ($topCategories as &$cat) {
                $cat['active_jobs'] = (int)$cat['active_jobs'];
                $cat['total_gmv'] = (float)$cat['total_gmv'];
            }
            
            $this->success([
                'overall_stats' => $stats,
                'top_performing_categories' => $topCategories
            ]);
            
        } catch (Exception $e) {
            logError("Category stats failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
    
    // Private helper methods
    private function formatCategoriesData($categories) {
        foreach ($categories as &$category) {
            $category = $this->formatCategoryData($category);
        }
        return $categories;
    }
    
    private function formatCategoryData($category) {
        $category['id'] = (int)$category['id'];
        $category['parent_id'] = $category['parent_id'] ? (int)$category['parent_id'] : null;
        $category['sort_order'] = (int)($category['sort_order'] ?? 0);
        $category['total_jobs'] = (int)($category['total_jobs'] ?? 0);
        $category['active_jobs'] = (int)($category['active_jobs'] ?? 0);
        $category['completed_jobs'] = (int)($category['completed_jobs'] ?? 0);
        $category['avg_job_budget'] = (float)($category['avg_job_budget'] ?? 0);
        $category['total_freelancers'] = (int)($category['total_freelancers'] ?? 0);
        $category['active_freelancers'] = (int)($category['active_freelancers'] ?? 0);
        $category['avg_freelancer_rate'] = (float)($category['avg_freelancer_rate'] ?? 0);
        $category['total_gmv'] = (float)($category['total_gmv'] ?? 0);
        $category['success_rate'] = (float)($category['success_rate'] ?? 0);
        $category['hourly_rate_min'] = (float)($category['hourly_rate_min'] ?? 0);
        $category['hourly_rate_max'] = (float)($category['hourly_rate_max'] ?? 0);
        $category['budget_min_popular'] = (float)($category['budget_min_popular'] ?? 0);
        $category['budget_max_popular'] = (float)($category['budget_max_popular'] ?? 0);
        
        return $category;
    }
}