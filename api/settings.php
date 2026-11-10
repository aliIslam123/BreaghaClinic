<?php
require_once __DIR__ . '/../config/init.php';
include __DIR__ . '/../config/connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!empty($db_error) || !$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

echo json_encode([
    'male_only_days' => [],
]);
