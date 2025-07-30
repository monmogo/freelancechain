<?php
class User extends Model {
    protected $table = 'users';

    protected $fillable = [
        'email', 'password_hash', 'role', 'wallet_address', 'wallet_type',
        'email_verified', 'kyc_verified', 'status', 'country_id', 
        'preferred_language', 'timezone', 'currency_preference'
    ];
    
    public function createWithProfile($userData, $profileData = []) {
        $this->db->beginTransaction();
        
        try {
            // Create user
            $user = $this->create($userData);
            
            if (!$user) {
                throw new Exception('Failed to create user');
            }
            
            // Create user profile
            $profileData['user_id'] = $user['id'];
            $profileData['display_name'] = trim(($profileData['first_name'] ?? '') . ' ' . ($profileData['last_name'] ?? ''));
            
            $stmt = $this->db->prepare("
                INSERT INTO user_profiles (user_id, first_name, last_name, display_name, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user['id'],
                $profileData['first_name'] ?? '',
                $profileData['last_name'] ?? '',
                $profileData['display_name']
            ]);
            
            // Create user stats
            $stmt = $this->db->prepare("INSERT INTO user_stats (user_id) VALUES (?)");
            $stmt->execute([$user['id']]);
            
            $this->db->commit();
            return $user;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getWithProfile($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, up.display_name, up.first_name, up.last_name, up.avatar
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function incrementLoginAttempts($id) {
        $stmt = $this->db->prepare("
            UPDATE users SET 
            login_attempts = login_attempts + 1,
            locked_until = CASE 
                WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                ELSE locked_until
            END
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
    
    public function resetLoginAttempts($id) {
        $stmt = $this->db->prepare("
            UPDATE users SET 
            login_attempts = 0, 
            locked_until = NULL, 
            last_login = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
    
    public function getUserPermissions($role) {
        $permissions = [
            'admin' => ['*'],
            'freelancer' => ['read_profile', 'write_profile', 'submit_proposals'],
            'client' => ['read_profile', 'write_profile', 'post_jobs']
        ];
        
        return $permissions[$role] ?? [];
    }
}