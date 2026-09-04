<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    public function create(string $name, string $email, string $password, string $role = 'user', int $verified = 1, ?string $profileImage = null): int
    {
        $roleId = $this->roleId($role);
        $stmt = $this->db->prepare('INSERT INTO users (name,email,password_hash,profile_image,role_id,is_verified) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$name, strtolower($email), password_hash($password, PASSWORD_DEFAULT), $profileImage, $roleId, $verified]);
        return (int)$this->db->lastInsertId();
    }

    public function roleId(string $role): int
    {
        $stmt = $this->db->prepare('SELECT id FROM roles WHERE role_name = ? LIMIT 1');
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? LIMIT 1');
        $stmt->execute([strtolower($email)]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function updateProfile(int $id, string $name, string $email, ?string $profileImage): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET name=?, email=?, profile_image=? WHERE id=?');
        return $stmt->execute([$name, strtolower($email), $profileImage ?: null, $id]);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash=? WHERE id=?');
        return $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id=?');
        return $stmt->execute([$id]);
    }

    public function all(): array
    {
        return $this->db->query('SELECT u.id,u.name,u.email,u.profile_image,u.is_verified,u.created_at,r.role_name FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.created_at DESC')->fetchAll();
    }

    public function verify(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET is_verified=1 WHERE id=?');
        return $stmt->execute([$id]);
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE r.role_name=?');
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
