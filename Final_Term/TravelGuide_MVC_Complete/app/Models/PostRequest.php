<?php
namespace App\Models;

use App\Core\Model;

class PostRequest extends Model
{
    public function create(int $scoutId, array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO post_requests (scout_id,title,short_history,country,category,cost_level,image_url,status) VALUES (?,?,?,?,?,?,?,"pending")');
        $stmt->execute([$scoutId,$data['title'],$data['short_history'],$data['country'],$data['category'],$data['cost_level'],$data['image_url'] ?: null]);
        return (int)$this->db->lastInsertId();
    }

    public function byScout(int $scoutId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM post_requests WHERE scout_id=? ORDER BY created_at DESC');
        $stmt->execute([$scoutId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT pr.*,u.name AS scout_name,u.email AS scout_email FROM post_requests pr LEFT JOIN users u ON u.id=pr.scout_id WHERE pr.id=? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findForScout(int $id, int $scoutId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM post_requests WHERE id=? AND scout_id=? LIMIT 1');
        $stmt->execute([$id,$scoutId]);
        return $stmt->fetch() ?: null;
    }

    public function updateForScout(int $id, int $scoutId, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE post_requests SET title=?,short_history=?,country=?,category=?,cost_level=?,image_url=?,status="pending",admin_feedback=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=? AND scout_id=? AND status IN ("pending","change_requested","rejected")');
        return $stmt->execute([$data['title'],$data['short_history'],$data['country'],$data['category'],$data['cost_level'],$data['image_url'] ?: null,$id,$scoutId]);
    }

    public function deleteForScout(int $id, int $scoutId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM post_requests WHERE id=? AND scout_id=? AND status IN ("pending","change_requested","rejected")');
        $stmt->execute([$id,$scoutId]);
        return $stmt->rowCount() > 0;
    }

    public function all(): array
    {
        return $this->db->query('SELECT pr.*,u.name AS scout_name,u.email AS scout_email FROM post_requests pr LEFT JOIN users u ON u.id=pr.scout_id ORDER BY FIELD(pr.status,"pending","change_requested","rejected","approved"),pr.created_at DESC')->fetchAll();
    }

    public function setReview(int $id, string $status, ?string $feedback): bool
    {
        $stmt = $this->db->prepare('UPDATE post_requests SET status=?,admin_feedback=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');
        return $stmt->execute([$status,$feedback ?: null,$id]);
    }

    public function countStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM post_requests WHERE status=?');
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }
}
