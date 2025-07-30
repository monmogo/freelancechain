<?php
class Job extends Model {
    protected $table = 'jobs';
    protected $fillable = [
        'client_id', 'title', 'description', 'requirements', 'budget_type',
        'budget_min', 'budget_max', 'currency', 'estimated_hours', 'deadline',
        'category_id', 'experience_level', 'project_length', 'location_requirement',
        'timezone_preference', 'status', 'visibility', 'featured', 'urgent',
        'max_proposals', 'proposal_deadline'
    ];
    
    // Get jobs with filtering, sorting, pagination
    public function getJobsList($filters = [], $sort = 'newest', $page = 1, $limit = 20) {
        $where_conditions = ["js.status = ?"];
        $params = [$filters['status'] ?? 'open'];
        
        // Build WHERE clause based on filters
        if (!empty($filters['category_id'])) {
            $where_conditions[] = "js.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['budget_type'])) {
            $where_conditions[] = "j.budget_type = ?";
            $params[] = $filters['budget_type'];
        }
        
        if (!empty($filters['budget_min']) && !empty($filters['budget_max'])) {
            $where_conditions[] = "(js.budget_min >= ? AND js.budget_max <= ?)";
            $params[] = $filters['budget_min'];
            $params[] = $filters['budget_max'];
        } elseif (!empty($filters['budget_min'])) {
            $where_conditions[] = "js.budget_min >= ?";
            $params[] = $filters['budget_min'];
        } elseif (!empty($filters['budget_max'])) {
            $where_conditions[] = "js.budget_max <= ?";
            $params[] = $filters['budget_max'];
        }
        
        if (!empty($filters['experience_level'])) {
            $where_conditions[] = "j.experience_level = ?";
            $params[] = $filters['experience_level'];
        }
        
        if (!empty($filters['location_requirement'])) {
            $where_conditions[] = "j.location_requirement = ?";
            $params[] = $filters['location_requirement'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = "(js.title LIKE ? OR j.description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
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
        
        // Pagination
        $offset = ($page - 1) * $limit;
        
        // Get total count
        $count_query = "
            SELECT COUNT(*) as total 
            FROM job_summaries js 
            JOIN jobs j ON js.job_id = j.id 
            WHERE $where_clause
        ";
        $stmt = $this->db->prepare($count_query);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get jobs
        $query = "
            SELECT js.*, j.description, j.requirements, j.estimated_hours, j.deadline,
                   j.experience_level, j.project_length, j.location_requirement,
                   j.timezone_preference, j.urgent, j.featured,
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
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'jobs' => $jobs,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit),
            'current_page' => $page,
            'items_per_page' => $limit
        ];
    }
    
    // Get job details with all related info
    public function getJobDetails($id) {
        $stmt = $this->db->prepare("
            SELECT j.*, up.display_name as client_name, up.avatar as client_avatar,
                   c.name as category_name, c.slug as category_slug,
                   co.name as country_name, co.timezone as client_timezone,
                   cp.payment_verified, cp.identity_verified, cp.avg_rating,
                   cp.total_jobs_posted, cp.total_spent, cp.company_name, cp.industry
            FROM jobs j
            LEFT JOIN user_profiles up ON j.client_id = up.user_id
            LEFT JOIN categories c ON j.category_id = c.id
            LEFT JOIN users u ON j.client_id = u.id
            LEFT JOIN countries co ON u.country_id = co.id
            LEFT JOIN client_profiles cp ON j.client_id = cp.user_id
            WHERE j.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get job skills
    public function getJobSkills($jobId) {
        $stmt = $this->db->prepare("
            SELECT s.id, s.name, s.slug, js.required, js.proficiency_required
            FROM job_skills js
            JOIN skills s ON js.skill_id = s.id
            WHERE js.job_id = ?
            ORDER BY js.required DESC, s.popularity_score DESC
        ");
        $stmt->execute([$jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get proposal statistics for job
    public function getProposalStats($jobId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_proposals,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_proposals,
                COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_proposals,
                AVG(proposed_budget) as avg_proposal_budget,
                MIN(proposed_budget) as min_proposal_budget,
                MAX(proposed_budget) as max_proposal_budget
            FROM proposals
            WHERE job_id = ? AND status != 'withdrawn'
        ");
        $stmt->execute([$jobId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_proposals' => (int)($stats['total_proposals'] ?? 0),
            'pending_proposals' => (int)($stats['pending_proposals'] ?? 0),
            'accepted_proposals' => (int)($stats['accepted_proposals'] ?? 0),
            'avg_proposal_budget' => (float)($stats['avg_proposal_budget'] ?? 0),
            'min_proposal_budget' => (float)($stats['min_proposal_budget'] ?? 0),
            'max_proposal_budget' => (float)($stats['max_proposal_budget'] ?? 0)
        ];
    }
    
    // Get recent proposals (for job owner)
    public function getRecentProposals($jobId, $limit = 5) {
        $stmt = $this->db->prepare("
            SELECT p.id, p.proposed_budget, p.proposed_timeline, p.submitted_at,
                   up.display_name as freelancer_name, up.avatar as freelancer_avatar
            FROM proposals p
            JOIN user_profiles up ON p.freelancer_id = up.user_id
            WHERE p.job_id = ? AND p.status = 'pending'
            ORDER BY p.submitted_at DESC
            LIMIT ?
        ");
        $stmt->execute([$jobId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add skills to job
    public function addSkills($jobId, $skills) {
        // Delete existing skills
        $stmt = $this->db->prepare("DELETE FROM job_skills WHERE job_id = ?");
        $stmt->execute([$jobId]);
        
        if (empty($skills)) return true;
        
        // Add new skills
        $stmt = $this->db->prepare("
            INSERT INTO job_skills (job_id, skill_id, required, proficiency_required) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($skills as $skill) {
            $stmt->execute([
                $jobId,
                $skill['skill_id'],
                $skill['required'] ?? 1,
                $skill['proficiency_required'] ?? 'intermediate'
            ]);
        }
        
        return true;
    }
    
    // Publish job
    public function publishJob($id) {
        $job = $this->find($id);
        
        if (!$job) {
            throw new Exception('Job not found');
        }
        
        if ($job['status'] !== 'draft') {
            throw new Exception('Only draft jobs can be published');
        }
        
        // Validate required fields
        $required = ['title', 'description', 'category_id', 'budget_type'];
        foreach ($required as $field) {
            if (empty($job[$field])) {
                throw new Exception("Field '$field' is required for publishing");
            }
        }
        
        if ($job['budget_type'] === 'fixed' && (empty($job['budget_min']) || empty($job['budget_max']))) {
            throw new Exception('Budget range is required for fixed budget jobs');
        }
        
        // Check client verification for high-budget jobs
        if ($job['budget_max'] > 5000) {
            $stmt = $this->db->prepare("SELECT payment_verified FROM client_profiles WHERE user_id = ?");
            $stmt->execute([$job['client_id']]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$client || !$client['payment_verified']) {
                throw new Exception('Payment verification required for high-budget jobs');
            }
        }
        
        // Publish job
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $this->db->prepare("
            UPDATE jobs SET 
            status = 'open', 
            published_at = NOW(), 
            expires_at = ?,
            updated_at = NOW()
            WHERE id = ?
        ");
        $success = $stmt->execute([$expires_at, $id]);
        
        if ($success) {
            // Update client profile
            $stmt = $this->db->prepare("
                UPDATE client_profiles SET 
                total_jobs_posted = total_jobs_posted + 1,
                last_job_posted = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$job['client_id']]);
            
            // Update job summary
            updateJobSummary($id);
        }
        
        return $success;
    }
    
    // Check if user can modify job
    public function canModify($jobId, $userId) {
        $stmt = $this->db->prepare("SELECT client_id, status FROM jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job || $job['client_id'] != $userId) {
            return false;
        }
        
        if (in_array($job['status'], ['completed', 'cancelled'])) {
            return false;
        }
        
        return true;
    }
    
    // Check if job can be deleted
    public function canDelete($jobId, $userId) {
        $job = $this->find($jobId);
        
        if (!$job || $job['client_id'] != $userId) {
            return false;
        }
        
        // Check if has proposals
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM proposals WHERE job_id = ?");
        $stmt->execute([$jobId]);
        $proposalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($job['status'] !== 'draft' && $proposalCount > 0) {
            return false;
        }
        
        return true;
    }
    
    // Check what fields can be updated
    public function getUpdatableFields($jobId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM proposals WHERE job_id = ? AND status = 'accepted'");
        $stmt->execute([$jobId]);
        $hasAcceptedProposals = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($hasAcceptedProposals) {
            return ['description', 'requirements'];
        }
        
        return [
            'title', 'description', 'requirements', 'budget_min', 'budget_max',
            'estimated_hours', 'deadline', 'experience_level', 'project_length',
            'location_requirement', 'timezone_preference', 'max_proposals', 'proposal_deadline'
        ];
    }
    
    // Check if user can apply to job
    public function canUserApply($jobId, $userId) {
        $job = $this->find($jobId);
        
        if (!$job) {
            return ['can_apply' => false, 'message' => 'Job not found'];
        }
        
        if ($job['client_id'] == $userId) {
            return ['can_apply' => false, 'message' => 'You cannot apply to your own job'];
        }
        
        if ($job['status'] !== 'open') {
            return ['can_apply' => false, 'message' => 'This job is no longer accepting proposals'];
        }
        
        // Check if already applied
        $stmt = $this->db->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
        $stmt->execute([$jobId, $userId]);
        if ($stmt->fetch()) {
            return ['can_apply' => false, 'message' => 'You have already submitted a proposal'];
        }
        
        if ($job['proposal_count'] >= $job['max_proposals']) {
            return ['can_apply' => false, 'message' => 'Maximum number of proposals reached'];
        }
        
        if ($job['proposal_deadline'] && strtotime($job['proposal_deadline']) < time()) {
            return ['can_apply' => false, 'message' => 'Proposal deadline has passed'];
        }
        
        return ['can_apply' => true, 'message' => ''];
    }
    
    // Increment view count
    public function incrementViewCount($jobId) {
        $stmt = $this->db->prepare("UPDATE jobs SET view_count = view_count + 1 WHERE id = ?");
        return $stmt->execute([$jobId]);
    }
}