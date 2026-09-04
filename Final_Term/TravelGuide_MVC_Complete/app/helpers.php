<?php

use App\Core\Auth;

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function base_path_url(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $dir === '' || $dir === '.' ? '' : $dir;
}

function url(string $route = '', array $params = []): string
{
    $base = base_path_url() . '/index.php';
    if ($route !== '') {
        $params = ['route' => $route] + $params;
    }
    return $params ? $base . '?' . http_build_query($params) : $base;
}

function asset(string $path): string
{
    return base_path_url() . '/public/' . ltrim($path, '/');
}

function redirect(string $route, array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

function old(string $key, string $default = ''): string
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', (string)$token)) {
        http_response_code(419);
        exit('Invalid or expired CSRF token. Please go back and try again.');
    }
}

function auth(): ?array
{
    return Auth::user();
}

function is_logged_in(): bool
{
    return Auth::check();
}

function has_role(string ...$roles): bool
{
    return Auth::hasRole(...$roles);
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function status_badge(string $status): string
{
    $safe = preg_replace('/[^a-z_]/', '', strtolower($status));
    return '<span class="status status-' . e($safe) . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
