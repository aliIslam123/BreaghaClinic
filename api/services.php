<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../config/connection.php';

$department = isset($_GET['department']) ? $_GET['department'] : null;

if ($department) {
    $stmt = $conn->prepare("SELECT id, department_slug, name, description, image FROM services WHERE department_slug = ? AND visible = 1 ORDER BY id ASC");
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, department_slug, name, description, image FROM services WHERE visible = 1 ORDER BY department_slug, id ASC");
}

$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
echo json_encode($services, JSON_UNESCAPED_UNICODE);
?>
