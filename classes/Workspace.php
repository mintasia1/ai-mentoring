<?php
/**
 * Workspace Class - For notes and goals
 * CUHK Law E-Mentoring Platform
 */

require_once __DIR__ . '/Database.php';

class Workspace {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Add workspace item (note, goal, or milestone)
     */
    public function addItem($mentorshipId, $createdBy, $itemType, $title, $content, $dueDate = null) {
        $stmt = $this->db->prepare(
            "INSERT INTO workspace_items 
             (mentorship_id, created_by, item_type, title, content, due_date) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$mentorshipId, $createdBy, $itemType, $title, $content, $dueDate]);
    }
    
    /**
     * Get workspace items for a mentorship
     */
    public function getItems($mentorshipId, $itemType = null) {
        if ($itemType) {
            $stmt = $this->db->prepare(
                "SELECT wi.*, u.first_name, u.last_name 
                 FROM workspace_items wi 
                 INNER JOIN users u ON wi.created_by = u.id 
                 WHERE wi.mentorship_id = ? AND wi.item_type = ? 
                 ORDER BY wi.created_at DESC"
            );
            $stmt->execute([$mentorshipId, $itemType]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT wi.*, u.first_name, u.last_name 
                 FROM workspace_items wi 
                 INNER JOIN users u ON wi.created_by = u.id 
                 WHERE wi.mentorship_id = ? 
                 ORDER BY wi.created_at DESC"
            );
            $stmt->execute([$mentorshipId]);
        }
        return $stmt->fetchAll();
    }
    
    /**
     * Update workspace item status
     */
    public function updateStatus($itemId, $status) {
        $stmt = $this->db->prepare("UPDATE workspace_items SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $itemId]);
    }
    
    /**
     * Update workspace item
     */
    public function updateItem($itemId, $title, $content, $dueDate = null) {
        $stmt = $this->db->prepare(
            "UPDATE workspace_items SET title = ?, content = ?, due_date = ? WHERE id = ?"
        );
        return $stmt->execute([$title, $content, $dueDate, $itemId]);
    }
    
    /**
     * Delete workspace item
     */
    public function deleteItem($itemId) {
        $stmt = $this->db->prepare("DELETE FROM workspace_items WHERE id = ?");
        return $stmt->execute([$itemId]);
    }
}
