<?php
namespace App\Core;

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int)$_SESSION['user']['id'] : null;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role_name'],
            'is_verified' => (int)$user['is_verified'],
            'profile_image' => $user['profile_image'] ?? null,
        ];
    }

    public static function refresh(array $user): void
    {
        if (!self::check()) {
            return;
        }
        $_SESSION['user']['name'] = $user['name'];
        $_SESSION['user']['email'] = $user['email'];
        $_SESSION['user']['role'] = $user['role_name'];
        $_SESSION['user']['is_verified'] = (int)$user['is_verified'];
        $_SESSION['user']['profile_image'] = $user['profile_image'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function hasRole(string ...$roles): bool
    {
        return self::check() && in_array(self::user()['role'], $roles, true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Please log in to continue.');
            redirect('login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            exit('403 Forbidden - You do not have permission to access this page.');
        }
    }
}
