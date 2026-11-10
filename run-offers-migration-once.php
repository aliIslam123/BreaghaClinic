<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$expectedHash = 'ad4f59d59733c0f17b8ef013d3e264e36683c9459649a53ab4f2554ceb33d579';
$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$providedToken = preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) ? $matches[1] : '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $providedToken === '' || !hash_equals($expectedHash, hash('sha256', $providedToken))) {
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

$columns = [
    'original_price' => 'DECIMAL(10,2) NULL DEFAULT NULL AFTER `price`',
    'details' => 'TEXT NULL AFTER `name`',
    'gift_label' => 'VARCHAR(120) NULL DEFAULT NULL AFTER `details`',
];

foreach ($columns as $column => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `offers` LIKE '" . $conn->real_escape_string($column) . "'");
    if (!$check || $check->num_rows === 0) {
        if (!$conn->query("ALTER TABLE `offers` ADD COLUMN `$column` $definition")) {
            error_log('Offers migration schema update failed: ' . $conn->error);
            http_response_code(500);
            echo json_encode(['error' => 'Offers schema update failed']);
            exit;
        }
    }
}

$offers = [
    ['العناية بالبشرة', 'ديرما بن بالميزو', '', 1000, 1200, '', 0],
    ['التجميل بالحقن', 'ديرما بن بالبلازما', '', 1800, 2000, '', 0],
    ['العناية بالشعر', 'Hair Mesotherapy', 'جلستان لإنبات الشعر', 2000, 3000, '+ جلسة هدية', 0],
    ['العناية بالبشرة', 'Dermapen', 'جلستان', 2000, 3000, '+ جلسة هدية', 0],
    ['الليزر', 'Fractional Laser', 'جلستان', 2000, 3000, '+ جلسة هدية', 0],
    ['التجميل بالحقن', 'Lip Skin Booster', 'جلستان', 1600, 2400, '+ جلسة هدية', 0],
    ['العناية بالبشرة', 'عرض التقشير', 'جلستا تقشير للوجه أو الإبط أو البكيني', 2400, null, '+ جلسة هدية', 0],
    ['التجميل بالحقن', 'عرض البلازما', 'جلستا بلازما', 2400, null, '+ جلسة خلايا جذعية هدية', 0],
    ['التجميل بالحقن', 'ترطيب الشفايف', '3 جلسات', 2000, null, '', 0],
    ['التجميل بالحقن', 'جلسة البلازما', '', 1200, null, '', 0],
];

try {
    $conn->begin_transaction();
    if (!$conn->query('DELETE FROM `offers`')) {
        throw new RuntimeException('Could not clear existing offers.');
    }

    $stmt = $conn->prepare('INSERT INTO `offers` (`category`, `name`, `details`, `price`, `original_price`, `gift_label`, `starting`, `visible`) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
    if (!$stmt) {
        throw new RuntimeException('Could not prepare offer insert.');
    }

    foreach ($offers as [$category, $name, $details, $price, $originalPrice, $giftLabel, $starting]) {
        $stmt->bind_param('sssddsi', $category, $name, $details, $price, $originalPrice, $giftLabel, $starting);
        if (!$stmt->execute()) {
            throw new RuntimeException('Could not insert an offer.');
        }
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

$result = $conn->query('SELECT `category`, `name`, `price`, `original_price`, `details`, `gift_label` FROM `offers` WHERE `visible` = 1 ORDER BY `category`, `id`');
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
echo json_encode(['success' => true, 'count' => count($rows), 'offers' => $rows], JSON_UNESCAPED_UNICODE);
