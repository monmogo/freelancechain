<?php
class JobController extends Controller {
    private $jobModel;
    private $categoryModel;
    private $skillModel;
    
    public function __construct() {
        parent::__construct();
        $this->jobModel = new Job();
        $this->categoryModel = new Category();
        $this->skillModel = new Skill();
    }
    
    // GET /api/jobs - List jobs with filtering
    public function index() {
        $filters = [
            'category_id' => $_GET['category_id'] ?? null,
            'budget_min' => $_GET['budget_min'] ?? null,
            'budget_max' => $_GET['budget_max'] ?? null,
            'budget_type' => $_GET['budget_type'] ?? null,
            'experience_level' => $_GET['experience_level'] ?? null,
            'location_requirement' => $_GET['location_requirement'] ?? null,
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? 'open'
        ];
        
        $sort = $_GET['sort'] ?? 'newest';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        
        try {
            $result = $this->jobModel->getJobsList($filters, $sort, $page, $limit);
            
            // Format jobs data
            foreach ($result['jobs'] as &$job) {
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
            
            $this->success([
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
            logError("Jobs list failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
    
    // GET /api/jobs/{id} - Get job details
    public function show($id) {
        try {
            $job = $this->jobModel->getJobDetails($id);
            
            if (!$job) {
                $this->error('Job not found', 404);
            }
            
            // Check access permissions
            if (($job['visibility'] === 'private' || $job['status'] === 'draft') && 
                (!$this->user || $this->user['user_id'] != $job['client_id'])) {
                $this->error('Access denied', 403);
            }
            
            // Get related data
            $skills = $this->jobModel->getJobSkills($id);
            $proposalStats = $this->jobModel->getProposalStats($id);
            
            // Get recent proposals (only for job owner or admin)
            $recentProposals = [];
            if ($this->user && ($this->user['user_id'] == $job['client_id'] || $this->user['role'] === 'admin')) {
                $recentProposals = $this->jobModel->getRecentProposals($id);
            }
            
            // Format job data
            $job['budget_min'] = (float)($job['budget_min'] ?? 0);
            $job['budget_max'] = (float)($job['budget_max'] ?? 0);
            $job['proposal_count'] = (int)($job['proposal_count'] ?? 0);
            $job['view_count'] = (int)($job['view_count'] ?? 0);
            $job['max_proposals'] = (int)($job['max_proposals'] ?? 50);
            $job['estimated_hours'] = $job['estimated_hours'] ? (int)$job['estimated_hours'] : null;
            $job['featured'] = (bool)($job['featured'] ?? false);
            $job['urgent'] = (bool)($job['urgent'] ?? false);
            $job['payment_verified'] = (bool)($job['payment_verified'] ?? false);
            $job['identity_verified'] = (bool)($job['identity_verified'] ?? false);
            
            // Format skills
            foreach ($skills as &$skill) {
                $skill['required'] = (bool)$skill['required'];
            }
            
            // Time calculations
            if ($job['published_at']) {
                $job['published_ago'] = time() - strtotime($job['published_at']);
            }
            if ($job['deadline']) {
                $job['days_until_deadline'] = ceil((strtotime($job['deadline']) - time()) / 86400);
            }
            if ($job['proposal_deadline']) {
                $job['proposal_deadline_remaining'] = strtotime($job['proposal_deadline']) - time();
            }
            
            // Check if user can apply
            $canApply = ['can_apply' => false, 'message' => 'Please login to apply'];
            if ($this->user && $this->user['role'] === 'freelancer') {
                $canApply = $this->jobModel->canUserApply($id, $this->user['user_id']);
            }
            
            // Increment view count (only for non-owners)
            if (!$this->user || $this->user['user_id'] != $job['client_id']) {
                $this->jobModel->incrementViewCount($id);
            }
            
            $this->success([
                'job' => $job,
                'skills' => $skills,
                'proposal_stats' => $proposalStats,
                'recent_proposals' => $recentProposals,
                'can_apply' => $canApply['can_apply'],
                'apply_message' => $canApply['message']
            ]);
            
        } catch (Exception $e) {
            logError("Job detail failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
    
    // POST /api/jobs - Create job
    public function create() {
        $this->requireAuth('client');
        
        $data = $this->getRequestData();
        
        // Validation
        $this->validate($data, [
            'title' => 'required|min:10|max:255',
            'description' => 'required|min:50',
            'category_id' => 'required',
            'budget_type' => 'required'
        ]);
        
        // Additional validation
        if (!in_array($data['budget_type'], ['fixed', 'hourly'])) {
            $this->error('Budget type must be "fixed" or "hourly"');
        }
        
        if ($data['budget_type'] === 'fixed') {
            if (empty($data['budget_min']) || empty($data['budget_max'])) {
                $this->error('Budget min and max are required for fixed budget');
            }
            if ($data['budget_min'] > $data['budget_max']) {
                $this->error('Budget min cannot be greater than max');
            }
        }
        
        try {
            // Verify category exists
            if (!$this->categoryModel->find($data['category_id'])) {
                $this->error('Invalid category', 400);
            }
            
            // Prepare job data
            $jobData = [
                'client_id' => $this->user['user_id'],
                'title' => sanitizeInput($data['title']),
                'description' => sanitizeInput($data['description']),
                'requirements' => sanitizeInput($data['requirements'] ?? null),
                'budget_type' => $data['budget_type'],
                'budget_min' => $data['budget_min'] ?? null,
                'budget_max' => $data['budget_max'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'estimated_hours' => $data['estimated_hours'] ?? null,
                'deadline' => !empty($data['deadline']) ? date('Y-m-d', strtotime($data['deadline'])) : null,
                'category_id' => $data['category_id'],
                'experience_level' => $data['experience_level'] ?? 'intermediate',
                'project_length' => $data['project_length'] ?? 'medium',
                'location_requirement' => $data['location_requirement'] ?? 'remote',
                'timezone_preference' => sanitizeInput($data['timezone_preference'] ?? null),
                'status' => 'draft',
                'visibility' => $data['visibility'] ?? 'public',
                'featured' => $data['featured'] ?? 0,
                'urgent' => $data['urgent'] ?? 0,
                'max_proposals' => $data['max_proposals'] ?? 50,
                'proposal_deadline' => !empty($data['proposal_deadline']) ? date('Y-m-d H:i:s', strtotime($data['proposal_deadline'])) : null
            ];
            
            $job = $this->jobModel->create($jobData);
            
            // Add skills if provided
            if (!empty($data['skills']) && is_array($data['skills'])) {
                $this->jobModel->addSkills($job['id'], $data['skills']);
            }
            
            // Update job summary
            updateJobSummary($job['id']);
            
            // Log activity
            logActivity($this->user['user_id'], 'job_created', 'job', $job['id'], [
                'title' => $data['title'],
                'budget_type' => $data['budget_type']
            ]);
            
            $this->success([
                'job_id' => $job['id'],
                'status' => 'draft'
            ], 'Job created successfully', 201);
            
        } catch (Exception $e) {
            logError("Job creation failed", [
                'user_id' => $this->user['user_id'],
                'error' => $e->getMessage()
            ]);
            $this->error('Internal server error', 500);
        }
    }
    
    // PUT /api/jobs/{id} - Update job
    public function update($id) {
        $this->requireAuth('client');
        
        if (!$this->jobModel->canModify($id, $this->user['user_id'])) {
            $this->error('Job not found or access denied', 404);
        }
        
        $data = $this->getRequestData();
        
        try {
            $updatableFields = $this->jobModel->getUpdatableFields($id);
            $updateData = array_intersect_key($data, array_flip($updatableFields));
            
            if (empty($updateData)) {
                $this->error('No valid fields to update');
            }
            
            // Sanitize text fields
            foreach (['title', 'description', 'requirements', 'timezone_preference'] as $field) {
                if (isset($updateData[$field])) {
                    $updateData[$field] = sanitizeInput($updateData[$field]);
                }
            }
            
            // Format date fields
            if (isset($updateData['deadline'])) {
                $updateData['deadline'] = $updateData['deadline'] ? date('Y-m-d', strtotime($updateData['deadline'])) : null;
            }
            if (isset($updateData['proposal_deadline'])) {
                $updateData['proposal_deadline'] = $updateData['proposal_deadline'] ? date('Y-m-d H:i:s', strtotime($updateData['proposal_deadline'])) : null;
            }
            
            $job = $this->jobModel->update($id, $updateData);
            
            // Update skills if provided and allowed (no accepted proposals)
            if (in_array('skills', $updatableFields) && isset($data['skills']) && is_array($data['skills'])) {
                $this->jobModel->addSkills($id, $data['skills']);
            }
            
            // Update job summary
            updateJobSummary($id);
            
            // Log activity
            logActivity($this->user['user_id'], 'job_updated', 'job', $id, [
                'updated_fields' => array_keys($updateData)
            ]);
            
            $this->success($job, 'Job updated successfully');
            
        } catch (Exception $e) {
            logError("Job update failed", [
                'job_id' => $id,
                'user_id' => $this->user['user_id'],
                'error' => $e->getMessage()
            ]);
            $this->error('Internal server error', 500);
        }
    }
    
    // POST /api/jobs/{id}/publish - Publish job
    public function publish($id) {
        $this->requireAuth('client');
        
        if (!$this->jobModel->canModify($id, $this->user['user_id'])) {
            $this->error('Job not found or access denied', 404);
        }
        
        try {
            $this->jobModel->publishJob($id);
            
            // Update job summary
            updateJobSummary($id);
            
            // Log activity
            logActivity($this->user['user_id'], 'job_published', 'job', $id);
            
            $this->success(null, 'Job published successfully');
            
        } catch (Exception $e) {
            logError("Job publish failed", [
                'job_id' => $id,
                'user_id' => $this->user['user_id'],
                'error' => $e->getMessage()
            ]);
            
            if (strpos($e->getMessage(), 'required') !== false) {
                $this->error($e->getMessage(), 400);
            } else {
                $this->error('Internal server error', 500);
            }
        }
    }
    
    // DELETE /api/jobs/{id} - Delete job
    public function delete($id) {
        $this->requireAuth('client');
        
        if (!$this->jobModel->canDelete($id, $this->user['user_id'])) {
            $this->error('Job not found or cannot be deleted', 400);
        }
        
        try {
            $job = $this->jobModel->find($id);
            $this->jobModel->delete($id);
            
            // Log activity
            logActivity($this->user['user_id'], 'job_deleted', 'job', $id, [
                'title' => $job['title']
            ]);
            
            $this->success(null, 'Job deleted successfully');
            
        } catch (Exception $e) {
            logError("Job deletion failed", [
                'job_id' => $id,
                'user_id' => $this->user['user_id'],
                'error' => $e->getMessage()
            ]);
            $this->error('Internal server error', 500);
        }
    }
    
    // GET /api/jobs/categories - Get categories
    public function getCategories() {
        try {
            $categories = $this->categoryModel->getAllWithStats();
            
            // Format data
            foreach ($categories as &$category) {
                $category['total_jobs'] = (int)($category['total_jobs'] ?? 0);
                $category['active_jobs'] = (int)($category['active_jobs'] ?? 0);
                $category['avg_job_budget'] = (float)($category['avg_job_budget'] ?? 0);
                $category['total_freelancers'] = (int)($category['total_freelancers'] ?? 0);
                $category['hourly_rate_min'] = (float)($category['hourly_rate_min'] ?? 0);
                $category['hourly_rate_max'] = (float)($category['hourly_rate_max'] ?? 0);
            }
            
            $this->success(['categories' => $categories]);
            
        } catch (Exception $e) {
            logError("Categories list failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
    
    // GET /api/jobs/skills - Get skills
    public function getSkills() {
        $categoryId = $_GET['category_id'] ?? null;
        $search = $_GET['search'] ?? null;
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        
        try {
            $skills = $this->skillModel->getSkillsByCategory($categoryId, $search, $limit);
            
            // Format data
            foreach ($skills as &$skill) {
                $skill['popularity_score'] = (int)$skill['popularity_score'];
            }
            
            $this->success(['skills' => $skills]);
            
        } catch (Exception $e) {
            logError("Skills list failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
}