<?php
class Skill extends Model {
    protected $table = 'skills';
    
    public function getSkillsByCategory($categoryId = null, $search = null, $limit = 50) {
        $where_conditions = ["s.status = 'active'"];
        $params = [];
        
        if ($categoryId) {
            $where_conditions[] = "s.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($search) {
            $where_conditions[] = "s.name LIKE ?";
            $params[] = "%$search%";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stmt = $this->db->prepare("
            SELECT s.*, c.name as category_name
            FROM skills s
            JOIN categories c ON s.category_id = c.id
            WHERE $where_clause
            ORDER BY s.popularity_score DESC, s.name
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}