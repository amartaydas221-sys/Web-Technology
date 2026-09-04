<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
    exit();
}

$destination = trim((string)($input['destination'] ?? 'Not specified'));
$travelers = max(1, (int)($input['travelers'] ?? 1));
$days = max(1, (int)($input['days'] ?? 1));
$transport = (float)($input['transport'] ?? 0);
$accommodation = (float)($input['accommodation'] ?? 0);
$food = (float)($input['food'] ?? 0);
$other = (float)($input['other'] ?? 0);

$totalAccommodation = $accommodation * $days;
$totalFood = $food * $travelers * $days;
$total = $transport + $totalAccommodation + $totalFood + $other;

echo json_encode([
    'success' => true,
    'destination' => $destination,
    'total' => $total,
    'breakdown' => [
        'travelers' => $travelers,
        'days' => $days,
        'transport' => $transport,
        'accommodation' => $totalAccommodation,
        'food' => $totalFood,
        'other' => $other,
    ],
    'message' => 'Budget calculated successfully.'
]);
