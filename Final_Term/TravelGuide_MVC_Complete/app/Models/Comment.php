<?php
namespace App\Models;

use App\Core\Model;

class Comment extends Model
{
    public function forPost(int $postId): array
    {
        $stmt = $this->db->prepare('SELECT c.*,u.name,u.profile_image FROM comments c JOIN users u ON u.id=c.user_id WHERE c.post_id=? ORDER BY c.created_at DESC');
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, int $postId, string $content): int
    {
        $stmt = $this->db->prepare('INSERT INTO comments (user_id,post_id,content) VALUES (?,?,?)');
        $stmt->execute([$userId,$postId,$content]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteOwn(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id=? AND user_id=?');
        $stmt->execute([$id,$userId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteAny(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id=?');
        return $stmt->execute([$id]);
    }

    public function all(): array
    {
        return $this->db->query('SELECT c.*,u.name AS user_name,u.email,p.title AS post_title FROM comments c JOIN users u ON u.id=c.user_id JOIN posts p ON p.id=c.post_id ORDER BY c.created_at DESC')->fetchAll();
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM comments')->fetchColumn();
    }
}
