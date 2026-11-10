<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$expectedHash = '10e5f502a6b1bee6f1a23c6b15509fd5ee50b77daaa06992cc3de2dfeed4f208';
$providedToken = (string)($_GET['key'] ?? '');
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || $providedToken === '' || !hash_equals($expectedHash, hash('sha256', $providedToken))) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

require_once __DIR__ . '/config/connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection unavailable']);
    exit;
}

$offers = [
    ['العناية بالبشرة', 'derma', 'ديرما بن بالميزو', 1000, 1200, '', '', 0],
    ['التجميل بالحقن', 'cosm', 'ديرما بن بالبلازما', 1800, 2000, '', '', 0],
    ['العناية بالشعر', 'derma', 'Hair Mesotherapy', 2000, 3000, 'جلستان لإنبات الشعر', '+ جلسة هدية', 0],
    ['العناية بالبشرة', 'derma', 'Dermapen', 2000, 3000, 'جلستان', '+ جلسة هدية', 0],
    ['الليزر', 'laser', 'Fractional Laser', 2000, 3000, 'جلستان', '+ جلسة هدية', 0],
    ['التجميل بالحقن', 'cosm', 'Lip Skin Booster', 1600, 2400, 'جلستان', '+ جلسة هدية', 0],
    ['العناية بالبشرة', 'derma', 'عرض التقشير', 2400, null, 'جلستا تقشير للوجه أو الإبط أو البكيني', '+ جلسة هدية', 0],
    ['التجميل بالحقن', 'cosm', 'عرض البلازما', 2400, null, 'جلستا بلازما', '+ جلسة خلايا جذعية هدية', 0],
    ['التجميل بالحقن', 'cosm', 'ترطيب الشفايف', 2000, null, '3 جلسات', '', 0],
    ['التجميل بالحقن', 'cosm', 'جلسة البلازما', 1200, null, '', '', 0],
];

try {
    $conn->begin_transaction();
    if (!$conn->query('DELETE FROM `offers`')) {
        throw new RuntimeException('Could not clear existing offers.');
    }
    $stmt = $conn->prepare('INSERT INTO `offers` (`category`, `department_slug`, `name`, `price`, `original_price`, `details`, `gift_label`, `starting`, `visible`, `sort_order`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0)');
    if (!$stmt) throw new RuntimeException('Could not prepare offer insert.');

    foreach ($offers as [$category, $department, $name, $price, $originalPrice, $details, $giftLabel, $starting]) {
        $stmt->bind_param('sssddssi', $category, $department, $name, $price, $originalPrice, $details, $giftLabel, $starting);
        if (!$stmt->execute()) throw new RuntimeException('Could not insert an offer.');
    }
    $stmt->close();
    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Offers migration failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Offer data update failed']);
    exit;
}

$result = $conn->query('SELECT `category`, `name`, `price`, `original_price`, `details`, `gift_label`, `department_slug`, `visible` FROM `offers` ORDER BY `category`, `id`');
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
@unlink(__FILE__);
echo json_encode(['success' => count($rows) === 10, 'count' => count($rows), 'offers' => $rows], JSON_UNESCAPED_UNICODE);
