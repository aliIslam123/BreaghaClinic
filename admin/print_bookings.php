<?php
header('Content-Type: text/html; charset=utf-8');

require_once '../config/init.php';
include '../config/connection.php';

if (!isset($_GET['day'])) {
    die("لم يتم تحديد يوم");
}

$day_raw = $_GET['day'];
$day = sanitize_input($day_raw);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day_raw)) {
    die("صيغة التاريخ غير صحيحة");
}
$stmt = $conn->prepare("SELECT name, Customer_age, phone, gender, service, booking_type, price, time_slot FROM (
    SELECT name COLLATE utf8mb4_unicode_ci AS name, Customer_age, phone COLLATE utf8mb4_unicode_ci AS phone, gender COLLATE utf8mb4_unicode_ci AS gender, service COLLATE utf8mb4_unicode_ci AS service, booking_type COLLATE utf8mb4_unicode_ci AS booking_type, price, time_slot COLLATE utf8mb4_unicode_ci AS time_slot FROM customer WHERE booking_date = ?
    UNION ALL
    SELECT full_name AS name, age AS Customer_age, phone, COALESCE(gender,'') AS gender, service, booking_type, price, booking_time AS time_slot FROM bookings WHERE booking_date = ?
) t ORDER BY time_slot ASC");
$stmt->bind_param("ss", $day_raw, $day_raw);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="icon" type="image/svg+xml" href="../assets/imgs/Br%C3%A8agha%20-%20Logomark.svg">
  <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>تقرير الحجوزات - <?php echo htmlspecialchars($day, ENT_QUOTES, 'UTF-8'); ?></title>
  <style>
    body { font-family: 'Readex Pro', sans-serif; direction: rtl; margin: 20px; }
    h2 { color: #C39E31; text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
    th { background: #C39E31; color: #fff; }
    .print-btn { margin: 20px auto; display: block; padding: 10px 20px; background: #C39E31; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
    @media print {
      .print-btn { display: none; }
    }
  </style>
</head>
<body>
  <h2>تقرير الحجوزات - <?php echo htmlspecialchars($day, ENT_QUOTES, 'UTF-8'); ?></h2>

  <?php if ($res->num_rows > 0): ?>
    <table>
      <tr>
        <th>الاسم</th>
        <th>العمر</th>
        <th>الهاتف</th>
        <th>النوع</th>
        <th>نوع الحجز</th>
        <th>الخدمة/العرض</th>
        <th>السعر</th>
        <th>الوقت</th>
      </tr>
      <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($r['Customer_age'], ENT_QUOTES, 'UTF-8'); ?> سنة</td>
          <td><?php echo htmlspecialchars($r['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($r['gender'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo ($r['booking_type'] ?? '') === 'offer' ? 'عرض خاص' : 'خدمة عادية'; ?></td>
          <td><?php echo htmlspecialchars($r['service'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo $r['price'] !== null ? htmlspecialchars((float)$r['price'], ENT_QUOTES, 'UTF-8') . ' ج.م' : '—'; ?></td>
          <td><?php echo htmlspecialchars($r['time_slot'], ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
    <button class="print-btn" onclick="window.print()">🖨️ طباعة</button>
  <?php else: ?>
    <p style="color:red;text-align:center;">لا توجد حجوزات في هذا اليوم</p>
  <?php endif; ?>
</body>
</html>
