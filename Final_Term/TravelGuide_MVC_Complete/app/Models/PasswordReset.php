<?php
namespace App\Models;

use App\Core\Model;

class PasswordReset extends Model
{
    public function create(int $userId): string
    {
        $this->db->prepare('DELETE FROM password_resets WHERE user_id=?')->execute([$userId]);
        $token = bin2hex(random_bytes(24));
        $hash = hash('sha256',$token);
        $stmt = $this->db->prepare('INSERT INTO password_resets (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))');
        $stmt->execute([$userId,$hash]);
        return $token;
    }

    public function findValid(string $token): ?array
    {
        $hash = hash('sha256',$token);
        $stmt = $this->db->prepare('SELECT pr.*,u.email FROM password_resets pr JOIN users u ON u.id=pr.user_id WHERE pr.token_hash=? AND pr.used_at IS NULL AND pr.expires_at>NOW() LIMIT 1');
        $stmt->execute([$hash]);
        return $stmt->fetch() ?: null;
    }

    public function markUsed(int $id): void
    {
        $this->db->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?')->execute([$id]);
    }
}
