<?php
require_once '../config/init.php';
include '../config/connection.php';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="حجوزات_براجيا_'.date('Y-m-d').'.xls"');

$stmt = $conn->prepare(
    "SELECT * FROM (
        SELECT 
            name COLLATE utf8mb4_unicode_ci AS full_name,
            Customer_age AS age,
            phone COLLATE utf8mb4_unicode_ci AS phone,
            gender COLLATE utf8mb4_unicode_ci AS gender,
            department COLLATE utf8mb4_unicode_ci AS department,
            service COLLATE utf8mb4_unicode_ci AS service,
            booking_date,
            time_slot COLLATE utf8mb4_unicode_ci AS booking_time,
            'pending' AS status,
            created_at,
            'customer' AS source
        FROM customer 
        UNION ALL
        SELECT 
            full_name,
            age,
            phone,
            COALESCE(gender,'') AS gender,
            department,
            service,
            booking_date,
            booking_time,
            status,
            created_at,
            'bookings' AS source
        FROM bookings
    ) t ORDER BY created_at DESC"
);

$stmt->execute();
$res = $stmt->get_result();

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <link rel="icon" type="image/svg+xml" href="../assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
<meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style>
td { mso-number-format:"\@"; }
</style>
</head><body>';

echo '<table border="1">
<tr style="background:#C39E31;color:#fff;font-weight:bold;">
<td>#</td>
<td>الاسم</td>
<td>العمر</td>
<td>الهاتف</td>
<td>النوع</td>
<td>القسم</td>
<td>الخدمة</td>
<td>تاريخ الحجز</td>
<td>وقت الحجز</td>
<td>الحالة</td>
<td>تاريخ الإنشاء</td>
<td>المصدر</td>
</tr>';

$row_num = 1;
$status_labels = ['pending' => 'قيد المراجعة', 'confirmed' => 'مؤكد', 'cancelled' => 'ملغي'];
while ($r = $res->fetch_assoc()) {
    $source = $r['source'] === 'customer' ? 'نموذج قديم' : 'نموذج جديد';
    $status_ar = $status_labels[$r['status']] ?? $r['status'];
    echo '<tr>';
    echo '<td>'.$row_num++.'</td>';
    echo '<td>'.$r['full_name'].'</td>';
    echo '<td>'.$r['age'].'</td>';
    echo '<td>'.$r['phone'].'</td>';
    echo '<td>'.($r['gender'] ?: '-').'</td>';
    echo '<td>'.$r['department'].'</td>';
    echo '<td>'.$r['service'].'</td>';
    echo '<td>'.$r['booking_date'].'</td>';
    echo '<td>'.($r['booking_time'] ?: '-').'</td>';
    echo '<td>'.$status_ar.'</td>';
    echo '<td>'.$r['created_at'].'</td>';
    echo '<td>'.$source.'</td>';
    echo '</tr>';
}

echo '</table></body></html>';
exit;