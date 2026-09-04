<?php
/**
 * Authentication Endpoint
 * Accepts POST JSON / Sets HTTP Cookies on success
 */

header('Content-Type: application/json');
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$input    = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');
$role     = trim($input['role'] ?? 'user');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please provide both email and password.']);
    exit();
}

// Demo fallback check
$demo_users = [
    'admin@travelguide.com' => ['name' => 'Admin User',  'role' => 'admin'],
    'scout@travelguide.com' => ['name' => 'Scout User',  'role' => 'scout'],
    'user@travelguide.com'  => ['name' => 'Normal User', 'role' => 'user']
];

if (isset($demo_users[$email]) && $password === 'password123') {
    $name = $demo_users[$email]['name'];
    $role = $demo_users[$email]['role'];

    $expiry = time() + (7 * 86400); // 7 days
    setcookie('tg_user',  $name,  $expiry, '/');
    setcookie('tg_email', $email, $expiry, '/');
    setcookie('tg_role',  $role,  $expiry, '/');

    echo json_encode([
        'success' => true,
        'message' => "Welcome back, {$name}!",
        'user'    => ['name' => $name, 'email' => $email, 'role' => $role]
    ]);
    exit();
}

// Database credential check
try {
    $stmt = $pdo->prepare("SELECT name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $expiry = time() + (7 * 86400);
        setcookie('tg_user',  $user['name'],  $expiry, '/');
        setcookie('tg_email', $user['email'], $expiry, '/');
        setcookie('tg_role',  $user['role'],  $expiry, '/');

        echo json_encode([
            'success' => true,
            'message' => "Welcome back, {$user['name']}!",
            'user'    => ['name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred.']);
}