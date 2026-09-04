<?php
/**
 * Destinations Fetch Endpoint
 * Returns list of travel destinations
 */

header('Content-Type: application/json');
require_once '../db.php';

try {
    $stmt = $pdo->query("SELECT id, name, country, category, budget_type, description, image_color FROM destinations ORDER BY id ASC");
    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count'   => count($data),
        'data'    => $data
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch destinations.']);
}