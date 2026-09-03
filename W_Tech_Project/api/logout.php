<?php
/**
 * Session Cleanup Endpoint
 * Unsets user cookies
 */

header('Content-Type: application/json');

setcookie('tg_user',  '', time() - 3600, '/');
setcookie('tg_email', '', time() - 3600, '/');
setcookie('tg_role',  '', time() - 3600, '/');

echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);