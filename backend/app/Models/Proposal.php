<?php
class Proposal extends Model {
    protected $table = 'proposals';
    protected $fillable = [
        'job_id', 'freelancer_id', 'cover_letter', 'proposed_budget',
        'proposed_timeline', 'questions', 'attachments', 'status'
    ];
    
    public function createProposal($data) {
        $this->db->beginTransaction();
        
        try {
            // Check if job exists and is open
            $stmt = $this->db->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'open'");
            $stmt->execute([$data['job_id']]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$job) {
                throw new Exception('Job not found or not open');
            }
            
            // Check if already submitted
            if ($this->hasUserApplied($data['job_id'], $data['freelancer_id'])) {
                throw new Exception('Proposal already submitted');
            }
            
            // Create proposal
            $proposalData = [
                'job_id' => $data['job_id'],
                'freelancer_id' => $data['freelancer_id'],
                'cover_letter' => sanitizeInput($data['cover_letter']),
                'proposed_budget' => $data['proposed_budget'],
                'proposed_timeline' => sanitizeInput($data['proposed_timeline'] ?? null),
                'questions' => json_encode($data['questions'] ?? []),
                'attachments' => json_encode($data['attachments'] ?? []),
                'status' => 'pending'
            ];
            
            $proposal = $this->create($proposalData);
            
            // Update job proposal count
            updateJobProposalCount($data['job_id']);
            
            // Send notification to client
            sendNotification(
                $job['client_id'],
                'new_proposal',
                'New Proposal Received',
                'You received a new proposal for your job: ' . $job['title'],
                ['job_id' => $data['job_id'], 'proposal_id' => $proposal['id']]
            );
            
            $this->db->commit();
            return $proposal;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function hasUserApplied($jobId, $freelancerId) {
        $stmt = $this->db->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
        $stmt->execute([$jobId, $freelancerId]);
        return $stmt->fetch() !== false;
    }
    
    public function getProposalsForJob($jobId, $status = null) {
        $query = "
            SELECT p.*, up.display_name as freelancer_name, up.avatar as freelancer_avatar,
                   fp.avg_rating, fp.total_jobs_completed, fp.success_rate
            FROM proposals p
            JOIN user_profiles up ON p.freelancer_id = up.user_id
            LEFT JOIN freelancer_profiles fp ON p.freelancer_id = fp.user_id
            WHERE p.job_id = ?
        ";
        
        $params = [$jobId];
        
        if ($status) {
            $query .= " AND p.status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY p.submitted_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}