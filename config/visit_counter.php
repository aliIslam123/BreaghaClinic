<?php
// visit_counter.php
// Records unique visits per visitor/device per page every 4 hours.
// Usage: require_once 'visit_counter.php'; record_visit('home'); $counts = get_visit_counts();

if (!isset($conn)) {
    // try to include connection if not present
    if (file_exists(__DIR__ . '/connection.php')) {
        require_once __DIR__ . '/connection.php';
    } else {
        throw new Exception('Database connection not available for visit counter.');
    }
}

function ensure_visits_table() {
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS `visit_logs` (
      `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `visitor_id` VARCHAR(64) NOT NULL,
      `page` VARCHAR(32) NOT NULL,
      `created_at` DATETIME NOT NULL,
      INDEX (`visitor_id`),
      INDEX (`page`),
      INDEX (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->query($sql);
}

function get_visitor_id() {
    // cookie-based visitor id
    if (!empty($_COOKIE['breagha_vid'])) return $_COOKIE['breagha_vid'];
    try {
        $id = bin2hex(random_bytes(12));
    } catch (Exception $e) {
        $id = uniqid('v', true);
    }
    // set cookie for 1 year, HTTP only
    setcookie('breagha_vid', $id, time() + 31536000, '/', '', false, true);
    $_COOKIE['breagha_vid'] = $id;
    return $id;
}

function record_visit($page) {
    global $conn;
    ensure_visits_table();
    $visitor = get_visitor_id();
    // check if already recorded in last 4 hours for this page
    $sql = "SELECT id FROM visit_logs WHERE visitor_id = ? AND page = ? AND created_at >= (NOW() - INTERVAL 4 HOUR) LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ss', $visitor, $page);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            $ins = $conn->prepare("INSERT INTO visit_logs (visitor_id, page, created_at) VALUES (?, ?, NOW())");
            if ($ins) {
                $ins->bind_param('ss', $visitor, $page);
                $ins->execute();
                $ins->close();
            }
        } else {
            $stmt->close();
        }
    }
}

function get_visit_counts() {
    global $conn;
    ensure_visits_table();
    $res = [];
    $sql = "SELECT page, COUNT(*) AS cnt FROM visit_logs WHERE page IN('home','services') GROUP BY page";
    if ($q = $conn->query($sql)) {
        while ($row = $q->fetch_assoc()) {
            $res[$row['page']] = (int)$row['cnt'];
        }
    }
    // ensure keys exist
    if (!isset($res['home'])) $res['home'] = 0;
    if (!isset($res['services'])) $res['services'] = 0;
    return $res;
}

?>