<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class Post extends Model
{
    public function approved(array $filters = [], ?int $limit = null): array
    {
        $where = ['p.is_approved=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(p.title LIKE ? OR p.country LIKE ? OR p.short_history LIKE ?)';
            $q = '%' . $filters['search'] . '%';
            array_push($params, $q, $q, $q);
        }
        foreach (['country','category','cost_level'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "p.$field=?";
                $params[] = $filters[$field];
            }
        }
        $sql = 'SELECT p.*, u.name AS scout_name FROM posts p LEFT JOIN users u ON u.id=p.scout_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.created_at DESC';
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, (int)$limit);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findApproved(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT p.*,u.name AS scout_name FROM posts p LEFT JOIN users u ON u.id=p.scout_id WHERE p.id=? AND p.is_approved=1 LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findAny(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT p.*,u.name AS scout_name FROM posts p LEFT JOIN users u ON u.id=p.scout_id WHERE p.id=? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function allAdmin(): array
    {
        return $this->db->query('SELECT p.*,u.name AS scout_name FROM posts p LEFT JOIN users u ON u.id=p.scout_id ORDER BY p.created_at DESC')->fetchAll();
    }

    public function publishFromRequest(array $request): int
    {
        $sql = 'INSERT INTO posts (source_request_id,scout_id,title,short_history,country,category,cost_level,image_url,is_approved)
                VALUES (?,?,?,?,?,?,?,?,1)
                ON DUPLICATE KEY UPDATE scout_id=VALUES(scout_id),title=VALUES(title),short_history=VALUES(short_history),country=VALUES(country),category=VALUES(category),cost_level=VALUES(cost_level),image_url=VALUES(image_url),is_approved=1,updated_at=CURRENT_TIMESTAMP';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $request['id'], $request['scout_id'], $request['title'], $request['short_history'], $request['country'],
            $request['category'], $request['cost_level'], $request['image_url'] ?: null
        ]);
        $id = (int)$this->db->lastInsertId();
        if ($id === 0) {
            $q = $this->db->prepare('SELECT id FROM posts WHERE source_request_id=?');
            $q->execute([$request['id']]);
            $id = (int)$q->fetchColumn();
        }
        return $id;
    }

    public function unpublishByRequest(int $requestId): void
    {
        $stmt = $this->db->prepare('UPDATE posts SET is_approved=0 WHERE source_request_id=?');
        $stmt->execute([$requestId]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE posts SET title=?,short_history=?,country=?,category=?,cost_level=?,image_url=?,is_approved=? WHERE id=?');
        return $stmt->execute([
            $data['title'], $data['short_history'], $data['country'], $data['category'], $data['cost_level'],
            $data['image_url'] ?: null, !empty($data['is_approved']) ? 1 : 0, $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id=?');
        return $stmt->execute([$id]);
    }

    public function countApproved(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM posts WHERE is_approved=1')->fetchColumn();
    }

    public function distinct(string $field): array
    {
        if (!in_array($field, ['country','category','cost_level'], true)) {
            return [];
        }
        return $this->db->query("SELECT DISTINCT $field AS value FROM posts WHERE is_approved=1 ORDER BY $field")->fetchAll(PDO::FETCH_COLUMN);
    }
}
