<?php
class ProposalController extends Controller {
    private $proposalModel;
    
    public function __construct() {
        parent::__construct();
        $this->proposalModel = new Proposal();
    }
    
    public function create() {
        $this->requireAuth('freelancer');
        
        $data = $this->getRequestData();
        
        $this->validate($data, [
            'job_id' => 'required',
            'cover_letter' => 'required|min:50',
            'proposed_budget' => 'required'
        ]);
        
        try {
            $data['freelancer_id'] = $this->user['user_id'];
            $proposal = $this->proposalModel->createProposal($data);
            
            // Log activity
            logActivity($this->user['user_id'], 'proposal_submitted', 'proposal', $proposal['id']);
            
            $this->success([
                'proposal_id' => $proposal['id']
            ], 'Proposal submitted successfully', 201);
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already submitted') !== false) {
                $this->error($e->getMessage(), 400);
            } elseif (strpos($e->getMessage(), 'not found') !== false) {
                $this->error($e->getMessage(), 404);
            } else {
                logError("Proposal creation failed", ['error' => $e->getMessage()]);
                $this->error('Internal server error', 500);
            }
        }
    }
    
    public function getForJob($jobId) {
        $this->requireAuth('client');
        
        try {
            // Verify job belongs to client
            $jobModel = new Job();
            if (!$jobModel->canModify($jobId, $this->user['user_id'])) {
                $this->error('Access denied', 403);
            }
            
            $proposals = $this->proposalModel->getProposalsForJob($jobId);
            
            $this->success(['proposals' => $proposals]);
            
        } catch (Exception $e) {
            logError("Get proposals failed", ['error' => $e->getMessage()]);
            $this->error('Internal server error', 500);
        }
    }
}