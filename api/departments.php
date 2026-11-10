<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_once __DIR__ . '/../config/connection.php';

$gender = isset($_GET['gender']) ? strtolower(trim($_GET['gender'])) : '';
$valid_genders = ['male', 'female'];

if (!empty($gender) && !in_array($gender, $valid_genders, true)) {
    $gender = '';
}

$dept_result = $conn->query("SELECT id, slug, name, icon, description FROM departments WHERE visible = 1 ORDER BY sort_order ASC");
$departments = [];
while ($dept = $dept_result->fetch_assoc()) {
    $dept_data = [
        'id' => (int)$dept['id'],
        'slug' => $dept['slug'],
        'name' => $dept['name'],
        'icon' => $dept['icon'],
        'description' => $dept['description'],
    ];

    if (!empty($gender)) {
        $stmt = $conn->prepare("SELECT working_days, start_time, end_time FROM department_schedules WHERE department_id = ? AND gender = ?");
        if ($stmt) {
            $stmt->bind_param('is', $dept['id'], $gender);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $dept_data['working_days'] = json_decode($row['working_days'], true) ?: [];
                $dept_data['start_time'] = $row['start_time'];
                $dept_data['end_time'] = $row['end_time'];
            } else {
                $dept_data['working_days'] = [];
                $dept_data['start_time'] = '19:00:00';
                $dept_data['end_time'] = '23:00:00';
            }
            $stmt->close();
        }
    } else {
        $schedules = [];
        $stmt = $conn->prepare("SELECT gender, working_days, start_time, end_time FROM department_schedules WHERE department_id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $dept['id']);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $schedules[$row['gender']] = [
                    'working_days' => json_decode($row['working_days'], true) ?: [],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                ];
            }
            $stmt->close();
        }
        $dept_data['schedules'] = $schedules;
    }

    $departments[] = $dept_data;
}

echo json_encode($departments, JSON_UNESCAPED_UNICODE);
?>
