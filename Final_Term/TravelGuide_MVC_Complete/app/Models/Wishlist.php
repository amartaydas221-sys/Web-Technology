<?php
namespace App\Models;

use App\Core\Model;

class Wishlist extends Model
{
    public function exists(int $userId, int $postId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM wishlists WHERE user_id=? AND post_id=? LIMIT 1');
        $stmt->execute([$userId,$postId]);
        return (bool)$stmt->fetchColumn();
    }

    public function toggle(int $userId, int $postId): bool
    {
        if ($this->exists($userId,$postId)) {
            $stmt = $this->db->prepare('DELETE FROM wishlists WHERE user_id=? AND post_id=?');
            $stmt->execute([$userId,$postId]);
            return false;
        }
        $stmt = $this->db->prepare('INSERT IGNORE INTO wishlists (user_id,post_id) VALUES (?,?)');
        $stmt->execute([$userId,$postId]);
        return true;
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT p.*,w.added_at,u.name AS scout_name FROM wishlists w JOIN posts p ON p.id=w.post_id LEFT JOIN users u ON u.id=p.scout_id WHERE w.user_id=? AND p.is_approved=1 ORDER BY w.added_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function countForUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM wishlists WHERE user_id=?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}
