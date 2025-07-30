<?php
class Category extends Model {
    protected $table = 'categories';
    protected $fillable = [
        'name', 'slug', 'icon', 'color', 'parent_id', 
        'sort_order', 'status'
    ];
    
    // Get all categories with statistics
    public function getAllWithStats() {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                cs.total_jobs,
                cs.active_jobs,
                cs.completed_jobs,
                cs.avg_job_budget,
                cs.total_freelancers,
                cs.active_freelancers,
                cs.avg_freelancer_rate,
                cs.total_gmv,
                cs.avg_project_duration_days,
                cs.success_rate,
                cs.budget_min_popular,
                cs.budget_max_popular,
                cs.hourly_rate_min,
                cs.hourly_rate_max,
                cs.last_calculated
            FROM categories c
            LEFT JOIN category_stats cs ON c.id = cs.category_id
            WHERE c.status = 'active'
            ORDER BY c.sort_order, c.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get category by slug
    public function findBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ? AND status = 'active'");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get category details with full statistics
    public function getCategoryDetails($id) {
        $stmt = $this->db->prepare("
            SELECT 
                c.*,
                cs.*,
                COUNT(DISTINCT j.id) as current_jobs,
                COUNT(DISTINCT CASE WHEN j.status = 'open' THEN j.id END) as open_jobs,
                COUNT(DISTINCT CASE WHEN j.featured = 1 THEN j.id END) as featured_jobs,
                AVG(j.budget_max) as avg_current_budget
            FROM categories c
            LEFT JOIN category_stats cs ON c.id = cs.category_id
            LEFT JOIN jobs j ON c.id = j.category_id AND j.published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHERE c.id = ? AND c.status = 'active'
            GROUP BY c.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get popular categories (by job count)
    public function getPopularCategories($limit = 6) {
        $stmt = $this->db->prepare("
            SELECT c.*, cs.total_jobs, cs.active_jobs, cs.avg_job_budget
            FROM categories c
            LEFT JOIN category_stats cs ON c.id = cs.category_id
            WHERE c.status = 'active' AND c.parent_id IS NULL
            ORDER BY cs.active_jobs DESC, cs.total_jobs DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get subcategories for a parent category
    public function getSubcategories($parentId) {
        $stmt = $this->db->prepare("
            SELECT c.*, cs.total_jobs, cs.active_jobs, cs.avg_job_budget
            FROM categories c
            LEFT JOIN category_stats cs ON c.id = cs.category_id
            WHERE c.parent_id = ? AND c.status = 'active'
            ORDER BY c.sort_order, c.name
        ");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get category hierarchy (parent -> children)
    public function getCategoryHierarchy() {
        // Get parent categories
        $stmt = $this->db->prepare("
            SELECT c.*, cs.total_jobs, cs.active_jobs, cs.avg_job_budget
            FROM categories c
            LEFT JOIN category_stats cs ON c.id = cs.category_id
            WHERE c.parent_id IS NULL AND c.status = 'active'
            ORDER BY c.sort_order, c.name
        ");
        $stmt->execute();
        $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get children for each parent
        foreach ($parents as &$parent) {
            $parent['subcategories'] = $this->getSubcategories($parent['id']);
        }
        
        return $parents;
    }
    
    // Get recent jobs for category
    public function getRecentJobs($categoryId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT js.*, j.published_at
            FROM job_summaries js
            JOIN jobs j ON js.job_id = j.id
            WHERE js.category_id = ? AND js.status = 'open'
            ORDER BY j.published_at DESC
            LIMIT ?
        ");
        $stmt->execute([$categoryId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get top freelancers in category
    public function getTopFreelancers($categoryId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT 
                fp.user_id, fp.display_name, fp.title, fp.avatar,
                fp.avg_rating, fp.total_jobs_completed, fp.hourly_rate,
                fp.success_rate
            FROM freelancer_profiles fp
            JOIN user_skills us ON fp.user_id = us.user_id
            JOIN skills s ON us.skill_id = s.id
            WHERE s.category_id = ? 
            AND fp.available_for_work = 1 
            AND fp.profile_visibility = 'public'
            AND fp.total_jobs_completed > 0
            ORDER BY fp.avg_rating DESC, fp.total_jobs_completed DESC
            LIMIT ?
        ");
        $stmt->execute([$categoryId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get skills for category
    public function getCategorySkills($categoryId, $limit = 20) {
        $stmt = $this->db->prepare("
            SELECT s.*, COUNT(us.user_id) as freelancer_count
            FROM skills s
            LEFT JOIN user_skills us ON s.id = us.skill_id
            WHERE s.category_id = ? AND s.status = 'active'
            GROUP BY s.id
            ORDER BY s.popularity_score DESC, freelancer_count DESC
            LIMIT ?
        ");
        $stmt->execute([$categoryId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search categories
    public function searchCategories($query, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT c.*, cs.total_jobs, cs.active_jobs
            FROM categories c
            LEFT JOIN category_stats cs ON c.id = cs.category_id
            WHERE c.status = 'active' 
            AND (c.name LIKE ? OR c.slug LIKE ?)
            ORDER BY cs.active_jobs DESC, c.name
            LIMIT ?
        ");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}