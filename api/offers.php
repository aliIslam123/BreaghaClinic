<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/connection.php';

$department = isset($_GET['department']) ? trim($_GET['department']) : '';

if (!empty($department)) {
    $categoryMap = [
        'laser' => ['الليزر', 'ليزر'],
        'cosm' => ['التجميل بالحقن', 'الإجراءات التجميلية', 'فيلر', 'بوتوكس'],
        'derma' => ['العناية بالبشرة', 'العناية بالشعر', 'الجلدية وعلاجات الجلدية', 'الجلدية وعلاجات البشرة', 'تقشير'],
        'gyn' => ['النساء والتوليد'],
        'gastro' => ['أمراض الباطنة', 'أمراض البطن']
    ];

    if (isset($categoryMap[$department])) {
        $cats = $categoryMap[$department];
        if (empty($cats)) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Build placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($cats), '?'));
        
        // Query both department_slug and category mapping
        $query = "SELECT id, category, name, price, `starting` FROM offers WHERE (department_slug = ? OR category IN ($placeholders)) AND visible = 1 ORDER BY id ASC";
        $stmt = $conn->prepare($query);
        
        // Bind parameters dynamically
        $types = 's' . str_repeat('s', count($cats));
        $params = array_merge([$department], $cats);
        $stmt->bind_param($types, ...$params);
        
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Fallback to department name search
        $stmt = $conn->prepare("SELECT name FROM departments WHERE slug = ?");
        $stmt->bind_param("s", $department);
        $stmt->execute();
        $deptResult = $stmt->get_result();
        
        if ($deptRow = $deptResult->fetch_assoc()) {
            $deptName = $deptRow['name'];
            $stmt2 = $conn->prepare("SELECT id, category, name, price, `starting` FROM offers WHERE (category = ? OR department_slug = ?) AND visible = 1 ORDER BY id ASC");
            $stmt2->bind_param("ss", $deptName, $department);
            $stmt2->execute();
            $result = $stmt2->get_result();
        } else {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
} else {
    // Return all visible offers if no department parameter is supplied
    $result = $conn->query("SELECT id, category, name, price, `starting` FROM offers WHERE visible = 1 ORDER BY category, id ASC");
}

$offers = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $offers[] = $row;
    }
}
echo json_encode($offers, JSON_UNESCAPED_UNICODE);
?>
