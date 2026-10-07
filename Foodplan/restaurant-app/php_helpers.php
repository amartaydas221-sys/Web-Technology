<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_user(?string $role = null): array
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Please sign in to continue.'], 401);
    }
    if ($role !== null && $user['role'] !== $role) {
        json_response(['error' => 'This account does not have permission to perform that action.'], 403);
    }
    return $user;
}

function request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        if (!is_array($data)) {
            json_response(['error' => 'Request body must be valid JSON.'], 400);
        }
        return $data;
    }
    return $_POST;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function notify_user(PDO $pdo, int $userId, string $title, string $message): void
{
    $stmt = $pdo->prepare('INSERT INTO ff_notifications (user_id, title, message) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $title, $message]);
}

function safe_user(array $user): array
{
    return [
        'id' => (int)$user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role' => $user['role'],
    ];
}
